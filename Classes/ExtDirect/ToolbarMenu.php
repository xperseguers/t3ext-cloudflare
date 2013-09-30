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
 * Toolbar Menu ExtDirect handler.
 *
 * @category    ExtDirect
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class Tx_Cloudflare_ExtDirect_ToolbarMenu {

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
	 * Retrieves the CloudFlare status of selected domains.
	 *
	 * @param $parameter
	 * @return array
	 * @throws RuntimeException
	 */
	public function retrieveCloudFlareStatus($parameter) {
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			throw new RuntimeException('Unauthorized call', 1366652032);
		}
		$out = array();
		$domains = t3lib_div::trimExplode(',', $this->config['domains'], TRUE);
		if (count($domains)) {
			/** @var $cloudflare Tx_Cloudflare_Services_Cloudflare */
			$cloudflare = t3lib_div::makeInstance('Tx_Cloudflare_Services_Cloudflare', $this->config);

			try {
				$ret = $cloudflare->send(array('a' => 'zone_load_multi'));
				if ($ret['result'] === 'success') {
					foreach ($ret['response']['zones']['objs'] as $zone) {
						if (in_array($zone['zone_name'], $domains)) {
							$out[] = '<li><h3>&nbsp;' . $this->getZoneIcon($zone['zone_status_class']) . ' ' . $zone['zone_name'] . '</h3></li>';
							$active = NULL;
							switch ($zone['zone_status_class']) {
								case 'status-active':
									$active = 1;
									break;
								case 'status-dev-mode':
									$active = 0;
									break;
							}
							if ($active !== NULL) {
								$js = 'TYPO3BackendCloudflareMenu.toggleDevelopmentMode(\'' . $zone['zone_name'] . '\', ' . $active . ');';
								$out[] = '<li class="divider"><a href="#" onclick="' . $js . '">Toggle development mode</a></li>';
							} else {
								$out[] = '<li class="divider">This zone is currently inactive</li>';
							}
						}
					}
				}
			} catch (RuntimeException $e) {
				// Nothing to do
			}
		} else {
			$out[] = '<li>No domains configured.</li>';
		}

		return array('html' => implode('', $out));
	}

	/**
	 * Toggle the development mode.
	 *
	 * @param $parameter
	 * @return array
	 * @throws RuntimeException
	 */
	public function toggleDevelopmentMode($parameter) {
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			throw new RuntimeException('Unauthorized call', 1366652080);
		}

		/** @var $cloudflare Tx_Cloudflare_Services_Cloudflare */
		$cloudflare = t3lib_div::makeInstance('Tx_Cloudflare_Services_Cloudflare', $this->config);

		try {
			$ret = $cloudflare->send(array(
				'a' => 'devmode',
				'z' => $parameter->zone,
				'v' => $parameter->active,
			));
		} catch (RuntimeException $e) {
			// Nothing to do
		}

		return array('result' => 'success');
	}

	/**
	 * Returns the icon associated to a given CloudFlare status.
	 *
	 * @param string $status
	 * @return string
	 */
	protected function getZoneIcon($status) {
		switch ($status) {
			case 'status-active':
				$icon = t3lib_iconWorks::getSpriteIcon('extensions-cloudflare-online', array('title' => 'Zone is active'));
				break;
			case 'status-dev-mode':
				$icon = t3lib_iconWorks::getSpriteIcon('extensions-cloudflare-direct', array('title' => 'Zone is in development mode'));
				break;
			case 'status-deactivated':
			default:
				$icon = t3lib_iconWorks::getSpriteIcon('extensions-cloudflare-offline', array('title' => 'Zone is inactive'));
				break;
		}
		return $icon;
	}

}
