<?php
defined('TYPO3_MODE') || die();

$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);
if (!is_array($config)) {
    $config = array();
}

// Register additional clear_cache method
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/TCEmain.php:Causal\\Cloudflare\\Hooks\\TCEmain->clear_cacheCmd';

$remoteIp = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR');

// @see https://www.cloudflare.com/ips
$whiteListIPv4s = array(
    '103.21.244.0/22',
    '103.22.200.0/22',
    '103.31.4.0/22',
    '104.16.0.0/12',
    '108.162.192.0/18',
    '131.0.72.0/22',
    '141.101.64.0/18',
    '162.158.0.0/15',
    '172.64.0.0/13',
    '173.245.48.0/20',
    '188.114.96.0/20',
    '190.93.240.0/20',
    '197.234.240.0/22',
    '198.41.128.0/17',
    '199.27.128.0/21',
);
$whiteListIPv6s = array(
    '2400:cb00::/32',
    '2405:8100::/32',
    '2405:b500::/32',
    '2606:4700::/32',
    '2803:f800::/32',
    '2c0f:f248::/32',
    '2a06:98c0::/29',
);

$isProxied = false;
if (isset($config['enableOriginatingIPs']) && $config['enableOriginatingIPs'] == 1) {
    if (\TYPO3\CMS\Core\Utility\GeneralUtility::validIPv6($remoteIp)) {
        $isProxied |= \TYPO3\CMS\Core\Utility\GeneralUtility::cmpIPv6($remoteIp, implode(',', $whiteListIPv6s));
    } else {
        $isProxied |= \TYPO3\CMS\Core\Utility\GeneralUtility::cmpIPv4($remoteIp, implode(',', $whiteListIPv4s));
    }
} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    // We take for granted that reverse-proxy is properly configured
    $isProxied = true;
}

if ($isProxied) {
    // Flexible-SSL support
    if (isset($_SERVER['HTTP_CF_VISITOR'])) {
        $cloudflareVisitor = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
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

if (TYPO3_MODE === 'BE' && !empty($config['apiKey'])) {
    if (version_compare(TYPO3_version, '6.99.99', '<=')) {
        $cloudflareToolbarItemClassPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/Hooks/TYPO3backend_Cloudflare.php');
        $GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = $cloudflareToolbarItemClassPath;
    } else {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = 'Causal\\Cloudflare\\Backend\\ToolbarItems\\CloudflareToolbarItem';
    }

    if ($config['domains'] !== '') {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['clearCloudflareCache'] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/TYPO3backend.php:Causal\\Cloudflare\\Hooks\\TYPO3backend';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['cloudflare::clearCache'] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/TCEmain.php:Causal\\Cloudflare\\Hooks\\TCEmain->clearCache';
    }
}
