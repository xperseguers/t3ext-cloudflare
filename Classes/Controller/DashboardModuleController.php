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

namespace Causal\Cloudflare\Controller;

use Causal\Cloudflare\Services\CloudflareService;
use Causal\Cloudflare\Traits\ConfiguredDomainsTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Dashboard controller.
 *
 * @category    Controller
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
#[AsController]
class DashboardModuleController extends ActionController
{
    use ConfiguredDomainsTrait;

    protected PageRenderer $pageRenderer;

    protected ModuleTemplateFactory $moduleTemplateFactory;

    protected CloudflareService $cloudflareService;

    protected array $zones;

    public function __construct(
        PageRenderer $pageRenderer,
        ModuleTemplateFactory $moduleTemplateFactory,
        CloudflareService $cloudflareService
    ) {
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cloudflare') ?? [];
        $this->cloudflareService = $cloudflareService->setConfiguration($this->config);
        $this->pageRenderer = $pageRenderer;
        $this->moduleTemplateFactory = $moduleTemplateFactory;

        $domains = $this->getDomains();
        $this->zones = [];
        foreach ($domains as $domain) {
            list($identifier, $zone) = explode('|', $domain);
            $this->zones[$identifier] = $zone;
        }
    }

    /**
     * Default action: analytics.
     *
     * @return ResponseInterface
     */
    public function analyticsAction(): ResponseInterface
    {
        $typo3Version = (new Typo3Version())->getMajorVersion();

        $defaultIdentifier = null;
        if (!empty($this->zones)) {
            $defaultIdentifier = key($this->zones);
        }

        // Load CSS and JavaScript
        $this->pageRenderer->addCssFile('EXT:cloudflare/Resources/Public/Css/dashboard.css');
        /**
         * <script type="text/javascript" src="{f:uri.resource(path:'JavaScript/amcharts/amcharts.js')}"></script>
         * <script type="text/javascript" src="{f:uri.resource(path:'JavaScript/amcharts/serial.js')}"></script>
         * <script type="text/javascript" src="{f:uri.resource(path:'JavaScript/amcharts/pie.js')}"></script>
         * <script type="text/javascript" src="{f:uri.resource(path:'JavaScript/amcharts/themes/light.js')}"></script>
         */

        if ($typo3Version >= 12) {
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::create('@causal/cloudflare/module-analytics.js')
                    ->invoke('create', [
                        // options go here...
                    ])
            );
        } else {
            $this->pageRenderer->addInlineLanguageLabelFile(
                'EXT:cloudflare/Resources/Private/Language/locallang.xlf',
                'dashboard.'
            );

            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Cloudflare/Analytics');
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $view = $typo3Version >= 13 ? $moduleTemplate : $this->view;

        $view->assignMultiple([
            'zones' => $this->zones,
            'defaultIdentifier' => $defaultIdentifier,
            'defaultZone' => $defaultIdentifier !== null ? $this->zones[$defaultIdentifier] : null,
            'periods' => $this->getAvailablePeriods($defaultIdentifier),
        ]);

        if ($typo3Version < 12) {
            $moduleTemplate->setContent($view->render());
            return $this->htmlResponse($moduleTemplate->renderContent());
        }

        return $moduleTemplate->renderResponse('DashboardModule/Analytics');
    }

    /**
     * Returns the JSON data for the requests analytics.
     */
    public function fetchAnalytics(ServerRequestInterface $request): ResponseInterface
    {
        $zone = $request->getQueryParams()['zone'] ?? null;
        $since = (int)($request->getQueryParams()['since'] ?? 1440);

        if (empty($zone)) {
            return new JsonResponse([], 400);
        }

        $availablePeriods = $this->getAvailablePeriods($zone);
        if (!isset($availablePeriods[$since])) {
            $since = key($availablePeriods);
        }
        $cfData = $this->cloudflareService->send('/zones/' . $zone . '/analytics/dashboard', ['since' => -$since]);
        if (!$cfData['success']) {
            return new JsonResponse([], 400);
        }

        $data = [
            'periods' => $availablePeriods,
            'period' => $this->sL('period.' . $since),
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

        return new JsonResponse($data);
    }

    /**
     * Returns the available periods for a given zone (depends on the Cloudflare plan).
     *
     * @param string $zone
     * @return array
     */
    protected function getAvailablePeriods(string $zone): array
    {
        if ($zone === null) {
            return [];
        }

        $periods = [
            '30' => $this->sL('period.30'),
            '360' => $this->sL('period.360'),
            '720' => $this->sL('period.720'),
            '1440' => $this->sL('period.1440'),
            '10080' => $this->sL('period.10080'),
            '43200' => $this->sL('period.43200'),
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
        $name = $this->sL('dashboard.threats.' . $type);
        if (empty($name)) {
            $name = $type;
        }
        return $name;
    }

    /**
     * Returns the localized label of a given key.
     *
     * @param string $key The key from the LOCAL_LANG array for which to return the value.
     * @param array|null $arguments the arguments of the extension, being passed over to vsprintf
     * @return string Localized label
     */
    protected function sL(string $key, ?array $arguments = null): string
    {
        return LocalizationUtility::translate($key, 'cloudflare', $arguments);
    }
}
