<?php
declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\Cloudflare\EventListener;

use Causal\Cloudflare\Services\CloudflareService;
use Causal\Cloudflare\Traits\ConfiguredDomainsTrait;
use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClearCacheEventListener
{
    use ConfiguredDomainsTrait;

    public function __construct(
        CloudflareService $cloudflareService
    )
    {
        $this->cloudflareService = $cloudflareService;
    }

    public function __invoke(ModifyClearCacheActionsEvent $event): void
    {
        $config = $this->cloudflareService->getConfiguration();

        $domains = $this->getDomains();
        if (empty($config['apiKey']) || empty($domains)) {
            return;
        }

        $backendUser = $this->getBackendUser();

        $canClearAllCache = (bool)($backendUser->getTSConfig()['options.']['clearCache.']['all'] ?? false);
        $canClearCloudflareCache = (bool)($backendUser->getTSConfig()['options.']['clearCache.']['cloudflare'] ?? false);

        if ($backendUser->isAdmin() || $canClearAllCache || $canClearCloudflareCache) {
            $cacheActions = $event->getCacheActions();
            $cacheActionIdentifiers = $event->getCacheActionIdentifiers();

            /** @var UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $ajaxRoute = (string)$uriBuilder->buildUriFromRoute('ajax_cloudflare_purge');
            $clearCloudflare = [
                'id' => 'cloudflare',
                'title' => 'LLL:EXT:cloudflare/Resources/Private/Language/locallang.xlf:clear_cache',
                'description' => 'LLL:EXT:cloudflare/Resources/Private/Language/locallang.xlf:clear_cache.description',
                'href' => $ajaxRoute,
                'severity' => 'success',
                'iconIdentifier' => 'actions-system-cache-clear-impact-low',
            ];

            $posClearAll = array_search('all', $cacheActionIdentifiers);
            if ($posClearAll !== false) {
                // Insert Cloudflare cache clear action before 'all'
                $cacheActions = array_merge(
                    array_slice($cacheActions, 0, $posClearAll),
                    [$clearCloudflare],
                    array_slice($cacheActions, $posClearAll)
                );
                $cacheActionIdentifiers = array_merge(
                    array_slice($cacheActionIdentifiers, 0, $posClearAll),
                    ['cloudflare'],
                    array_slice($cacheActionIdentifiers, $posClearAll)
                );
            } else {
                $cacheActions[] = $clearCloudflare;
                $cacheActionIdentifiers[] = 'cloudflare';
            }

            $event->setCacheActions($cacheActions);
            $event->setCacheActionIdentifiers($cacheActionIdentifiers);
        }
    }

    /**
     * Returns the current Backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the LanguageService.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
