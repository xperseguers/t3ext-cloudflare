<?php
namespace Causal\Cloudflare\Services;

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

use Causal\Cloudflare\Domain\Model\QueueItem;
use Causal\Cloudflare\Domain\Repository\QueueItemRepository;
use Causal\Cloudflare\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class PurgeCacheQueueService
 * @package Causal\Cloudflare\Services
 */
class PurgeCacheQueueService
{
    /**
     * @var QueueItemRepository
     */
    protected $queueItemRepository = null;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        $this->queueItemRepository = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(QueueItemRepository::class);
    }

    /**
     * Clear queue and request purge from cloudflare
     */
    public function purgeCacheQueue()
    {
        $queueItems = $this->queueItemRepository->findAll();
        $clearAllCache = false;
        $clearPageUids = [];
        $clearCacheTags = [];
        $clearRootLinePageUids = [];

        /** @var QueueItem $queueItem */
        foreach ($queueItems as $queueItem) {
            switch ($queueItem->getCacheCommand()) {
                // Clear all
                case QueueItem::CLEAR_CACHE_COMMAND_ALL:
                    // If clear all caches no sense to do anything else
                    $clearAllCache = true;
                    break 2;
                // Clear by tag
                case QueueItem::CLEAR_CACHE_COMMAND_CACHE_TAG:
                    $clearCacheTags[] = $queueItem->getCacheTag();
                    break;
                // Clear page
                case QueueItem::CLEAR_CACHE_COMMAND_PAGE:
                    $clearPageUids[] = $queueItem->getPageUid();
                    break;
                case QueueItem::CLEAR_CACHE_ROOT_LINE:
                    $clearRootLinePageUids[] = $queueItem->getPageUid();
                    break;
                // No default action
            }
        }

        $clearCacheService = $this->getClearCacheService();

        if ($clearAllCache) {
            $clearCacheService->clearCloudflareCache();
        } else {
            if (!empty($clearCacheTags)) {
                $clearCacheService->clearCacheTags(array_unique($clearCacheTags));
            }
            if (!empty($clearPageUids)) {
                $clearCacheService->clearPagesCache(array_unique($clearPageUids));
            }
            if (!empty($clearRootLinePageUids)) {
                $clearCacheService->clearSitesRootLineCache($clearRootLinePageUids);
            }
        }

        $this->queueItemRepository->removeQueueItems($queueItems);
    }

    /**
     * @return ClearCacheService|object
     */
    protected function getClearCacheService()
    {
        return GeneralUtility::makeInstance(
            ClearCacheService::class,
            ConfigurationUtility::getExtensionConfiguration(),
            $GLOBALS['BE_USER']
        );
    }
}
