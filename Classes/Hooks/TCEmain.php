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

use Causal\Cloudflare\Services\CloudflareService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;

/**
 * Hook for clearing cache on Cloudflare.
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
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($this->extKey);
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
        static $handledPageUids = [];
        static $handledTags = [];

        $enablePurgeByUrl = isset($this->config['enablePurgeSingleFile']) && (bool)$this->config['enablePurgeSingleFile'];
        $enablePurgeByTags = isset($this->config['enablePurgeByTags']) && (bool)$this->config['enablePurgeByTags'];

        if (!isset($params['cacheCmd'])) {
            if ($params['table'] === 'pages' && $enablePurgeByTags) {
                $this->purgeIndividualFileByCacheTag(
                    isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : null,
                    'pageId_' . (int)$params['uid']
                );
            }
            return;
        }

        if (GeneralUtility::inList('all,pages', $params['cacheCmd'])) {
            $this->clearCloudflareCache($pObj->BE_USER);
        } else {
            if (strpos(strtolower($params['cacheCmd']), 'cachetag:') !== false && $enablePurgeByTags) {
                $cacheTag = substr($params['cacheCmd'], 9);
                if (!in_array($cacheTag, $handledTags)) {
                    $handledTags[] = $cacheTag;
                    $this->purgeIndividualFileByCacheTag(
                        isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : null,
                        $cacheTag
                    );
                }
            } elseif ($enablePurgeByUrl) {
                $pageUid = (int)$params['cacheCmd'];
                if ($pageUid && !in_array($pageUid, $handledPageUids)) {
                    $handledPageUids[] = $pageUid;
                    $url = $this->getFrontendUrl($pageUid);
                    if ($url) {
                        $this->purgeIndividualFileByUrl(
                            isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : null,
                            $url
                        );
                    }
                }
            }
        }
    }

    /**
     * Answers to AJAX call invoked when clearing only Cloudflare cache.
     *
     * @return void
     */
    public function clearCache()
    {
        if (!isset($GLOBALS['BE_USER'])) {
            return;
        }

        $canClearAllCache = (bool)($GLOBALS['BE_USER']->getTSConfig()['options.']['clearCache.']['all'] ?? false);
        $canClearCloudflareCache = (bool)($GLOBALS['BE_USER']->getTSConfig()['options.']['clearCache.']['cloudflare'] ?? false);

        if ($GLOBALS['BE_USER']->isAdmin() || $canClearAllCache || $canClearCloudflareCache) {
            $this->clearCloudflareCache($GLOBALS['BE_USER']);
        }
    }

    /**
     * Clears the Cloudflare cache.
     *
     * @param AbstractUserAuthentication $beUser
     * @return void
     */
    protected function clearCloudflareCache(AbstractUserAuthentication $beUser = null)
    {
        $domains = $this->config['domains'] ? GeneralUtility::trimExplode(',', $this->config['domains'], true) : [];

        /** @var CloudflareService $cloudflareService */
        $cloudflareService = GeneralUtility::makeInstance(CloudflareService::class, $this->config);

        foreach ($domains as $domain) {
            try {
                list($identifier, $zoneName) = explode('|', $domain, 2);
                $ret = $cloudflareService->send('/zones/' . $identifier . '/purge_cache', [
                    'purge_everything' => true,
                ], 'DELETE');
                if (!is_array($ret)) {
                    $ret = [
                        'success' => false,
                        'errors' => []
                    ];
                }

                if ($beUser !== null) {
                    if ($ret['success']) {
                        $beUser->writelog(4, 1, 0, 0, 'User %s cleared the cache on Cloudflare (domain: "%s")', [$beUser->user['username'], $zoneName]);
                    } else {
                        $beUser->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on Cloudflare (domain: "%s"): %s', [$beUser->user['username'], $zoneName, implode(LF, $ret['errors'])]);
                    }
                }
            } catch (\RuntimeException $e) {
                if ($beUser !== null) {
                    $beUser->writelog(4, 1, 1, 0, $e->getMessage(), []);
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
            \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class,
            $GLOBALS['TYPO3_CONF_VARS'],
            $uid,
            ''
        );
        $GLOBALS['TSFE'] = $tsfe;

        $GLOBALS['TT'] = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TimeTracker\TimeTracker::class);
        $GLOBALS['TT']->start();
        $GLOBALS['TSFE']->config['config']['language'] = 'default';

        // Fire all the required function to get the TYPO3 Frontend all set up
        $GLOBALS['TSFE']->id = $uid;

        $GLOBALS['TSFE']->settingLanguage();
        $GLOBALS['TSFE']->settingLocale();
        $GLOBALS['TSFE']->initFEuser();

        // If the page is not found (if the page is a sysfolder, etc), then return no URL,
        // preventing any further processing which would result in an error page.
        $page = $GLOBALS['TSFE']->sys_page->getPage($uid);

        if (empty($page)) {
            return null;
        }

        // If the page is a shortcut, look up the page to which the shortcut references,
        // and do the same check as above.
        $pageShortcut = null;
        if ($page['doktype'] == 4) {
            try {
                $pageShortcut = $GLOBALS['TSFE']->sys_page->getPageShortcut($page['shortcut'], $page['shortcut_mode'], $page['uid']);
            } catch (\Exception $e) {
                // Page is not accessible
            }
        }
        if ($page['doktype'] == 4 && empty($pageShortcut)) {
            return null;
        }

        // Spacer pages and sysfolders result in a page not found page too...
        if ($page['doktype'] == 199 || $page['doktype'] == 254) {
            return null;
        }


        // TODO: find a way around this reflection hack
        // Possibly inspire from \TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit() in TYPO3 v8?
        $class = new \ReflectionClass(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class);
        $method = $class->getMethod('getPageAndRootline');
        $method->setAccessible(true);
        $method->invoke($GLOBALS['TSFE']);

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
            // Typically problem: #1294587218: No TypoScript template found!
            return null;
        }

        /** @var $contentObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
        $contentObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $contentObj->start([], '');

        // Create the URL
        $link = $contentObj->typolink('', [
            'parameter' => $uid,
            'forceAbsoluteUrl' => 1,
        ]);
        $url = $contentObj->lastTypoLinkUrl;

        return $url;
    }

    /**
     * Granularly removes an individual file from Cloudflare's cache by specifying the URL.
     *
     * @param AbstractUserAuthentication $beUser
     * @param string $url
     * @return void
     */
    protected function purgeIndividualFileByUrl(AbstractUserAuthentication $beUser = null, $url)
    {
        $domains = $this->config['domains'] ? GeneralUtility::trimExplode(',', $this->config['domains'], true) : [];

        $domain = null;
        $zoneIdentifier = null;

        if (preg_match('#^https?://([^/]+)#', $url, $matches)) {
            $domainParts = explode('.', $matches[1]);
            if (count($domainParts) > 1) {
                $size = count($domainParts);
                $zoneName = $domainParts[$size - 2] . '.' . $domainParts[$size - 1];

                foreach ($domains as $domain) {
                    list($identifier, $z) = explode('|', $domain, 2);
                    if ($z === $zoneName) {
                        $zoneIdentifier = $identifier;
                        break;
                    }
                }
            }
        }

        if ($zoneIdentifier === null) {
            return;
        }

        /** @var CloudflareService $cloudflareService */
        $cloudflareService = GeneralUtility::makeInstance(CloudflareService::class, $this->config);

        try {
            $ret = $cloudflareService->send('/zones/' . $zoneIdentifier . '/purge_cache', [
                'files' => [$url],
            ], 'DELETE');

            if ($beUser !== null) {
                if ($ret['result'] === 'error') {
                    $beUser->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on Cloudflare (domain: "%s") for "%s": %s', [$beUser->user['username'], $domain, $url, $ret['msg']]);
                } else {
                    $beUser->writelog(4, 1, 0, 0, 'User %s cleared the cache on Cloudflare (domain: "%s") for "%s"', [$beUser->user['username'], $domain, $url]);
                }
            }
        } catch (\RuntimeException $e) {
            if ($beUser !== null) {
                $beUser->writelog(4, 1, 1, 0, $e->getMessage(), []);
            }
        }
    }

    /**
     * Granularly removes an individual file from Cloudflare's cache by specifying the associated Cache-Tag.
     *
     * @param AbstractUserAuthentication|null $beUser
     * @param string $cacheTag
     * @return void
     */
    protected function purgeIndividualFileByCacheTag(AbstractUserAuthentication $beUser = null, $cacheTag)
    {
        $domains = $this->config['domains'] ? GeneralUtility::trimExplode(',', $this->config['domains'], true) : [];

        /** @var CloudflareService $cloudflareService */
        $cloudflareService = GeneralUtility::makeInstance(CloudflareService::class, $this->config);

        foreach ($domains as $domain) {
            try {
                list($identifier, $zoneName) = explode('|', $domain, 2);
                $ret = $cloudflareService->send('/zones/' . $identifier . '/purge_cache', [
                    'tags' => [$cacheTag],
                ], 'DELETE');
                if (!is_array($ret)) {
                    $ret = [
                        'success' => false,
                        'errors' => []
                    ];
                }

                if ($beUser !== null) {
                    if ($ret['success']) {
                        $beUser->writelog(4, 1, 0, 0, 'User %s cleared the cache on Cloudflare using Cache-Tag (domain: "%s")', [$beUser->user['username'], $zoneName]);
                    } else {
                        $beUser->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on Cloudflare using Cache-Tag (domain: "%s"): %s', [$beUser->user['username'], $zoneName, implode(LF, $ret['errors'])]);
                    }
                }
            } catch (\RuntimeException $e) {
                if ($beUser !== null) {
                    $beUser->writelog(4, 1, 1, 0, $e->getMessage(), []);
                }
            }
        }
    }
}
