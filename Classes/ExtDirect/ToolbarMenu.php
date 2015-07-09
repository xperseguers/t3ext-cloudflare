<?php
namespace Causal\Cloudflare\ExtDirect;

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
use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * Toolbar Menu ExtDirect handler (TYPO3 6.2).
 *
 * @category    ExtDirect
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ToolbarMenu {

	/** @var string */
	protected $extKey = 'cloudflare';

	/** @var array */
	protected $config;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey];
		$this->config = $config ? unserialize($config) : array();
		$this->getLanguageService()->includeLLFile('EXT:cloudflare/Resources/Private/Language/locallang.xlf');
	}

	/**
	 * Retrieves the CloudFlare status of selected domains.
	 *
	 * @param $parameter
	 * @return array
	 * @throws \RuntimeException
	 */
	public function retrieveCloudFlareStatus($parameter) {
		if (!$this->getBackendUser()->isAdmin()) {
			throw new \RuntimeException('Unauthorized call', 1366652032);
		}
		$languageService = $this->getLanguageService();
		$out = array();
		$domains = GeneralUtility::trimExplode(',', $this->config['domains'], TRUE);
		if (count($domains)) {
			/** @var $cloudflareService \Causal\Cloudflare\Services\CloudflareService */
			$cloudflareService = GeneralUtility::makeInstance('Causal\\Cloudflare\\Services\\CloudflareService', $this->config);

			try {
				$ret = $cloudflareService->send(array('a' => 'zone_load_multi'));
				if ($ret['result'] === 'success') {
					foreach ($ret['response']['zones']['objs'] as $zone) {
						if (in_array($zone['zone_name'], $domains)) {
							$out[] = '<li><h3>&nbsp;' . $this->getZoneIcon($zone['zone_status_class']) . ' ' . htmlspecialchars($zone['zone_name']) . '</h3></li>';
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
								$onClickCode = 'TYPO3BackendCloudflareMenu.toggleDevelopmentMode(\'' . $zone['zone_name'] . '\', ' . $active . ');';
								$out[] = '<li class="divider"><a href="#" onclick="' . htmlspecialchars($onClickCode) . '">' . $languageService->getLL('toggle_development', TRUE) . '</a></li>';
							} else {
								$out[] = '<li class="divider">' . $languageService->getLL('zone_inactive', TRUE) . '</li>';
							}
						}
					}
				}
			} catch (\RuntimeException $e) {
				// Nothing to do
			}
		} else {
			$out[] = '<li>' . $languageService->getLL('no_domains') . '</li>';
		}

		return array('html' => implode('', $out));
	}

	/**
	 * Toggle the development mode.
	 *
	 * @param $parameter
	 * @return array
	 * @throws \RuntimeException
	 */
	public function toggleDevelopmentMode($parameter) {
		if (!$this->getBackendUser()->isAdmin()) {
			throw new \RuntimeException('Unauthorized call', 1366652080);
		}

		/** @var $cloudflareService \Causal\Cloudflare\Services\CloudflareService */
		$cloudflareService = GeneralUtility::makeInstance('Causal\\Cloudflare\\Services\\CloudflareService', $this->config);

		try {
			$ret = $cloudflareService->send(array(
				'a' => 'devmode',
				'z' => $parameter->zone,
				'v' => $parameter->active,
			));
		} catch (\RuntimeException $e) {
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
		$languageService = $this->getLanguageService();
		switch ($status) {
			case 'status-active':
				$icon = IconUtility::getSpriteIcon('extensions-cloudflare-online', array('title' => $languageService->getLL('zone_active')));
				break;
			case 'status-dev-mode':
				$icon = IconUtility::getSpriteIcon('extensions-cloudflare-direct', array('title' => $languageService->getLL('zone_development')));
				break;
			case 'status-deactivated':
			default:
				$icon = IconUtility::getSpriteIcon('extensions-cloudflare-offline', array('title' => $languageService->getLL('zone_inactive')));
				break;
		}
		return $icon;
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
