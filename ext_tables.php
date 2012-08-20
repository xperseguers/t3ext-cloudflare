<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$icons = array(
	'cloudflare' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Icons/cloudflare-16.png',
	'direct' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Icons/direct-16.png',
	'offline' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Icons/offline-16.png',
	'online' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Icons/online-16.png',
);
t3lib_SpriteManager::addSingleIcons($icons, $_EXTKEY);

// Register our custom CSS
$GLOBALS['TBE_STYLES']['skins'][$_EXTKEY]['stylesheetDirectories']['visual'] = 'EXT:' . $_EXTKEY . '/Resources/Public/Css/visual/';

t3lib_extMgm::registerExtDirectComponent(
	'TYPO3.Ajax.ExtDirect.CloudflareToolbarMenu',
	t3lib_extMgm::extPath($_EXTKEY) . 'Classes/ExtDirect/ToolbarMenu.php:Tx_Cloudflare_ExtDirect_ToolbarMenu',
	NULL,
	'admin'
);

?>