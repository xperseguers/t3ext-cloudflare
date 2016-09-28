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
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey];
        $this->config = $config ? unserialize($config) : array();
        $this->cloudflareService = GeneralUtility::makeInstance('Causal\\Cloudflare\\Services\\CloudflareService', $this->config);
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
        $title = $this->getLanguageService()->getLL('toolbarItem', true);

        $cloudflare = array();
        $cloudflare[] = '<span title="' . htmlspecialchars($title) . '">' . $this->getSpriteIcon('actions-system-extension-configure', array(), 'inline') . '</span>';
        $cloudflare[] = '<span class="badge" id="tx-cloudflare-counter">0</span>';

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
        $entries = array();

        $domains = GeneralUtility::trimExplode(',', $this->config['domains'], true);
        if (count($domains)) {
            $entries[] = '<li class="divider"></li>';

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

                        $entries[] = '<li class="dropdown-header" data-zone-status="' . $status . '">' . $this->getZoneIcon($status) . ' ' . htmlspecialchars($zone['name']) . '</li>';

                        if ($active !== null) {
                            $onClickCode = 'TYPO3.CloudflareMenu.toggleDevelopmentMode(\'' . $identifier . '\', ' . $active . '); return false;';
                            $entries[] = '<li><a href="#" onclick="' . htmlspecialchars($onClickCode) . '">' . $languageService->getLL('toggle_development', true) . '</a></li>';
                        } else {
                            $entries[] = '<li>' . $languageService->getLL('zone_inactive', true) . '</li>';
                        }
                    }
                } catch (\RuntimeException $e) {
                    // Nothing to do
                }
            }
        }

        if (count($entries)) {
            $content = '<ul class="dropdown-list">' . implode('', $entries) . '</ul>';
        } else {
            $content = '<p>' . $languageService->getLL('no_domains', true) . '</p>';
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
                $icon = $this->getSpriteIcon('extensions-cloudflare-online', array('title' => $languageService->getLL('zone_active')));
                break;
            case 'dev-mode':
                $icon = $this->getSpriteIcon('extensions-cloudflare-direct', array('title' => $languageService->getLL('zone_development')));
                break;
            case 'deactivated':
            default:
                $icon = $this->getSpriteIcon('extensions-cloudflare-offline', array('title' => $languageService->getLL('zone_inactive')));
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
        /** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory */
        static $iconFactory = null;

        if ($iconFactory === null) {
            $iconFactory = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\IconFactory');
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
        return array();
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
     * @param array $params Array of parameters from the AJAX interface, currently unused
     * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
     * @return void
     */
    public function renderAjax($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj = null)
    {
        $ajaxObj->addContent('cloudflareMenu', $this->getDropDown());
    }

    /**
     * Toggles development mode for a given zone.
     *
     * @param array $params Array of parameters from the AJAX interface, currently unused
     * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
     * @return void
     */
    public function toggleDevelopmentMode($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj = null)
    {
        $zone = GeneralUtility::_GP('zone');
        $active = GeneralUtility::_GP('active');

        try {
            $ret = $this->cloudflareService->send('/zones/' . $zone . '/settings/development_mode', array(
                'value' => $active ? 'on' : 'off',
            ), 'PATCH');
        } catch (\RuntimeException $e) {
            // Nothing to do
        }

        $ajaxObj->addContent('success', $ret['success'] === true);
    }

    /**
     * Purges cache from all configured zones.
     *
     * @param array $params Array of parameters from the AJAX interface, currently unused
     * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
     * @return void
     */
    public function purge($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj = null)
    {
        /** @var \Causal\Cloudflare\Hooks\TCEmain $tceMain */
        $tceMain = GeneralUtility::makeInstance('Causal\\Cloudflare\\Hooks\\TCEmain');
        $tceMain->clearCache();

        $ajaxObj->addContent('success', true);
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
        $pageRenderer = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Page\\PageRenderer');
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
