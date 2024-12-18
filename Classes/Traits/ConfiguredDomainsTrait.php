<?php
declare(strict_types=1);

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

namespace Causal\Cloudflare\Traits;

use Causal\Cloudflare\Services\CloudflareService;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait ConfiguredDomainsTrait
{
    // Expected to be initialized during class construction
    protected CloudflareService $cloudflareService;

    protected function getDomains(): array
    {
        $config = $this->cloudflareService->getConfiguration();

        if ((new Typo3Version())->getMajorVersion() >= 12) {
            $domains = [];
            $numberOfDomains = (int)($config['domains_count'] ?? 0);
            for ($i = 0; $i < $numberOfDomains; $i++) {
                $value = $config['domains_' . $i] ?? '';
                if ($value !== '') {
                    $domains[] = $value;
                }
            }
        } else {
            $domains = GeneralUtility::trimExplode(',', $config['domains'] ?? '', true);
        }

        return $domains;
    }
}
