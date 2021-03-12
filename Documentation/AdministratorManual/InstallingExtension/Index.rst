.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _admin-manual-install:

Installing the extension
------------------------

With thousands of sites on the internet using TYPO3, many TYPO3 sites have
decided to use Cloudflare to make their site faster with this
`free CDN <https://www.cloudflare.com/cdn/>`__ and to make the site more secure
with their `security services <https://www.cloudflare.com/security/>`__.

We will cover the recommended first steps so that any TYPO3 administrator should
be able to get Cloudflare up and running in a few minutes.


.. _admin-manual-install-step1:

Step 1
^^^^^^

Install the
`Cloudflare TYPO3 extension <https://extensions.typo3.org/extension/cloudflare>`__
to restore visitor IP. Since Cloudflare acts as a proxy for sites, Cloudflare's
IPs are going to show in your logs, unless you install something to restore the
original visitor IP.

This extension can be installed through the typical TYPO3 installation process
using the Extension Manager or using
`composer <https://packagist.org/packages/causal/cloudflare>`__, if you prefer.


.. _admin-manual-install-step2:

Step 2
^^^^^^

Review your basic security settings.

If you have a site that is frequently the target of spam or botnet attacks,
changing your security level to a higher setting will help further reduce the
amount of spam you get on your site. Cloudflare defaults all users to a medium
setting when you first add the domain to Cloudflare.

Why do this? If you want your site to have less security and protection from
various attacks, then you would want to change your setting to a lower level
(please keep in mind this makes your site more vulnerable). If you want your
site to have higher security, please keep in mind that you may get more false
positives from visitors complaining about a challenge page that they have to
pass to enter your site.


.. _admin-manual-install-step3:

Step 3
^^^^^^

To do so, create a
`Page Rule <https://support.cloudflare.com/hc/en-us/articles/200168306-Is-there-a-tutorial-for-Page-Rules->`__
to exclude the `typo3` sections from Cloudflare's caching and performance
features. You can access Page Rules in your Cloudflare dashboard.

::

    URL pattern: www.example.com/typo3/*
    Performance: off
