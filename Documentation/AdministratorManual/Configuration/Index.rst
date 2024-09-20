.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _admin-manual-configuration:

Configuration
-------------

This extension comes with a few settings available from Admin Tools > Settings.


.. _admin-manual-configuration-basic:

Category Basic
^^^^^^^^^^^^^^

- **Use Bearer Authentication (recommended)** : Enables RFC 6750 Bearer
  Authentication. With this setting enabled, Email is no longer a required field
  (:ref:`see below for configuration <admin-manual-configuration-bearer-authentication>`).

- **Email** : The e-mail address associated with the API key.

- **API Key** : This is the API key made available on your account page
  (https://www.cloudflare.com/a/account/my-account)

- **Domains** : Once the API key and the email are successfully saved (be sure
  to click on "Save cloudflare configuration" button first and possibly reload
  the extension configuration modal dialog), a list of domains (or zones in
  Cloudflare's terminology) handled by the corresponding account is rendered.
  Just tick the corresponding check boxes to instruct TYPO3 which domains should
  get their cache flushed when clearing all caches in TYPO3 Backend.

- **Enable Analytics Backend module** : Shows the Analytics module in Backend
  (under "Cloudflare" dedicated section).


.. _admin-manual-configuration-advanced:

Category Advanced
^^^^^^^^^^^^^^^^^

- **Purge individual files by URL:** This checkbox allows you to purge individual
  files on Cloudflare's cache using an URL.
  **Beware:** *This is still highly experimental.*

- **Purge cache by Cache-Tag:** This checkbox allows you to purge individual
  files on Cloudflare's cache using the associated Cache-Tag.
  **Beware: This option requires an Enterprise account.**

  .. note::
     In addition you will need to create a page rule asking to cache
     **everything** because HTML content is not cached by default.

- **Originating IPs** : This checkbox allows you to restore the originating IPs.

You should consider restoring originating IPs at the Web Server level instead.
If using Nginx, please read
https://support.cloudflare.com/hc/en-us/articles/200170706-Does-CloudFlare-have-an-IP-module-for-Nginx-
for instructions.

The official list of Cloudflare's reverse-proxy IPs (both IPv4 and IPv6) can be
found on https://www.cloudflare.com/ips.

.. caution::

   If you already are operating your website behind a (local) reverse-proxy,
   then you MUST configure TYPO3 correctly with a configuration like:

   .. code-block:: php

      $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'] = '10.0.0.5';
      $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyHeaderMultiValue'] = 'first';

   where ``10.0.0.5`` is the IP of your reverse-proxy. This is needed in order
   for this extension to allow the originating IP to be overridden based on the
   HTTP header ``HTTP_CF_CONNECTING_IP`` that is only allowed if the remote IP
   matches one of the official Cloudflare's reverse-proxies.


.. _admin-manual-configuration-clearcache:

Allowing Backend users to clear cache on Cloudflare
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You can enable the "flash icon" clear cache command for common Backend users by
adding following code to user's and/or user group's TSconfig:

.. code-block:: typoscript

   options.clearCache.cloudflare = 1


.. _admin-manual-configuration-bearer-authentication:

Configuration of the Bearer Authentication
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

- Go to https://dash.cloudflare.com/profile/api-tokens
- Under the section "API Tokens", click the button "Create Token"
- Choose to create a custom token instead of a template
- This extension requires following permissions:

  - **Zone / Zone / Read** *(to be able to select the zone while configuring the
    extension)*
  - **Zone / Zone Settings / Edit** *(to toggle Development mode)*
  - **Zone / Cache Purge / Purge** *(for obvious reason)*
  - **Zone / Analytics / Read** *(for the Backend module showing statistics)*

Naturally you should restrict your token to one or more zones (zone resources).
