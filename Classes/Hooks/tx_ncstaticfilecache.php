<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Hook for caching content over SSL with EXT:nc_staticfilecache.
 *
 * @category    Hooks
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Tx_Cloudflare_Hooks_NcStaticfilecache
{

    /**
     * Pretends we are NOT running over SSL to let EXT:nc_staticfilecache
     * potentially cache the content.
     *
     * @param array $params
     * @param \tx_ncstaticfilecache $pObj
     * @return void
     */
    public function createFile_initializeVariables(array $params, \tx_ncstaticfilecache $pObj)
    {
        $params['isHttp'] = true;
    }

}
