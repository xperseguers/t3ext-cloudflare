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
 * Helper methods for t3lib_div.
 *
 * @category    Hooks
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Tx_Cloudflare_Hooks_Div implements t3lib_Singleton {

	/** @var array */
	protected $config;

	/** @var boolean */
	protected $bypass = FALSE;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cloudflare']);
		if (!is_array($this->config)) {
			$this->config = array();
		}
	}

	/**
	 * Post-processes the value of t3lib_div::getIndpEnv().
	 *
	 * @param array $parameters
	 * @return void
	 */
	public function postProcessGetIndpEnv(array &$parameters) {
		if ($this->bypass) {
			return;
		}

		switch ($parameters['getEnvName']) {
			case 'REMOTE_ADDR':
				if ($this->isProxied($parameters['retVal']) && isset($this->config['enableOriginatingIPs']) && $this->config['enableOriginatingIPs'] == 1) {
					if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
						$parameters['retVal'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
					}
				}
				break;
			case 'TYPO3_SSL':
				$this->bypass = TRUE;
				$remoteAddr = t3lib_div::getIndpEnv('REMOTE_ADDR');
				$this->bypass = FALSE;

				if ($this->isProxied($remoteAddr)) {
						// Flexible-SSL support
					if (isset($_SERVER['HTTP_CF_VISITOR'])) {
						$cloudflareVisitor = json_decode($_SERVER['HTTP_CF_VISITOR'], TRUE);
						if ($cloudflareVisitor['scheme'] === 'https') {
							$parameters['retVal'] = 'https';
						}
					}
				}
				break;
		}
	}

	/**
	 * Returns TRUE if website is behind a reverse-proxy.
	 *
	 * @param string $remoteIp
	 * @return boolean
	 */
	protected function isProxied($remoteIp) {
		// @see https://www.cloudflare.com/ips
		$whiteListIPv4s = array(
			'199.27.128.0/21',
			'173.245.48.0/20',
			'103.21.244.0/22',
			'103.22.200.0/22',
			'103.31.4.0/22',
			'141.101.64.0/18',
			'108.162.192.0/18',
			'190.93.240.0/20',
			'188.114.96.0/20',
			'197.234.240.0/22',
			'198.41.128.0/17',
			'162.158.0.0/15',
			'104.16.0.0/12',
		);
		$whiteListIPv6s = array(
			'2400:cb00::/32',
			'2606:4700::/32',
			'2803:f800::/32',
			'2405:b500::/32',
			'2405:8100::/32',
		);

		$isProxied = FALSE;
		if (isset($config['enableOriginatingIPs']) && $config['enableOriginatingIPs'] == 1) {
			if (t3lib_div::validIPv6($remoteIp)) {
				$isProxied |= t3lib_div::cmpIPv6($remoteIp, implode(',', $whiteListIPv6s));
			} else {
				$isProxied |= t3lib_div::cmpIPv4($remoteIp, implode(',', $whiteListIPv4s));
			}
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			// We take for granted that reverse-proxy is properly configured
			$isProxied = TRUE;
		}

		return $isProxied;
	}

}
