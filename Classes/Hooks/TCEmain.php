<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Xavier Perseguers <xavier@causal.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
class Tx_Cloudflare_Hooks_TCEmain {

	/** @var string */
	protected $extKey = 'cloudflare';

	/** @var array */
	protected $config;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
	}

	/**
	 * Hooks into the "clear all caches" call.
	 *
	 * @param array $params
	 * @param t3lib_TCEmain $pObj
	 * @return void
	 */
	public function clear_cacheCmd(array $params, t3lib_TCEmain $pObj) {
		static $handledPageUids = array();

		if ($params['cacheCmd'] === 'all') {
			$this->clearCloudFlareCache($pObj->BE_USER);
		} elseif (!empty($this->config['enablePurgeSingleFile'])) {
			$pageUid = intval($params['cacheCmd']);
			if ($pageUid && !in_array($pageUid, $handledPageUids)) {
				$handledPageUids[] = $pageUid;
				$url = $this->getFrontendUrl($pageUid);
				if ($url) {
					$this->purgeCloudFlareSingleFile(
						isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : NULL,
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
	public function clearCache() {
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
	 * @param t3lib_beUserAuth $beUser
	 * @return void
	 */
	protected function clearCloudFlareCache(t3lib_beUserAuth $beUser = NULL) {
		$domains = $this->config['domains'] ? t3lib_div::trimExplode(',', $this->config['domains'], TRUE) : array();

		/** @var $cloudflare Tx_Cloudflare_Services_Cloudflare */
		$cloudflare = t3lib_div::makeInstance('Tx_Cloudflare_Services_Cloudflare', $this->config);

		foreach ($domains as $domain) {
			$parameters = array(
				'a' => 'fpurge_ts',
				'z' => $domain,
				'v' => '1',
			);
			try {
				$ret = $cloudflare->send($parameters);

				if ($beUser !== NULL) {
					if ($ret['result'] === 'error') {
						$beUser->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on CloudFlare (domain: "%s"): %s', array($beUser->user['username'], $domain, $ret['msg']));
					} else {
						$beUser->writelog(4, 1, 0, 0, 'User %s cleared the cache on CloudFlare (domain: "%s")', array($beUser->user['username'], $domain));
					}
				}
			} catch (RuntimeException $e) {
				if ($beUser !== NULL) {
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
	protected function getFrontendUrl($uid) {
		if (isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->workspace != 0) {
			// Preview in workspaces is not supported!
			return NULL;
		}

		$GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], $uid, '');

		$GLOBALS['TT'] = t3lib_div::makeInstance('t3lib_timeTrack');
		$GLOBALS['TT']->start();
		$GLOBALS['TSFE']->config['config']['language'] = 'default';

		// Fire all the required function to get the TYPO3 Frontend all set up
		$GLOBALS['TSFE']->id = $uid;
		$GLOBALS['TSFE']->connectToDB();

		// Prevent database debug messages from messing up the output
		$GLOBALS['TYPO3_DB']->debugOutput = FALSE;

		$GLOBALS['TSFE']->initLLVars();
		$GLOBALS['TSFE']->initFEuser();

		// Look up the page
		$GLOBALS['TSFE']->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		$GLOBALS['TSFE']->sys_page->init($GLOBALS['TSFE']->showHiddenPage);

		// If the page is not found (if the page is a sysfolder, etc), then return no URL,
		// preventing any further processing which would result in an error page.
		$page = $GLOBALS['TSFE']->sys_page->getPage($uid);

		if (count($page) == 0) {
			return NULL;
		}

		// If the page is a shortcut, look up the page to which the shortcut references,
		// and do the same check as above.
		if ($page['doktype'] == 4 && count($GLOBALS['TSFE']->getPageShortcut($page['shortcut'],$page['shortcut_mode'], $page['uid'])) == 0) {
			return NULL;
		}

		// Spacer pages and sysfolders result in a page not found page too...
		if ($page['doktype'] == 199 || $page['doktype'] == 254) {
			return NULL;
		}

		$GLOBALS['TSFE']->getPageAndRootline();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->forceTemplateParsing = 1;

		// Find the root template
		$GLOBALS['TSFE']->tmpl->start($GLOBALS['TSFE']->rootLine);

		// Fill the pSetup from the same variables from the same location as where
		// tslib_fe->getConfigArray will get them, so they can be checked before
		// this function is called
		//$GLOBALS['TSFE']->sPre = $GLOBALS['TSFE']->tmpl->setup['types.'][$GLOBALS['TSFE']->type];	 // toplevel - objArrayName
		//$GLOBALS['TSFE']->pSetup = $GLOBALS['TSFE']->tmpl->setup[$GLOBALS['TSFE']->sPre . '.'];

		// If there is no root template found, there is no point in continuing which would
		// result in a 'template not found' page and then call exit PHP.
		// And the same applies if pSetup is empty, which would result in a
		// "The page is not configured" message.
		if (!$GLOBALS['TSFE']->tmpl->loaded || ($GLOBALS['TSFE']->tmpl->loaded && !$GLOBALS['TSFE']->pSetup)) {
			//return NULL;
		}

		$GLOBALS['TSFE']->checkAlternativeIdMethods();
		$GLOBALS['TSFE']->determineId();
		try {
			$GLOBALS['TSFE']->getConfigArray();
		} catch (Exception $e) {
			// Typicall problem: #1294587218: No TypoScript template found!
			return NULL;
		}


		// Get linkVars, absRefPrefix, etc
		TSpagegen::pagegenInit();

		/** @var $contentObj tslib_cObj */
		$contentObj = t3lib_div::makeInstance('tslib_cObj');
		$contentObj->start(array(), '');

		// Create the URL
		$link = $contentObj->typolink('', array(
			'parameter'        => $uid,
			'forceAbsoluteUrl' => 1,
		));
		$url = $contentObj->lastTypoLinkUrl;

		return $url;
	}

	/**
	 * Purges a single file in CloudFlare cache.
	 *
	 * @param t3lib_beUserAuth $beUser
	 * @param string $url
	 * @return void
	 */
	protected function purgeCloudFlareSingleFile(t3lib_beUserAuth $beUser = NULL, $url) {
		$domains = $this->config['domains'] ? t3lib_div::trimExplode(',', $this->config['domains'], TRUE) : array();

		$isValidUrl = FALSE;
		$domain = NULL;

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

		/** @var $cloudflare Tx_Cloudflare_Services_Cloudflare */
		$cloudflare = t3lib_div::makeInstance('Tx_Cloudflare_Services_Cloudflare', $this->config);

		$parameters = array(
			'a'   => 'zone_file_purge',
			'z'   => $domain,
			'url' => $url,
		);
		try {
			$ret = $cloudflare->send($parameters);

			if ($beUser !== NULL) {
				if ($ret['result'] === 'error') {
					$beUser->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on CloudFlare (domain: "%s") for "%s": %s', array($beUser->user['username'], $domain, $url, $ret['msg']));
				} else {
					$beUser->writelog(4, 1, 0, 0, 'User %s cleared the cache on CloudFlare (domain: "%s") for "%s"', array($beUser->user['username'], $domain, $url));
				}
			}
		} catch (RuntimeException $e) {
			if ($beUser !== NULL) {
				$beUser->writelog(4, 1, 1, 0, $e->getMessage(), array());
			}
		}
	}

}
