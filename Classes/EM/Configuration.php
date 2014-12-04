<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013-2014 Xavier Perseguers <xavier@causal.ch>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configuration class for the TYPO3 Extension Manager.
 *
 * @category    Extension Manager
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Tx_Cloudflare_EM_Configuration {

	/** @var string */
	protected $extKey = 'cloudflare';

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey];
		$this->config = $config ? unserialize($config) : array();
	}

	/**
	 * Returns an Extension Manager field for selecting domains.
	 *
	 * @param array $params
	 * @param t3lib_tsStyleConfig|\TYPO3\CMS\Extensionmanager\ViewHelpers\Form\TypoScriptConstantsViewHelper $pObj
	 * @return string
	 */
	public function getDomains(array $params, $pObj) {
		if (version_compare(TYPO3_version, '6.0.0', '<')) {
			// Prior to TYPO3 6.0 the global configuration does not yet reflect changes that were made
			$this->overrideConfiguration($pObj);
		}
		$domains = array();
		$out = array();

		/** @var $cloudflare Tx_Cloudflare_Services_Cloudflare */
		$cloudflare = GeneralUtility::makeInstance('Tx_Cloudflare_Services_Cloudflare', $this->config);

		try {
			$ret = $cloudflare->send(array('a' => 'zone_load_multi'));
			if ($ret['result'] === 'success') {
				foreach ($ret['response']['zones']['objs'] as $zone) {
					$domains[] = $zone['zone_name'];
				}
			}
		} catch (RuntimeException $e) {
			// Nothing to do
		}

		if (count($domains) == 0) {
			$host = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
			$hostParts = explode('.', $host);
			$domains[] = count($hostParts) > 1 ? implode('.', array_slice($hostParts, -2)) : $host;
		}

		$i = 0;
		$selectedDomains = GeneralUtility::trimExplode(',', $params['fieldValue'], TRUE);
		foreach ($domains as $domain) {
			$out[] = '<div>';
			$checked = in_array($domain, $selectedDomains) ? ' checked="checked"' : '';
			$out[] = '<input type="checkbox" id="cloudflare_domain_' . $i . '" value="' . $domain . '"' . $checked . ' onclick="toggleCloudflareDomains();" />';
			$out[] = '<label for="cloudflare_domain_' . $i . '" style="display:inline-block">' . $domain . '</label>';
			$out[] = '</div>';
			$i++;
		}

		$fieldId = str_replace(array('[', ']'), '_', $params['fieldName']);
		$out[] = '<script type="text/javascript">';
		$out[] = <<<JS

function toggleCloudflareDomains() {
	var domains = new Array();
	for (var i = 0; i < {$i}; i++) {
		var e = document.getElementById("cloudflare_domain_" + i);
		if (e.checked) {
			domains.push(e.value);
		}
	}
	document.getElementById("{$fieldId}").value = domains.join(',');
}

JS;
		$out[] = '</script>';
		$out[] = '<input type="hidden" id="' . $fieldId . '" name="' . $params['fieldName'] .  '" value="' . $params['fieldValue'] . '" />';

		return implode(LF, $out);
	}

	/**
	 * Overrides local configuration.
	 *
	 * @param t3lib_tsStyleConfig $pObj
	 * @return void
	 */
	protected function overrideConfiguration(t3lib_tsStyleConfig $pObj) {
		foreach ($pObj->ext_incomingValues as $incomingValues) {
			list($key, $value) = explode('=', $incomingValues, 2);
			$this->config[$key] = $value;
		}
	}

}
