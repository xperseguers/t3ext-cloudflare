<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "cloudflare".
 *
 * Auto generated 25-06-2014 16:32
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
	'version' => '1.2.8',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.3-5.5.99',
			'typo3' => '4.5.0-6.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:39:{s:16:"ext_autoload.php";s:4:"804b";s:21:"ext_conf_template.txt";s:4:"24de";s:12:"ext_icon.gif";s:4:"b156";s:17:"ext_localconf.php";s:4:"fa35";s:14:"ext_tables.php";s:4:"482a";s:28:"Classes/EM/Configuration.php";s:4:"5abe";s:33:"Classes/ExtDirect/ToolbarMenu.php";s:4:"1070";s:27:"Classes/Hooks/t3lib_div.php";s:4:"c7f7";s:25:"Classes/Hooks/TCEmain.php";s:4:"d360";s:38:"Classes/Hooks/tx_ncstaticfilecache.php";s:4:"8768";s:30:"Classes/Hooks/TYPO3backend.php";s:4:"c149";s:41:"Classes/Hooks/TYPO3backend_Cloudflare.php";s:4:"ee0f";s:31:"Classes/Services/Cloudflare.php";s:4:"4627";s:26:"Documentation/Includes.txt";s:4:"c83c";s:23:"Documentation/Index.rst";s:4:"eb50";s:26:"Documentation/Settings.yml";s:4:"5e7b";s:25:"Documentation/Targets.rst";s:4:"94c2";s:43:"Documentation/AdministratorManual/Index.rst";s:4:"65f1";s:57:"Documentation/AdministratorManual/Configuration/Index.rst";s:4:"6295";s:63:"Documentation/AdministratorManual/InstallingExtension/Index.rst";s:4:"3246";s:33:"Documentation/ChangeLog/Index.rst";s:4:"3cdc";s:41:"Documentation/Images/development-mode.png";s:4:"de4b";s:36:"Documentation/Images/flush-cache.png";s:4:"9177";s:44:"Documentation/Images/overview-cloudflare.png";s:4:"b8bb";s:36:"Documentation/Introduction/Index.rst";s:4:"8f7a";s:37:"Documentation/KnownProblems/Index.rst";s:4:"2916";s:32:"Documentation/ToDoList/Index.rst";s:4:"a878";s:35:"Documentation/UsersManual/Index.rst";s:4:"2c1a";s:61:"Documentation/UsersManual/FlushingCacheOnCloudFlare/Index.rst";s:4:"9d97";s:48:"Documentation/UsersManual/Requirements/Index.rst";s:4:"1bf7";s:49:"Documentation/UsersManual/SupportForSSL/Index.rst";s:4:"320e";s:59:"Documentation/UsersManual/TogglingDevelopmentMode/Index.rst";s:4:"0df2";s:39:"Resources/Examples/proxy-cloudflare.php";s:4:"1404";s:39:"Resources/Public/Css/visual/toolbar.css";s:4:"ac64";s:40:"Resources/Public/Icons/cloudflare-16.png";s:4:"6365";s:36:"Resources/Public/Icons/direct-16.png";s:4:"923b";s:37:"Resources/Public/Icons/offline-16.png";s:4:"326b";s:36:"Resources/Public/Icons/online-16.png";s:4:"9216";s:45:"Resources/Public/JavaScript/cloudflaremenu.js";s:4:"ecd6";}',
	'suggests' => array(
	),
);

?>