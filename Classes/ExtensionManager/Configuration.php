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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
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
        /** @var array config */
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($this->extKey);
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
        $domains = [];
        $out = [];

        /** @var $cloudflareService \Causal\Cloudflare\Services\CloudflareService */
        $cloudflareService = GeneralUtility::makeInstance(\Causal\Cloudflare\Services\CloudflareService::class, $this->config);

        try {
            $ret = $cloudflareService->send('/zones/');
            if ($ret['success']) {
                $data = $cloudflareService->sort($ret, 'name');
                foreach ($data['result'] as $zone) {
                    $domains[$zone['id']] = $zone['name'];
                }
            }
        } catch (\RuntimeException $e) {
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                $e->getMessage(),
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                true
            );
            $out[] = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver::class)->resolve()->render([$flashMessage]);
        }

        $i = 0;
        $selectedDomains = GeneralUtility::trimExplode(',', $params['fieldValue'], true);

        if (!empty($domains)) {
            $out[] = '<table class="table table-striped table-hover">';
            $out[] = '<thead>';
            $out[] = '<tr><th></th><th></th><th>' . htmlspecialchars($this->sL('settings.labels.zoneIdentifiers')) . '</th></tr>';
            $out[] = '<thead>';
            $out[] = '<tbody>';
        } else {
            $out[] = '<em>' . htmlspecialchars($this->sL('settings.labels.emptyList')) . '</em>';
        }

        foreach ($domains as $identifier => $domain) {
            $out[] = '<tr>';

            // Check: in_array($domain, $selectedDomains) is for configuration coming from EXT:cloudflare < v1.4.0
            $value = $identifier . '|' . $domain;
            $checked = in_array($domain, $selectedDomains) || in_array($value, $selectedDomains)
                ? ' checked="checked"'
                : '';
            $out[] = '<td style="width:20px"><input type="checkbox" id="cloudflare_domain_' . $i . '" value="' . $value . '"' . $checked . ' onclick="toggleCloudflareDomains();" /></td>';
            $out[] = '<td style="padding-right:50px"><label for="cloudflare_domain_' . $i . '">' . htmlspecialchars($domain) . '</label></td>';
            $out[] = '<td><tt>' . htmlspecialchars($identifier) . '</tt></td>';
            $out[] = '</tr>';
            $i++;
        }

        if (!empty($domains)) {
            $out[] = '</tbody>';
            $out[] = '</table>';
        }

        $fieldId = str_replace(['[', ']'], '_', $params['fieldName']);
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

    /**
     * Translates a message.
     *
     * @param string $key
     * @return string
     */
    protected function sL($key)
    {
        $message = $this->getLanguageService()->sL('LLL:EXT:' . $this->extKey . '/Resources/Private/Language/locallang_db.xlf:' . $key);
        return $message;
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

}
