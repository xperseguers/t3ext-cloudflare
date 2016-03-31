<?php
// DO NOT REMOVE OR CHANGE THESE 3 LINES:
$MCONF['name'] = 'txcloudflare';
$MCONF['access'] = 'user,group';
$MCONF['script'] = '_DISPATCH';

if (version_compare(TYPO3_version, '6.99.99', '<=')) {
    $MLANG['default']['tabs_images']['tab'] = 'EXT:cloudflare/ext_icon.png';
} else {
    // Only useful in TYPO3 7.6
    $MLANG['default']['tabs_images']['tab'] = 'EXT:cloudflare/Resources/Public/Icons/module-cloudflare.png';
}

// Only useful in TYPO3 6.2 and 7.6
$MLANG['default']['ll_ref'] = 'LLL:EXT:cloudflare/Resources/Private/Language/locallang_mod_cloudflare.xlf';
