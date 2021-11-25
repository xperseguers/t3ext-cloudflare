require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
    var CloudflareAnalytics = {
        timeseries: null,
        totals: null,

        createSimpleGraph: function (elementId, key) {
            var data = [];

            Array.prototype.forEach.call(this.timeseries, function (object) {
                data.push({
                    'since': object.since,
                    'data1': object[key].all
                })
            });
            this.createGraph(elementId, key, [
                this.getGraphDefinition('#2f7bbf', TYPO3.lang['dashboard.' + key], 'data1')
            ], data);
        },

        createCacheGraph: function (elementId, key) {
            var data = [];
            Array.prototype.forEach.call(this.timeseries, function (object) {
                data.push({
                    'since': object.since,
                    'data1': object[key].cached,
                    'data2': object[key].uncached,
                })
            });
            this.createGraph(elementId, key, [
                this.getGraphDefinition('#f68b1f', TYPO3.lang['dashboard.cached'], 'data1'),
                this.getGraphDefinition('#2f7bbf', TYPO3.lang['dashboard.uncached'], 'data2')
            ], data);
        },

        createThreatsGraph: function (elementId) {
            var threats = {
                'bic.ban.unknown': {        // Bad browser
                    'enable': false,
                    'color': '#2f7bbf',
                    'name': ''
                },
                'hot.ban.unknown': {        // Blocked hotlink
                    'enable': false,
                    'color': '#666666',
                    'name': ''
                },
                'macro.ban.unknown': {      // Bad IP
                    'enable': false,
                    'color': '#f68b1f',
                    'name': ''
                },
                'macro.chl.captchaFail': {  // Human challenged
                    'enable': false,
                    'color': '#009900',
                    'name': ''
                },
                'macro.chl.jschlFail': {    // Browser challenged
                    'enable': false,
                    'color': '#9545E5',
                    'name': ''
                },
                'user.ban.ip': {            // IP block (user)
                    'enable': false,
                    'color': '#ff3300',
                    'name': ''
                },
            };

            var data = [];
            Array.prototype.forEach.call(this.timeseries, function (object) {
                var item = {
                    'since': object.since
                };

                Array.prototype.forEach.call(threats, function (threat, type) {
                    item[type] = 0;
                });
                Array.prototype.forEach.call(object.threats.type, function (threat, type) {
                    item[type] = threat.all;
                    threats[type].name = threat.name;
                    threats[type].enable = true;
                });
                data.push(item);
            });

            var graphs = [];
            var self = this;
            Array.prototype.forEach.call(threats, function (threat, type) {
                if (threat.enable) {
                    graphs.push(self.getGraphDefinition(threat.color, threat.name, type));
                }
            });

            this.createGraph(elementId, 'threats', graphs, data);
        },

        createGraph: function (elementId, key, graphs, data) {
            AmCharts.makeChart(elementId, {
                'type': 'serial',
                'theme': 'light',
                'creditsPosition': 'bottom-right',
                'categoryField': 'since',
                'dataDateFormat': 'YYYY-MM-DDTJJ:NN:SSZ',
                'categoryAxis': {
                    'parseDates': true,
                    'minPeriod': 'mm'
                },
                'chartCursor': {
                    'categoryBalloonDateFormat': 'DD MMMM, JJ:NN',
                    'cursorPosition': 'mouse'
                },
                'trendLines': [],
                'graphs': graphs,
                'guides': [],
                'valueAxes': [
                    {
                        'title': TYPO3.lang['dashboard.' + key]
                    }
                ],
                'allLabels': [],
                'balloon': {},
                'legend': {
                    'useGraphSettings': true
                },
                'dataProvider': data,
                'usePrefixes': true
            });

            document.getElementById(key + 'C1').innerHTML = this.totals[key].c1;
            document.getElementById(key + 'C2').innerHTML = this.totals[key].c2;
            document.getElementById(key + 'C3').innerHTML = this.totals[key].c3;
        },

        getGraphDefinition: function (color, title, field) {
            return {
                'lineColor': color,
                'lineThickness': 3,
                'fillAlphas': 0.2,
                'bullet': 'round',
                'bulletBorderColor': '#fff',
                'bulletBorderAlpha': .6,
                'title': title,
                'valueField': field,
                'balloonText': '[[title]]: [[value]]'
            };
        },

        createDonut: function (elementId, data, colors) {
            var options = {
                'type': 'pie',
                'theme': 'light',
                'creditsPosition': 'bottom-right',
                'dataProvider': data,
                'titleField': 'title',
                'valueField': 'value',
                'labelRadius': 5,
                'radius': '50%',
                'labelText': '[[title]]',
                'labelsEnabled': false,
                'sequencedAnimation': false
            };
            if (!(typeof colors === 'undefined')) {
                options.colors = colors;
                options.innerRadius = '70%';
            }

            var chart = AmCharts.makeChart(elementId, options);

            if (!(typeof colors === 'undefined')) {
                var total = data[0].value + data[1].value;
                var value = Math.floor(data[0].value * 100 / total);
                chart.addLabel("50%", "42%", "" + value + "%", "middle", 20);
            }
        },

        update: function (zone, since) {
            if (!zone) {
                zone = '#unknown';
            }
            if (!since) {
                since = '#0';
            }

            zone = zone.substring(1);
            since = since.substring(1);

            var self = this;

            new AjaxRequest(TYPO3.settings.ajaxUrls.cloudflare_dashboard)
                .withQueryArguments({zone: zone, since: since})
                .get()
                .then(async function (ajaxResponse, res) {
                    let resolved = await ajaxResponse.resolve();
                    let data = resolved.ressult;
                    if (!data) {
                        console.log('No data retrieved, skip processing');
                        return;
                    }

                    self.timeseries = data.timeseries;
                    self.totals = data.totals;

                    // Update the list of available periods
                    const periods = document.getElementById('period');
                    while (periods.firstChild) {
                        periods.removeChild(el.firstChild);
                    }

                    Array.prototype.forEach.call(data.periods, function (label, value) {
                        let option = document.createElement('option')
                        option.value = value;
                        option.text = label;

                        periods.add(option);
                    });
                    periods.value = since;

                    self.createCacheGraph('chartRequests', 'requests');
                    self.createCacheGraph('chartBandwidth', 'bandwidth');
                    self.createSimpleGraph('chartUniques', 'uniques');
                    self.createThreatsGraph('chartThreats');

                    var donutData = [
                        {
                            'title': TYPO3.lang['dashboard.cached'],
                            'value': data.totals.bandwidth.cached
                        },
                        {
                            'title': TYPO3.lang['dashboard.uncached'],
                            'value': data.totals.bandwidth.uncached
                        }
                    ];
                    self.createDonut('donutBandwidth', donutData, ['#9bca3e', '#ebebeb']);

                    donutData = [];
                    Array.prototype.forEach.call(data.totals.requests.content_type, function (count, type) {
                        donutData.push({
                            'title': type,
                            'value': count
                        });
                    });
                    self.createDonut('donutContentType', donutData);

                    donutData = [
                        {
                            'title': TYPO3.lang['dashboard.encrypted'],
                            'value': data.totals.requests.ssl.encrypted
                        },
                        {
                            'title': TYPO3.lang['dashboard.unencrypted'],
                            'value': data.totals.requests.ssl.unencrypted
                        }
                    ];
                    self.createDonut('donutSsl', donutData, ['#2f7bbf', '#ebebeb']);
                    const blocks = document.querySelectorAll('.blocks small');
                    Array.prototype.forEach.call(blocks, function (el) {
                        el.innerHTML = data.period;
                    });
                })
                .catch(function (reason) {
                    console.error('CloudFlare: Analytics called .catch on %o with arguments: %o', this, arguments);
                });
        }
    };

    var cloudflareEventListeners = function () {
        const tabLinks = document.querySelectorAll('.tabs a');
        Array.prototype.forEach.call(tabLinks, function (tabLink) {
            tabLink.addEventListener('click', function (e) {
                e.preventDefault();

                if (this.parentNode === null) return;

                Array.prototype.filter.call(this.parentNode.parentNode.children, function (child) {
                    child.classList.remove('active');
                });
                this.parentNode.classList.add('active');

                const tabLink = this.getAttribute('href');
                const inactiveTabs = document.querySelectorAll(".tab-content:not(" + tabLink + ")");
                Array.prototype.filter.call(inactiveTabs, function (tab) {
                    tab.classList.remove('show');
                });
                var activeTab = document.querySelector(tabLink);
                activeTab.classList.add('show');

                return false;
            }, false);
        });


        document.getElementById('requests').style.display = '';
        const period = document.querySelector('select[name=period]');
        const zone = document.querySelector('select[name=zone]');

        zone.addEventListener('change', function () {
            CloudflareAnalytics.update(zone.value, period.value);
        });


        period.addEventListener('change', function () {
            CloudflareAnalytics.update(zone.value, period.value);
        });

        // Trigger change event by default for period
        var event = document.createEvent('HTMLEvents');
        event.initEvent('change', true, false);
        period.dispatchEvent(event);
    }

    if (document.readyState !== 'loading') {
        cloudflareEventListeners();
    } else {
        document.addEventListener('DOMContentLoaded', cloudflareEventListeners);
    }
});
