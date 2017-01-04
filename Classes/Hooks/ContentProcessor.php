<?php
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

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Hooks for \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController.
 *
 * @category    Hooks
 * @package     tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   2016-2017 Causal Sàrl
 * @license     https://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ContentProcessor extends \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
{

    /**
     * Default constructor to prevent a PHP warning due to missing initialization
     * arguments for parent constructor.
     */
    public function __construct()
    {

    }

    /**
     * Sends Cache-Tags header for Cloudflare.
     *
     * @param TypoScriptFrontendController $parentObject
     * @param int $timeOutTime
     * @return void
     */
    public function insertPageIncache(TypoScriptFrontendController $parentObject, $timeOutTime)
    {
        // Trick: By extending the parent class we may access the protected list of cache tags!
        $cacheTags = array_unique($parentObject->pageCacheTags);

        if (!empty($cacheTags)) {
            $chunks = array_chunk($cacheTags, 30);  // Cloudflare does not support more than 30 tags at once
            foreach ($chunks as $chunk) {
                header('Cache-Tag: ' . implode(',', $chunk));
            }
        }
    }

    /**
     * Sends Cache-Tag header for current page.
     *
     * This method is called when the page is read from TYPO3 cache and does not
     * go through the whole page generation process. Other possible associated
     * cache tags are not available for reading and are thus not considered. This
     * would probably need a query to the database to do so and is probably not
     * a must have.
     *
     * @param array $params
     * @return void
     */
    public function sendPageCacheTag(array $params)
    {
        $cacheTag = 'Cache-Tag: pageId_' . $params['pObj']->id;
        if (!in_array($cacheTag, headers_list())) {
            header($cacheTag);
        };
    }
}
