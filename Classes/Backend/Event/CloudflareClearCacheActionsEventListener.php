<?php

namespace Causal\Cloudflare\Backend\Event;

use Causal\Cloudflare\ExtensionManager\Configuration;
use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class CloudflareClearCacheActionsEventListener
{
    /** @var array */
    private $config;

    /** @var \TYPO3\CMS\Backend\Routing\UriBuilder  */
    private $uriBuilder;

    /**
     * @param \TYPO3\CMS\Core\Configuration\ExtensionConfiguration $extensionConfiguration
     * @param \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder
     */
    public function __construct(ExtensionConfiguration $extensionConfiguration, UriBuilder $uriBuilder)
    {
        $this->config = $extensionConfiguration->get(Configuration::KEY);
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * @param \TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent $event
     * @return void
     */
    public function __invoke(ModifyClearCacheActionsEvent $event): void
    {
        if (!$this->canClearCache()) {
            return;
        }

        $event->addCacheAction([
            'id' => 'cloudflare',
            'title' => 'LLL:EXT:cloudflare/Resources/Private/Language/locallang.xlf:clear_cache',
            'description' => 'LLL:EXT:cloudflare/Resources/Private/Language/locallang.xlf:clear_cache.description',
            'href' => $this->uriBuilder->buildUriFromRoute('ajax_cloudflare_purge'),
            'iconIdentifier' => 'actions-system-cache-clear-impact-low',
        ]);
        $event->addCacheActionIdentifier('cloudflare');
    }

    /**
     * @return bool
     */
    private function canClearCache(): bool
    {
        if (empty($this->config['apiKey']) || empty($this->config['domains'])) {
            return false;
        }

        $backendUser = $this->getBackendUser();
        if ($backendUser === null) {
            return false;
        }

        $canClearAllCache = (bool)($backendUser->getTSConfig()['options.']['clearCache.']['all'] ?? false);
        $canClearCloudflareCache = (bool)($backendUser->getTSConfig()['options.']['clearCache.']['cloudflare'] ?? false);

        // If user has no ability to clear the cache
        return $backendUser->isAdmin() || $canClearAllCache || $canClearCloudflareCache;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|null
     */
    private function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
