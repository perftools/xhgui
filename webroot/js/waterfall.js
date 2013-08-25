/**
 * Render a waterfall chart into el using the data from url
 */
Xhgui.waterfall = function (el, url) {
    var w = 800;

    d3.json(url, function (data) {
        var h = 50 + (30 * data.length),
            endTimes = [],
            startTimes = [];

        data.forEach(function (d) {
            d.startdt = new Date(d.start);
            d.enddt = new Date(d.start + d.duration);

            endTimes.push(d.enddt);
            startTimes.push(d.startdt);
        });

        var x = d3.time.scale().range([0, w]),
            y = d3.scale.linear().range([0, h]),
            xAxis = d3.svg.axis().scale(x).tickSize(-h).tickSubdivide(true),
            yAxis = d3.svg.axis().scale(y).ticks(4).orient("bottom");

        var max = d3.max(endTimes);

        var seconds = max.getSeconds();
        max.setSeconds(seconds + 1);

        x.domain([d3.min(startTimes), max]);
        y.domain([0, data.length]);

        var svg = d3.select(el);
        svg.attr("width", w)
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
                return width > 2 ? width : 6;
            })
            .attr('height', 20);

        g.append('text').text(function (d, i) {return d.title; })
            .attr('dy', '1em')
            .attr('fill','black')
            .attr("text-anchor", "left");
    });
};
