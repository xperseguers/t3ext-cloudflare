<?php
/**
 * Created by JetBrains PhpStorm.
 * User: xavier
 * Date: 20.08.12
 * Time: 19:42
 * To change this template use File | Settings | File Templates.
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
			'204.93.240.0/24',
			'204.93.177.0/24',
			'199.27.128.0/21',
			'173.245.48.0/20',
			'103.22.200.0/22',
			'141.101.64.0/18',
			'108.162.192.0/18',
			'190.93.240.0/20',
		);
		$whiteListIPv6s = array(
			'2400:cb00::/32',
			'2606:4700::/32',
			'2803:f800::/32',
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

?>