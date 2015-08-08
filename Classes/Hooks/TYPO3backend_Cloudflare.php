<?php
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

if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
    require_once(PATH_typo3 . 'interfaces/interface.backend_toolbaritem.php');
}

/**
 * Class to render the CloudFlare toolbar menu (TYPO3 6.2).
 *
 * @category    Hooks
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Tx_Cloudflare_Hooks_TYPO3backend_Cloudflare implements \TYPO3\CMS\Backend\Toolbar\ToolbarItemHookInterface
{

    protected $changeWorkspace;
    protected $changeWorkspacePreview;

    /**
     * Reference back to the Backend object
     *
     * @var TYPO3backend
     */
    protected $backendReference;

    /** @var boolean */
    protected $checkAccess = null;

    /**
     * Default constructor.
     *
     * @param \TYPO3\CMS\Backend\Controller\BackendController TYPO3 Backend object reference
     */
    public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = null)
    {
        $this->backendReference = $backendReference;
        $this->getLanguageService()->includeLLFile('EXT:cloudflare/Resources/Private/Language/locallang.xlf');
    }

    /**
     * Checks whether the user has access to this toolbar item
     *
     * @return boolean true if user has access, false otherwise
     * @see typo3/alt_shortcut.php
     */
    public function checkAccess()
    {
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cloudflare')) {
            if ($this->checkAccess === null) {
                if ($this->getBackendUser()->isAdmin()) {
                    $this->checkAccess = true;
                } else {
                    $this->checkAccess = false;
                }
            }
            return $this->checkAccess;
        }
        return false;
    }

    /**
     * Creates the CloudFlare toolbar icon with popup menu.
     *
     * @return string CloudFlare menu as HTML select
     */
    public function render()
    {
        $title = $this->getLanguageService()->getLL('toolbarItem', true);
        $this->addJavascriptToBackend();
        $cloudflareMenu = array();

        $cloudflareMenu[] = '<a href="#" class="toolbar-item">' .
            \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('extensions-cloudflare-cloudflare', array('title' => $title)) .
            '</a>';
        $cloudflareMenu[] = '<ul class="toolbar-item-menu" style="display: none;">';
        $cloudflareMenu[] = '</ul>';

        return implode(LF, $cloudflareMenu);
    }

    /**
     * Adds the necessary JavaScript to the backend.
     *
     * @return    void
     */
    protected function addJavascriptToBackend()
    {
        $this->backendReference->addJavascriptFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('cloudflare') . 'Resources/Public/JavaScript/cloudflaremenu.js');
    }

    /**
     * Returns additional attributes for the list item in the toolbar.
     *
     * @return string List item HTML attributes
     */
    public function getAdditionalAttributes()
    {
        return ' id="cloudflare-menu" ';
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


if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
    $GLOBALS['TYPO3backend']->addToolbarItem('cloudflare', 'Tx_Cloudflare_Hooks_TYPO3backend_Cloudflare');
}
