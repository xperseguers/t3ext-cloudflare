<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Xavier Perseguers <xavier@causal.ch>
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

require_once(PATH_typo3 . 'interfaces/interface.backend_cacheActionsHook.php');

/**
 * Hook for clearing cache on CloudFlare.
 *
 * @category    Hooks
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class Tx_Cloudflare_Hooks_TYPO3backend implements backend_cacheActionsHook {

	/**
	 * Adds cache menu item.
	 *
	 * @param array $cacheActions
	 * @param array $optionValues
	 * @return void
	 */
	public function manipulateCacheActions(&$cacheActions, &$optionValues) {
		if ($GLOBALS['BE_USER']->isAdmin()) {
				// Add new cache menu item
			$title = 'Clear Cloud Flare cache';
			$clearAll = array_shift($cacheActions);
			$clearCloudFlare = array(
				'id'    => 'clearCloudflareCache',
				'title' => $title,
				'href'  => $GLOBALS['BACK_PATH'] . 'ajax.php?ajaxID=cloudflare::clearCache',
				'icon'  => '<span class="t3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-cache-clear-impact-high"></span>'
			);
			$cacheActions = array_merge(array($clearAll, $clearCloudFlare), $cacheActions);
			$optionValues[] = 'clearCloudflareCache';
		}
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cloudflare/Classes/Hooks/TYPO3Backend.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cloudflare/Classes/Hooks/TYPO3Backend.php']);
}

?>