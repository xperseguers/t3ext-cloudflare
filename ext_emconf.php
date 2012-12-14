<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "cloudflare".
 *
 * Auto generated 14-12-2012 11:11
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'CloudFlare Client',
	'description' => 'Implementation of the CloudFlare Client Interface API to flush content cache and restore originating IPs.',
	'category' => 'Service',
	'author' => 'Xavier Perseguers',
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
	'version' => '1.2.1',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-4.7.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:21:{s:9:"ChangeLog";s:4:"3b3f";s:16:"ext_autoload.php";s:4:"6e12";s:21:"ext_conf_template.txt";s:4:"e82e";s:12:"ext_icon.gif";s:4:"f7d7";s:17:"ext_localconf.php";s:4:"49b4";s:14:"ext_tables.php";s:4:"b987";s:28:"Classes/EM/Configuration.php";s:4:"66c2";s:43:"Classes/ExtDirect/CloudflareToolbarItem.php";s:4:"ca21";s:33:"Classes/ExtDirect/ToolbarMenu.php";s:4:"d457";s:27:"Classes/Hooks/t3lib_div.php";s:4:"35a2";s:25:"Classes/Hooks/TCEmain.php";s:4:"7e26";s:30:"Classes/Hooks/TYPO3backend.php";s:4:"817a";s:31:"Classes/Services/Cloudflare.php";s:4:"447b";s:39:"Resources/Examples/proxy-cloudflare.php";s:4:"b6fc";s:39:"Resources/Public/Css/visual/toolbar.css";s:4:"ac64";s:40:"Resources/Public/Icons/cloudflare-16.png";s:4:"6365";s:36:"Resources/Public/Icons/direct-16.png";s:4:"923b";s:37:"Resources/Public/Icons/offline-16.png";s:4:"326b";s:36:"Resources/Public/Icons/online-16.png";s:4:"9216";s:45:"Resources/Public/JavaScript/cloudflaremenu.js";s:4:"dea1";s:14:"doc/manual.sxw";s:4:"04d3";}',
	'suggests' => array(
	),
);

?>