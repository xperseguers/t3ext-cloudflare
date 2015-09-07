CloudflareAnalytics = {
    labels: {
        requests: '',
        bandwidth: '',
        uniques: '',
        threats: '',
        cached: '',
        uncached: '',
        encrypted: '',
        unencrypted: ''
    },

    timeseries: null,
    totals: null,

    createSimpleGraph: function (elementId, key) {
        var data = [];
        $.each(this.timeseries, function (index, object) {
            data.push({
                'since': object.since,
                'data1': object[key].all
            })
        });
        this.createGraph(elementId, key, [
            this.getGraphDefinition('#2f7bbf', this.labels[key], 'data1')
        ], data);
    },

    createCacheGraph: function (elementId, key) {
        var data = [];
        $.each(this.timeseries, function (index, object) {
            data.push({
                'since': object.since,
                'data1': object[key].cached,
                'data2': object[key].uncached,
            })
        });
        this.createGraph(elementId, key, [
            this.getGraphDefinition('#f68b1f', this.labels.cached, 'data1'),
            this.getGraphDefinition('#2f7bbf', this.labels.uncached, 'data2')
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
        $.each(this.timeseries, function (index, object) {
            var item = {
                'since': object.since
            };
            $.each(threats, function (type, threat) {
                item[type] = 0;
            });
            $.each(object.threats.type, function (type, threat) {
                item[type] = threat.all;
                threats[type].name = threat.name;
                threats[type].enable = true;
            });
            data.push(item);
        });

        var graphs = [];
        var self = this;
        $.each(threats, function (type, threat) {
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
                    'title': this.labels[key]
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

        $('#' + key + 'C1').html(this.totals[key].c1);
        $('#' + key + 'C2').html(this.totals[key].c2);
        $('#' + key + 'C3').html(this.totals[key].c3);
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
        var self = this;

        $.ajax({
            url: dataUrl.replace(/_ZONE_/, zone).replace(/_SINCE_/, since),
            success: function (data) {
                self.timeseries = data.timeseries;
                self.totals = data.totals;

                // Update the list of available periods
                var $periods = $('#period');
                $periods.empty();
                $.each(data.periods, function (value, label) {
                    $periods.append($('<option></option>').attr('value', value).text(label));
                });
                $periods.val(since);

                self.createCacheGraph('chartRequests', 'requests');
                self.createCacheGraph('chartBandwidth', 'bandwidth');
                self.createSimpleGraph('chartUniques', 'uniques');
                self.createThreatsGraph('chartThreats');

                var donutData = [
                    {
                        'title': self.labels.cached,
                        'value': data.totals.bandwidth.cached
                    },
                    {
                        'title': self.labels.uncached,
                        'value': data.totals.bandwidth.uncached
                    }
                ];
                self.createDonut('donutBandwidth', donutData, ['#9bca3e', '#ebebeb']);

                donutData = [];
                $.each(data.totals.requests.content_type, function (type, count) {
                    donutData.push({
                        'title': type,
                        'value': count
                    });
                });
                self.createDonut('donutContentType', donutData);

                donutData = [
                    {
                        'title': self.labels.encrypted,
                        'value': data.totals.requests.ssl.encrypted
                    },
                    {
                        'title': self.labels.unencrypted,
                        'value': data.totals.requests.ssl.unencrypted
                    }
                ];
                self.createDonut('donutSsl', donutData, ['#2f7bbf', '#ebebeb']);

                $('.blocks small').html(data.period);
            }
        });
    }
};

$(document).ready(function () {
    $('.tabs a').click(function (event) {
        event.preventDefault();
        $(this).parent().addClass('active');
        $(this).parent().siblings().removeClass('active');
        var tab = $(this).attr('href');
        $('.tab-content').not(tab).css('display', 'none');
        $(tab).fadeIn();
    });
    $('#requests').fadeIn();

    $('#zone').change(function () {
        var zone = $(this).val();
        var period = $('#period').val();

        CloudflareAnalytics.update(zone, period);
    });

    $('#period').change(function () {
        var zone = $('#zone').val();
        var period = $(this).val();

        CloudflareAnalytics.update(zone, period);
    });

    $('#period').change();
});