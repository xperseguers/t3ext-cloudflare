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
	'version' => '1.3.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.3-5.6.99',
			'typo3' => '6.2.0-7.99.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:43:{s:13:"composer.json";s:4:"6c30";s:21:"ext_conf_template.txt";s:4:"c237";s:12:"ext_icon.png";s:4:"2100";s:15:"ext_icon@2x.png";s:4:"9162";s:17:"ext_localconf.php";s:4:"6ace";s:14:"ext_tables.php";s:4:"2098";s:54:"Classes/Backend/ToolbarItems/CloudflareToolbarItem.php";s:4:"d916";s:33:"Classes/ExtDirect/ToolbarMenu.php";s:4:"cde1";s:42:"Classes/ExtensionManager/Configuration.php";s:4:"23f4";s:25:"Classes/Hooks/TCEmain.php";s:4:"8da7";s:38:"Classes/Hooks/tx_ncstaticfilecache.php";s:4:"b51c";s:30:"Classes/Hooks/TYPO3backend.php";s:4:"84c4";s:41:"Classes/Hooks/TYPO3backend_Cloudflare.php";s:4:"445f";s:38:"Classes/Services/CloudflareService.php";s:4:"4093";s:26:"Documentation/Includes.txt";s:4:"c83c";s:23:"Documentation/Index.rst";s:4:"6892";s:23:"Documentation/Links.rst";s:4:"7e1e";s:26:"Documentation/Settings.yml";s:4:"d14f";s:43:"Documentation/AdministratorManual/Index.rst";s:4:"65f1";s:57:"Documentation/AdministratorManual/Configuration/Index.rst";s:4:"e7b0";s:63:"Documentation/AdministratorManual/InstallingExtension/Index.rst";s:4:"3246";s:33:"Documentation/ChangeLog/Index.rst";s:4:"3d0f";s:41:"Documentation/Images/development-mode.png";s:4:"f993";s:36:"Documentation/Images/flush-cache.png";s:4:"fe98";s:44:"Documentation/Images/overview-cloudflare.png";s:4:"b8bb";s:36:"Documentation/Introduction/Index.rst";s:4:"8f7a";s:37:"Documentation/KnownProblems/Index.rst";s:4:"2916";s:32:"Documentation/ToDoList/Index.rst";s:4:"a878";s:35:"Documentation/UsersManual/Index.rst";s:4:"2c1a";s:61:"Documentation/UsersManual/FlushingCacheOnCloudFlare/Index.rst";s:4:"a9db";s:48:"Documentation/UsersManual/Requirements/Index.rst";s:4:"1bf7";s:49:"Documentation/UsersManual/SupportForSSL/Index.rst";s:4:"f387";s:59:"Documentation/UsersManual/TogglingDevelopmentMode/Index.rst";s:4:"c2ef";s:39:"Resources/Examples/proxy-cloudflare.php";s:4:"0f09";s:40:"Resources/Private/Language/locallang.xlf";s:4:"6114";s:43:"Resources/Private/Language/locallang_db.xlf";s:4:"5853";s:39:"Resources/Public/Css/visual/toolbar.css";s:4:"ac64";s:40:"Resources/Public/Icons/cloudflare-16.png";s:4:"6365";s:36:"Resources/Public/Icons/direct-16.png";s:4:"923b";s:37:"Resources/Public/Icons/offline-16.png";s:4:"326b";s:36:"Resources/Public/Icons/online-16.png";s:4:"9216";s:45:"Resources/Public/JavaScript/cloudflaremenu.js";s:4:"ba48";s:53:"Resources/Public/JavaScript/Toolbar/CloudflareMenu.js";s:4:"1481";}',
	'suggests' => array(
	),
);

?>