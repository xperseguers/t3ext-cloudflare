<?php
namespace Causal\Cloudflare\Services;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service to talk to Cloudflare.
 *
 * @category    Services
 * @package     TYPO3
 * @subpackage  tx_cloudflare
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class CloudflareService implements SingletonInterface
{

    /** @var array */
    protected $config;

    /** @var string */
    protected $apiEndpoint = 'https://api.cloudflare.com/client/v4/';

    /**
     * Default constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;

        if (!empty($this->config['apiEndpoint'])) {
            $this->apiEndpoint = $this->config['apiEndpoint'] . '?v=4&route=';
        }
    }

    /**
     * Sends data to Cloudflare.
     *
     * @param string $route
     * @param array $parameters
     * @param string $request
     * @return array
     * @throws \RuntimeException
     */
    public function send($route, array $parameters = [], $request = 'GET')
    {
        if (!trim($this->config['apiKey'])) {
            throw new \RuntimeException('Cannot clear cache on Cloudflare: Invalid apiKey for EXT:cloudflare', 1337770232);
        }

        if (!$this->config['useBearerAuthentication'] && !GeneralUtility::validEmail(trim($this->config['email']))) {
            throw new \RuntimeException('Cannot clear cache on Cloudflare: Invalid email for EXT:cloudflare', 1337770383);
        }

        $url = rtrim($this->apiEndpoint, '/') . '/' . ltrim($route, '/');
        $headers = [
            'Content-Type: application/json',
        ];

        if ($this->config['useBearerAuthentication']) {
            $headers[] = 'Authorization: Bearer ' . trim($this->config['apiKey']);
        } else {
            $headers[] = 'X-Auth-Key: ' . trim($this->config['apiKey']);
            $headers[] = 'X-Auth-Email: ' . trim($this->config['email']);
        }

        if ($request === 'GET') {
            $data = $this->sendHttpRequest($request, $url, $headers, $parameters);
            if ($data['success'] && $data['result_info']['total_pages'] > 1) {
                $accumulatedData = $data;
                for ($i = $data['result_info']['page'] + 1; $i <= $data['result_info']['total_pages']; $i++) {
                    $nextParameters = $parameters;
                    $nextParameters['page'] = $i;
                    $data = $this->sendHttpRequest($request, $url, $headers, $nextParameters);
                    if ($data['success']) {
                        $accumulatedData['result'] = array_merge($accumulatedData['result'], $data['result']);
                        $accumulatedData['result_info']['count'] += $data['result_info']['count'];
                        $accumulatedData['result_info']['total_pages'] = 1;
                    } else {
                        break;
                    }
                }
                $data = $accumulatedData;
            }
        } else {
            $data = $this->sendHttpRequest($request, $url, $headers, $parameters);
        }

        return $data;
    }

    /**
     * Sorts $data using a given sorting key from $data['result'].
     *
     * @param array $data
     * @param string $resultSortingKey
     * @return array
     */
    public function sort(array $data, $resultSortingKey)
    {
        $keyValues = [];
        foreach ($data['result'] as $key => $arr) {
            $keyValues[$key] = $arr[$resultSortingKey];
        }

        array_multisort($keyValues, SORT_ASC, $data['result']);

        return $data;
    }

    /**
     * Sends a custom HTTP request to Cloudflare.
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param array $data
     * @return array JSON payload returned by Cloudflare
     * @throws \RuntimeException
     */
    protected function sendHttpRequest($method, $url, array $headers, array $data)
    {
        if (true || $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'] == '1') {
            if (!function_exists('curl_init') || !($ch = curl_init())) {
                list ($major, $_) = explode('.', phpversion(), 2);
                throw new \RuntimeException('cURL cannot be used. Make sure php' . $major . '-curl is loaded.', 1337673614);
            }

            if ($method === 'GET') {
                if (!empty($data)) {
                    $parameters = '?' . http_build_query($data);
                    if (strpos($url, '?') === false) {
                        $url .= $parameters;
                    } else {
                        // URL is currently proxied
                        $pos = strpos($url, '&route=') + 7;
                        $route = substr($url, $pos) . $parameters;
                        $url = substr($url, 0, $pos) . urlencode($route);
                    }
                }
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, max(0, intval($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlTimeout'])));

            if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']) {
                curl_setopt($ch, CURLOPT_PROXY, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']);

                if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel']) {
                    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel']);
                }
                if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']);
                }
            }

            if (!($result = curl_exec($ch))) {
                trigger_error(curl_errno($ch));
            }
            curl_close($ch);

            return json_decode($result, true);
        } else {
            // TODO with fsockopen()
        }
    }

}
