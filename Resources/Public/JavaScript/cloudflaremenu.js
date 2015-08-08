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

Ext.ns('TYPO3', 'TYPO3.configuration');

/**
 * Class to handle the CloudFlare menu (TYPO3 6.2)
 */
var CloudflareMenu = Class.create({

    /**
     * registers for resize event listener and executes on DOM ready
     */
    initialize: function () {

        Ext.onReady(function () {
            Event.observe(
                window, 'resize',
                function () {
                    TYPO3BackendToolbarManager.positionMenu('cloudflare-menu');
                }
            );
            TYPO3BackendToolbarManager.refreshAll();
            Event.observe('cloudflare-menu', 'click', this.toggleMenu);
        }, this);
    },

    /**
     * toggles the visibility of the menu and places it under the toolbar icon
     */
    toggleMenu: function (event) {
        var toolbarItem = $$('#cloudflare-menu > a')[0];
        var menu = $$('#cloudflare-menu .toolbar-item-menu')[0];
        toolbarItem.blur();

        if (!toolbarItem.hasClassName('toolbar-item-active')) {
            toolbarItem.addClassName('toolbar-item-active');
            TYPO3.Ajax.ExtDirect.CloudflareToolbarMenu.retrieveCloudFlareStatus({}, function (response) {
                menu.innerHTML = response.html;
                Effect.Appear(menu, {duration: 0.2});
                TYPO3BackendToolbarManager.hideOthers(toolbarItem);
            });
        } else {
            toolbarItem.removeClassName('toolbar-item-active');
            Effect.Fade(menu, {duration: 0.1});
        }

        if (event) {
            Event.stop(event);
        }
    },

    /**
     * toggles the development mode for a zone
     */
    toggleDevelopmentMode: function (zone, active) {
        var toolbarItemIcon = $$('#cloudflare-menu .toolbar-item span.t3-icon')[0];
        var spinner = new Element('span').addClassName('spinner');
        var oldIcon = toolbarItemIcon.replace(spinner);

        TYPO3.Ajax.ExtDirect.CloudflareToolbarMenu.toggleDevelopmentMode({
            zone: zone,
            active: active
        }, function (response) {
            spinner.replace(oldIcon);
        });
    }

});

var TYPO3BackendCloudflareMenu = new CloudflareMenu();
//TYPO3BackendCloudflareMenu.initialize();