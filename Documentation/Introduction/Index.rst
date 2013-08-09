.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt
.. include:: Images.txt


.. _introduction:

Introduction
============


.. _what-it-does:

What does it do?
----------------

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


.. _what-is-cloudflare:

What is CloudFlare?
-------------------

CloudFlare protects and accelerates any website online. Once your
website is a part of the CloudFlare community, its web traffic is
routed through their intelligent global network. They automatically
optimize the delivery of your web pages so your visitors get the
fastest page load times and best performance. They also block threats
and limit abusive bots and crawlers from wasting your bandwidth and
server resources. The result: CloudFlare-powered websites see a
significant improvement in performance and a decrease in spam and
other attacks.

|overview-cloudflare|

CloudFlare's system gets faster and smarter as their community of
users grows larger. They have designed the system to scale with their
goal in mind: helping power and protect the entire Internet.

CloudFlare can be used by anyone with a website and their own domain,
regardless of your choice in platform. From start to finish, setup
takes most website owners less than 5 minutes. Adding your website
requires only a simple change to your domain's DNS settings. There is
no hardware or software to install or maintain and you do not need to
change any of your site's existing code. If you are ever unhappy you
can turn CloudFlare off as easily as you turned it on. Their core
service is free and they offer enhanced services for websites who need
extra features like real time reporting or SSL.

Read more on: `https://www.cloudflare.com/overview
<https://www.cloudflare.com/overview>`_.