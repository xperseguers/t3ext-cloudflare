/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Xavier Perseguers <xavier@causal.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

Ext.ns('TYPO3.Cloudflare');

TYPO3.Cloudflare.App = {
	menuLoaded: false,
	userMenu: null,

	buildMenu: function(el) {

		if (!this.menuLoaded) {
			TYPO3.Ajax.ExtDirect.CloudflareToolbarMenu.retrieveCloudFlareStatus({},{
				scope: this,
				callback: function(response) {
					var domains = [];
					for (var i = 0; i < response.length; i++) {
						var record = response[i];
						var domainMenu = new Ext.menu.Item({
							text: record.domain,
							iconCls: record.icon,
							menu: {}
						});
						for (var o = 0; o < record.operations.length; o++) {
							domainMenu.menu.add({
								text: record.operations[o].text,
								iconCls: record.operations[o].icon,
								handler: eval(record.operations[o].fn),
								disabled: record.operations[o].disabled,
								handlerParams: record.operations[o].params,
								zoneMenu: domainMenu,
								scope: this
							});
						}
						domains.push(domainMenu);
					}
					this.domainMenu = new Ext.menu.Menu({
						items: domains
					});

					var tooltipTitle = 'Cloudflare';
					var tooltipDescription = 'This module allows you to manage your domains on CloudFlare.';
					var button = new Ext.Button({
						iconCls: 't3-icon t3-icon-extensions t3-icon-extensions-cloudflare t3-icon-cloudflare-cloudflare',
						menu: TYPO3.Cloudflare.App.domainMenu,
						renderTo: 'cloudflare-manage-action',
						tooltip: '<h1>' + tooltipTitle + '</h1><p>' + tooltipDescription + '</p>'
					});
				}
			});
		}
	},

	toggleDevelopmentMode: function(item, event) {
		TYPO3.Ajax.ExtDirect.CloudflareToolbarMenu.toggleDevelopmentMode({
			zone: item.handlerParams.zone,
			active: item.handlerParams.active
		}, function(response) {
			if (response.result == 'success') {
				item.handlerParams.active = 1 - item.handlerParams.active;
				var img = document.getElementById(Ext.fly(item.zoneMenu.id).child('img').id);
				img.className = 'x-menu-item-icon ' + response.icon;
			}
		});
	},

	clearCache: function(item, event) {
		TYPO3.Ajax.ExtDirect.CloudflareToolbarMenu.clearCache({
			zone: item.handlerParams.zone
		});
	}

}

Ext.onReady(function() {
	var dh = Ext.DomHelper;

	var spec = {
		id: 'cloudflare-manage',
		tag: 'li',
		cls: 'my-list',
		// append children after creating
		children: [
			{
				tag: 'div',
				id: 'cloudflare-manage-action',
				style: 'margin:0 5px;'
			}
		]
	};

	dh.insertAfter('clear-cache-actions-menu', spec);
	TYPO3.Backend.Topbar.doLayout();

	TYPO3.Cloudflare.App.buildMenu();
});
