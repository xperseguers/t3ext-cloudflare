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

use Causal\Cloudflare\Services\CloudflareService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;

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

    /** @var string */
    protected $extKey = 'cloudflare';

    /** @var array */
    protected $config;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($this->extKey);
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
                $this->purgeIndividualFileByCacheTag(
                    isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : null,
                    'pageId_' . (int)$params['uid']
                );
            }
            return;
        }

        if (GeneralUtility::inList('all,pages', $params['cacheCmd'])) {
            $this->clearCloudflareCache($pObj->BE_USER);
        } else {
            if (strpos(strtolower($params['cacheCmd']), 'cachetag:') !== false && $enablePurgeByTags) {
                $cacheTag = substr($params['cacheCmd'], 9);
                if (!in_array($cacheTag, $handledTags)) {
                    $handledTags[] = $cacheTag;
                    $this->purgeIndividualFileByCacheTag(
                        isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : null,
                        $cacheTag
                    );
                }
            } elseif ($enablePurgeByUrl) {
                $pageUid = (int)$params['cacheCmd'];
                if ($pageUid && !in_array($pageUid, $handledPageUids)) {
                    $handledPageUids[] = $pageUid;
                    $url = $this->getFrontendUrl($pageUid);
                    if ($url) {
                        $this->purgeIndividualFileByUrl(
                            isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : null,
                            $url
                        );
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
    public function clearCache()
    {
        if (!isset($GLOBALS['BE_USER'])) {
            return;
        }

        $canClearAllCache = (bool)($GLOBALS['BE_USER']->getTSConfig()['options.']['clearCache.']['all'] ?? false);
        $canClearCloudflareCache = (bool)($GLOBALS['BE_USER']->getTSConfig()['options.']['clearCache.']['cloudflare'] ?? false);

        if ($GLOBALS['BE_USER']->isAdmin() || $canClearAllCache || $canClearCloudflareCache) {
            $this->clearCloudflareCache($GLOBALS['BE_USER']);
        }
    }

    /**
     * Clears the Cloudflare cache.
     *
     * @param AbstractUserAuthentication $beUser
     * @return void
     */
    protected function clearCloudflareCache(AbstractUserAuthentication $beUser = null)
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

                if ($beUser !== null) {
                    if ($ret['success']) {
                        $beUser->writelog(4, 1, 0, 0, 'User %s cleared the cache on Cloudflare (domain: "%s")', [$beUser->user['username'], $zoneName]);
                    } else {
                        $beUser->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on Cloudflare (domain: "%s"): %s', [$beUser->user['username'], $zoneName, implode(LF, $ret['errors'])]);
                    }
                }
            } catch (\RuntimeException $e) {
                if ($beUser !== null) {
                    $beUser->writelog(4, 1, 1, 0, $e->getMessage(), []);
                }
            }
        }
    }

    /**
     * Returns a Frontend URL corresponding to a given page UID.
     * Implementation was inspired by EXT:vara_feurlfrombe
     *
     * @param integer $uid
     * @return string
     * @todo Add support for multiple Frontend URLs
     */
    protected function getFrontendUrl($uid)
    {
        if (isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->workspace != 0) {
            // Preview in workspaces is not supported!
            return null;
        }

        $tsfe = GeneralUtility::makeInstance(\Causal\Cloudflare\Utilities\Tsfe::class);
        return $tsfe -> getPageUrl($uid, [], true);
    }

    /**
     * Granularly removes an individual file from Cloudflare's cache by specifying the URL.
     *
     * @param AbstractUserAuthentication $beUser
     * @param string $url
     * @return void
     */
    protected function purgeIndividualFileByUrl(AbstractUserAuthentication $beUser = null, $url)
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

            if ($beUser !== null) {
                if ($ret['result'] === 'error') {
                    $beUser->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on Cloudflare (domain: "%s") for "%s": %s', [$beUser->user['username'], $domain, $url, $ret['msg']]);
                } else {
                    $beUser->writelog(4, 1, 0, 0, 'User %s cleared the cache on Cloudflare (domain: "%s") for "%s"', [$beUser->user['username'], $domain, $url]);
                }
            }
        } catch (\RuntimeException $e) {
            if ($beUser !== null) {
                $beUser->writelog(4, 1, 1, 0, $e->getMessage(), []);
            }
        }
    }

    /**
     * Granularly removes an individual file from Cloudflare's cache by specifying the associated Cache-Tag.
     *
     * @param AbstractUserAuthentication|null $beUser
     * @param string $cacheTag
     * @return void
     */
    protected function purgeIndividualFileByCacheTag(AbstractUserAuthentication $beUser = null, $cacheTag)
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

                if ($beUser !== null) {
                    if ($ret['success']) {
                        $beUser->writelog(4, 1, 0, 0, 'User %s cleared the cache on Cloudflare using Cache-Tag (domain: "%s")', [$beUser->user['username'], $zoneName]);
                    } else {
                        $beUser->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on Cloudflare using Cache-Tag (domain: "%s"): %s', [$beUser->user['username'], $zoneName, implode(LF, $ret['errors'])]);
                    }
                }
            } catch (\RuntimeException $e) {
                if ($beUser !== null) {
                    $beUser->writelog(4, 1, 1, 0, $e->getMessage(), []);
                }
            }
        }
    }
}
