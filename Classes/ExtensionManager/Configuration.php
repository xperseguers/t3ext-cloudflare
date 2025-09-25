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

namespace Causal\Cloudflare\ExtensionManager;

use Causal\Cloudflare\Services\CloudflareService;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configuration class for the TYPO3 Extension Manager.
 *
 * @category    Extension Manager
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
class Configuration
{
    protected CloudflareService $cloudflareService;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        // DI is not available in this context
        $this->cloudflareService = GeneralUtility::makeInstance(CloudflareService::class);
    }

    /**
     * Returns an Extension Manager field for selecting domains.
     *
     * @param array $params
     * @return string
     */
    public function getDomains(array $params): string
    {
        $config = $this->cloudflareService->getConfiguration();
        $typo3Version = (new Typo3Version())->getMajorVersion();
        $domains = [];
        $out = [];

        try {
            $ret = $this->cloudflareService->send('/zones/');
            if ($ret['success']) {
                $data = $this->cloudflareService->sort($ret, 'name');
                foreach ($data['result'] as $zone) {
                    $domains[$zone['id']] = $zone['name'];
                }
            }
        } catch (\RuntimeException $e) {
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $e->getMessage(),
                '',
                defined('TYPO3\CMS\Core\Messaging\FlashMessagey::ERROR') ? FlashMessage::ERROR : ContextualFeedbackSeverity::ERROR,
                true
            );
            $out[] = GeneralUtility::makeInstance(FlashMessageRendererResolver::class)->resolve()->render([$flashMessage]);
        }

        if ($typo3Version >= 12) {
            $selectedDomains = GeneralUtility::trimExplode(',', $config[$params['fieldName']] ?? '', true);
        } else {
            $selectedDomains = GeneralUtility::trimExplode(',', $params['fieldValue'], true);
        }

        $numberOfDomains = count($domains);

        // TODO: Candidate for backporting to TYPO3 v11 and drop JS trick?
        if ($typo3Version >= 12) {
            // The configuration is stored in various fields named domains_0, domains_1, etc.
            // as the trick we used up to TYPO3 v11 with virtual fields and a JavaScript to
            // bring the values back to the original field is not possible anymore with
            // TYPO3 v12.
            $domainsCountName = $params['fieldName'] . '_count';
            $out[] = '<input type="hidden" name="' . $domainsCountName . '" value="' . $numberOfDomains . '" />';

            for ($i = 0; $i < $numberOfDomains; $i++) {
                $configKey = $params['fieldName'] . '_' . $i;
                $value = $config[$configKey] ?? '';
                if (!empty($value)) {
                    $selectedDomains[] = $value;
                }
            }
        }

        if (!empty($domains)) {
            $out[] = '<table class="table table-striped table-hover">';
            $out[] = '<thead>';
            $out[] = '<tr><th></th><th></th><th>' . htmlspecialchars($this->sL('settings.labels.zoneIdentifiers')) . '</th></tr>';
            $out[] = '<thead>';
            $out[] = '<tbody>';
        } else {
            $out[] = '<em>' . htmlspecialchars($this->sL('settings.labels.emptyList')) . '</em>';
        }

        $i = 0;
        foreach ($domains as $identifier => $domain) {
            $out[] = '<tr>';

            $value = $identifier . '|' . $domain;
            $checked = in_array($value, $selectedDomains, true)
                ? ' checked="checked"'
                : '';
            $out[] = '<td style="width:20px">';

            // Virtual checkbox field for TYPO3 v12 and below
            $checkboxName = $typo3Version >= 12
                ? ' name="' . $params['fieldName'] . '_' . $i . '"'
                : '';

            $out[] = '  <input type="checkbox" class="cloudflare_domain" id="cloudflare_domain_' . $i . '" value="' . $value . '"' . $checkboxName . $checked . '" />';
            $out[] = '</td>';
            $out[] = '<td style="padding-right:50px"><label for="cloudflare_domain_' . $i . '">' . htmlspecialchars($domain) . '</label></td>';
            $out[] = '<td><tt>' . htmlspecialchars($identifier) . '</tt></td>';
            $out[] = '</tr>';
            $i++;
        }

        if (!empty($domains)) {
            $out[] = '</tbody>';
            $out[] = '</table>';
        }

        if ($typo3Version < 12) {
            $fieldId = str_replace(['[', ']'], '_', $params['fieldName']);

            $out[] = '<script>';
            $out[] = <<<JS
function toggleCloudflareDomains() {
    var domains = new Array();
    for (var i = 0; i < {$numberOfDomains}; i++) {
        var e = document.getElementById("cloudflare_domain_" + i);
        if (e.checked) {
            domains.push(e.value);
        }
    }
    document.getElementById("{$fieldId}").value = domains.join(',');
}
setTimeout(function() {
    document.querySelectorAll('.cloudflare_domain').forEach(function (item, idx) {
        item.addEventListener('click', function (event) {
            toggleCloudflareDomains();
        });
    });
}, 1000);
JS;
            $out[] = '</script>';
            $out[] = '<input type="hidden" id="' . $fieldId . '" name="' . $params['fieldName'] . '" value="' . $params['fieldValue'] . '" />';
        }

        return implode(LF, $out);
    }

    /**
     * Translates a message.
     *
     * @param string $key
     * @return string
     */
    protected function sL(string $key): string
    {
        $message = $this->getLanguageService()->sL('LLL:EXT:cloudflare/Resources/Private/Language/locallang_db.xlf:' . $key);
        return $message;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
