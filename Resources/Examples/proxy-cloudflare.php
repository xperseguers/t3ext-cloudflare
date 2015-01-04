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
 * Implementation example of a proxy to CloudFlare API.
 *
 * Should naturally be copied outside of this directory and adapted
 * as needed. This script is intended to be as-this and does not
 * depend on anything.
 *
 * @category    Examples
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class cloudflareProxy {

	protected $email = '';
	protected $token = '';
	protected $clients = array();

	/**
	 * Default constructor.
	 *
	 * @param string $email
	 * @param string $token
	 */
	public function __construct($email, $token) {
		$this->email = $email;
		$this->token = $token;
	}

	/**
	 * Adds a client to this proxy.
	 *
	 * @param string $email
	 * @param string $token
	 * @param array $allowedDomains
	 * @return cloudflareProxy this instance for method chaining
	 */
	public function addClient($email, $token, array $allowedDomains) {
		$this->clients[$email] = array(
			'token'   => $token,
			'domains' => $allowedDomains,
		);
		return $this;
	}

	/**
	 * Handles a proxy request.
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function handleRequest() {
		$parameters = $GLOBALS['_POST'];

		$email = isset($parameters['email']) ? $parameters['email'] : '';
		$token = isset($parameters['tkn']) ? $parameters['tkn'] : '';

		if ($email !== '' && $token !== '') {
			if (isset($this->clients[$email]) && $this->clients[$email]['token'] === $token) {
				$allowedDomains = $this->clients[$email]['domains'];
				$data = NULL;

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

				if ($data !== NULL) {
					return $data;
				}
			}
		}

		throw new RuntimeException('Not Authorized', 1354810958);
	}

	/**
	 * This lists all domains in a CloudFlare account along with other data.
	 *
	 * @param array $allowedDomains
	 * @return string
	 */
	protected function zone_load_multi(array $allowedDomains) {
		$args = array(
			'a' => 'zone_load_multi',
		);
		$data = json_decode($this->POST($args), TRUE);
		$objs = array();
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
	 */
	protected function devmode(array $parameters) {
		$args = array(
			'a' => 'devmode',
			'z' => $parameters['z'],
			'v' => $parameters['v'] ? 1 : 0,
		);
		return $this->POST($args);
	}

	/**
	 * This function will purge CloudFlare of any cached files. It may take up to
	 * 48 hours for the cache to rebuild and optimum performance to be achieved
	 * so this function should be used sparingly.
	 *
	 * @param array $parameters
	 * @return string
	 */
	protected function fpurge_ts(array $parameters) {
		$args = array(
			'a' => 'fpurge_ts',
			'z' => $parameters['z'],
			'v' => 1,
		);
		return $this->POST($args);
	}

	/**
	 * This function will purge a single file from CloudFlare's cache.
	 *
	 * @param array $parameters
	 * @return string
	 */
	protected function zone_file_purge(array $parameters) {
		$args = array(
			'a'   => 'zone_file_purge',
			'z'   => $parameters['z'],
			'url' => $parameters['url'],
		);
		return $this->POST($args);
	}

	/**
	 * This methods POSTs data to CloudFlare.
	 *
	 * @param array $data
	 * @return string
	 * @throws RuntimeException
	 */
	protected function POST(array $data) {
		$data['email'] = $this->email;
		$data['tkn'] = $this->token;

		if (!function_exists('curl_init') || !($ch = curl_init())) {
			throw new RuntimeException('cURL cannot be used', 1354811692);
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

// Enter your CloudFlare API credentials below
$proxy = new cloudflareProxy(
	'api-email@your-domain.tld',
	'000111222333444555666777888999aaabbbc'
);

// Add a few clients to our proxy
$proxy
	->addClient(
		'domain@mydomain.tld',
		'1234567890ABCDEF',
		array(
			'mydomain.tld'
		)
	)
	->addClient(
		'other@somedomain.tld',
		'an-arbitrary-k3y',
		array(
			'somedomain.tld',
			'someotherdomain.tld',
		)
	)
;

// Actually proxy the request to CloudFlare API
try {
	echo $proxy->handleRequest();
} catch (Exception $e) {
	echo json_encode(array(
		'result' => 'failure',
		'message' => $e->getMessage(),
	));
}
