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
 * Implementation example of a proxy to Cloudflare API.
 *
 * Should naturally be copied outside of this directory and adapted
 * as needed. This script is intended to be used as-this and does not
 * depend on anything.
 *
 * @category    Examples
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class cloudflareProxy
{

    protected $email = '';
    protected $key = '';
    protected $clients = [];

    /**
     * Default constructor.
     *
     * @param string $email
     * @param string $key
     */
    public function __construct($email, $key)
    {
        $this->email = $email;
        $this->key = $key;
    }

    /**
     * Adds a client to this proxy.
     *
     * @param string $email
     * @param string $key
     * @param array $allowedIdentifiers
     * @return cloudflareProxy this instance for method chaining
     */
    public function addClient($email, $key, array $allowedIdentifiers)
    {
        $this->clients[$email] = [
            'key' => $key,
            'identifiers' => $allowedIdentifiers,
        ];
        return $this;
    }

    /**
     * Handles a proxy request.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function handleRequest()
    {
        if (!empty($_GET['v']) && (int)$_GET['v'] === 4) {
            return $this->handleRequestV4();
        } else {
            return $this->handleRequestV1();
        }
    }

    /**
     * Handles a proxy request with Cloudflare API v4.
     *
     * @return string JSON-encoded answer
     * @throws \RuntimeException
     * @api v4
     */
    protected function handleRequestV4()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $headers = getallheaders();

        $email = $headers['X-Auth-Email'];
        $key = $headers['X-Auth-Key'];

        if (($email === '' || $key === '')
            || !(isset($this->clients[$email]) && $this->clients[$email]['key'] === $key)) {
            throw new \RuntimeException('Not Authorized', 1440754856);
        }

        $allowedIdentifiers = $this->clients[$email]['identifiers'];
        $data = null;

        $route = ltrim($_GET['route'], '/');
        $parameters = $method === 'GET' ? '' : file_get_contents('php://input');

        if (strpos($route, '?') !== false) {
            list($route, $parameters) = explode('?', ltrim($route, '/'));
        }

        $arguments = explode('/', $route);
        $object = array_shift($arguments);
        if (empty($arguments[0])) {
            $arguments = [];
        }

        switch ($object) {
            case 'zones':
                $data = $this->zones($method, $arguments, $parameters, $allowedIdentifiers);
                break;
            case 'analytics':
                $data = $this->analytics($method, $arguments, $parameters, $allowedIdentifiers);
                break;
        }

        return $data;
    }

    /**
     * Sends a /zones request.
     *
     * @param string $method
     * @param array $arguments
     * @param string $parameters
     * @param array $allowedIdentifiers
     * @return string
     */
    protected function zones($method, array $arguments, $parameters, array $allowedIdentifiers)
    {
        if (!empty($arguments) && !isset($allowedIdentifiers[$arguments[0]])) {
            throw new \RuntimeException('Not Authorized', 1440756109);
        }

        if (strtoupper($method) === 'PATCH') {
            $p = json_decode($parameters, TRUE);
            if (isset($p['plan'])) {
                throw new \RuntimeException('Changing the plan is not authorized', 1440854632);
            }
        }

        // Proxy to Cloudflare
        $json = $this->sendHttpRequest($method, 'zones/' . implode('/', $arguments), $parameters);

        if (empty($arguments)) {
            // Keep only allowed identifiers
            $data = json_decode($json, true);

            $newResult = [];
            foreach ($data['result'] as $zone) {
                if (isset($allowedIdentifiers[$zone['id']])) {
                    $newResult[] = $zone;
                }
            }
            $data['result'] = $newResult;
            $data['result_info']['count'] = count($newResult);
            // Arbitrary total_count value without disclosing the real value
            $data['result_info']['total_count'] = $data['result_info']['total_pages'] * count($newResult);

            $json = json_encode($data);
        }

        return $json;
    }

    /**
     * Sends an /analytics request.
     *
     * @param string $method
     * @param array $arguments
     * @param string $parameters
     * @param array $allowedIdentifiers
     * @return string
     */
    protected function analytics($method, array $arguments, $parameters, array $allowedIdentifiers)
    {
        if (!empty($arguments) && !isset($allowedIdentifiers[$arguments[0]])) {
            throw new \RuntimeException('Not Authorized', 1441355374);
        }

        // Proxy to Cloudflare
        $json = $this->sendHttpRequest($method, 'analytics/' . implode('/', $arguments), $parameters);

        return $json;
    }

    /**
     * This methods sends a custom HTTP request to Cloudflare.
     *
     * @param string $method
     * @param string $url
     * @param string $data
     * @return string
     * @throws \RuntimeException
     * @api v4
     */
    protected function sendHttpRequest($method, $route, $data)
    {
        $headers = [
            'Content-Type: application/json',
            'X-Auth-Key: ' . $this->key,
            'X-Auth-Email: ' . $this->email
        ];
        $url = 'https://api.cloudflare.com/client/v4/' . $route;

        if (!function_exists('curl_init') || !($ch = curl_init())) {
            throw new \RuntimeException('cURL cannot be used', 1440854374);
        }

        if ($method === 'GET') {
            if (!empty($data)) {
                $url .= '?' . $data;
            }
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        //curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if (!($result = curl_exec($ch))) {
            trigger_error(curl_errno($ch));
        }
        curl_close($ch);

        return $result;
    }

    /**
     * Handles a proxy request with Cloudflare API v1.
     *
     * @return string JSON-encoded answer
     * @throws \RuntimeException
     * @api v1
     */
    protected function handleRequestV1()
    {
        $parameters = $GLOBALS['_POST'];

        $email = isset($parameters['email']) ? $parameters['email'] : '';
        $key = isset($parameters['tkn']) ? $parameters['tkn'] : '';

        if (($email === '' || $key === '')
            || !(isset($this->clients[$email]) && $this->clients[$email]['key'] === $key)) {
            throw new \RuntimeException('Not Authorized', 1354810958);
        }

        $allowedDomains = $this->clients[$email]['identifiers'];
        $data = null;

        switch ($parameters['a']) {
            case 'zone_load_multi':
                $data = $this->zone_load_multi($allowedDomains);
                break;
            case 'devmode':
                if (in_array($parameters['z'], $allowedDomains)) {
                    $data = $this->devmode($parameters);
                }
                break;
            case 'fpurge_ts':
                if (in_array($parameters['z'], $allowedDomains)) {
                    $data = $this->fpurge_ts($parameters);
                }
                break;
            case 'zone_file_purge':
                if (in_array($parameters['z'], $allowedDomains)) {
                    $data = $this->zone_file_purge($parameters);
                }
                break;
        }

        return $data;
    }

    /**
     * This lists all domains in a Cloudflare account along with other data.
     *
     * @param array $allowedDomains
     * @return string
     * @api v1
     */
    protected function zone_load_multi(array $allowedDomains)
    {
        $args = [
            'a' => 'zone_load_multi',
        ];
        $data = json_decode($this->POSTv1($args), true);
        $objs = [];
        if ($data['result'] === 'success') {
            foreach ($data['response']['zones']['objs'] as $zone) {
                if (in_array($zone['zone_name'], $allowedDomains)) {
                    $objs[] = $zone;
                }
            }
            $data['response']['zones']['objs'] = $objs;
            $data['response']['zones']['count'] = count($objs);
            $data = json_encode($data);
        }

        return $data;
    }

    /**
     * This function allows you to toggle Development Mode on or off for a particular
     * domain. When Development Mode is on the cache is bypassed. Development mode
     * remains on for 3 hours or until when it is toggled back off.
     *
     * @param array $parameters
     * @param array $allowedDomains
     * @return string
     * @api v1
     */
    protected function devmode(array $parameters)
    {
        $args = [
            'a' => 'devmode',
            'z' => $parameters['z'],
            'v' => $parameters['v'] ? 1 : 0,
        ];
        return $this->POSTv1($args);
    }

    /**
     * This function will purge Cloudflare of any cached files. It may take up to
     * 48 hours for the cache to rebuild and optimum performance to be achieved
     * so this function should be used sparingly.
     *
     * @param array $parameters
     * @return string
     */
    protected function fpurge_ts(array $parameters)
    {
        $args = [
            'a' => 'fpurge_ts',
            'z' => $parameters['z'],
            'v' => 1,
        ];
        return $this->POSTv1($args);
    }

    /**
     * This function will purge a single file from Cloudflare's cache.
     *
     * @param array $parameters
     * @return string
     * @api v1
     */
    protected function zone_file_purge(array $parameters)
    {
        $args = [
            'a' => 'zone_file_purge',
            'z' => $parameters['z'],
            'url' => $parameters['url'],
        ];
        return $this->POSTv1($args);
    }

    /**
     * This methods POSTs data to Cloudflare.
     *
     * @param array $data
     * @return string
     * @throws \RuntimeException
     * @api v1
     */
    protected function POSTv1(array $data)
    {
        $data['email'] = $this->email;
        $data['tkn'] = $this->key;

        if (!function_exists('curl_init') || !($ch = curl_init())) {
            throw new \RuntimeException('cURL cannot be used', 1354811692);
        }

        $url = 'https://www.cloudflare.com/api_json.html';

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        //curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        if (!($result = curl_exec($ch))) {
            trigger_error(curl_errno($ch));
        }
        curl_close($ch);

        return $result;
    }

}

// When using nginx + Fast-CGI before PHP 5.4
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = '';
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Enter your Cloudflare API credentials below
$proxy = new cloudflareProxy(
    'api-email@your-domain.tld',
    '000111222333444555666777888999aaabbbc'
);

// Add a few clients to our proxy
$proxy
    ->addClient(
        'domain@mydomain.tld',
        '1234567890ABCDEF',
        [
            '627aaac32cbff7210660f400a6451ccc' => 'mydomain.tld',
        ]
    )
    ->addClient(
        'other@somedomain.tld',
        'an-arbitrary-k3y',
        [
            '627aaac32cbff7210660f400a6451ccc' => 'somedomain.tld',
            '123aaac32cbff7150660f999a1d2addd' => 'someotherdomain.tld',
        ]
    );

// Actually proxy the request to Cloudflare API
try {
    echo $proxy->handleRequest();
} catch (Exception $e) {
    echo json_encode([
        'result' => 'failure',
        'message' => $e->getMessage(),
    ]);
}
