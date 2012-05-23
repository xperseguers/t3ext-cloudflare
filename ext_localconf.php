<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

// Register additional clear_cache method
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/TCEmain.php:Tx_Cloudflare_Hooks_TCEmain->clear_cacheCmd';

?>