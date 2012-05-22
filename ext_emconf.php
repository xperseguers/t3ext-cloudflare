<?php

########################################################################
# Extension Manager/Repository config file for ext "cloudflare".
#
# Auto generated 22-05-2012 16:49
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'CloudFlare Client',
	'description' => 'Implementation of the CloudFlare Client Interface API to flush content cache and restore originating IPs.',
	'category' => 'service',
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
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-4.7.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:7:{s:9:"ChangeLog";s:4:"d546";s:21:"ext_conf_template.txt";s:4:"5b33";s:12:"ext_icon.gif";s:4:"f7d7";s:17:"ext_localconf.php";s:4:"3b25";s:14:"ext_tables.php";s:4:"cbda";s:14:"doc/manual.sxw";s:4:"e99f";s:37:"hooks/class.tx_cloudflare_tcemain.php";s:4:"9c6a";}',
	'suggests' => array(
	),
);

?>