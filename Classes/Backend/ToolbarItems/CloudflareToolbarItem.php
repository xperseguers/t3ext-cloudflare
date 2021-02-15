<?php
namespace Causal\Cloudflare\Backend\ToolbarItems;

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

use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
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
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class CloudflareToolbarItem implements ToolbarItemInterface
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
     * Default constructor.
     */
    public function __construct()
    {
        /** @var array config */
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($this->extKey);

        $this->cloudflareService = GeneralUtility::makeInstance(\Causal\Cloudflare\Services\CloudflareService::class, $this->config);
        $this->getLanguageService()->includeLLFile('EXT:cloudflare/Resources/Private/Language/locallang.xlf');
        $pageRenderer = $this->getPageRenderer();
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Cloudflare/Toolbar/CloudflareMenu');
    }

    /**
     * Checks whether the user has access to this toolbar item.
     *
     * @return bool true if user has access, false if not
     */
    public function checkAccess()
    {
        return $this->getBackendUser()->isAdmin();
    }

    /**
     * Renders the toolbar icon.
     *
     * @return string HTML
     */
    public function getItem()
    {
        $title = $this->getLanguageService()->getLL('toolbarItem');

        $cloudflare = [];
        $cloudflare[] = '<span title="' . htmlspecialchars($title) . '">' . $this->getSpriteIcon('actions-system-extension-configure', [], 'inline') . '</span>';
        $badgeClasses = ['badge', 'badge-danger', 'toolbar-item-badge'];

        $cloudflare[] = '<span class="' . implode(' ', $badgeClasses) . '" id="tx-cloudflare-counter" style="display:none">0</span>';
        return implode(LF, $cloudflare);
    }

    /**
     * Renders the drop down.
     *
     * @return string HTML
     */
    public function getDropDown()
    {
        $languageService = $this->getLanguageService();
        $entries = [];

        $domains = GeneralUtility::trimExplode(',', $this->config['domains'], true);
        if (!empty($domains)) {
            foreach ($domains as $domain) {
                list($identifier, ) = explode('|', $domain, 2);
                try {
                    $ret = $this->cloudflareService->send('/zones/' . $identifier);

                    if ($ret['success']) {
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

                        $entries[] = '<div class="dropdown-table-row" data-zone-status="' . $status . '">';
                        $entries[] = '    <div class="dropdown-table-column dropdown-table-column-top dropdown-table-icon">';
                        $entries[] = $this->getZoneIcon($status);
                        $entries[] = '    </div>';
                        $entries[] = '    <div class="dropdown-table-column">';
                        $entries[] = htmlspecialchars($zone['name']);
                        if ($active !== null) {
                            $onClickCode = 'TYPO3.CloudflareMenu.toggleDevelopmentMode(\'' . $identifier . '\', ' . $active . '); return false;';
                            $entries[] = '<a href="#" onclick="' . htmlspecialchars($onClickCode) . '">' . $languageService->getLL('toggle_development') . '</a>';
                        } else {
                            $entries[] = $languageService->getLL('zone_inactive');
                        }
                        $entries[] = '    </div>';
                        $entries[] = '</div>';
                    }
                } catch (\RuntimeException $e) {
                    // Nothing to do
                }
            }
        }

        $content = '';
        if (!empty($entries)) {
            $content .= '<h3 class="dropdown-headline">Cloudflare</h3>';
            $content .= '<hr />';
            $content .= '<div class="dropdown-table">' . implode('', $entries) . '</div>';
        } else {
            $content .= '<p>' . $languageService->getLL('no_domains') . '</p>';
        }

        return $content;
    }

    /**
     * Returns the icon associated to a given Cloudflare status.
     *
     * @param string $status
     * @return string
     */
    protected function getZoneIcon($status)
    {
        $languageService = $this->getLanguageService();
        switch ($status) {
            case 'active':
                $icon = $this->getSpriteIcon('extensions-cloudflare-online', ['title' => $languageService->getLL('zone_active')]);
                break;
            case 'dev-mode':
                $icon = $this->getSpriteIcon('extensions-cloudflare-direct', ['title' => $languageService->getLL('zone_development')]);
                break;
            case 'deactivated':
            default:
                $icon = $this->getSpriteIcon('extensions-cloudflare-offline', ['title' => $languageService->getLL('zone_inactive')]);
                break;
        }
        return $icon;
    }

    /**
     * Returns the HTML code for a sprite icon.
     *
     * @param string $iconName
     * @param array $options
     * @param string $alternativeMarkupIdentifier
     * @return string
     */
    protected function getSpriteIcon($iconName, array $options, $alternativeMarkupIdentifier = null)
    {
        /** @var IconFactory $iconFactory */
        static $iconFactory = null;

        if ($iconFactory === null) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        }
        $icon = $iconFactory->getIcon($iconName, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render($alternativeMarkupIdentifier);
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
    public function getAdditionalAttributes()
    {
        return [];
    }

    /**
     * This item has a drop down.
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return true;
    }

    /**
     * Position relative to others.
     *
     * @return int
     */
    public function getIndex()
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
     * @param ResponseInterface $response
     *
     * @return JsonResponse
     */
    public function renderAjax(ServerRequestInterface $request)
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
     * @param ResponseInterface $response
     *
     * @return JsonResponse
     */
    public function toggleDevelopmentMode(ServerRequestInterface $request)
    {
        $zone = GeneralUtility::_GP('zone');
        $active = GeneralUtility::_GP('active');

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
     * @param ResponseInterface $response
     *
     * @return JsonResponse
     */
    public function purge(ServerRequestInterface $request)
    {
        /** @var \Causal\Cloudflare\Hooks\TCEmain $tceMain */
        $tceMain = GeneralUtility::makeInstance(\Causal\Cloudflare\Hooks\TCEmain::class);
        $tceMain->clearCache();

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**********************
     *** HELPER METHODS ***
     **********************/

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
     * Returns current PageRenderer.
     *
     * @return \TYPO3\CMS\Core\Page\PageRenderer
     */
    protected function getPageRenderer()
    {
        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        return $pageRenderer;
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
