<?php

namespace Causal\Cloudflare\Services;

use Causal\Cloudflare\Domain\Model\QueueItem;
use Causal\Cloudflare\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;

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

/**
 * Class ClearCacheService
 * @package Causal\Cloudflare\Services
 */
class ClearCacheService
{
    /**
     * Limit to clear cache at once
     */
    const CLEAR_CACHE_URLS_LIMIT = 30;

    /**
     * Settings
     *
     * @var array
     */
    protected $config = [];

    /**
     * CDN domains
     *
     * @var array
     */
    protected $domains = [];

    /**
     * List of purged domains
     *
     * @var array
     */
    protected $purgedDomains = [];

    /**
     * @var AbstractUserAuthentication
     */
    protected $beUser = null;

    /**
     * @var CloudflareService
     */
    protected $cloudflareService = null;

    /**
     * Initalize
     *
     * @param array $config
     * @param AbstractUserAuthentication|null $beUser
     */
    public function __construct(array $config, AbstractUserAuthentication $beUser = null)
    {
        $this->config = $config;
        $this->beUser = $beUser;
        if ($this->config['domains']) {
            foreach (GeneralUtility::trimExplode(',', $this->config['domains'], true) as $domainItem) {
                list($identifier, $domain) = explode('|', $domainItem, 2);
                $this->domains[$identifier] = $domain;
            }
        }
    }

    /**
     * Main clear cache function
     *
     * @param QueueItem $queueItem
     */
    public function clearSingleItemCache(QueueItem $queueItem)
    {
        static $handledPageUids = [];
        static $handledTags = [];

        switch ($queueItem->getCacheCommand()) {
            // Clear all
            case QueueItem::CLEAR_CACHE_COMMAND_ALL:
                $this->clearCloudflareCache();
                break;
            // Clear by tag
            case QueueItem::CLEAR_CACHE_COMMAND_CACHE_TAG:
                $cacheTag = $queueItem->getCacheTag();
                if (!in_array($cacheTag, $handledTags)) {
                    $handledTags[] = $cacheTag;
                    $this->purgeIndividualFilesByCacheTag([$cacheTag]);
                }
                break;
            // Clear page
            case QueueItem::CLEAR_CACHE_COMMAND_PAGE:
                $pageUid = $queueItem->getPageUid();
                if (!in_array($pageUid, $handledPageUids)) {
                    $handledPageUids[] = $pageUid;
                    $this->clearPagesCache([$pageUid]);
                }
                break;
            case QueueItem::CLEAR_CACHE_ROOT_LINE:
                $this->clearSitesRootLineCache([$queueItem->getPageUid()]);
                break;
            // No default action
        }
    }

    /**
     * Clears the Cloudflare cache.
     *
     * @return void
     */
    public function clearCloudflareCache()
    {
        foreach ($this->domains as $identifier => $zone) {
            $this->purgeAll($identifier, $zone);
        }
    }

    /**
     * Returns a Frontend URL corresponding to a given page UID.
     * Implementation was inspired by EXT:vara_feurlfrombe
     *
     * @param array $uids
     */
    public function clearPagesCache(array $uids)
    {
        $groupedByDomain = [];

        // Group all pages by root line
        foreach ($uids as $uid) {
            $domain = BackendUtility::firstDomainRecord(
                BackendUtility::BEgetRootLine($uid)
            );
            if ($domain === null) {
                $domain = GeneralUtility::getIndpEnv('HTTP_HOST');
            }

            if (!is_array($groupedByDomain[$domain])) {
                $groupedByDomain[$domain] = [
                    'uids' => [],
                    'urls' => [],
                    'zoneIdentifier' => $this->determinateDomainZoneIdentifier($domain)
                ];
            }
            $groupedByDomain[$domain]['uids'][] = $uid;
        }

        // Get pages URLs using EID
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        foreach ($groupedByDomain as $domain => $domainGroup) {
            if (empty($domainGroup['zoneIdentifier'])) {
                unset($groupedByDomain[$domain]);
                continue;
            }
            $data = [
                'uids' => implode(',', $domainGroup['uids'])
            ];
            $eidUrl = sprintf(
                '%s://%s/index.php?eID=%s&data=%s',
                $scheme,
                $domain,
                ConfigurationUtility::EXT_KEY,
                base64_encode(json_encode($data))
            );
            $headers = [
                'Cookie: fe_typo_user=' . $_COOKIE['fe_typo_user']
            ];
            $result = json_decode(GeneralUtility::getURL($eidUrl, false, $headers), true);

            if (is_array($result)) {
                $groupedByDomain[$domain]['urls'] = $this->formatUrls($result);
            }
        }

        // Purge cache on cloud flare
        foreach ($groupedByDomain as $domain => $domainGroup) {
            // Cloudflare has limit for clear files at once
            foreach (array_chunk($domainGroup['urls'], self::CLEAR_CACHE_URLS_LIMIT) as $urlChunk) {
                $this->purgeIndividualFilesByUrl(
                    $urlChunk,
                    $domain,
                    $domainGroup['uids'],
                    $domainGroup['zoneIdentifier']
                );
            }
        }
    }

    /**
     * Clear all caches for sites that has given pages uids
     *
     * @param array $pages
     */
    public function clearSitesRootLineCache(array $pages)
    {
        foreach ($pages as $page) {
            $rootLine = BackendUtility::BEgetRootLine($page);
            $domains = [];
            if (is_array($rootLine)) {
                foreach ($rootLine as $row) {
                    $dRecs = BackendUtility::getRecordsByField('sys_domain', 'pid', $row['uid'], ' AND redirectTo=\'\' AND hidden=0', '', 'sorting');
                    if (is_array($dRecs)) {
                        foreach ($dRecs as $dRec) {
                            $domains[] = $dRec['domainName'];
                        }
                    }
                }
            }

            foreach ($domains as $domain) {
                $identifier = $this->determinateDomainZoneIdentifier($domain);
                if (!empty($identifier)) {
                    $this->purgeAll($identifier, $domain);
                }
            }
        }
    }

    /**
     * Purge cdn caches by tags
     *
     * @param array $cacheTags
     */
    public function clearCacheTags(array $cacheTags)
    {
        foreach (array_chunk($cacheTags, self::CLEAR_CACHE_URLS_LIMIT) as $cacheTagsChunk) {
            $this->purgeIndividualFilesByCacheTag($cacheTagsChunk);
        }
    }

    /**
     * Get zone identifier for domain
     *
     * @param string $domain
     * @return null
     */
    protected function determinateDomainZoneIdentifier($domain)
    {
        $key = array_search($domain, $this->domains);
        if ($key !== false) {
            return $key;
        }

        // If it's www.domain.com, try to search for domain.com
        $domainParts = explode('.', $domain);
        $size = count($domainParts);
        if ($size > 2) {
            $rootDomain = $domainParts[$size - 2] . '.' . $domainParts[$size - 1];

            $key = array_search($rootDomain, $this->domains);
            if ($key !== false) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Granularly removes an individual file from Cloudflare's cache by specifying the URL.
     *
     * @param array $urls
     * @param string $domain
     * @param array $pageUids
     * @param string $zoneIdentifier
     * @return void
     */
    protected function purgeIndividualFilesByUrl(array $urls, $domain, array $pageUids, $zoneIdentifier)
    {
        /** @var CloudflareService $cloudflareService */
        $cloudflareService = $this->getCloudFlareService();

        try {
            $ret = $cloudflareService->send(
                '/zones/' . $zoneIdentifier . '/purge_cache',
                [
                    'files' => $urls,
                ],
                'DELETE'
            );

            if ($ret['result'] === 'error') {
                $this->writelog(
                    1,
                    'User %s failed to clear the cache on Cloudflare (domain: "%s") for pages "%s": %s',
                    [$this->beUser->user['username'], $domain, implode(',', $pageUids), $ret['msg']]
                );
            } else {
                $this->writelog(
                    0,
                    'User %s cleared the cache on Cloudflare (domain: "%s") for pages "%s"',
                    [$this->beUser->user['username'], $domain, implode(',', $pageUids)]
                );
            }
        } catch (\RuntimeException $e) {
            $this->writelog(1, $e->getMessage(), []);
        }
    }

    /**
     * Granularly removes an individual file from Cloudflare's cache by specifying the associated Cache-Tag.
     *
     * @param array $cacheTags
     * @return void
     */
    protected function purgeIndividualFilesByCacheTag(array $cacheTags)
    {
        /** @var CloudflareService $cloudflareService */
        $cloudflareService = $this->getCloudFlareService();

        foreach ($this->domains as $identifier => $zoneName) {
            try {
                $ret = $cloudflareService->send(
                    '/zones/' . $identifier . '/purge_cache',
                    [
                        'tags' => $cacheTags,
                    ],
                    'DELETE'
                );
                if (!is_array($ret)) {
                    $ret = [
                        'success' => false,
                        'errors' => []
                    ];
                }


                if ($ret['success']) {
                    $this->writelog(
                        0,
                        'User %s cleared the cache on Cloudflare using Cache-Tag (domain: "%s")',
                        [$this->beUser->user['username'], $zoneName]
                    );
                } else {
                    $this->writelog(
                        1,
                        'User %s failed to clear the cache on Cloudflare using Cache-Tag (domain: "%s"): %s',
                        [$this->beUser->user['username'], $zoneName, implode(LF, $ret['errors'])]
                    );
                }
            } catch (\RuntimeException $e) {
                $this->writelog(1, $e->getMessage(), []);
            }
        }
    }

    /**
     * Flush each url with and without ending "/"
     * "host/url/" and "host/url" is different urls for cloudflare
     *
     * @param array $urls
     * @return array
     */
    protected function formatUrls(array $urls)
    {
        $formattedUrls = [];

        foreach ($urls as $url) {
            $url = rtrim($url, '/');
            $formattedUrls[] = $url;
            $formattedUrls[] = $url . '/';
        }

        return $formattedUrls;
    }

    /**
     * Write log wrapper
     *
     * @param $error
     * @param $message
     * @param array $arguments
     */
    protected function writelog($error, $message, array $arguments)
    {
        if ($this->beUser !== null) {
            /** @noinspection PhpParamsInspection */
            $this->beUser->writelog(
                4,
                1,
                $error,
                0,
                $message,
                $arguments
            );
        }
    }

    /**
     * @return CloudflareService
     */
    protected function getCloudFlareService()
    {
        if ($this->cloudflareService === null) {
            $this->cloudflareService = GeneralUtility::makeInstance(CloudflareService::class, $this->config);
        }

        return $this->cloudflareService;
    }

    /**
     * Purge for zone (site)
     *
     * @param $identifier
     * @param $zoneName
     */
    protected function purgeAll($identifier, $zoneName)
    {
        try {
            $ret = $this->getCloudFlareService()->send(
                '/zones/' . $identifier . '/purge_cache',
                [
                    'purge_everything' => true,
                ],
                'DELETE'
            );
            if (!is_array($ret)) {
                $ret = [
                    'success' => false,
                    'errors' => []
                ];
            }

            if ($ret['success']) {
                $this->writelog(
                    0,
                    'User %s cleared the cache on Cloudflare (domain: "%s")',
                    [$this->beUser->user['username'], $zoneName]
                );
            } else {
                $this->writelog(
                    1,
                    'User %s failed to clear the cache on Cloudflare (domain: "%s"): %s',
                    [$this->beUser->user['username'], $zoneName, implode(LF, $ret['errors'])]
                );
            }
        } catch (\RuntimeException $e) {
            $this->writelog(1, $e->getMessage(), []);
        }
    }
}
