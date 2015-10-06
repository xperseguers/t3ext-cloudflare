<?php
defined('TYPO3_MODE') || die();

$icons = array(
    'cloudflare' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/cloudflare-16.png',
    'direct' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/direct-16.png',
    'offline' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/offline-16.png',
    'online' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/online-16.png',
);
\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, $_EXTKEY);

// Register our custom CSS
$GLOBALS['TBE_STYLES']['skins'][$_EXTKEY]['stylesheetDirectories']['visual'] = 'EXT:' . $_EXTKEY . '/Resources/Public/Css/visual/';

if (TYPO3_MODE === 'BE') {
    if (version_compare(TYPO3_version, '6.99.99', '<=')) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent(
            'TYPO3.Ajax.ExtDirect.CloudflareToolbarMenu',
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/ExtDirect/ToolbarMenu.php:Causal\\Cloudflare\\ExtDirect\\ToolbarMenu',
            null,
            'admin'
        );

        // Register AJAX calls
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('TxCloudflare::purge', 'Causal\\Cloudflare\\ExtDirect\\ToolbarMenu->purge');
    } else {
        // Register AJAX calls
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('TxCloudflare::renderMenu', 'Causal\\Cloudflare\\Backend\\ToolbarItems\\CloudflareToolbarItem->renderAjax');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('TxCloudflare::toggleDevelopmentMode', 'Causal\\Cloudflare\\Backend\\ToolbarItems\\CloudflareToolbarItem->toggleDevelopmentMode');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('TxCloudflare::purge', 'Causal\\Cloudflare\\Backend\\ToolbarItems\\CloudflareToolbarItem->purge');
    }

    // Create a module section "CloudFlare" before 'Admin Tools'
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txcloudflare', '', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Cloudflare/');
    $temp_TBE_MODULES = array();
    foreach ($GLOBALS['TBE_MODULES'] as $key => $val) {
        if ($key === 'tools') {
            $temp_TBE_MODULES['txcloudflare'] = '';
            $temp_TBE_MODULES[$key] = $val;
        } else {
            $temp_TBE_MODULES[$key] = $val;
        }
    }
    $GLOBALS['TBE_MODULES'] = $temp_TBE_MODULES;

    if (version_compare(TYPO3_version, '6.99.99', '<=')) {
        $moduleIcon = 'ext_icon.png';
    } else {
        $moduleIcon = 'Resources/Public/Icons/module-analytics.png';
    }
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Causal.' . $_EXTKEY,
        'txcloudflare',
        'analytics',
        '',
        array(
            'Dashboard' => 'analytics, ajaxAnalytics',
        ),
        array(
            'access' => 'user,group',
            'icon' => 'EXT:' . $_EXTKEY . '/' . $moduleIcon,
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_analytics.xlf',
        )
    );
}
