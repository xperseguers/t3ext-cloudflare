<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);
if (!is_array($config)) {
	$config = array();
}

$remoteIp = t3lib_div::getIndpEnv('REMOTE_ADDR');

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

if ($isProxied) {
		// Flexible-SSL support
	if (isset($_SERVER['HTTP_CF_VISITOR'])) {
		$cloudflareVisitor = json_decode($_SERVER['HTTP_CF_VISITOR'], TRUE);
		if ($cloudflareVisitor['scheme'] === 'https') {
			$_SERVER['HTTPS'] = 'on';
			$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
		}
	}

	if (isset($config['enableOriginatingIPs']) && $config['enableOriginatingIPs'] == 1) {
		if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
	}
}

$icons = array(
	'cloudflare' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Icons/cloudflare-16.png',
	'direct' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Icons/direct-16.png',
	'offline' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Icons/offline-16.png',
	'online' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Icons/online-16.png',
);
t3lib_SpriteManager::addSingleIcons($icons, $_EXTKEY);

// Register our custom CSS
$GLOBALS['TBE_STYLES']['skins'][$_EXTKEY]['stylesheetDirectories']['visual'] = 'EXT:' . $_EXTKEY . '/Resources/Public/Css/visual/';

t3lib_extMgm::registerExtDirectComponent(
	'TYPO3.Ajax.ExtDirect.CloudflareToolbarMenu',
	t3lib_extMgm::extPath($_EXTKEY) . 'Classes/ExtDirect/ToolbarMenu.php:Tx_Cloudflare_ExtDirect_ToolbarMenu',
	NULL,
	'admin'
);

?>