<?php
defined('TYPO3_MODE') or die();

$icons = array(
	'cloudflare' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/cloudflare-16.png',
	'direct' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/direct-16.png',
	'offline' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/offline-16.png',
	'online' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/online-16.png',
);
\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, $_EXTKEY);

// Register our custom CSS
$GLOBALS['TBE_STYLES']['skins'][$_EXTKEY]['stylesheetDirectories']['visual'] = 'EXT:' . $_EXTKEY . '/Resources/Public/Css/visual/';

if (TYPO3_MODE === 'BE') {
	if (version_compare(TYPO3_version, '6.99.99', '<=')) {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent(
			'TYPO3.Ajax.ExtDirect.CloudflareToolbarMenu',
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/ExtDirect/ToolbarMenu.php:Causal\\Cloudflare\\ExtDirect\\ToolbarMenu',
			NULL,
			'admin'
		);
	} else {
		// Register AJAX calls
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('TxCloudflare::renderMenu', 'Causal\\Cloudflare\\Backend\\ToolbarItems\\CloudflareToolbarItem->renderAjax');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('TxCloudflare::toggleDevelopmentMode', 'Causal\\Cloudflare\\Backend\\ToolbarItems\\CloudflareToolbarItem->toggleDevelopmentMode');
	}
}
