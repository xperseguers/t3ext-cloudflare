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
use TYPO3\CMS\Backend\Utility\IconUtility;
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
     * @return bool TRUE if user has access, FALSE if not
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
        $title = $this->getLanguageService()->getLL('toolbarItem', TRUE);

        $cloudflare = array();
        $cloudflare[] = IconUtility::getSpriteIcon('actions-system-extension-configure', array('title' => $title));
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

        $domains = GeneralUtility::trimExplode(',', $this->config['domains'], TRUE);
        if (count($domains)) {
            try {
                $ret = $this->cloudflareService->send(array('a' => 'zone_load_multi'));
                if ($ret['result'] === 'success') {
                    foreach ($ret['response']['zones']['objs'] as $zone) {
                        if (in_array($zone['zone_name'], $domains)) {
                            if (count($entries)) {
                                $entries[] = '<li class="divider"></li>';
                            }
                            $entries[] = '<li class="dropdown-header" data-zone-status="' . htmlspecialchars($zone['zone_status_class']) . '">' . $this->getZoneIcon($zone['zone_status_class']) . ' ' . htmlspecialchars($zone['zone_name']) . '</li>';
                            $active = NULL;
                            switch ($zone['zone_status_class']) {
                                case 'status-active':
                                    $active = 1;
                                    break;
                                case 'status-dev-mode':
                                    $active = 0;
                                    break;
                            }
                            if ($active !== NULL) {
                                $onClickCode = 'TYPO3.CloudflareMenu.toggleDevelopmentMode(\'' . $zone['zone_name'] . '\', ' . $active . '); return false;';
                                $entries[] = '<li><a href="#" onclick="' . htmlspecialchars($onClickCode) . '">' . $languageService->getLL('toggle_development', TRUE) . '</a></li>';
                            } else {
                                $entries[] = '<li>' . $languageService->getLL('zone_inactive', TRUE) . '</li>';
                            }
                        }
                    }
                }
            } catch (\RuntimeException $e) {
                // Nothing to do
            }
        }

        if (count($entries)) {
            $content = '<ul class="dropdown-list">' . implode('', $entries) . '</ul>';
        } else {
            $content = '<p>' . $languageService->getLL('no_domains', TRUE) . '</p>';
        }
        return $content;
    }

    /**
     * Returns the icon associated to a given CloudFlare status.
     *
     * @param string $status
     * @return string
     */
    protected function getZoneIcon($status)
    {
        $languageService = $this->getLanguageService();
        switch ($status) {
            case 'status-active':
                $icon = IconUtility::getSpriteIcon('extensions-cloudflare-online', array('title' => $languageService->getLL('zone_active')));
                break;
            case 'status-dev-mode':
                $icon = IconUtility::getSpriteIcon('extensions-cloudflare-direct', array('title' => $languageService->getLL('zone_development')));
                break;
            case 'status-deactivated':
            default:
                $icon = IconUtility::getSpriteIcon('extensions-cloudflare-offline', array('title' => $languageService->getLL('zone_inactive')));
                break;
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
        return TRUE;
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
    public function renderAjax($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj = NULL)
    {
        $ajaxObj->addContent('cloudflareMenu', $this->getDropDown());
    }

    /**
     * Toggles development mode for a given zone.
     *
     * @param array $params Array of parameters from the AJAX interface, currently unused
     * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
     */
    public function toggleDevelopmentMode($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj = NULL)
    {
        $zone = GeneralUtility::_GP('zone');
        $active = GeneralUtility::_GP('active');

        try {
            $ret = $this->cloudflareService->send(array(
                'a' => 'devmode',
                'z' => $zone,
                'v' => $active,
            ));
        } catch (\RuntimeException $e) {
            // Nothing to do
        }

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
        /** @var  \TYPO3\CMS\Backend\Template\DocumentTemplate $documentTemplate */
        $documentTemplate = $GLOBALS['TBE_TEMPLATE'];
        return $documentTemplate->getPageRenderer();
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
