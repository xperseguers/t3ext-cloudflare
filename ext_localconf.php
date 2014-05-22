<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);
if (!is_array($config)) {
	$config = array();
}

// Register additional clear_cache method
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/TCEmain.php:Tx_Cloudflare_Hooks_TCEmain->clear_cacheCmd';

$versionParts = explode('.', TYPO3_version);
$version = intval((int) $versionParts[0] . str_pad((int) $versionParts[1], 3, '0', STR_PAD_LEFT) . str_pad((int) $versionParts[2], 3, '0', STR_PAD_LEFT));

// Hook has been reverted in master:
// @see https://review.typo3.org/#/c/16127/
if (FALSE && $version >= 6000000) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['postIndpEnvValue'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/t3lib_div.php:Tx_Cloudflare_Hooks_Div->postProcessGetIndpEnv';
} else {
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
				$_SERVER['SERVER_PORT'] = '443';
			}
		}

			// Cache SSL content
		if (isset($config['cacheSslContent']) && $config['cacheSslContent'] == 1) {
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['nc_staticfilecache/class.tx_ncstaticfilecache.php']['createFile_initializeVariables'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/tx_ncstaticfilecache.php:Tx_Cloudflare_Hooks_NcStaticfilecache->createFile_initializeVariables';
		}

		if (isset($config['enableOriginatingIPs']) && $config['enableOriginatingIPs'] == 1) {
			if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
				$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
			}
		}
	}
}

if (TYPO3_MODE === 'BE' && !empty($config['apiKey'])) {
	$cloudflareToolbarItemClassPath = t3lib_extMgm::extPath($_EXTKEY, 'Classes/Hooks/TYPO3backend_Cloudflare.php');
	$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = $cloudflareToolbarItemClassPath;

	if ($config['domains'] !== '') {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['clearCloudflareCache'] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/TYPO3backend.php:&Tx_Cloudflare_Hooks_TYPO3backend';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['cloudflare::clearCache'] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/TCEmain.php:Tx_Cloudflare_Hooks_TCEmain->clearCache';
	}
}
