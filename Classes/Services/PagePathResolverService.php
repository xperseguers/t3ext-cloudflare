<?php

namespace Causal\Cloudflare\Services;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageGenerator;
use TYPO3\CMS\Frontend\Utility\EidUtility;

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
 * Class PagePathResolverService
 * @package Causal\Cloudflare\Services
 */
class PagePathResolverService
{
    /**
     * Array of pages
     * Assume that all pages are in one root line
     *
     * @var array
     */
    protected $pageUids = [];

    /**
     * Initializes variables for link
     */
    public function __construct()
    {
        $params = json_decode(base64_decode(GeneralUtility::_GP('data')), true);

        if (is_array($params)) {
            $this->pageUids = GeneralUtility::intExplode(',', $params['uids'], true);
        }

        EidUtility::initTCA();
    }

    /**
     * Generate link
     *
     * @return string
     */
    public function getLinks()
    {
        $result = [];
        if ($this->pageUids) {
            $this->createTSFE();

            /** @var ContentObjectRenderer $cObj */
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

            foreach ($this->pageUids as $pageId) {
                $typoLinkConf = [
                    'parameter' => $pageId,
                    'forceAbsoluteUrl' => 1
                ];

                $url = $cObj->typoLink_URL($typoLinkConf);
                if (!empty($url)) {
                    $host = parse_url($url, PHP_URL_HOST);
                    $result[$pageId] = $host ? $url : GeneralUtility::locationHeaderUrl($url);
                }
            }
        }

        return json_encode($result);
    }

    /**
     * Initializes TSFE. This is necessary to have proper environment for typoLink.
     *
     * @return void
     */
    protected function createTSFE()
    {
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            $GLOBALS['TYPO3_CONF_VARS'],
            $this->pageUids[0],
            ''
        );
        $tsfe->connectToDB();
        $tsfe->initFEuser();
        $tsfe->determineId();
        $tsfe->initTemplate();
        $tsfe->getConfigArray();

        $GLOBALS['TSFE'] = $tsfe;

        // Set linkVars, absRefPrefix, etc
        PageGenerator::pagegenInit();
    }
}
