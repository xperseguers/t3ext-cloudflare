.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


Support for SSL
^^^^^^^^^^^^^^^

SSL support for CloudFlare requires a Pro account. Please read
`http://support.cloudflare.com/cgi/kb/pro-accounts/how-do-i-upgrade-
to-a-pro-account <http://support.cloudflare.com/cgi/kb/pro-accounts
/how-do-i-upgrade-to-a-pro-account>`_ if you want to upgrade your FREE
account.

CloudFlare supports either Full SSL or Flexible SSL. Full SSL requires
your web server to run over SSL whereas Flexible SSL will only use SSL
from the client to CloudFlare but then your web server will still
operate on port 80. This extension is able to deal with Flexible SSL
and will automatically set the HTTPS header to "``on``" if Flexible SSL is
detected, allowing you to generate links containing the "``https://``"
prefix.

You may enforce SSL either at the Web Server level or using Page Rules
in CloudFlare. If using Apache, please read
`http://support.cloudflare.com/kb/pro-accounts/how-do-i-redirect-
https-traffic-with-flexible-ssl-and-apache
<http://support.cloudflare.com/kb/pro-accounts/how-do-i-redirect-
https-traffic-with-flexible-ssl-and-apache>`_.
