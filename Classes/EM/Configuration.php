<?php
/**
 * Created by JetBrains PhpStorm.
 * User: xavier
 * Date: 23.05.12
 * Time: 11:57
 * To change this template use File | Settings | File Templates.
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
	 * @param t3lib_tsStyleConfig $pObj
	 * @return string
	 */
	public function getDomains(array $params, t3lib_tsStyleConfig $pObj) {
		$this->overrideConfiguration($pObj);
		$domains = array();
		$out = array();

		/** @var $cloudflare Tx_Cloudflare_Services_Cloudflare */
		$cloudflare = t3lib_div::makeInstance('Tx_Cloudflare_Services_Cloudflare');

		try {
			$ret = $cloudflare->send(array('a' => 'zone_load_multi'));
			if ($ret['result'] === 'success') {
				foreach ($ret['response']['zones']['objs'] as $zone) {
					$domains[] = $zone['zone_name'];
				}
			}
		} catch (RuntimeException $e) {

		}

		if (count($domains) == 0) {
			$host = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
			$hostParts = explode('.', $host);
			$domains[] = count($hostParts) > 1 ? implode('.', array_slice($hostParts, -2)) : $host;
		}

		$i = 0;
		$selectedDomains = t3lib_div::trimExplode(',', $params['fieldValue'], TRUE);
		foreach ($domains as $domain) {
			$out[] = '<div style="margin-bottom:1ex">';
			$checked = in_array($domain, $selectedDomains) ? ' checked="checked"' : '';
			$out[] = '<input type="checkbox" id="cloudflare_domain_' . $i . '" value="' . $domain . '"' . $checked . ' onclick="toggleCloudflareDomains();" />';
			$out[] = '<label for="cloudflare_domain_' . $i . '">' . $domain . '</label>';
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

?>