<?php
namespace Causal\Cloudflare\Utility;

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
 * Class ConfigurationUtility
 * @package Causal\Cloudflare\Utility
 */
class ConfigurationUtility
{
    /**
     * Extension key
     */
    const EXT_KEY = 'cloudflare';

    /**
     * Clear cache mode on fly
     */
    const MODE_ON_FLY = 1;

    /**
     * Clear cache mode scheduler
     */
    const MODE_SCHEDULER = 2;

    /**
     * Extension configuration
     *
     * @var array
     */
    protected static $extensionConfiguration = null;

    /**
     * Get extension configuration
     *
     * @return array
     */
    public static function getExtensionConfiguration()
    {
        if (self::$extensionConfiguration === null) {
            self::$extensionConfiguration = unserialize(
                $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::EXT_KEY] ?: ''
            );
        }

        return self::$extensionConfiguration ?: [];
    }

    /**
     * Check if clear cache is in mode fly
     *
     * @return bool
     */
    public static function isFlyMode()
    {
        $config = self::getExtensionConfiguration();

        return isset($config['clearCacheMode']) && (int)$config['clearCacheMode'] === self::MODE_ON_FLY;
    }
}
