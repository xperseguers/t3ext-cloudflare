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
						domains.push({
							text: response[i].domain,
							iconCls: 't3-icon t3-icon-extensions t3-icon-extensions-cloudflare ' + response[i].icon,
							menu: {}
						});
					}
					this.domainMenu = new Ext.menu.Menu({
						items: domains
					});

					// Trick for Internet Explorer :-/
					var tooltipTitle = 'Cloudflare Management'
					var tooltipDescription = 'todo...';
					var button = new Ext.Button({
						iconCls: 't3-icon t3-icon-extensions t3-icon-extensions-cloudflare t3-icon-cloudflare-cloudflare',
						menu: TYPO3.Cloudflare.App.domainMenu,
						renderTo: 'cloudflare-manage-action',
						tooltip: '<h1>' + tooltipTitle + '</h1><p>' + tooltipDescription + '</p>'
					});
				}
			});
		}
	}

}

Ext.onReady(function() {
/*
	TYPO3.Cloudflare.Store = new Ext.data.Store({
		storeId: 'beUserSwitchStore',
		autoLoad: false,
		reader: new Ext.data.JsonReader({
			idProperty: 'extkey',
			root: 'data',
			totalProperty: 'length',
			fields:[
				{name:'username'},
				{name:'realName'},
				{name:'admin'},
				{name:'iconCls'},
				{name:'uid'}
			]
		}),
		proxy: new Ext.data.DirectProxy({
			directFn: TYPO3.Ajax.ExtDirect.Cloudflare.getDomains
		})
	});
*/

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
