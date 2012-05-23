<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Xavier Perseguers <xavier@causal.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Hook for clearing cache on CloudFlare.
 *
 * @category    Hooks
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class Tx_Cloudflare_Hooks_TCEmain {

	/** @var string */
	protected $extKey = 'cloudflare';

	/** @var array */
	protected $config;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
	}

	/**
	 * Clears the CloudFlare cache.
	 *
	 * @param array $params
	 * @param t3lib_TCEmain $pObj
	 * @return void
	 */
	public function clear_cacheCmd(array $params, t3lib_TCEmain $pObj) {
		if ($params['cacheCmd'] !== 'all') {
			return;
		}

		$domains = $this->config['domains'] ? t3lib_div::trimExplode(',', $this->config['domains'], TRUE) : array();

		/** @var $cloudflare Tx_Cloudflare_Services_Cloudflare */
		$cloudflare = t3lib_div::makeInstance('Tx_Cloudflare_Services_Cloudflare');

		foreach ($domains as $domain) {
			$parameters = array(
				'a' => 'fpurge_ts',
				'z' => $domain,
				'v' => '1',
			);
			try {
				$ret = $cloudflare->send($parameters);

				if (is_object($pObj->BE_USER)) {
					if ($ret['result'] === 'error') {
						$pObj->BE_USER->writelog(4, 1, 1, 0, 'User %s failed to clear the cache on CloudFlare (domain: "%s"): %s', array($pObj->BE_USER->user['username'], $domain, $ret['msg']));
					} else {
						$pObj->BE_USER->writelog(4, 1, 0, 0, 'User %s cleared the cache on CloudFlare (domain: "%s")', array($pObj->BE_USER->user['username'], $domain));
					}
				}
			} catch (RuntimeException $e) {
				if (is_object($pObj->BE_USER)) {
					$pObj->BE_USER->writelog(4, 1, 1, 0, $e->getMessage(), array());
				}
			}
		}
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cloudflare/Classes/Hooks/TCEmain.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cloudflare/Classes/Hooks/TCEmain.php']);
}

?>