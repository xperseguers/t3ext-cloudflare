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
    'description' => 'Implementation of the CloudFlare Client Interface API to flush content cache and restore originating IPs.',
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
    'version' => '1.4.0-dev',
    'constraints' => array(
        'depends' => array(
            'php' => '5.3.3-5.6.99',
            'typo3' => '6.2.0-7.99.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
    '_md5_values_when_last_written' => '',
    'suggests' => array(),
);
