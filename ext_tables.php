<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);
if (!is_array($config)) {
	$config = array();
}

if (isset($config['enableOriginatingIPs']) && $config['enableOriginatingIPs'] == 1) {
	if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
		$remoteIp = t3lib_div::getIndpEnv('REMOTE_ADDR');

		// @see http://support.cloudflare.com/kb/troubleshooting/how-do-i-whitelist-cloudflares-ip-addresses-in-htacess
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

		$overrideRemoteAddr = FALSE;
		if (t3lib_div::validIPv6($remoteIp)) {
			$overrideRemoteAddr |= t3lib_div::cmpIPv6($remoteIp, implode(',', $whiteListIPv6s));
		} else {
			$overrideRemoteAddr |= t3lib_div::cmpIPv4($remoteIp, implode(',', $whiteListIPv4s));
		}

		if ($overrideRemoteAddr) {
			$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
	}
}

?>