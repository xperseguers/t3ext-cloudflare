<?php
defined('TYPO3_MODE') || die();

(static function (string $_EXTKEY) {
    // Register additional sprite icons
    /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'extensions-' . $_EXTKEY . '-cloudflare-icon',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/cloudflare-icon.svg']
    );
    $iconRegistry->registerIcon(
        'extensions-' . $_EXTKEY . '-cloudflare',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        ['source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/cloudflare-16.png']
    );
    $iconRegistry->registerIcon(
        'extensions-' . $_EXTKEY . '-direct',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        ['source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/direct-16.png']
    );
    $iconRegistry->registerIcon(
        'extensions-' . $_EXTKEY . '-offline',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        ['source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/offline-16.png']
    );
    $iconRegistry->registerIcon(
        'extensions-' . $_EXTKEY . '-online',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        ['source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/online-16.png']
    );
    $iconRegistry->registerIcon(
        'extensions-' . $_EXTKEY . '-module',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-cloudflare.svg']
    );
    unset($iconRegistry);

    /** @var array $config */
    $config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
        ->get($_EXTKEY);

    // Register additional clear_cache method
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = \Causal\Cloudflare\Hooks\TCEmain::class . '->clear_cacheCmd';

    // Do not use GeneralUtility::getIndpEnv('REMOTE_ADDR'), as the remote address need to be changed first in case
    // option 'enableOriginatingIPs' is activated. Otherwise, the proxied IP address will be cached and used
    // (e.g. in backend authentication process).
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;

    // @see https://www.cloudflare.com/ips
    // Last updated: April 8, 2021
    $whiteListIPv4s = [
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
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
    ];
    $whiteListIPv6s = [
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    $isProxied = false;
    if (filter_var($config['enableOriginatingIPs'] ?? false, FILTER_VALIDATE_BOOL)) {
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

        if (filter_var($config['enableOriginatingIPs'] ?? false, FILTER_VALIDATE_BOOL)) {
            if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
        }
    }

    if (filter_var($config['enablePurgeByTags'] ?? false, FILTER_VALIDATE_BOOL)) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache'][$_EXTKEY] =
            \Causal\Cloudflare\Hooks\ContentProcessor::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][$_EXTKEY] =
            \Causal\Cloudflare\Hooks\ContentProcessor::class . '->sendPageCacheTag';
    }

    if (!empty($config['apiKey'])) {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][] = \Causal\Cloudflare\Backend\ToolbarItems\CloudflareToolbarItem::class;
    }
})('cloudflare');
