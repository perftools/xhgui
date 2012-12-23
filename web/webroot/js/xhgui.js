window.Xhgui = {};

/**
 * Bind a tooltip to an element.
 */
Xhgui.tooltip = function (container, options) {
    if (
        !options.formatter ||
        !options.positioner ||
        !options.bindTo
    ) {
        throw new Exception('You need the formatter, positioner & bindTo options.');
    }
    var popover = container.append('div');

    popover.attr('class', 'popover top')
        .append('div').attr('class', 'arrow');

    var popoverContent = popover.append('div')
        .attr('class', 'popover-content');

    function stop() {
        d3.event.stopPropagation();
    }

    function hide() {
        popover.transition().style('opacity', 0);
        d3.select(document).on('mouseout', false);
    }

    options.bindTo.on('mouseover', function (d, i) {
        var top, left,
            tooltipHeight, tooltipWidth,
            content, position;

        // Get the tooltip content.
        content = options.formatter.call(this, d, i);

        // Get the tooltip position.
        position = options.positioner.call(this, d, i);

        popoverContent.html(content);
        popover.style({
            display: 'block',
            opacity: 1
        });

        tooltipWidth = parseInt(popover.style('width'), 10);
        tooltipHeight = parseInt(popover.style('height'), 10);

        // Recalculate based on width/height of tooltip.
        // arrow is 10x10, so 7 & 5 are magic numbers
        top = position.y - (tooltipHeight / 2) - 7;
        left = position.x - (tooltipWidth / 2) + 5;

        popover.style({
            top: top + 'px',
            left: left + 'px'
        });

        d3.select(document).on('mouseout', hide);
    });

    // stop flickering tooltips.
    container.on('mouseout', stop);
    popover.on('mouseout', stop);
};

/**
 * Create a pie chart.
 *
 * @param selector container The container for the chart
 * @param array data The data list with name, value keys.
 * @param object options
 */
Xhgui.piechart = function (container, data, options) {
    options = options || {};
    var height = options.height || 400,
        width = options.width || 400,
        radius = Math.min(width, height) / 2;

    var arc = d3.svg.arc()
        .outerRadius(radius - 10)
        .innerRadius(0);

    var pie = d3.layout.pie()
        .sort(null)
        .value(function (d) {
            return d.value;
        });

    var color = d3.scale.category20();

    container = d3.select(container);

    var svg = container.append('svg')
        .attr('width', width)
        .attr('height', height)
            .append('g')
            .attr('transform', "translate(" + width / 2 + "," + height / 2 + ")");

    var g = svg.selectAll('.chart-arc')
        .data(pie(data))
            .enter().append('g')
        .attr('class', 'chart-arc');

    g.append('path')
        .attr('d', arc)
        .style('fill', function (d) {
            return color(d.data.value);
        });

    Xhgui.tooltip(container, {
        bindTo: g,
        positioner: function (d, i) {
            var position, sliceX, sliceY;

            position = arc.centroid(d, i);

            // Recalculate base on outer transform.
            sliceX = position[0] + (height / 2);
            sliceY = position[1] + (width / 2);
            return {x: sliceX, y: sliceY};
        },
        formatter: function (d, i) {
            var label = '<strong>' + d.data.name +
                '</strong><br />' +
                d.data.value + options.postfix;
            return label;
        }
    });
};

/**
 * Create a column chart.
 *
 * @param selector container The container for the chart
 * @param array data The data list with name, value keys.
 * @param object options
 */
Xhgui.columnchart = function (container, data, options) {
    options = options || {};
    var height = options.height || 400,
        width = options.width || 400,
        margin = {top: 20, right: 20, bottom: 30, left: 50};

    var y = d3.scale.linear()
        .range([height, 0]);

    var x = d3.scale.ordinal()
        .rangeRoundBands([0, width], 0.1);

    var yAxis = d3.svg.axis()
        .scale(y)
        .tickFormat(d3.format('2s'))
        .orient("left");

    var xAxis = d3.svg.axis()
        .scale(x)
        .orient("bottom");

    container = d3.select(container);

    var svg = container.append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
      .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    x.domain(data.map(function(d, i) { return i + 1; }));
    y.domain([0, d3.max(data, function(d) { return d.value; })]);

    svg.append("g")
        .attr("class", "chart-axis x-axis")
        .attr("transform", "translate(0," + height + ")")
        .call(xAxis);

    svg.append("g")
        .attr("class", "chart-axis y-axis")
        .call(yAxis);

    svg.selectAll('.chart-bar')
        .data(data)
    .enter().append("rect")
        .attr("class", "chart-bar")
        .attr("x", function(d) { return x(d.value); })
        .attr("width", x.rangeBand())
        .attr("y", function(d) { return y(d.value); })
        .attr("height", function(d) { return height - y(d.value); });

    Xhgui.tooltip(container, {
        bindTo: svg.selectAll('.chart-bar'),
        positioner: function (d, i) {
            var position, x, y;
            position = this.getBBox();

            // Recalculate base on outer transform.
            // 7 is a magic number. It offsets the arrow.
            x = position.x + (position.width * 1.5) - 7;
            return {x: x, y: position.y};
        },
        formatter: function (d, i) {
            var label = '<strong>' + d.name +
                '</strong><br />' +
                d.value + options.postfix;
            return label;
        }
    });
};

/**
 * Creates a single or multiseries line graph with tooltips.
 *
 * @param string container Selector to the container for the graph
 * @param array data The data to graph. Should be an array of objects. Each
 * object should contain a key for each element in `options.series`.
 * @param object options The options to use. Needs to define xAxis & series
 */
Xhgui.linegraph = function (container, data, options) {
    options = options || {};
    if (!options.xAxis || !options.series) {
        throw new Exception('You need to define series & xAxis');
    }

    container = d3.select(container);

    var margin = {top: 20, right: 20, bottom: 30, left: 50},
        height = options.height || (parseInt(container.style('height'), 10) - margin.top - margin.bottom),
        width = options.width || (parseInt(container.style('width'), 10) - margin.left - margin.right),
        lastIndex = data.length - 1;

    if (!Array.isArray(options.series)) {
        options.series = [options.series];
    }

    var x = d3.time.scale()
        .range([0, width])
        .domain(d3.extent(data, function (d) {
            return d[options.xAxis];
        }));

    // Get the mins/maxes for all series.
    var mins = [];
    var maxes = [];
    options.series.forEach(function (key) {
        var extent = d3.extent(data, function (d) {
            return d[key];
        });
        mins.push(extent[0]);
        maxes.push(extent[1]);
    });
    var yDomain = [d3.min(mins), d3.max(maxes)];

    var y = d3.scale.linear()
        .range([height, 0])
        .domain(yDomain);

    var xAxis = d3.svg.axis()
        .scale(x)
        .orient("bottom");

    var yAxis = d3.svg.axis()
        .scale(y)
        .tickFormat(d3.format('2s'))
        .orient("left");

    // If there are going to be too
    // many ticks (they are ~18px tall)
    // make fewer ticks.
    if (height / 18 < 10) {
        yAxis = yAxis.ticks(height / 18);
    }

    var svg = container.append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
      .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    // Add axis
    svg.append("g")
      .attr("class", "chart-axis x-axis")
      .attr("transform", "translate(0," + height + ")")
      .call(xAxis);

    svg.append("g")
      .attr("class", "chart-axis y-axis")
      .call(yAxis);

    if (options.title) {
        svg.append('text')
            .attr('y', 10)
            .attr('x', width / 2)
            .style('text-anchor', 'middle')
            .text(options.title);
    }

    var colors = d3.scale.category10();

    function drawLine(i, series) {
        var line = d3.svg.line()
            .x(function(d) { return x(d[options.xAxis]); })
            .y(function(d) { return y(d[series]); });

        svg.append("path")
          .datum(data)
          .attr("class", "chart-line")
          .style('stroke', function (d) {
              return colors(i);
          })
          .attr("d", line);
    }

    function drawDots(i, series) {
        var circleClass = 'dot-' + series;
        var circle = svg.selectAll('.' + circleClass)
            .data(data)
            .enter()
            .append('circle')
            .style('fill', function (d) {
                return colors(i);
            })
            .attr('class', circleClass)
            .attr('cx', function (d) {
                return x(d[options.xAxis]);
            })
            .attr('cy', function (d) {
                return y(d[series]);
            })
            .attr('r', 3);
    }

    for (var i = 0, len = options.series.length; i < len; i++) {
        var series = options.series[i];
        drawLine(i, series);
        drawDots(i, series);
    }
};

// Utilitarian DOM behavior.
$(document).ready(function () {
    $('.tip').tooltip();
    $('.table-sort').tablesorter({
        textExtraction: function(node) {
            if (node.className.match(/text/)) {
                return node.innerText;
            }
            var text = node.innerText || node.textContent;
            return '' + parseInt(text.replace(',', ''), 10);
        }
    });
});
