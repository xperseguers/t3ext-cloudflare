<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);
if (!is_array($config)) {
	$config = array();
}

// Register additional clear_cache method
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/TCEmain.php:Tx_Cloudflare_Hooks_TCEmain->clear_cacheCmd';

if (TYPO3_MODE === 'BE') {
	$cloudflareToolbarItemClassPath = t3lib_extMgm::extPath($_EXTKEY, 'Classes/ExtDirect/CloudflareToolbarItem.php');
	$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = $cloudflareToolbarItemClassPath;

	if ($config['domains'] !== '') {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['clearCloudflareCache'] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/TYPO3backend.php:&Tx_Cloudflare_Hooks_TYPO3backend';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['cloudflare::clearCache'] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/TCEmain.php:Tx_Cloudflare_Hooks_TCEmain->clearCache';
	}
}

?>