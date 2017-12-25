<?php
namespace Causal\Cloudflare\Domain\Repository;

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
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class QueueItemRepository
 * @package Causal\Cloudflare\Domain\Repository
 */
class QueueItemRepository extends Repository
{
    public function initializeObject() {
        /** @var Typo3QuerySettings $defaultQuerySettings */
        $defaultQuerySettings = $this->objectManager->get(Typo3QuerySettings::class);
        // disable storage
        $defaultQuerySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($defaultQuerySettings);
    }

    /**
     * Create own save function
     *
     * @param QueueItem $queueItem
     */
    public function add($queueItem)
    {
        $this->getDBConnection()->exec_INSERTquery(
            'tx_cloudflare_domain_model_queueitem',
            [
                'crdate' => time(),
                'page_uid' => $queueItem->getPageUid(),
                'cache_command' => $queueItem->getCacheCommand(),
                'cache_tag' => $queueItem->getCacheTag(),
            ]
        );
    }

    /**
     * @param array|QueryResultInterface $queueItems
     */
    public function removeQueueItems($queueItems)
    {
        if (count($queueItems) > 0) {
            $uids = [];
            /** @var QueueItem $queueItem */
            foreach ($queueItems as $queueItem) {
                $uids[] = $queueItem->getUid();
            }

            $this->getDBConnection()->exec_DELETEquery(
                'tx_cloudflare_domain_model_queueitem',
                sprintf(
                    'uid IN (%s)',
                    implode(',', $uids)
                )
            );
        }
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDBConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
