<?php
namespace Causal\Cloudflare\Task;

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

use Causal\Cloudflare\Services\PurgeCacheQueueService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Class ClearCacheTask
 * @package Causal\Cloudflare\Task
 */
class ClearCacheTask extends AbstractTask
{
    /**
     * @return boolean
     */
    public function execute()
    {
        /** @var PurgeCacheQueueService $purgeCacheQueueService */
        $purgeCacheQueueService = GeneralUtility::makeInstance(PurgeCacheQueueService::class);
        $purgeCacheQueueService->purgeCacheQueue();

        return true;
    }
}
