/**
 * Render a waterfall chart into el using the data from url
 */
Xhgui.waterfall = function (el, options) {
    el = d3.select(el);

    // Use the containing element to get the width.
    var w = parseInt(el.style('width'), 10);

    d3.json(options.dataUrl, function (data) {
        var h = 50 + (30 * data.length),
            endTimes = [],
            startTimes = [];

        data.forEach(function (d) {
            d.startdt = new Date(d.start);
            d.enddt = new Date(d.start + d.duration);

            endTimes.push(d.enddt);
            startTimes.push(d.startdt);
        });

        // Sort the set so it looks like a waterfall.
        data.sort(function (a, b) {
            if (a.start < b.start) {
                return -1;
            }
            if (a.start > b.start) {
                return 1;
            }
            return 0;
        });


        var x = d3.time.scale().rangeRound([0, w]).nice(d3.time.second),
            y = d3.scale.linear().range([0, h]),
            xAxis = d3.svg.axis().scale(x).tickSize(-h).tickSubdivide(true),
            yAxis = d3.svg.axis().scale(y).ticks(4).orient("bottom");

        var max = d3.max(endTimes);
        var min = d3.min(startTimes);

        var seconds = max.getSeconds();
        max.setSeconds(seconds + 1);

        x.domain([min, max]);
        y.domain([0, data.length]);

        var svg = el.append('svg')
            .attr("width", w)
            .attr("height", h);

        svg.append("g")
            .attr("class", "x axis")
            .attr("transform", "translate(0," + (h  - 20) + ")")
            .call(xAxis);

        var g = svg.selectAll('g.bar')
            .data(data).enter().append('g')
            .attr('class', 'bar')
            .attr('transform', function (d,i) {
                return 'translate(' + x(d.startdt) + ',' + y(i) + ')'
            });

        g.append('rect')
            .attr('width', function (d) {
                var width = x(new Date(data[0].start + d.duration));
                return width > 2 ? width : 3;
            })
            .attr('height', 20);

        g.append('text').text(function (d, i) {return d.title; })
            .attr('dy', '1em')
            .attr('fill','black')
            .attr("text-anchor", "left");


        // Set tooltips on circles.
        Xhgui.tooltip(el, {
            bindTo: g,
            positioner: function (d, i) {
                // Use the translate attribute to position the tooltip.
                var transform = this.getAttribute('transform');
                var position = this.getBBox();

                var match = /translate\(([^,]+),([^\)]+)\)/.exec(transform);

                return {
                    // 7 = 1/2 width of arrow
                    x: parseFloat(match[1]) + (position.width / 2) - 7,
                    // 25 = fudge factor.
                    y: parseFloat(match[2]) - 25
                };
            },
            formatter: function (d, i) {
                var urlName = '?id=' + encodeURIComponent(d.id);
                var label = '<strong>' + d.title + '</strong>' +
                    ' <a href="' + options.baseUrl + urlName + '">view</a> <br />' +
                    ' Duration ' + Xhgui.formatNumber(d.duration) + ' <span class="units">µs</span> ';
                return label;
            }
        });
    });
};
