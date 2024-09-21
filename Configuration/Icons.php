<?php
declare(strict_types = 1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'cloudflare-direct' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:cloudflare/Resources/Public/Icons/direct.svg',
    ],
    'cloudflare-offline' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:cloudflare/Resources/Public/Icons/offline.svg',
    ],
    'cloudflare-online' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:cloudflare/Resources/Public/Icons/online.svg',
    ],
    'cloudflare-module' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:cloudflare/Resources/Public/Icons/module-cloudflare.svg',
    ],
    'cloudflare-module-analytics' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:cloudflare/Resources/Public/Icons/module-analytics.svg',
    ],
];
