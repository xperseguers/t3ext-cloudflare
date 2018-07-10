<?php
namespace Causal\Cloudflare\Hooks;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Hook for clearing cache on Cloudflare.
 *
 * @category    Hooks
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class TYPO3backend implements \TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface
{

    /**
     * Default constructor.
     */
    public function __construct()
    {
        if (version_compare(TYPO3_branch, '8.7', '<=')) {
            $this->getLanguageService()->includeLLFile('EXT:cloudflare/Resources/Private/Language/locallang.xlf');
        }
    }

    /**
     * Adds cache menu item.
     *
     * @param array $cacheActions
     * @param array $optionValues
     * @return void
     */
    public function manipulateCacheActions(&$cacheActions, &$optionValues)
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin() || $backendUser->getTSConfigVal('options.clearCache.all') || $backendUser->getTSConfigVal('options.clearCache.cloudflare')) {
            // Add new cache menu item
            $clearAll = array_shift($cacheActions);
            if (version_compare(TYPO3_branch, '8.7', '>=')) {
                /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
                $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
                $ajaxRoute = (string)$uriBuilder->buildUriFromRoute('ajax_cloudflare_purge');
                $clearCloudflare = [
                    'id' => 'cloudflare',
                    'title' => 'LLL:EXT:cloudflare/Resources/Private/Language/locallang.xlf:clear_cache',
                    'description' => 'LLL:EXT:cloudflare/Resources/Private/Language/locallang.xlf:clear_cache.description',
                    'href' => $ajaxRoute,
                    'iconIdentifier' => 'actions-system-cache-clear-impact-low',
                ];
            } else {
                /** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory */
                $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
                $icon = $iconFactory->getIcon('actions-system-cache-clear-impact-low', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL);

                $clearCloudflare = [
                    'id' => 'cloudflare',
                    'title' => $this->getLanguageService()->getLL('clear_cache'),
                    'href' => $GLOBALS['BACK_PATH'] . BackendUtility::getAjaxUrl('cloudflare_purge'),
                    'icon' => $icon,
                ];
            }
            if ($clearAll !== null) {
                $cacheActions = array_merge([$clearAll, $clearCloudflare], $cacheActions);
            } else {
                $cacheActions[] = $clearCloudflare;
            }
            $optionValues[] = 'cloudflare';
        }
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
