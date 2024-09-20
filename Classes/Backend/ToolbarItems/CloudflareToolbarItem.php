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

namespace Causal\Cloudflare\Backend\ToolbarItems;

use Causal\Cloudflare\Services\CloudflareService;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Toolbar Menu handler.
 *
 * @category    Toolbar Items
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
class CloudflareToolbarItem implements ToolbarItemInterface
{
    protected PageRenderer $pageRenderer;

    protected CloudflareService $cloudflareService;

    protected array $config;

    /**
     * Default constructor.
     *
     * @param PageRenderer $pageRenderer
     * @param CloudflareService $cloudflareService
     */
    public function __construct(
        PageRenderer $pageRenderer,
        CloudflareService $cloudflareService
    )
    {
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cloudflare') ?? [];

        $this->pageRenderer = $pageRenderer;
        $this->cloudflareService = $cloudflareService->setConfiguration($this->config);

        if ((new Typo3Version())->getMajorVersion() >= 12) {
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::create('@causal/cloudflare/toolbar.js')
                    ->invoke('create', [
                        // options go here...
                    ])
            );
        } else {
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Cloudflare/Toolbar/CloudflareMenu');
        }
    }

    /**
     * Checks whether the user has access to this toolbar item.
     *
     * @return bool true if user has access, false if not
     */
    public function checkAccess(): bool
    {
        return $this->getBackendUser()->isAdmin();
    }

    /**
     * Renders the toolbar icon.
     *
     * @return string HTML
     */
    public function getItem(): string
    {
        $title = $this->translate('toolbarItem');

        $cloudflare = [];
        $cloudflare[] = '<span title="' . htmlspecialchars($title) . '">' . $this->getSpriteIcon('actions-system-extension-configure', [], 'inline') . '</span>';
        $badgeClasses = ['badge', 'badge-danger', 'toolbar-item-badge'];

        $cloudflare[] = '<span class="' . implode(' ', $badgeClasses) . '" id="tx-cloudflare-counter" style="display:none">0</span>';
        return implode(LF, $cloudflare);
    }

    /**
     * Renders the dropdown.
     *
     * @return string HTML
     */
    public function getDropDown(): string
    {
        $typo3Version = (new Typo3Version())->getMajorVersion();
        $entries = [];

        $domains = GeneralUtility::trimExplode(',', $this->config['domains'], true);
        if (!empty($domains)) {
            foreach ($domains as $domain) {
                list($identifier, ) = explode('|', $domain, 2);
                try {
                    $ret = $this->cloudflareService->send('/zones/' . $identifier);

                    if ($ret['success'] ?? false) {
                        $zone = $ret['result'];

                        switch (true) {
                            case $zone['development_mode'] > 0:
                                $status = 'dev-mode';
                                $active = 0;
                                break;
                            case $zone['status'] === 'active':
                                $status = 'active';
                                $active = 1;
                                break;
                            case $zone['paused']:
                            default:
                                $status = 'deactivated';
                                $active = null;
                                break;
                        }

                        if ($typo3Version >= 12) {
                            $entries[] = '<li>';
                            $entries[] = '  <div class="dropdown-item" role="menuitem" data-zone-status="' . $status . '">';
                            $entries[] = '    <span class="dropdown-item-columns">';
                            $entries[] = '      <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">'
                                . $this->getZoneIcon($status) . '</span>';
                            $entries[] = '      <span class="dropdown-item-column dropdown-item-column-title">';
                            $entries[] = '        ' . htmlspecialchars($zone['name']) . '<br>';
                            if ($active !== null) {
                                $entries[] = '        <a href="#" class="cloudflare-zone" data-zone="' . $identifier . '" data-active="' . $active . '">'
                                    . $this->translate('toggle_development') . '</a>';
                            } else {
                                $entries[] = '        <span class="text-muted">' . $this->translate('zone_inactive') . '</span>';
                            }
                            $entries[] = '      </span>';
                            $entries[] = '    </span>';
                            $entries[] = '  </div>';
                            $entries[] = '</li>';
                        } else {
                            $entries[] = '<div class="dropdown-table-row" data-zone-status="' . $status . '">';
                            $entries[] = '  <div class="dropdown-table-column dropdown-table-column-top dropdown-table-icon">';
                            $entries[] = $this->getZoneIcon($status);
                            $entries[] = '  </div>';
                            $entries[] = '  <div class="dropdown-table-column">';
                            $entries[] = '    ' . htmlspecialchars($zone['name']);
                            if ($active !== null) {
                                $onClickCode = 'TYPO3.CloudflareMenu.toggleDevelopmentMode(\'' . $identifier . '\', ' . $active . '); return false;';
                                $entries[] = '    <a href="#" onclick="' . htmlspecialchars($onClickCode) . '">' . $this->translate('toggle_development') . '</a>';
                            } else {
                                $entries[] = '    ' . $this->translate('zone_inactive');
                            }
                            $entries[] = '  </div>';
                            $entries[] = '</div>';
                        }
                    }
                } catch (\RuntimeException $e) {
                    // Nothing to do
                }
            }
        }

        $content = '';
        if (!empty($entries)) {
            if ($typo3Version >= 12) {
                $content .= '<p class="h3 dropdown-headline" id="cloudflare-dropdown-headline">Cloudflare</p>';
                $content .= '<hr class="dropdown-divider" aria-hidden="true">';
                $content .= '<nav class="t3js-cloudflaremenu">';
                $content .= '<ul class="dropdown-list" role="menu" aria-labelledby="cloudflare-dropdown-headline">';
                $content .= implode(LF, $entries);
                $content .= '</ul>';
                $content .= '</nav>';
            } else {
                $content .= '<h3 class="dropdown-headline">Cloudflare</h3>';
                $content .= '<hr />';
                $content .= '<div class="dropdown-table">' . implode('', $entries) . '</div>';
            }
        } else {
            $content .= '<p>' . $this->translate('no_domains') . '</p>';
        }

        return $content;
    }

    /**
     * Returns the icon associated to a given Cloudflare status.
     *
     * @param string $status
     * @return string
     */
    protected function getZoneIcon(string $status): string
    {
        switch ($status) {
            case 'active':
                $icon = $this->getSpriteIcon('cloudflare-online', ['title' => $this->translate('zone_active')]);
                break;
            case 'dev-mode':
                $icon = $this->getSpriteIcon('cloudflare-direct', ['title' => $this->translate('zone_development')]);
                break;
            case 'deactivated':
            default:
                $icon = $this->getSpriteIcon('cloudflare-offline', ['title' => $this->translate('zone_inactive')]);
                break;
        }
        return $icon;
    }

    /**
     * Returns the HTML code for a sprite icon.
     *
     * @param string $iconName
     * @param array $options
     * @param string|null $alternativeMarkupIdentifier
     * @return string
     */
    protected function getSpriteIcon(
        string $iconName,
        array $options,
        ?string $alternativeMarkupIdentifier = null
    ): string
    {
        /** @var IconFactory $iconFactory */
        static $iconFactory = null;

        if ($iconFactory === null) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        }
        $icon = $iconFactory->getIcon($iconName, Icon::SIZE_SMALL)->render($alternativeMarkupIdentifier);
        if (strpos($icon, '<img ') !== false) {
            $icon = str_replace('<img ', '<img title="' . htmlspecialchars($options['title']) . '" ', $icon);
        }

        return $icon;
    }

    /**
     * No additional attributes.
     *
     * @return array List item HTML attributes
     */
    public function getAdditionalAttributes(): array
    {
        return [];
    }

    /**
     * This item has a dropdown.
     *
     * @return bool
     */
    public function hasDropDown(): bool
    {
        return true;
    }

    /**
     * Position relative to others.
     *
     * @return int
     */
    public function getIndex(): int
    {
        return 25;
    }

    /******************
     *** AJAX CALLS ***
     ******************/

    /**
     * Renders the menu so that it can be returned as response to an AJAX call
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function renderAjax(ServerRequestInterface $request): ResponseInterface
    {
        $menu = $this->getDropDown();

        return new JsonResponse([
            'success' => true,
            'html' => $menu,
        ]);
    }

    /**
     * Toggles development mode for a given zone.
     *
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function toggleDevelopmentMode(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();
        if ($params === null) {
            // TODO: This happens in TYPO3 v12, understand the underlying issue
            $params = json_decode($request->getBody()->getContents(), true);
        }

        $zone = $params['zone'] ?? null;
        $active = (bool)($params['active'] ?? false);

        try {
            $ret = $this->cloudflareService->send('/zones/' . $zone . '/settings/development_mode', [
                'value' => $active ? 'on' : 'off',
            ], 'PATCH');
        } catch (\RuntimeException $e) {
            // Nothing to do
        }

        return new JsonResponse([
            'success' => $ret['success'] === true,
        ]);
    }

    /**
     * Purges cache from all configured zones.
     *
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function purge(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \Causal\Cloudflare\Hooks\TCEmain $tceMain */
        $tceMain = GeneralUtility::makeInstance(\Causal\Cloudflare\Hooks\TCEmain::class);
        $tceMain->clearCache();

        return new HtmlResponse('');
    }

    /**********************
     *** HELPER METHODS ***
     **********************/

    /**
     * Returns the current Backend user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function translate(string $key): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:cloudflare/Resources/Private/Language/locallang.xlf:' . $key);
    }

    /**
     * Returns the LanguageService.
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
