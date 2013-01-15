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
	 */
	public function retrieveCloudFlareStatus($parameter) {
		$infoLines = array();
		$domains = t3lib_div::trimExplode(',', $this->config['domains'], TRUE);
		if (count($domains)) {
			/** @var $cloudflare Tx_Cloudflare_Services_Cloudflare */
			$cloudflare = t3lib_div::makeInstance('Tx_Cloudflare_Services_Cloudflare', $this->config);

			try {
				$ret = $cloudflare->send(array('a' => 'zone_load_multi'));
				if ($ret['result'] === 'success') {
					foreach ($ret['response']['zones']['objs'] as $zone) {
						if (in_array($zone['zone_name'], $domains)) {
							$infoLine = array(
								'domain' => $zone['zone_name'],
								'icon'   => $this->getOperationStatusIcon($zone['zone_status_class']),
								'operations' => array(),
							);
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
								$infoLine['operations'][] = array(
									'text'   => 'Toggle development mode',
									'icon'   => $this->getOperationStatusIcon('operation-toggle'),
									'fn'     => 'this.toggleDevelopmentMode',
									'params' => array(
										'zone'   => $zone['zone_name'],
										'active' => $active,
									),
								);
								$infoLine['operations'][] = array(
									'text'   => 'Clear CloudFlare cache',
									'icon'   => $this->getOperationStatusIcon('operation-clear-cache'),
									'fn'     => 'this.clearCache',
									'params' => array(
										'zone' => $zone['zone_name'],
									),
								);
							} else {
								$infoLine['operations'][] = array(
									'text'     => 'Zone is inactive',
									'icon'     => $this->getOperationStatusIcon('status-inactive'),
									'fn'       => 'function(){}',
									'disabled' => 1,
								);
							}

							$infoLines[] = $infoLine;
						}
					}
				}
			} catch (RuntimeException $e) {
				// Nothing to do
			}
		}

		return $infoLines;
	}

	/**
	 * Toggles the development mode.
	 *
	 * @param $parameter
	 * @return array
	 */
	public function toggleDevelopmentMode($parameter) {
		/** @var $cloudflare Tx_Cloudflare_Services_Cloudflare */
		$cloudflare = t3lib_div::makeInstance('Tx_Cloudflare_Services_Cloudflare', $this->config);

		try {
			$ret = $cloudflare->send(array(
				'a' => 'devmode',
				'z' => $parameter->zone,
				'v' => $parameter->active,
			));
			$newStatus = $parameter->active ? 'status-dev-mode' : 'status-active';
		} catch (RuntimeException $e) {
			// Nothing to do
		}

		return array(
			'result' => 'success',
			'icon' => $this->getOperationStatusIcon($newStatus),
		);
	}

	/**
	 * Clears the cache of a given zone.
	 *
	 * @param $parameter
	 * @return array
	 */
	public function clearCache($parameter) {
		/** @var $cloudflare Tx_Cloudflare_Services_Cloudflare */
		$cloudflare = t3lib_div::makeInstance('Tx_Cloudflare_Services_Cloudflare', $this->config);

		try {
			$ret = $cloudflare->send(array(
				'a' => 'fpurge_ts',
				'z' => $parameter->zone,
				'v' => '1',
			));
		} catch (RuntimeException $e) {
			// Nothing to do
		}

		return array(
			'result' => 'success',
		);
	}

	/**
	 * Returns the icon associated to a given operation/status.
	 *
	 * @param string $operationStatus
	 * @return string
	 */
	protected function getOperationStatusIcon($operationStatus) {
		switch ($operationStatus) {
			case 'operation-toggle':
				$span = t3lib_iconWorks::getSpriteIcon('extensions-cloudflare-toggle');
				break;
			case 'operation-clear-cache':
				return 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-cache-clear-impact-high';
			case 'status-inactive':
				$span = t3lib_iconWorks::getSpriteIcon('extensions-cloudflare-inactive');
				break;
			case 'status-active':
				$span = t3lib_iconWorks::getSpriteIcon('extensions-cloudflare-online');
				break;
			case 'status-dev-mode':
				$span = t3lib_iconWorks::getSpriteIcon('extensions-cloudflare-direct');
				break;
			case 'status-deactivated':
			default:
				$span = t3lib_iconWorks::getSpriteIcon('extensions-cloudflare-offline');
				break;
		}

		if (preg_match('/class="(.*)"/', $span, $matches)) {
			return $matches[1];
		}
		return '';
	}

}

?>