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


if (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR') != $_SERVER['SERVER_ADDR']) {
    header('HTTP/1.0 403 Access denied');
    // Empty output!!!
} else {
    /** @var \Causal\Cloudflare\Services\PagePathResolverService $pagePathResolverService */
    $pagePathResolverService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \Causal\Cloudflare\Services\PagePathResolverService::class
    );

    header('Content-Type: application/json');
    echo $pagePathResolverService->getLinks();
}