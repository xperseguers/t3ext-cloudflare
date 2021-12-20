<?php
defined('TYPO3_MODE') || die();

(static function (string $_EXTKEY) {
    // Register our custom CSS
    $GLOBALS['TBE_STYLES']['skins'][$_EXTKEY]['stylesheetDirectories']['visual'] = 'EXT:' . $_EXTKEY . '/Resources/Public/Css/visual/';

    /** @var array $config */
    $config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
        ->get($_EXTKEY);

    if (filter_var($config['enableAnalyticsModule'] ?? false, FILTER_VALIDATE_BOOL)) {
        // Create a module section "Cloudflare" before 'Admin Tools'
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txcloudflare', '', '', '', [
            'access' => 'user,group',
            'name' => 'txcloudflare',
            'iconIdentifier' => 'extensions-cloudflare-module',
            //'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-cloudflare.png',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_cloudflare.xlf',
        ]);
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
            $_EXTKEY,
            'txcloudflare',
            'analytics',
            '',
            [\Causal\Cloudflare\Controller\DashboardController::class => 'analytics, ajaxAnalytics'],
            [
                'access' => 'user,group',
                'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-analytics.png',
                'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_analytics.xlf',
            ]
        );
    }
})('cloudflare');
