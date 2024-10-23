<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "cloudflare".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Cloudflare Client',
    'description' => 'The Cloudflare extension for TYPO3 is a powerful solution to integrate your website with Cloudflare, whose mission is to help build a better Internet. This extension provides a variety of features to ensure your website is running optimally on the Cloudflare platform, a global leader in web performance and security.',
    'category' => 'services',
    'author' => 'Xavier Perseguers',
    'author_company' => 'Causal Sàrl',
    'author_email' => 'xavier@causal.ch',
    'state' => 'stable',
    'version' => '2.6.1',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.4.99',
            'typo3' => '11.5.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => ['Causal\\Cloudflare\\' => 'Classes']
    ],
];
