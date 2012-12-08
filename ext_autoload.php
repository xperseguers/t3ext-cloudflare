<?php
$extensionPath = t3lib_extMgm::extPath('cloudflare');
return array(
	'tx_cloudflare_extdirect_toolbarmenu' => $extensionPath . 'Classes/ExtDirect/ToolbarMenu.php',
	'tx_cloudflare_services_cloudflare' => $extensionPath . 'Classes/Services/Cloudflare.php',
);
?>