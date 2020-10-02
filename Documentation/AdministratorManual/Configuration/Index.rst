.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _admin-manual-configuration:

Configuration
-------------

This extension comes with a few settings available from the Extension Manager.


.. _admin-manual-configuration-basic:

Category Basic
^^^^^^^^^^^^^^
- **Use Bearer Authentication** : Enables RFC 6750 Bearer Authentication. With this setting enabled, Email is no longer
  a required field.

- **Email** : The e-mail address associated with the API key.

- **API Key** : This is the API key made available on your account page (https://www.cloudflare.com/a/account/my-account)

- **Domains** : Once the API key and the email are successfully saved (be sure to click on "Update" button first), a
  list of domains (or zones in Cloudflare's terminology) handled by the corresponding account is rendered. Just tick the
  corresponding check boxes to instruct TYPO3 which domains should get their cache flushed when clearing all caches in
  TYPO3 Backend.


.. _admin-manual-configuration-advanced:

Category Advanced
^^^^^^^^^^^^^^^^^

- **Cache content over SSL:** This checkbox implements a hook of extension
  `nc\_staticfilecache <https://typo3.org/extensions/repository/view/nc_staticfilecache>`_ and should be ticked in case
  you would like to cache content over SSL. This is typically useful when securing your website with Cloudflare's
  flexible SSL.

- **Purge individual files by URL:** This checkbox allows you to purge individual files on Cloudflare's cache using an
  URL.  **Beware:** *This is still highly experimental.*

- **Purge cache by Cache-Tag:** This checkbox allows you to purge individual files on Cloudflare's cache using the
  associated Cache-Tag.  **Beware: This option requires an Enterprise account.**

  .. note::
     In addition you will need to create a page rule asking to cache **everything** because HTML content is not
     cached by default.

- **Originating IPs** : This checkbox allows you to restore the originating IPs.

You should consider restoring originating IPs at the Web Server level instead. If using Nginx, please read
https://support.cloudflare.com/hc/en-us/articles/200170706-Does-CloudFlare-have-an-IP-module-for-Nginx- for
instructions.

The official list of Cloudflare's reverse-proxy IPs (both IPv4 and IPv6) can be found on https://www.cloudflare.com/ips.

.. caution::
	If you already are operating your website behind a (local) reverse-proxy, then you MUST configure TYPO3 correctly
	with a configuration like:

	.. code-block:: php

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'] = '10.0.0.5';
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyHeaderMultiValue'] = 'first';

	where ``10.0.0.5`` is the IP of your reverse-proxy. This is needed in order for this extension to allow the
	originating IP to be overridden based on the HTTP header ``HTTP_CF_CONNECTING_IP`` that is only allowed if the
	remote IP matches one of the official Cloudflare's reverse-proxies.


**Proxy Settings**

- **API Endpoint:** An alternate API endpoint/proxy for Cloudflare.

The goal of a proxy for Cloudflare is to solve the problematic of having your client's domains all managed with a single
Cloudflare account without having to share your "administrator credentials" with your clients. In fact, Cloudflare does
not provide API credentials on a domain/zone basis but for the whole account which is why you are forced to use
"administrator credentials" when configuring this TYPO3 extension.

This proxy setting lets you use a proxy for Cloudflare instead of the real endpoint. The proxy should provide its own
authentication mechanism and then forward the request to the real Cloudflare endpoint using the administrator credentials.

.. tip::
	A sample proxy you may deploy on your own server is part of the extension and may be downloaded from Forge as well
	off https://git.typo3.org/TYPO3CMS/Extensions/cloudflare.git/blob/HEAD:/Resources/Examples/proxy-cloudflare.php.

The configuration takes place at the end of the file:

.. code-block:: php

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
	    )
	;

Feel free to enhance it to fit your needs!


.. _admin-manual-configuration-clearcache:

Allowing Backend users to clear cache on Cloudflare
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You can enable the "flash icon" clear cache command for common Backend users by adding following code to user's and/or
user group's TSconfig:

.. code-block:: typoscript

	options.clearCache.cloudflare = 1
