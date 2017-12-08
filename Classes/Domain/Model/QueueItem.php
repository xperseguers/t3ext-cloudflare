<?php
namespace Causal\Cloudflare\Domain\Model;

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

use Causal\Cloudflare\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class QueueItem
 * @package Causal\Cloudflare\Domain\Model
 */
class QueueItem extends AbstractEntity
{
    /**
     * Clear cache all
     */
    const CLEAR_CACHE_COMMAND_ALL = 1;

    /**
     * Clear cache by cache tag
     */
    const CLEAR_CACHE_COMMAND_CACHE_TAG = 2;

    /**
     * Clear cache for single page
     */
    const CLEAR_CACHE_COMMAND_PAGE = 3;

    /**
     * Page Uid to clear cache
     *
     * @var int
     */
    protected $pageUid = 0;

    /**
     * Cache command
     *
     * @var int
     */
    protected $cacheCommand = 0;

    /**
     * Cache tag
     *
     * @var string
     */
    protected $cacheTag = '';

    /**
     * Initalize queue item
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->setPropertiesFromParams($params);
    }
    
    /**
     * @return int
     */
    public function getPageUid()
    {
        return $this->pageUid;
    }

    /**
     * @param int $pageUid
     */
    public function setPageUid($pageUid)
    {
        $this->pageUid = $pageUid;
    }

    /**
     * @return int
     */
    public function getCacheCommand()
    {
        return $this->cacheCommand;
    }

    /**
     * @param int $cacheCommand
     */
    public function setCacheCommand($cacheCommand)
    {
        $this->cacheCommand = $cacheCommand;
    }

    /**
     * @return string
     */
    public function getCacheTag()
    {
        return $this->cacheTag;
    }

    /**
     * @param string $cacheTag
     */
    public function setCacheTag($cacheTag)
    {
        $this->cacheTag = $cacheTag;
    }

    /**
     * Check if it's valid queue item
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->getCacheCommand() === self::CLEAR_CACHE_COMMAND_ALL
            || ConfigurationUtility::isEnablePurgeByTags() && $this->getCacheCommand() === self::CLEAR_CACHE_COMMAND_CACHE_TAG && !empty($this->getCacheTag())
            || ConfigurationUtility::isEnablePurgeByUrl() && $this->getCacheCommand() === self::CLEAR_CACHE_COMMAND_PAGE && $this->getPageUid() !== 0;
    }

    /**
     * Convert params from clear cache hook to obejct properties
     *
     * @param array $params
     */
    protected function setPropertiesFromParams(array $params)
    {
        if (!isset($params['cacheCmd'])) {
            if ($params['table'] === 'pages') {
                $this->setCacheCommand(self::CLEAR_CACHE_COMMAND_CACHE_TAG);
                $this->setCacheTag('pageId_' . (int)$params['uid']);
            }
        } elseif (GeneralUtility::inList('all,pages', $params['cacheCmd'])) {
            $this->setCacheCommand(self::CLEAR_CACHE_COMMAND_ALL);
        } else {
            if (strpos(strtolower($params['cacheCmd']), 'cachetag:') !== false) {
                $cacheTag = substr($params['cacheCmd'], 9);
                $this->setCacheCommand(self::CLEAR_CACHE_COMMAND_CACHE_TAG);
                $this->setCacheTag($cacheTag);
            } else {
                $this->setCacheCommand(self::CLEAR_CACHE_COMMAND_PAGE);
                $this->setPageUid((int)$params['cacheCmd']);
            }
        }
    }
}
