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
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class QueueItemRepository
 * @package Causal\Cloudflare\Domain\Repository
 */
class QueueItemRepository extends Repository
{
    /**
     * Create own save function
     *
     * @param QueueItem $queueItem
     */
    public function add($queueItem)
    {
        /** @var DatabaseConnection $db */
        $db = $GLOBALS['TYPO3_DB'];

        $db->exec_INSERTquery(
            'tx_cloudflare_domain_model_queueitem',
            [
                'crdate' => time(),
                'page_uid' => $queueItem->getPageUid(),
                'cache_command' => $queueItem->getCacheCommand(),
                'cache_tag' => $queueItem->getCacheTag(),
            ]
        );
    }
}
