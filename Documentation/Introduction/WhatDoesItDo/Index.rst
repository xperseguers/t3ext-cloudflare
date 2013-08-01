.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


What does it do?
^^^^^^^^^^^^^^^^

This extension lets you flush cache on CloudFlare when content changes
in your TYPO3 website and toggle "Developer Mode" for your domains
from within TYPO3 Backend.

CloudFlare introduced a mechanism to purge single files instead of
only allowing to flush all caches of a domain. This feature must be
explicitly activated in the advanced configuration section. Even if
activated, a full cache flush will be sent whenever your
``TCEMAIN.clearCacheCmd`` is set to "``all``".

As CloudFlare acts a reverse-proxy for your website, originating IPs
get replaced by the ones from the CloudFlare's reverse proxies. TYPO3
lets you fix it but as they are numerous proxy servers, the
configuration may be tedious. The best method is to restore the
originating IP at the Web Server level but sometimes this is not
possible. This is the reason why, in addition to flushing cache on
CloudFlare, this extension lets the user restore the originating IP by
ticking a configuration checkbox.