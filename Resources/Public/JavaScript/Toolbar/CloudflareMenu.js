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
 * main JS part taking care of
 *  - operating on Cloudflare domains
 *  - updating the menu
 */
define([
    'jquery',
    'TYPO3/CMS/Backend/Icons'
    ], function ($, Icons) {

    var CloudflareMenu = {
        options: {
            containerSelector: '#causal-cloudflare-backend-toolbaritems-cloudflaretoolbaritem',
            menuContainerSelector: '.dropdown-menu',
            menuItemSelector: '.dropdown-menu li',
            toolbarIconSelector: '.dropdown-toggle span.t3js-icon',
            counterSelector: '#tx-cloudflare-counter'
        }
    };

    /**
     * Displays the menu and does the AJAX call to the TYPO3 backend
     */
    CloudflareMenu.updateMenu = function () {
        var $toolbarItemIcon = $(CloudflareMenu.options.toolbarIconSelector, CloudflareMenu.options.containerSelector),
            $existingIcon = $toolbarItemIcon.clone();

        Icons.getIcon('spinner-circle-light', Icons.sizes.small).done(function(spinner) {
            $toolbarItemIcon.replaceWith(spinner);
        });

        $.ajax({
            url: TYPO3.settings.ajaxUrls['cloudflare_rendermenu'],
            type: 'post',
            cache: false,
            success: function (data) {
                $(CloudflareMenu.options.toolbarIconSelector, CloudflareMenu.options.containerSelector).replaceWith($existingIcon);
                $(CloudflareMenu.options.containerSelector).find(CloudflareMenu.options.menuContainerSelector).html(data.html);
                CloudflareMenu.updateNumberOfDomainsInDevelopmentMode();
            }
        });
    };

    /**
     * Updates the number of domains in development mode in the toolbar.
     */
    CloudflareMenu.updateNumberOfDomainsInDevelopmentMode = function () {
        var num = $(CloudflareMenu.options.containerSelector).find(CloudflareMenu.options.menuItemSelector).filter('li[data-zone-status=dev-mode]').length;
        $(CloudflareMenu.options.counterSelector).text(num).toggle(num > 0);
    };

    CloudflareMenu.toggleDevelopmentMode = function (zone, active) {
        var $toolbarItemIcon = $(CloudflareMenu.options.toolbarIconSelector, CloudflareMenu.options.containerSelector),
            $existingIcon = $toolbarItemIcon.clone();

        Icons.getIcon('spinner-circle-light', Icons.sizes.small).done(function(spinner) {
            $toolbarItemIcon.replaceWith(spinner);
        });

        TYPO3.CloudflareMenu.toggleMenu();

        $.ajax({
            url: TYPO3.settings.ajaxUrls['cloudflare_toggledev'],
            type: 'post',
            data: {
                zone: zone,
                active: active
            },
            cache: false,
            success: function (data) {
                $(CloudflareMenu.options.toolbarIconSelector, CloudflareMenu.options.containerSelector).replaceWith($existingIcon);
                CloudflareMenu.updateMenu();
            }
        });
    },

    /**
     * Closes the menu (e.g. when clicked on an item)
     */
    CloudflareMenu.toggleMenu = function () {
        $(CloudflareMenu.options.containerSelector).toggleClass('open');
    };

    /**
     * Initializes and return the Cloudflare object
     */
    return function () {
        $(document).ready(function () {
            CloudflareMenu.updateMenu();
        });

        TYPO3.CloudflareMenu = CloudflareMenu;
        return CloudflareMenu;
    }();
});
