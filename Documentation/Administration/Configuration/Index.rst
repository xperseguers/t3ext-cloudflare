.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


Configuration
^^^^^^^^^^^^^

This extension comes with a few settings available from the Extension
Manager.


Category Basic
""""""""""""""

- **API Key** : This is the API key made available on your account page
  (`https://www.cloudflare.com/my-account.html
  <https://www.cloudflare.com/my-account.html>`_)

- **Email** : The e-mail address associated with the API key.

- **Domains** : Once the API key and the email are successfully saved
  (be sure to click on "Update" button first), a list of domains (or
  zones in CloudFlare's terminology) handled by the corresponding
  account is rendered. Just tick the corresponding check boxes to
  instruct TYPO3 which domains should get their cache flushed when
  clearing all caches in TYPO3 Backend.


Category Advanced
"""""""""""""""""

- **Cache content over SSL:** This checkbox implements a hook of
  extension `nc\_staticfilecache
  <http://typo3.org/extensions/repository/view/nc_staticfilecache>`_ and
  should be ticked in case you would like to cache content over SSL.
  This is typically useful when securing your website with CloudFlare's
  flexible SSL.

- **Purge single files:** This checkbox allows you to purge single files
  on CloudFlare.  **Beware:** *This is still highly experimental.*

- **Originating IPs** : This checkbox allows you to restore the
  originating IPs.

You should consider restoring originating IPs at the Web Server level
instead. If using Nginx, please read
`https://www.cloudflare.com/wiki/Nginx
<https://www.cloudflare.com/wiki/Nginx>`_ for instructions.

The official list of CloudFlare's reverse-proxy IPs (both IPv4 and
IPv6) can be found on: `https://www.cloudflare.com/ips
<https://www.cloudflare.com/ips>`_.

Beware: If you already are operating your website behind a (local)
reverse-proxy, then you MUST configure TYPO3 correctly with a
configuration like:

.. code-block:: php

	$TYPO3_CONF_VARS['SYS']['reverseProxyIP'] = '10.0.0.5';
	$TYPO3_CONF_VARS['SYS']['reverseProxyHeaderMultiValue'] = 'first';

where 10.0.0.5 is the IP of your reverse-proxy. This is needed in
order for this extension to allow the originating IP to be overridden
based on the HTTP header HTTP\_CF\_CONNECTING\_IP that is only allowed
if the remote IP matches one of the official CloudFlare's reverse-
proxies.

**Proxy Settings**

- **API Endpoint:** An alternate API endpoint/proxy for CloudFlare.

The goal of a proxy for CloudFlare is to solve the problematic of
having your client's domains all managed with a single CloudFlare
account. This is interesting because it lets you lower the fees of the
"Pro" accounts (which are typically useful to secure the connection
with a wildcard SSL certificate). However, CloudFlare does not provide
API credentials on a domain/zone basis but for the whole account which
forces you to use "administrator credentials" when configuring this
TYPO3 extension.

This proxy setting lets you use a proxy for CloudFlare instead of the
real endpoint. The proxy should provide its own authentication
mechanism and then forward the request to the real CloudFlare endpoint
using the administrator credentials.

A sample proxy you may deploy on your own server is part of the
extension and may be downloaded from Forge as well off
`https://git.typo3.org/TYPO3CMS/Extensions/cloudflare.git/blob/HEAD:/Resources/Examples/proxy-cloudflare.php
<https://git.typo3.org/TYPO3CMS/Extensions/cloudflare.git/blob/HEAD:/Resources/Examples/proxy-cloudflare.php>`_.

The configuration takes place at the end of the file:

.. code-block:: php

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

Feel free to enhance it to fit your needs!


Allowing Backend users to clear cache on CloudFlare
"""""""""""""""""""""""""""""""""""""""""""""""""""

You can enable the "flash icon" clear cache command for common Backend
users by adding following code to user's and/or user group's TSconfig:

.. code-block:: typoscript

   options.clearCache.cloudflare = 1
