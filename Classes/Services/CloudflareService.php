<?php
namespace Causal\Cloudflare\Services;

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

/**
 * Service to talk to CloudFlare.
 *
 * @category    Services
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class CloudflareService implements \TYPO3\CMS\Core\SingletonInterface {

	/** @var array */
	protected $config;

	/** @var string */
	protected $apiEndpoint = 'https://www.cloudflare.com/api_json.html';

	/**
	 * Default constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array()) {
		$this->config = $config;

		if (!empty($this->config['apiEndpoint'])) {
			$this->apiEndpoint = $this->config['apiEndpoint'];
		}

	}

	/**
	 * Sends data to CloudFlare.
	 *
	 * @param array $additionalParams
	 * @return array
	 * @throws \RuntimeException
	 */
	public function send(array $additionalParams) {
		if (!trim($this->config['apiKey'])) {
			throw new \RuntimeException('Cannot clear cache on CloudFlare: Invalid apiKey for EXT:cloudflare', 1337770232);
		} elseif (!\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail(trim($this->config['email']))) {
			throw new \RuntimeException('Cannot clear cache on CloudFlare: Invalid email for EXT:cloudflare', 1337770383);
		}

		$params = array(
			'tkn'   => trim($this->config['apiKey']),
			'email' => trim($this->config['email']),
		);
		$allParams = array_merge($params, $additionalParams);

		return $this->POST($this->apiEndpoint, $allParams);
	}

	/**
	 * This methods POSTs data to CloudFlare.
	 *
	 * @param array $data
	 * @return array JSON payload returned by CloudFlare
	 * @throws \RuntimeException
	 */
	protected function POST($url, array $data) {
		if (TRUE || $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'] == '1') {
			if (!function_exists('curl_init') || !($ch = curl_init())) {
				throw new \RuntimeException('cURL cannot be used', 1337673614);
			}

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, max(0, intval($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlTimeout'])));
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']) {
				curl_setopt($ch, CURLOPT_PROXY, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']);

				if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel']) {
					curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel']);
				}
				if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']) {
					curl_setopt($ch, CURLOPT_PROXYUSERPWD, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']);
				}
			}

			if (!($result = curl_exec($ch))) {
				trigger_error(curl_errno($ch));
			}
			curl_close($ch);
			return json_decode($result, TRUE);
		} else {
			// TODO with fsockopen()
		}
	}

}
