<?php
namespace Causal\Cloudflare\Controller;

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
 * Dashboard controller.
 *
 * @category    Controller
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class DashboardController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var string
     */
    protected $extKey = 'cloudflare';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Causal\Cloudflare\Services\CloudflareService
     */
    protected $cloudflareService;

    /**
     * @var array
     */
    protected $zones;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        /** @var array config */
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($this->extKey);

        $this->cloudflareService = GeneralUtility::makeInstance(\Causal\Cloudflare\Services\CloudflareService::class, $this->config);

        $domains = GeneralUtility::trimExplode(',', $this->config['domains'], true);
        $this->zones = [];
        foreach ($domains as $domain) {
            list($identifier, $zone) = explode('|', $domain);
            $this->zones[$identifier] = $zone;
        }
    }

    /**
     * Default action: analytics.
     *
     * @return void
     */
    public function analyticsAction()
    {
        $defaultIdentifier = null;
        if (!empty($this->zones)) {
            $defaultIdentifier = key($this->zones);
        }
        $this->view->assignMultiple([
            'zones' => $this->zones,
            'defaultIdentifier' => $defaultIdentifier,
            'defaultZone' => $defaultIdentifier !== null ? $this->zones[$defaultIdentifier] : null,
            'periods' => $this->getAvailablePeriods($defaultIdentifier),
        ]);
    }

    /**
     * Returns the JSON data for the requests analytics.
     *
     * @param string $zone
     * @param int $since
     * @return void
     */
    public function ajaxAnalyticsAction($zone, $since)
    {
        if (empty($zone)) {
            $this->returnAjax(null);
        }

        $availablePeriods = $this->getAvailablePeriods($zone);
        if (!isset($availablePeriods[$since])) {
            $since = key($availablePeriods);
        }
        $cfData = $this->cloudflareService->send('/zones/' . $zone . '/analytics/dashboard', ['since' => -$since]);
        if (!$cfData['success']) {
            $this->returnAjax(null);
        }

        $data = [
            'periods' => $availablePeriods,
            'period' => $this->translate('period.' . $since),
            'timeseries' => $cfData['result']['timeseries'],
        ];

        // Compute some additional statistics
        $uniquesMinimum = PHP_INT_MAX;
        $uniquesMaximum = 0;
        $threatsMaximumCountry = 0;
        $threatsTopCountry = 'N/A';
        $threatsMaximumType = 0;
        $threatsTopType = 'N/A';

        foreach ($data['timeseries'] as $tsKey => $info) {
            $threats = [
                'all' => $info['threats']['all'],
                'type' => [],
            ];

            // Fix info from Cloudflare API
            if (!is_array($info['threats']['type'])) {
                $info['threats']['type'] = [];
            }
            if (!is_array($info['threats']['country'])) {
                $info['threats']['country'] = [];
            }

            if ($info['uniques']['all'] < $uniquesMinimum) {
                $uniquesMinimum = $info['uniques']['all'];
            }
            if ($info['uniques']['all'] > $uniquesMaximum) {
                $uniquesMaximum = $info['uniques']['all'];
            }
            foreach ($info['threats']['country'] as $country => $count) {
                if ($count > $threatsMaximumCountry) {
                    $threatsMaximumCountry = $count;
                    $threatsTopCountry = $country;
                }
            }
            foreach ($info['threats']['type'] as $type => $count) {
                $threatName = $this->getThreatName($type);
                if ($count > $threatsMaximumType) {
                    $threatsMaximumType = $count;
                    $threatsTopType = $threatName;
                }
                $threats['type'][$type] = [
                    'name' => $threatName,
                    'all' => $count,
                ];
            }

            $data['timeseries'][$tsKey]['threats'] = $threats;
        }

        $data['totals'] = array_merge_recursive($cfData['result']['totals'], [
            'requests' => [
                'c1' => number_format($cfData['result']['totals']['requests']['all']),
                'c2' => number_format($cfData['result']['totals']['requests']['cached']),
                'c3' => number_format($cfData['result']['totals']['requests']['uncached']),
            ],
            'bandwidth' => [
                'c1' => GeneralUtility::formatSize($cfData['result']['totals']['bandwidth']['all']),
                'c2' => GeneralUtility::formatSize($cfData['result']['totals']['bandwidth']['cached']),
                'c3' => GeneralUtility::formatSize($cfData['result']['totals']['bandwidth']['uncached']),
            ],
            'uniques' => [
                'c1' => number_format($cfData['result']['totals']['uniques']['all']),
                'c2' => $uniquesMaximum,
                'c3' => $uniquesMinimum,
            ],
            'threats' => [
                'c1' => number_format($cfData['result']['totals']['threats']['all']),
                'c2' => $threatsTopCountry,
                'c3' => $threatsTopType,
            ],
        ]);

        // Sort some data for better display as graphs
        arsort($data['totals']['bandwidth']['content_type']);
        arsort($data['totals']['requests']['content_type']);

        $this->returnAjax($data);
    }

    /**
     * Returns an AJAX response.
     *
     * @param array $response
     * @param bool $wrapForIframe see http://cmlenz.github.io/jquery-iframe-transport/#section-13
     * return void
     */
    protected function returnAjax(array $response = null, $wrapForIframe = false)
    {
        $payload = json_encode($response);
        if (!$wrapForIframe) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/html');
            $payload = '<textarea data-type="application/json">' . $payload . '</textarea>';
        }
        echo $payload;
        exit;
    }

    /**
     * Returns the available periods for a given zone (depends on the Cloudflare plan).
     *
     * @param string $zone
     * @return array
     */
    protected function getAvailablePeriods($zone)
    {
        if ($zone === null) {
            return [];
        }

        $periods = [
            '30' => $this->translate('period.30'),
            '360' => $this->translate('period.360'),
            '720' => $this->translate('period.720'),
            '1440' => $this->translate('period.1440'),
            '10080' => $this->translate('period.10080'),
            '43200' => $this->translate('period.43200'),
        ];

        $info = $this->cloudflareService->send('/zones/' . $zone);
        if ($info['success']) {
            switch ($info['result']['plan']['legacy_id']) {
                case 'free':
                    unset($periods['30'], $periods['360'], $periods['720']);
                    break;
                case 'pro':
                    unset($periods['30']);
                    break;
                case 'business':
                    unset($periods['30']);
                    break;
                case 'enterprise':
                    break;
            }
        }

        return $periods;
    }

    /**
     * Returns a human-readable name of a given threat type.
     *
     * See https://support.cloudflare.com/hc/en-us/articles/204191238-What-are-the-types-of-Threats-
     *
     * @param string $type
     * @return string
     */
    protected function getThreatName($type)
    {
        $name = $this->translate('dashboard.threats.' . $type);
        if (empty($name)) {
            $name = $type;
        }
        return $name;
    }

    /**
     * Returns the localized label of a given key.
     *
     * @param string $key The key from the LOCAL_LANG array for which to return the value.
     * @param array $arguments the arguments of the extension, being passed over to vsprintf
     * @return string Localized label
     */
    protected function translate($key, $arguments = null)
    {
        return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, $this->request->getControllerExtensionKey(), $arguments);
    }

}
