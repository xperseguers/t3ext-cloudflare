<?php
namespace Causal\Cloudflare\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2014 Xavier Perseguers <xavier@causal.ch>
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
class TYPO3backend implements \TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface {

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->getLanguageService()->includeLLFile('EXT:cloudflare/Resources/Private/Language/locallang.xlf');
	}

	/**
	 * Adds cache menu item.
	 *
	 * @param array $cacheActions
	 * @param array $optionValues
	 * @return void
	 */
	public function manipulateCacheActions(&$cacheActions, &$optionValues) {
		$backendUser = $this->getBackendUser();
		if ($backendUser->isAdmin() || $backendUser->getTSConfigVal('options.clearCache.all') || $backendUser->getTSConfigVal('options.clearCache.cloudflare')) {
			// Add new cache menu item
			$title = $this->getLanguageService()->getLL('clear_cache');
			$clearAll = array_shift($cacheActions);
			$clearCloudFlare = array(
				'id'    => 'cloudflare',
				'title' => $title,
				'href'  => $GLOBALS['BACK_PATH'] . 'ajax.php?ajaxID=cloudflare::clearCache',
				'icon'  => '<span class="t3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-cache-clear-impact-low"></span>'
			);
			if ($clearAll !== NULL) {
				$cacheActions = array_merge(array($clearAll, $clearCloudFlare), $cacheActions);
			} else {
				$cacheActions[] = $clearCloudFlare;
			}
			$optionValues[] = 'cloudflare';
		}
	}

	/**
	 * Returns the current Backend user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Returns the LanguageService.
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
