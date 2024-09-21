<?php

use Causal\Cloudflare\Controller\DashboardModuleController;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cloudflare');
$enableAnalyticsModule = (bool)($config['enableAnalyticsModule'] ?? true);

$modules = [];
if ($enableAnalyticsModule) {
    $modules = [
        'txcloudflare' => [
            'position' => ['before' => 'tools'],
            'labels' => 'LLL:EXT:cloudflare/Resources/Private/Language/locallang_mod_cloudflare.xlf',
            'iconIdentifier' => 'cloudflare-module',
            'navigationComponent' => '',
        ],
        'txcloudflare_analytics' => [
            'parent' => 'txcloudflare',
            'position' => ['top'],
            'access' => 'user',
            'workspaces' => 'live',
            'path' => '/module/cloudflare/analytics',
            'navigationComponent' => '',
            'labels' => 'LLL:EXT:cloudflare/Resources/Private/Language/locallang_mod_analytics.xlf',
            'extensionName' => 'Cloudflare',
            'iconIdentifier' => 'cloudflare-module-analytics',
            'controllerActions' => [
                DashboardModuleController::class => 'analytics, ajaxAnalytics',
            ],
        ],
    ];
}

return $modules;
