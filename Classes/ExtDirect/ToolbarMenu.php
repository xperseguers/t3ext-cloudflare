<?php
namespace Causal\Cloudflare\ExtDirect;

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
use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * Toolbar Menu ExtDirect handler (TYPO3 6.2).
 *
 * @category    ExtDirect
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ToolbarMenu
{

    /** @var string */
    protected $extKey = 'cloudflare';

    /** @var array */
    protected $config;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey];
        $this->config = $config ? unserialize($config) : [];
        $this->getLanguageService()->includeLLFile('EXT:cloudflare/Resources/Private/Language/locallang.xlf');
    }

    /**
     * Retrieves the Cloudflare status of selected domains.
     *
     * @param $parameter
     * @return array
     * @throws \RuntimeException
     */
    public function retrieveCloudflareStatus($parameter)
    {
        if (!$this->getBackendUser()->isAdmin()) {
            throw new \RuntimeException('Unauthorized call', 1366652032);
        }
        $languageService = $this->getLanguageService();
        $out = [];
        $domains = GeneralUtility::trimExplode(',', $this->config['domains'], true);
        if (!empty($domains)) {
            /** @var $cloudflareService \Causal\Cloudflare\Services\CloudflareService */
            $cloudflareService = GeneralUtility::makeInstance(\Causal\Cloudflare\Services\CloudflareService::class, $this->config);

            foreach ($domains as $domain) {
                list($identifier,) = explode('|', $domain, 2);
                try {
                    $ret = $cloudflareService->send('/zones/' . $identifier);
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

                        $out[] = '<li><h3>&nbsp;' . $this->getZoneIcon($status) . ' ' . htmlspecialchars($zone['name']) . '</h3></li>';
                        if ($active !== null) {
                            $onClickCode = 'TYPO3BackendCloudflareMenu.toggleDevelopmentMode(\'' . $identifier . '\', ' . $active . ');';
                            $out[] = '<li class="divider"><a href="#" onclick="' . htmlspecialchars($onClickCode) . '">' . $languageService->getLL('toggle_development', true) . '</a></li>';
                        } else {
                            $out[] = '<li class="divider">' . $languageService->getLL('zone_inactive', true) . '</li>';
                        }
                    }
                } catch (\RuntimeException $e) {
                    // Nothing to do
                }
            }
        } else {
            $out[] = '<li>' . $languageService->getLL('no_domains') . '</li>';
        }

        return ['html' => implode('', $out)];
    }

    /**
     * Toggle the development mode.
     *
     * @param $parameter
     * @return array
     * @throws \RuntimeException
     */
    public function toggleDevelopmentMode($parameter)
    {
        if (!$this->getBackendUser()->isAdmin()) {
            throw new \RuntimeException('Unauthorized call', 1366652080);
        }

        /** @var $cloudflareService \Causal\Cloudflare\Services\CloudflareService */
        $cloudflareService = GeneralUtility::makeInstance(\Causal\Cloudflare\Services\CloudflareService::class, $this->config);

        try {
            $ret = $cloudflareService->send('/zones/' . $parameter->zone . '/settings/development_mode', [
                'value' => $parameter->active ? 'on' : 'off',
            ], 'PATCH');
        } catch (\RuntimeException $e) {
            // Nothing to do
        }

        return ['result' => 'success'];
    }

    /**
     * Purges cache from all configured zones.
     *
     * @param array $params Array of parameters from the AJAX interface, currently unused
     * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
     * @return void
     */
    public function purge($params = [], \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj = null)
    {
        /** @var \Causal\Cloudflare\Hooks\TCEmain $tceMain */
        $tceMain = GeneralUtility::makeInstance(\Causal\Cloudflare\Hooks\TCEmain::class);
        $tceMain->clearCache();

        $ajaxObj->addContent('success', true);
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
                $icon = IconUtility::getSpriteIcon('extensions-cloudflare-online', ['title' => $languageService->getLL('zone_active')]);
                break;
            case 'dev-mode':
                $icon = IconUtility::getSpriteIcon('extensions-cloudflare-direct', ['title' => $languageService->getLL('zone_development')]);
                break;
            case 'deactivated':
            default:
                $icon = IconUtility::getSpriteIcon('extensions-cloudflare-offline', ['title' => $languageService->getLL('zone_inactive')]);
                break;
        }
        return $icon;
    }

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
     * Returns the LanguageService.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

}
