<?php
namespace Causal\Cloudflare\Hooks;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook for clearing cache on CloudFlare.
 *
 * @category    Hooks
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class TCEmain
{

    /** @var string */
    protected $extKey = 'cloudflare';

    /** @var array */
    protected $config;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey];
        $this->config = $config ? unserialize($config) : array();
    }

    /**
     * Hooks into the "clear all caches" call.
     *
     * @param array $params
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     * @return void
     */
    public function clear_cacheCmd(array $params, \TYPO3\CMS\Core\DataHandling\DataHandler $pObj)
    {
        static $handledPageUids = array();

        if (GeneralUtility::inList('all,pages', $params['cacheCmd'])) {
            $this->clearCloudFlareCache($pObj->BE_USER);
        } elseif (!empty($this->config['enablePurgeSingleFile'])) {
            $pageUid = intval($params['cacheCmd']);
            if ($pageUid && !in_array($pageUid, $handledPageUids)) {
                $handledPageUids[] = $pageUid;
                $url = $this->getFrontendUrl($pageUid);
                if ($url) {
                    $this->purgeCloudFlareSingleFile(
                        isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : null,
                        $url
                    );
                }
            }
        }
    }

    /**
     * Answers to AJAX call invoked when clearing only CloudFlare cache.
     *
     * @return void
     */
    public function clearCache()
    {
        if (!isset($GLOBALS['BE_USER'])) {
            return;
        }
        if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.all') || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.cloudflare')) {
            $this->clearCloudFlareCache($GLOBALS['BE_USER']);
        }
    }

    /**
     * Clears the CloudFlare cache.
     *
     * @param \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $beUser
     * @return void
     */
    protected function clearCloudFlareCache(\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $beUser = null)
    {
        $domains = $this->config['domains'] ? GeneralUtility::trimExplode(',', $this->config['domains'], true) : array();

        /** @var $cloudflareService \Causal\Cloudflare\Services\CloudflareService */
        $cloudflareService = GeneralUtility::makeInstance('Causal\\Cloudflare\\Services\\CloudflareService', $this->config);

        foreach ($domains as $domain) {
            $parameters = array(
                'a' => 'fpurge_ts',
                'z' => $domain,
                'v' => '1',
            );
            try {
                $ret = $cloudflareService->send($parameters);

                if ($beUser !== null) {
                    if ($ret['result'] === 'error') {
                        $beUser->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on CloudFlare (domain: "%s"): %s', array($beUser->user['username'], $domain, $ret['msg']));
                    } else {
                        $beUser->writelog(4, 1, 0, 0, 'User %s cleared the cache on CloudFlare (domain: "%s")', array($beUser->user['username'], $domain));
                    }
                }
            } catch (\RuntimeException $e) {
                if ($beUser !== null) {
                    $beUser->writelog(4, 1, 1, 0, $e->getMessage(), array());
                }
            }
        }
    }

    /**
     * Returns a Frontend URL corresponding to a given page UID.
     * Implementation was inspired by EXT:vara_feurlfrombe
     *
     * @param integer $uid
     * @return string
     * @todo Add support for multiple Frontend URLs
     */
    protected function getFrontendUrl($uid)
    {
        if (isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->workspace != 0) {
            // Preview in workspaces is not supported!
            return null;
        }

        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe */
        $tsfe = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
            $GLOBALS['TYPO3_CONF_VARS'],
            $uid,
            ''
        );
        $GLOBALS['TSFE'] = $tsfe;

        $GLOBALS['TT'] = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TimeTracker\\TimeTracker');
        $GLOBALS['TT']->start();
        $GLOBALS['TSFE']->config['config']['language'] = 'default';

        // Fire all the required function to get the TYPO3 Frontend all set up
        $GLOBALS['TSFE']->id = $uid;
        $GLOBALS['TSFE']->connectToDB();

        // Prevent database debug messages from messing up the output
        $GLOBALS['TYPO3_DB']->debugOutput = false;

        $GLOBALS['TSFE']->initLLVars();
        $GLOBALS['TSFE']->initFEuser();

        // Look up the page
        $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
        $GLOBALS['TSFE']->sys_page->init($GLOBALS['TSFE']->showHiddenPage);

        // If the page is not found (if the page is a sysfolder, etc), then return no URL,
        // preventing any further processing which would result in an error page.
        $page = $GLOBALS['TSFE']->sys_page->getPage($uid);

        if (count($page) == 0) {
            return null;
        }

        // If the page is a shortcut, look up the page to which the shortcut references,
        // and do the same check as above.
        if ($page['doktype'] == 4 && count($GLOBALS['TSFE']->getPageShortcut($page['shortcut'], $page['shortcut_mode'], $page['uid'])) == 0) {
            return null;
        }

        // Spacer pages and sysfolders result in a page not found page too...
        if ($page['doktype'] == 199 || $page['doktype'] == 254) {
            return null;
        }

        $GLOBALS['TSFE']->getPageAndRootline();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->forceTemplateParsing = 1;

        // Find the root template
        $GLOBALS['TSFE']->tmpl->start($GLOBALS['TSFE']->rootLine);

        // Fill the pSetup from the same variables from the same location as where
        // tslib_fe->getConfigArray will get them, so they can be checked before
        // this function is called
        //$GLOBALS['TSFE']->sPre = $GLOBALS['TSFE']->tmpl->setup['types.'][$GLOBALS['TSFE']->type];    // toplevel - objArrayName
        //$GLOBALS['TSFE']->pSetup = $GLOBALS['TSFE']->tmpl->setup[$GLOBALS['TSFE']->sPre . '.'];

        // If there is no root template found, there is no point in continuing which would
        // result in a 'template not found' page and then call exit PHP.
        // And the same applies if pSetup is empty, which would result in a
        // "The page is not configured" message.
        if (!$GLOBALS['TSFE']->tmpl->loaded || ($GLOBALS['TSFE']->tmpl->loaded && !$GLOBALS['TSFE']->pSetup)) {
            //return null;
        }

        $GLOBALS['TSFE']->checkAlternativeIdMethods();
        $GLOBALS['TSFE']->determineId();
        try {
            $GLOBALS['TSFE']->getConfigArray();
        } catch (\Exception $e) {
            // Typicall problem: #1294587218: No TypoScript template found!
            return null;
        }

        // Get linkVars, absRefPrefix, etc
        \TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit();

        /** @var $contentObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
        $contentObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
        $contentObj->start(array(), '');

        // Create the URL
        $link = $contentObj->typolink('', array(
            'parameter' => $uid,
            'forceAbsoluteUrl' => 1,
        ));
        $url = $contentObj->lastTypoLinkUrl;

        return $url;
    }

    /**
     * Purges a single file in CloudFlare cache.
     *
     * @param \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $beUser
     * @param string $url
     * @return void
     */
    protected function purgeCloudFlareSingleFile(\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $beUser = null, $url)
    {
        $domains = $this->config['domains'] ? GeneralUtility::trimExplode(',', $this->config['domains'], true) : array();

        $isValidUrl = false;
        $domain = null;

        if (preg_match('#^https?://([^/]+)#', $url, $matches)) {
            $domainParts = explode('.', $matches[1]);
            if (count($domainParts) > 1) {
                $size = count($domainParts);
                $domain = $domainParts[$size - 2] . '.' . $domainParts[$size - 1];
                $isValidUrl = in_array($domain, $domains);
            }
        }

        if (!$isValidUrl) {
            return;
        }

        /** @var $cloudflareService \Causal\Cloudflare\Services\CloudflareService */
        $cloudflareService = GeneralUtility::makeInstance('Causal\\Cloudflare\\Services\\CloudflareService', $this->config);

        $parameters = array(
            'a' => 'zone_file_purge',
            'z' => $domain,
            'url' => $url,
        );
        try {
            $ret = $cloudflareService->send($parameters);

            if ($beUser !== null) {
                if ($ret['result'] === 'error') {
                    $beUser->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on CloudFlare (domain: "%s") for "%s": %s', array($beUser->user['username'], $domain, $url, $ret['msg']));
                } else {
                    $beUser->writelog(4, 1, 0, 0, 'User %s cleared the cache on CloudFlare (domain: "%s") for "%s"', array($beUser->user['username'], $domain, $url));
                }
            }
        } catch (\RuntimeException $e) {
            if ($beUser !== null) {
                $beUser->writelog(4, 1, 1, 0, $e->getMessage(), array());
            }
        }
    }

}
