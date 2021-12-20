<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "cloudflare".
 *
 * Auto generated 06-01-2015 16:56
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/
(static function (string $_EXTKEY) {
    $EM_CONF['$_EXTKEY'] = [
        'title' => 'Cloudflare Client',
        'description' => 'The Cloudflare TYPO3 extension ensures your TYPO3 website is running optimally on the Cloudflare platform.',
        'category' => 'services',
        'author' => 'Xavier Perseguers',
        'author_company' => 'Causal Sàrl',
        'author_email' => 'xavier@causal.ch',
        'state' => 'stable',
        'uploadfolder' => false,
        'createDirs' => '',
        'clearCacheOnLoad' => false,
        'version' => '3.0.0',
        'constraints' => [
            'depends' => [
                'php' => '7.4.0-8.0.99',
                'typo3' => '11.4.0-11.4.99',
            ],
            'conflicts' => [],
            'suggests' => [],
        ],
        'autoload' => [
            'psr-4' => ['Causal\\Cloudflare\\' => 'Classes']
        ],
    ];
})('cloudflare');

