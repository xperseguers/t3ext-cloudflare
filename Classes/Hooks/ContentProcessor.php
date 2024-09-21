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

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Hooks for \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController.
 *
 * @category    Hooks
 * @package     tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
class ContentProcessor
{
    /**
     * Sends Cache-Tags header for Cloudflare.
     *
     * @param TypoScriptFrontendController $parentObject
     * @param int $timeOutTime
     */
    public function insertPageIncache(
        TypoScriptFrontendController $parentObject,
        int $timeOutTime
    ): void
    {
        $cacheTags = array_unique($parentObject->getPageCacheTags());

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
     * a must-have.
     *
     * @param array $params
     */
    public function sendPageCacheTag(array $params): void
    {
        $cacheTag = 'Cache-Tag: pageId_' . $params['pObj']->id;
        if (!in_array($cacheTag, headers_list(), true)) {
            header($cacheTag);
        }
    }
}
