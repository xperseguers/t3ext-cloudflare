<?php
defined('TYPO3_MODE') || die();

(static function (string $_EXTKEY) {
    /** @var array $config */
    $config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get($_EXTKEY);

    // Register additional clear_cache method
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = \Causal\Cloudflare\Hooks\TCEmain::class . '->clear_cacheCmd';

    // Do not use GeneralUtility::getIndpEnv('REMOTE_ADDR'), as the remote address need to be changed first in case
    // option 'enableOriginatingIPs' is activated. Otherwise, the proxied IP address will be cached and used
    // (e.g. in backend authentication process).
    $remoteIp = $_SERVER['REMOTE_ADDR'];

    // @see https://www.cloudflare.com/ips
    $whiteListIPv4s = [
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
    ];
    $whiteListIPv6s = [
        '2400:cb00::/32',
        '2405:8100::/32',
        '2405:b500::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2c0f:f248::/32',
        '2a06:98c0::/29',
    ];

    $isProxied = false;
    if (isset($config['enableOriginatingIPs']) && (bool)$config['enableOriginatingIPs']) {
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

        if (isset($config['enableOriginatingIPs']) && (bool)$config['enableOriginatingIPs']) {
            if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
        }
    }

    if (isset($config['enablePurgeByTags']) && (bool)$config['enablePurgeByTags']) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache'][$_EXTKEY] =
            \Causal\Cloudflare\Hooks\ContentProcessor::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][$_EXTKEY] =
            \Causal\Cloudflare\Hooks\ContentProcessor::class . '->sendPageCacheTag';
    }

    if (TYPO3_MODE === 'BE' && !empty($config['apiKey'])) {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = \Causal\Cloudflare\Backend\ToolbarItems\CloudflareToolbarItem::class;

        if ($config['domains'] !== '') {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['clearCloudflareCache'] = \Causal\Cloudflare\Hooks\TYPO3backend::class;
        }
    }
})('cloudflare');
