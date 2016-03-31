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

$EM_CONF[$_EXTKEY] = array(
    'title' => 'CloudFlare Client',
    'description' => 'The CloudFlare TYPO3 extension ensures your TYPO3 website is running optimally on the CloudFlare platform.',
    'category' => 'services',
    'author' => 'Xavier Perseguers (Causal)',
    'author_company' => 'Causal Sàrl',
    'author_email' => 'xavier@causal.ch',
    'shy' => '',
    'dependencies' => '',
    'conflicts' => '',
    'priority' => '',
    'module' => '',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'lockType' => '',
    'version' => '2.0.2',
    'constraints' => array(
        'depends' => array(
            'php' => '5.3.3-7.0.99',
            'typo3' => '6.2.0-8.99.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
    '_md5_values_when_last_written' => '',
    'suggests' => array(),
    'autoload' => array(
        'psr-4' => array('Causal\\Cloudflare\\' => 'Classes')
    ),
);
