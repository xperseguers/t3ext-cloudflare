<?php
// DO NOT REMOVE OR CHANGE THESE 3 LINES:
$MCONF['name'] = 'txcloudflare';
$MCONF['access'] = 'user,group';
$MCONF['script'] = '_DISPATCH';

if (version_compare(TYPO3_version, '6.99.99', '<=')) {
    $MLANG['default']['tabs_images']['tab'] = 'EXT:cloudflare/ext_icon.png';
} else {
    $MLANG['default']['tabs_images']['tab'] = 'EXT:cloudflare/Resources/Public/Icons/module-cloudflare.png';
}
$MLANG['default']['ll_ref'] = 'LLL:EXT:cloudflare/Resources/Private/Language/locallang_mod_cloudflare.xlf';
