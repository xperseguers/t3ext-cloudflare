<?php

namespace Causal\Cloudflare\Factory;

use Causal\Cloudflare\ExtensionManager\Configuration;
use Causal\Cloudflare\Services\CloudflareService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CloudflareServiceFactory
{
    /**
     * @param \TYPO3\CMS\Core\Configuration\ExtensionConfiguration $extensionConfiguration
     * @return \Causal\Cloudflare\Services\CloudflareService
     */
    public function __invoke(ExtensionConfiguration $extensionConfiguration): CloudflareService
    {
        return GeneralUtility::makeInstance(
            CloudflareService::class,
            $extensionConfiguration->get(Configuration::KEY)
        );
    }
}
