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

/**
 * Module: @causal/cloudflare/toolbar
 * @exports @causal/cloudflare/toolbar
 */
class Toolbar {
    create (options) {
        this.options = options || {};
        this.initialize();
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

    toggleDevelopmentMode(item) {
        const zone = item.dataset.zone;
        const active = item.dataset.active;

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
                console.log(data);
            });
    }
}

export default new Toolbar();
