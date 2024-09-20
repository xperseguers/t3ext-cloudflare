<?php
defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    // Register our custom CSS
    $GLOBALS['TBE_STYLES']['skins'][$_EXTKEY]['stylesheetDirectories']['visual'] = 'EXT:' . $_EXTKEY . '/Resources/Public/Css/visual/';

    $typo3Version = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();
    if ($typo3Version < 12) {
        /** @var array $config */
        $config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get($_EXTKEY);
        $enableAnalyticsModule = (bool)($config['enableAnalyticsModule'] ?? true);

        // Disable analytics module until we have a proper implementation
        $enableAnalyticsModule = false;

        if ($enableAnalyticsModule) {
            // Create a module section "Cloudflare" before 'Admin Tools'
            $moduleConfiguration = [
                'access' => 'user,group',
                'name' => 'txcloudflare',
                'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-cloudflare.png',
                'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_cloudflare.xlf',
            ];

            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
                'txcloudflare', // main module key
                '',             // submodule key
                '',             // position
                '',
                $moduleConfiguration
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
                    \Causal\Cloudflare\Controller\DashboardController::class => 'analytics, ajaxAnalytics',
                ],
                [
                    'access' => 'user,group',
                    'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-analytics.png',
                    'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_analytics.xlf',
                ]
            );
        }
    }
})('cloudflare');
