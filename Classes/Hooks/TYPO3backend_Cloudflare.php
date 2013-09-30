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

if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
	require_once(PATH_typo3 . 'interfaces/interface.backend_toolbaritem.php');
}

/**
 * Class to render the CloudFlare toolbar menu.
 *
 * @category    Hooks
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class Tx_Cloudflare_Hooks_TYPO3backend_Cloudflare implements backend_toolbarItem {

	protected $changeWorkspace;
	protected $changeWorkspacePreview;

	/**
	 * Reference back to the Backend object
	 *
	 * @var TYPO3backend
	 */
	protected $backendReference;

	/** @var boolean */
	protected $checkAccess = NULL;

	/**
	 * Default constructor.
	 *
	 * @param TYPO3backend TYPO3 Backend object reference
	 */
	public function __construct(TYPO3backend &$backendReference = NULL) {
		$this->backendReference = $backendReference;
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return boolean TRUE if user has access, FALSE otherwise
	 * @see typo3/alt_shortcut.php
	 */
	public function checkAccess() {
		if (t3lib_extMgm::isLoaded('cloudflare')) {
			if ($this->checkAccess === NULL) {
					if ($GLOBALS['BE_USER']->isAdmin()) {
						$this->checkAccess = TRUE;
					} else {
						$this->checkAccess = FALSE;
					}
			}
			return $this->checkAccess;
		}
		return FALSE;
	}

	/**
	 * Creates the CloudFlare toolbar icon with popup menu.
	 *
	 * @return string CloudFlare menu as HTML select
	 */
	public function render() {
		$title = 'CloudFlare'; // $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:toolbarItems.workspace', TRUE);
		$this->addJavascriptToBackend();
		$cloudflareMenu = array();

		$cloudflareMenu[] = '<a href="#" class="toolbar-item">' .
			t3lib_iconWorks::getSpriteIcon('extensions-cloudflare-cloudflare', array('title' => $title)) .
				'</a>';
		$cloudflareMenu[] = '<ul class="toolbar-item-menu" style="display: none;">';
		$cloudflareMenu[] = '</ul>';

		return implode(LF, $cloudflareMenu);
	}

	/**
	 * Adds the necessary JavaScript to the backend.
	 *
	 * @return	void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile(t3lib_extMgm::extRelPath('cloudflare') . 'Resources/Public/JavaScript/cloudflaremenu.js');
	}

	/**
	 * Returns additional attributes for the list item in the toolbar.
	 *
	 * @return string List item HTML attributes
	 */
	public function getAdditionalAttributes() {
		return ' id="cloudflare-menu"';
	}
}


if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	$GLOBALS['TYPO3backend']->addToolbarItem('cloudflare', 'Tx_Cloudflare_Hooks_TYPO3backend_Cloudflare');
}
