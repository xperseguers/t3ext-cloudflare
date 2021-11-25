<?php

namespace Causal\Cloudflare\Hooks;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Causal\Cloudflare\ExtensionManager\Configuration;
use Causal\Cloudflare\Services\CloudflareService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook for clearing cache on Cloudflare.
 *
 * @category    Hooks
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class TCEmain
{
    /** @var array */
    protected $config;

    /** @var \TYPO3\CMS\Core\Context\UserAspect */
    protected $backendUserAspect;

    /**
     * Default constructor.
     */
    public function __construct(ExtensionConfiguration $extensionConfiguration, Context $context)
    {
        $this->config = $extensionConfiguration->get(Configuration::KEY);
        $this->backendUserAspect = $context->getAspect('backend.user');
    }

    /**
     * Hooks into the "clear all caches" call.
     *
     * @param array $params
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     * @return void
     */
    public function clear_cacheCmd(array $params, \TYPO3\CMS\Core\DataHandling\DataHandler $pObj)
    {
        static $handledPageUids = [];
        static $handledTags = [];

        $enablePurgeByUrl = isset($this->config['enablePurgeSingleFile']) && (bool)$this->config['enablePurgeSingleFile'];
        $enablePurgeByTags = isset($this->config['enablePurgeByTags']) && (bool)$this->config['enablePurgeByTags'];

        if (!isset($params['cacheCmd'])) {
            if ($params['table'] === 'pages' && $enablePurgeByTags) {
                $this->purgeIndividualFileByCacheTag('pageId_' . (int)$params['uid']);
            }
            return;
        }

        if (GeneralUtility::inList('all,pages', $params['cacheCmd'])) {
            $this->clearCloudflareCache($pObj->BE_USER);
        } else {
            if ($enablePurgeByTags && str_contains(strtolower($params['cacheCmd']), 'cachetag:')) {
                $cacheTag = substr($params['cacheCmd'], 9);
                if (!in_array($cacheTag, $handledTags, true)) {
                    $handledTags[] = $cacheTag;
                    $this->purgeIndividualFileByCacheTag($cacheTag);
                }
            } elseif ($enablePurgeByUrl) {
                $pageUid = (int)$params['cacheCmd'];
                if ($pageUid && !in_array($pageUid, $handledPageUids, true)) {
                    $handledPageUids[] = $pageUid;
                    $url = $this->getFrontendUrl($pageUid);
                    if ($url) {
                        $this->purgeIndividualFileByUrl($url);
                    }
                }
            }
        }
    }

    /**
     * Answers to AJAX call invoked when clearing only Cloudflare cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $backendUser = $this->getBackendUser();
        if (!$backendUser) {
            return;
        }

        $canClearAllCache = (bool)($backendUser->getTSConfig()['options.']['clearCache.']['all'] ?? false);
        $canClearCloudflareCache = (bool)($backendUser->getTSConfig()['options.']['clearCache.']['cloudflare'] ?? false);

        if ($this->getBackendUser()->isAdmin() || $canClearAllCache || $canClearCloudflareCache) {
            $this->clearCloudflareCache();
        }
    }

    /**
     * Clears the Cloudflare cache.
     *
     * @return void
     */
    protected function clearCloudflareCache(): void
    {
        $domains = $this->config['domains'] ? GeneralUtility::trimExplode(',', $this->config['domains'], true) : [];

        /** @var CloudflareService $cloudflareService */
        $cloudflareService = GeneralUtility::makeInstance(CloudflareService::class, $this->config);

        foreach ($domains as $domain) {
            try {
                list($identifier, $zoneName) = explode('|', $domain, 2);
                $ret = $cloudflareService->send('/zones/' . $identifier . '/purge_cache', [
                    'purge_everything' => true,
                ], 'DELETE');
                if (!is_array($ret)) {
                    $ret = [
                        'success' => false,
                        'errors' => []
                    ];
                }

                if ($ret['success']) {
                    $this->writelog(4, 1, 0, 0, 'User %s cleared the cache on Cloudflare (domain: "%s")', [$this->backendUserAspect->get('username'), $zoneName]);
                } else {
                    $this->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on Cloudflare (domain: "%s"): %s', [$this->backendUserAspect->get('username'), $zoneName, implode(LF, $ret['errors'])]);
                }

            } catch (\RuntimeException $e) {
                $this->writelog(4, 1, 1, 0, $e->getMessage(), []);

            }
        }
    }

    /**
     * Returns a Frontend URL corresponding to a given page UID
     * Using core getPreviewUrl() as backend now uses slug + site to generate url
     *
     * @param integer $uid
     * @return string|null
     */
    protected function getFrontendUrl(int $uid): ?string
    {
        if ($this->getBackendUser() && $this->getBackendUser()->workspace != 0) {
            // Preview in workspaces is not supported!
            return null;
        }

        return BackendUtility::getPreviewUrl($uid);
    }

    /**
     * Granularly removes an individual file from Cloudflare's cache by specifying the URL.
     *
     * @param string $url
     * @return void
     */
    protected function purgeIndividualFileByUrl(string $url): void
    {
        $domains = $this->config['domains'] ? GeneralUtility::trimExplode(',', $this->config['domains'], true) : [];

        $domain = null;
        $zoneIdentifier = null;

        if (preg_match('#^https?://([^/]+)#', $url, $matches)) {
            $domainParts = explode('.', $matches[1]);
            if (count($domainParts) > 1) {
                $size = count($domainParts);
                $zoneName = $domainParts[$size - 2] . '.' . $domainParts[$size - 1];

                foreach ($domains as $domain) {
                    list($identifier, $z) = explode('|', $domain, 2);
                    if ($z === $zoneName) {
                        $zoneIdentifier = $identifier;
                        break;
                    }
                }
            }
        }

        if ($zoneIdentifier === null) {
            return;
        }

        /** @var CloudflareService $cloudflareService */
        $cloudflareService = GeneralUtility::makeInstance(CloudflareService::class, $this->config);

        try {
            $ret = $cloudflareService->send('/zones/' . $zoneIdentifier . '/purge_cache', [
                'files' => [$url],
            ], 'DELETE');


            if ($ret['result'] === 'error') {
                $this->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on Cloudflare (domain: "%s") for "%s": %s', [$this->backendUserAspect->get('username'), $domain, $url, $ret['msg']]);
            } else {
                $this->writelog(4, 1, 0, 0, 'User %s cleared the cache on Cloudflare (domain: "%s") for "%s"', [$this->backendUserAspect->get('username'), $domain, $url]);
            }
        } catch (\RuntimeException $e) {
            $this->writelog(4, 1, 1, 0, $e->getMessage(), []);
        }
    }

    /**
     * Granularly removes an individual file from Cloudflare's cache by specifying the associated Cache-Tag.
     *
     * @param string $cacheTag
     * @return void
     */
    protected function purgeIndividualFileByCacheTag(string $cacheTag): void
    {
        $domains = $this->config['domains'] ? GeneralUtility::trimExplode(',', $this->config['domains'], true) : [];

        /** @var CloudflareService $cloudflareService */
        $cloudflareService = GeneralUtility::makeInstance(CloudflareService::class, $this->config);

        foreach ($domains as $domain) {
            try {
                list($identifier, $zoneName) = explode('|', $domain, 2);
                $ret = $cloudflareService->send('/zones/' . $identifier . '/purge_cache', [
                    'tags' => [$cacheTag],
                ], 'DELETE');
                if (!is_array($ret)) {
                    $ret = [
                        'success' => false,
                        'errors' => []
                    ];
                }

                if ($ret['success']) {
                    $this->writelog(4, 1, 0, 0, 'User %s cleared the cache on Cloudflare using Cache-Tag (domain: "%s")', [$this->backendUserAspect->get('username'), $zoneName]);
                } else {
                    $this->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on Cloudflare using Cache-Tag (domain: "%s"): %s', [$this->backendUserAspect->get('username'), $zoneName, implode(LF, $ret['errors'])]);
                }

            } catch (\RuntimeException $e) {
                $this->writelog(4, 1, 1, 0, $e->getMessage(), []);
            }
        }
    }

    /**
     * Wrapper for writing log for logged in backend user
     */
    protected function writelog($type, $action, $error, $details_nr, $details, $data, $tablename = '', $recuid = '', $recpid = '', $event_pid = -1, $NEWid = '', $userId = 0)
    {
        if (!$this->getBackendUser()) {
            return;
        }

        $this->getBackendUser()->writelog($type, $action, $error, $details_nr, $details, $data, $tablename, $recuid, $recpid, $event_pid, $NEWid, $userId);
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
