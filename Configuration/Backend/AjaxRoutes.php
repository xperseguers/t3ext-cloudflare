<?php
use Causal\Cloudflare\Backend\ToolbarItems\CloudflareToolbarItem;
use Causal\Cloudflare\Controller\DashboardModuleController;

/**
 * Definitions for AJAX routes provided by EXT:cloudflare
 */
return [
    'cloudflare_rendermenu' => [
        'path' => '/menu/cloudflare/render',
        'target' => CloudflareToolbarItem::class . '::renderAjax'
    ],
    'cloudflare_toggledev' => [
        'path' => 'menu/cloudflare/development/toggle',
        'target' => CloudflareToolbarItem::class . '::toggleDevelopmentMode'
    ],
    'cloudflare_purge' => [
        'path' => 'menu/cloudflare/purge',
        'target' => CloudflareToolbarItem::class . '::purge'
    ],
    'cloudflare_fetchanalytics' => [
        'path' => 'menu/cloudflare/analytics/fetch',
        'target' => DashboardModuleController::class . '::fetchAnalytics'
    ],
];
