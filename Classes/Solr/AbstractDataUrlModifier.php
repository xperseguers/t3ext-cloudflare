<?php

namespace Causal\Cloudflare\Solr;

use Causal\Cloudflare\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractDataUrlModifier
 * @package Causal\Cloudflare\Solr
 */
abstract class AbstractDataUrlModifier
{
    const GET_PARAM_NAME = '_cf';

    /**
     * Cloudflare domains
     *
     * @var array
     */
    protected static $cloudFlareDomainsCache = [];

    /**
     * Add time parameter to request
     *
     * @param string $pageUrl
     * @param array $urlData
     * @return string
     */
    public function modifyDataUrl($pageUrl, array $urlData)
    {
        list($microsec, $sec) = explode(' ', microtime());

        if ($this->isCloudflareDomain($urlData['host'])) {
            $pageUrl = rtrim($pageUrl, '&') . '&' . self::GET_PARAM_NAME . '=' . $sec . $microsec;
        }

        return $pageUrl;
    }

    /**
     * Check if domain is CDN domain
     *
     * @param string $domain
     * @return bool
     */
    protected function isCloudflareDomain($domain)
    {
        if (array_key_exists($domain, self::$cloudFlareDomainsCache)) {
            return self::$cloudFlareDomainsCache[$domain];
        }

        $isCloudFlareDomain = false;

        $settings = ConfigurationUtility::getExtensionConfiguration();
        $domains = GeneralUtility::trimExplode(',', $settings['domains'], true);

        $domainParts = explode('.', $domain);
        $size = count($domainParts);

        if ($size > 1) {
            $zoneName = $domainParts[$size - 2] . '.' . $domainParts[$size - 1];

            foreach ($domains as $cdnDomain) {
                list(, $z) = explode('|', $cdnDomain, 2);
                if ($z === $zoneName) {
                    $isCloudFlareDomain = true;
                    break;
                }
            }
        }

        self::$cloudFlareDomainsCache[$domain] = $isCloudFlareDomain;

        return $isCloudFlareDomain;
    }
}
