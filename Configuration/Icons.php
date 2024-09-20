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

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;

return [
    'cloudflare-icon' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:cloudflare/Resources/Public/Icons/cloudflare-16.png',
    ],
    'cloudflare-direct' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:cloudflare/Resources/Public/Icons/direct-16.png',
    ],
    'cloudflare-offline' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:cloudflare/Resources/Public/Icons/offline-16.png',
    ],
    'cloudflare-online' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:cloudflare/Resources/Public/Icons/online-16.png',
    ],
    'cloudflare-module' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:cloudflare/Resources/Public/Icons/module-cloudflare.png',
    ],
];
