/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import Icons from '@typo3/backend/icons.js';

/**
 * Module: @causal/cloudflare/toolbar
 * @exports @causal/cloudflare/toolbar
 */
class Toolbar {
    create (options) {
        this.options = options || {};

        this.selectors = {
            containerSelector: '#causal-cloudflare-backend-toolbaritems-cloudflaretoolbaritem',
            menuContainerSelector: '.dropdown-menu',
            //menuItemSelector: '.dropdown-menu div.dropdown-table-row',
            toolbarIconSelector: '.dropdown-toggle span.t3js-icon',
            //counterSelector: '#tx-cloudflare-counter'
        };

        this.initialize();
        this.updateMenu();
    }

    initialize() {
        const that = this;

        document.querySelectorAll('a.cloudflare-zone').forEach(function (item, idx) {
            item.addEventListener('click', function (event) {
                event.preventDefault();
                that.toggleDevelopmentMode(item);
            }.bind(this));
        })
    }

    updateMenu() {
        const that = this;
        const iconSelector = this.selectors.containerSelector + ' ' + this.selectors.toolbarIconSelector;
        const menuSelector = this.selectors.containerSelector + ' ' + this.selectors.menuContainerSelector;
        const toolbarItemIcon = document.querySelector(iconSelector);

        Icons.getIcon('spinner-circle-light', Icons.sizes.small).then(function (icon) {
            document.querySelector(iconSelector).outerHTML = icon;
        });

        fetch(TYPO3.settings.ajaxUrls['cloudflare_rendermenu'])
            .then(response => response.json())
            .then(data => {
                document.querySelector(iconSelector).outerHTML = toolbarItemIcon.outerHTML;
                document.querySelector(menuSelector).innerHTML = data.html;
                that.initialize();
            });
    }

    toggleDevelopmentMode(item) {
        const that = this;
        const zone = item.dataset.zone;
        const active = item.dataset.active;

        const iconSelector = this.selectors.containerSelector + ' ' + this.selectors.toolbarIconSelector;
        const toolbarItemIcon = document.querySelector(iconSelector);

        Icons.getIcon('spinner-circle-light', Icons.sizes.small).then(function (icon) {
            document.querySelector(iconSelector).outerHTML = icon;
        });

        fetch(
            TYPO3.settings.ajaxUrls['cloudflare_toggledev'],
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    zone: zone,
                    active: active
                })
            })
            .then(response => response.json())
            .then(data => {
                document.querySelector(iconSelector).outerHTML = toolbarItemIcon.outerHTML;
                that.updateMenu();
            });
    }
}

export default new Toolbar();
