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

// https://dev.to/bmsvieira/vanilla-js-fadein-out-2a6o
function fadeIn(el, display) {
    el.style.opacity = 0;
    el.style.display = display || "block";
    (function fade() {
        var val = parseFloat(el.style.opacity);
        if (!((val += .1) > 1)) {
            el.style.opacity = val;
            requestAnimationFrame(fade);
        }
    })();
};

class ModuleAnalytics {
    create(options) {
        this.options = options || {};

        this.initialize();
    }

    initialize() {
        document.querySelectorAll('.tabs a').forEach(function (item, idx) {
            item.addEventListener('click', function (event) {
                event.preventDefault();
                for (let sibling of item.parentElement.parentElement.children) {
                    sibling.classList.remove('active');
                }
                item.parentElement.classList.add('active');
                const tab = document.querySelector(item.getAttribute('href'));
                document.querySelectorAll('.tab-content').forEach(function (tabContent) {
                    if (tabContent !== tab) {
                        tabContent.style.display = 'none';
                    }
                });
                fadeIn(tab);
            }.bind(this));
        });
    }
}

export default new ModuleAnalytics();
