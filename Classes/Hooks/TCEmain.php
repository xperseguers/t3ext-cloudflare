<?php
declare(strict_types=1);

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

namespace Causal\Cloudflare\Hooks;

use Causal\Cloudflare\Services\CloudflareService;
use Causal\Cloudflare\Traits\ConfiguredDomainsTrait;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
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
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
class TCEmain
{
    use ConfiguredDomainsTrait;

    protected static array $handledPageUids = [];
    protected static array $handledTags = [];

    /**
     * Default constructor.
     */
    public function __construct()
    {
        // DI is not available in this context
        $this->cloudflareService = GeneralUtility::makeInstance(CloudflareService::class);
    }

    /**
     * Hooks into the "clear all caches" call.
     *
     * @param array $params
     * @param DataHandler $pObj
     */
    public function clear_cacheCmd(array $params, DataHandler $pObj): void
    {
        $config = $this->cloudflareService->getConfiguration();
        $enablePurgeByTags = (bool)($config['enablePurgeByTags'] ?? false);

        if (!isset($params['cacheCmd'])) {
            if ($params['table'] === 'pages') {
                $this->tryPurgePage((int)$params['uid']);
            }
            return;
        }

        if (GeneralUtility::inList('all,pages', $params['cacheCmd'])) {
            $this->clearCloudflareCache($pObj->BE_USER);
        } else {
            if ($enablePurgeByTags && stripos($params['cacheCmd'], 'cachetag:') !== false) {
                $cacheTag = substr($params['cacheCmd'], 9);
                if (!in_array($cacheTag, self::$handledTags, true)) {
                    self::$handledTags[] = $cacheTag;
                    $this->purgeIndividualFileByCacheTag(
                        $GLOBALS['BE_USER'] ?? null,
                        $cacheTag
                    );
                }
            } else {
                $this->tryPurgePage((int)$params['cacheCmd']);
            }
        }
    }

    protected function tryPurgePage(int $pageUid): void
    {
        if (empty($pageUid)) {
            return;
        }

        $config = $this->cloudflareService->getConfiguration();
        $enablePurgeByUrl = (bool)($config['enablePurgeSingleFile'] ?? false);
        $enablePurgeByTags = (bool)($config['enablePurgeByTags'] ?? false);

        if ($enablePurgeByTags) {
            $cacheTag = 'pageId_' . $pageUid;
            if (!in_array($cacheTag, self::$handledTags, true)) {
                self::$handledTags[] = $cacheTag;
                $this->purgeIndividualFileByCacheTag(
                    $GLOBALS['BE_USER'] ?? null,
                    $cacheTag
                );
            }
        } elseif ($enablePurgeByUrl) {
            if (!in_array($pageUid, self::$handledPageUids, true)) {
                self::$handledPageUids[] = $pageUid;
                $url = $this->getFrontendUrl($pageUid);
                if ($url) {
                    $this->purgeIndividualFileByUrl(
                        $GLOBALS['BE_USER'] ?? null,
                        $url
                    );
                }
            }
        }
    }

    /**
     * Answers to AJAX call invoked when clearing only Cloudflare cache.
     */
    public function clearCache(): void
    {
        if (!isset($GLOBALS['BE_USER'])) {
            return;
        }

        $canClearAllCache = (bool)($GLOBALS['BE_USER']->getTSConfig()['options.']['clearCache.']['all'] ?? false);
        $canClearCloudflareCache = (bool)($GLOBALS['BE_USER']->getTSConfig()['options.']['clearCache.']['cloudflare'] ?? false);

        if ($canClearAllCache || $canClearCloudflareCache || $GLOBALS['BE_USER']->isAdmin()) {
            $this->clearCloudflareCache($GLOBALS['BE_USER']);
        }
    }

    /**
     * Clears the Cloudflare cache.
     *
     * @param AbstractUserAuthentication|null $beUser
     */
    protected function clearCloudflareCache(?AbstractUserAuthentication $beUser = null): void
    {
        $domains = $this->getDomains();

        foreach ($domains as $domain) {
            try {
                list($identifier, $zoneName) = explode('|', $domain, 2);
                $ret = $this->cloudflareService->send('/zones/' . $identifier . '/purge_cache', [
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
     *
     * @param int $uid
     * @return string|null
     */
    protected function getFrontendUrl(int $uid): ?string
    {
        if (isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->workspace !== 0) {
            // Preview in workspaces is not supported!
            return null;
        }

        $previewBuilder = GeneralUtility::makeInstance(PreviewUriBuilder::class, $uid);
        $url = $previewBuilder->buildUri(['uid' => $uid]);
        return (string)$url;
    }

    /**
     * Granularly removes an individual file from Cloudflare's cache by specifying the URL.
     *
     * @param AbstractUserAuthentication|null $beUser
     * @param string $url
     */
    protected function purgeIndividualFileByUrl(
        ?AbstractUserAuthentication $beUser = null,
        string $url
    ): void
    {
        $domains = $this->getDomains();

        $domain = null;
        $zoneIdentifier = null;

        if (preg_match('#^https?://([^/]+)#', $url, $matches)) {
            $domainParts = explode('.', $matches[1]);
            $size = count($domainParts);
            if ($size > 1) {
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

        try {
            $ret = $this->cloudflareService->send('/zones/' . $zoneIdentifier . '/purge_cache', [
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
     * Granularly removes an individual file from Cloudflare's cache by
     * specifying the associated Cache-Tag.
     *
     * @param AbstractUserAuthentication|null $beUser
     * @param string $cacheTag
     */
    protected function purgeIndividualFileByCacheTag(
        ?AbstractUserAuthentication $beUser = null,
        string $cacheTag
    ): void
    {
        $domains = $this->getDomains();

        foreach ($domains as $domain) {
            try {
                list($identifier, $zoneName) = explode('|', $domain, 2);
                $ret = $this->cloudflareService->send('/zones/' . $identifier . '/purge_cache', [
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
