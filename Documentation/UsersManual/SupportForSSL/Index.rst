.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _users-manual-ssl:

Support for SSL
---------------

SSL support for Cloudflare originally required a Business plan (formerly "Pro
account") but is now freely available to everyone, including free accounts.
Please read
https://support.cloudflare.com/hc/en-us/articles/200170336-How-do-I-upgrade-to-a-Business-Plan
if you want to upgrade your FREE account.

Cloudflare supports either Full SSL (strict) or Flexible SSL. Full SSL requires
your web server to run over SSL whereas Flexible SSL will only use SSL from the
client to Cloudflare but then your web server will still operate on port 80.
This extension is able to deal with Flexible SSL and will automatically set the
HTTPS header to "``on``" if Flexible SSL is detected, allowing you to generate
links containing the "``https://``" prefix.

You may enforce SSL either at the Web Server level or using Page Rules in
Cloudflare. If using Apache, please read
https://support.cloudflare.com/hc/en-us/articles/200170536-How-do-I-redirect-HTTPS-traffic-with-Flexible-SSL-and-Apache.



.. _users-manual-ssl-full:

Full SSL
^^^^^^^^

As explained full SSL means Cloudflare provides its own wildcard certificate for
your end-users but still connects using SSL to your server. This is of course
the most secured option. The common problem with SSL on your own servers is when
having virtual hosts (multiple domains on the same IP).

Year ago I successfully checked if Cloudflare would support
:abbr:`SNI (Server Name Indication)` (an extension to the TLS protocol that
indicates what hostname the client is attempting to connect to at the start of
the handshaking process) and self-signed certificates and this is the case.

Read more:

- `Nginx configuration <http://nginx.org/en/docs/http/configuring_https_servers.html#sni>`_
- `Apache configuration <http://www.rackspace.com/knowledge_center/article/serving-secure-sites-with-sni-on-apache>`_

.. note::

   If you choose "Full SSL (strict)" instead of "Full SSL", you will not be able
   to use a self-signed certificate but may generate proper edge certificate
   for your zone within Cloudflare's dashboard.
