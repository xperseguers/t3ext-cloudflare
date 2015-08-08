<?php
namespace Causal\Cloudflare\ExtensionManager;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configuration class for the TYPO3 Extension Manager.
 *
 * @category    Extension Manager
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Configuration
{

    /** @var string */
    protected $extKey = 'cloudflare';

    /**
     * Default constructor.
     */
    public function __construct()
    {
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey];
        $this->config = $config ? unserialize($config) : array();
    }

    /**
     * Returns an Extension Manager field for selecting domains.
     *
     * @param array $params
     * @param \TYPO3\CMS\Extensionmanager\ViewHelpers\Form\TypoScriptConstantsViewHelper $pObj
     * @return string
     */
    public function getDomains(array $params, $pObj)
    {
        $domains = array();
        $out = array();

        /** @var $cloudflareService \Causal\Cloudflare\Services\CloudflareService */
        $cloudflareService = GeneralUtility::makeInstance('Causal\\Cloudflare\\Services\\CloudflareService', $this->config);

        try {
            $ret = $cloudflareService->send(array('a' => 'zone_load_multi'));
            if ($ret['result'] === 'success') {
                foreach ($ret['response']['zones']['objs'] as $zone) {
                    $domains[] = $zone['zone_name'];
                }
            }
        } catch (\RuntimeException $e) {
            // Nothing to do
        }

        if (count($domains) == 0) {
            $host = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
            $hostParts = explode('.', $host);
            $domains[] = count($hostParts) > 1 ? implode('.', array_slice($hostParts, -2)) : $host;
        }

        $i = 0;
        $selectedDomains = GeneralUtility::trimExplode(',', $params['fieldValue'], TRUE);
        foreach ($domains as $domain) {
            $out[] = '<div>';
            $checked = in_array($domain, $selectedDomains) ? ' checked="checked"' : '';
            $out[] = '<input type="checkbox" id="cloudflare_domain_' . $i . '" value="' . $domain . '"' . $checked . ' onclick="toggleCloudflareDomains();" />';
            $out[] = '<label for="cloudflare_domain_' . $i . '" style="display:inline-block">' . $domain . '</label>';
            $out[] = '</div>';
            $i++;
        }

        $fieldId = str_replace(array('[', ']'), '_', $params['fieldName']);
        $out[] = '<script type="text/javascript">';
        $out[] = <<<JS

function toggleCloudflareDomains() {
    var domains = new Array();
    for (var i = 0; i < {$i}; i++) {
        var e = document.getElementById("cloudflare_domain_" + i);
        if (e.checked) {
            domains.push(e.value);
        }
    }
    document.getElementById("{$fieldId}").value = domains.join(',');
}

JS;
        $out[] = '</script>';
        $out[] = '<input type="hidden" id="' . $fieldId . '" name="' . $params['fieldName'] . '" value="' . $params['fieldValue'] . '" />';

        return implode(LF, $out);
    }

}
