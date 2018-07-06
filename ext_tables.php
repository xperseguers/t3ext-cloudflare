<?php
defined('TYPO3_MODE') || die();

$boot = function ($_EXTKEY) {
    // Register additional sprite icons
    $icons = [
        'cloudflare' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/cloudflare-16.png',
        'direct' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/direct-16.png',
        'offline' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/offline-16.png',
        'online' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/online-16.png',
        'module' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-cloudflare.png',
    ];

    /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    foreach ($icons as $key => $icon) {
        $iconRegistry->registerIcon('extensions-' . $_EXTKEY . '-' . $key,
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            [
                'source' => $icon
            ]
        );
    }
    unset($iconRegistry);

    // Register our custom CSS
    $GLOBALS['TBE_STYLES']['skins'][$_EXTKEY]['stylesheetDirectories']['visual'] = 'EXT:' . $_EXTKEY . '/Resources/Public/Css/visual/';

    if (TYPO3_MODE === 'BE') {
        // Register AJAX calls
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
            'TxCloudflare::renderMenu',
            \Causal\Cloudflare\Backend\ToolbarItems\CloudflareToolbarItem::class . '->renderAjax'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
            'TxCloudflare::toggleDevelopmentMode',
            \Causal\Cloudflare\Backend\ToolbarItems\CloudflareToolbarItem::class . '->toggleDevelopmentMode'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
            'TxCloudflare::purge',
            \Causal\Cloudflare\Backend\ToolbarItems\CloudflareToolbarItem::class . '->purge'
        );

        // Create a module section "Cloudflare" before 'Admin Tools'
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
            'txcloudflare', // main module key
            '',             // submodule key
            '',             // position
            '',
            [
                'access' => 'user,group',
                'name' => 'txcloudflare',
                'labels' => [
                    'tabs_images' => [
                        'tab' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-cloudflare.png',
                    ],
                    'll_ref' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_cloudflare.xlf',
                ],
            ]
        );
        $temp_TBE_MODULES = [];
        foreach ($GLOBALS['TBE_MODULES'] as $key => $val) {
            if ($key === 'tools') {
                $temp_TBE_MODULES['txcloudflare'] = '';
                $temp_TBE_MODULES[$key] = $val;
            } else {
                $temp_TBE_MODULES[$key] = $val;
            }
        }
        $GLOBALS['TBE_MODULES'] = $temp_TBE_MODULES;

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'Causal.' . $_EXTKEY,
            'txcloudflare',
            'analytics',
            '',
            [
                'Dashboard' => 'analytics, ajaxAnalytics',
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-analytics.png',
                'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_analytics.xlf',
            ]
        );
    }
};

$boot($_EXTKEY);
unset($boot);
