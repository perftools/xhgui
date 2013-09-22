window.Xhgui = {};

/**
 * Color generator for graphs.
 */
Xhgui.colors = function () {
    var colors = [
        '#59bdd2', // blue
        '#637964', // green
        '#d46245', // red
        '#ffe85e', // yellow
        '#e9814f', // orange
        '#e3b7b3', // pink
        '#b63c71' // purple
    ];
    return d3.scale.ordinal().range(colors);
};

/**
 * Format a date object to a readable string in SQL date format.
 *
 * @param Date date The date to format.
 * @return String Formatted date string.
 */
Xhgui.formatDate = d3.time.format('%Y-%m-%d');

/**
 * Format a number to have thousand separators + decimal places.
 * All inputs will be cast to a Number
 *
 * @param Mixed num The number or string number to format.
 * @param Number decimalPlaces Number of decimal places to use.
 * @return String Formatted number string.
 */
Xhgui.formatNumber = function (num, decimalPlaces) {
    if (decimalPlaces === undefined) {
        decimalPlaces = 2;
    }
    var sep = ',';

    number = +num;
    var val = number.toFixed(decimalPlaces);
    if (val < 1000) {
        return val;
    }
    var split = val.split(/\./);
    var thousands = split[0];
    var i = thousands.length % 3 || 3;

    thousands = thousands.slice(0, i) + thousands.slice(i).replace(/(\d{3})/g, sep + '$1');
    split[0] = thousands;
    return split.join('.');
};


Xhgui.legend = function(svg, text, height, margin, color) {
    if (!text) {
        return;
    }
    // position on y. 3 offsets descenders.
    var yOffset = height + margin.bottom - 3;

    // calculate the xOffset based on all the other legend.
    // cross fingers that we don't run out of X space.
    var legendGroups = svg.select('.legend-group');
    var xOffset = 0;

    if (legendGroups[0].length && legendGroups[0][0]) {
        var box = legendGroups[legendGroups.length - 1][0].getBBox();
        // 20 is some margin.
        xOffset = box.x + box.width + 20;
    }

    var group = svg.append('g')
        .attr('class', 'legend-group');

    // Append the legend dot
    group.append('circle')
        .attr('fill', color)
        .attr('r', 3)
        .attr('cx', 0)
        .attr('cy', -5);

    // Add text.
    group.append('text')
        .attr('x', 5)
        .attr('y', 0)
        .text(text);

    // position the group
    group.attr('transform', 'translate(' + xOffset + ', ' + yOffset + ')');
};

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

    function stop() {
        d3.event.stopPropagation();
    }

    function createTooltip(container) {
        var exists = container.select('.popover'),
            popover, content;

        if (exists.empty()) {
            popover = container.append('div');

            popover.attr('class', 'popover top')
                .append('div').attr('class', 'arrow');

            content = popover.append('div')
                .attr('class', 'popover-content');

            // stop flickering tooltips.
            container.on('mouseout', stop);
            popover.on('mouseout', stop);
            return {frame: popover, content: content};
        }
        popover = exists;
        content = exists.select('.popover-content');
        return {frame: popover, content: content};
    }

    var tooltip = createTooltip(container);

    function hide() {
        tooltip.frame.transition().style('opacity', 0);
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

        tooltip.content.html(content);
        tooltip.frame.style({
            display: 'block',
            opacity: 1
        });

        tooltipWidth = parseInt(tooltip.frame.style('width'), 10);
        tooltipHeight = parseInt(tooltip.frame.style('height'), 10);

        // Recalculate based on width/height of tooltip.
        // arrow is 10x10, so 7 & 5 are magic numbers
        top = position.y - (tooltipHeight / 2) - 7;
        left = position.x - (tooltipWidth / 2) + 5;

        tooltip.frame.style({
            top: top + 'px',
            left: left + 'px'
        });

        d3.select(document).on('mouseout', hide);
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

    data = [{
        key:'',
        values:data
    }];

    options = options || {};
    var height = options.height || 400,
        width = options.width || 400,
        margin = {top: 20, right: 20, bottom: 30, left: 50};

    nv.addGraph(function() {
      var chart = nv.models.discreteBarChart()
          .x(function(d) { return d.name})
          .y(function(d) { return d.value })
          .staggerLabels(true)
          .tooltips(true)
          .showValues(false)
          .showXAxis(false)
          .color([])

      chart.yAxis
        .tickFormat(d3.format('s'));

      d3.select(container).append('svg')
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
          .datum(data)
        .transition().duration(500)
          .call(chart);

      nv.utils.windowResize(chart.update);

      return chart;
    });
};

/**
 * Creates a single or multiseries line graph with tooltips.
 *
 * Options:
 *
 * - xAxis - The key to use for the x-axis.
 * - series - An array of the keys used for the series data.
 * - title - The chart title.
 * - legend - An array of legends for each series.
 * - postfix - A string to append to the tooltip for each datapoint.
 * - height - The height of the chart.
 *
 * @param string container Selector to the container for the graph
 * @param array data The data to graph. Should be an array of objects. Each
 * object should contain a key for each element in `options.series`.
 * @param object options The options to use. Needs to define xAxis & series
 */
Xhgui.linegraph = function (container, data, options) {
 
    data = data.filter(function(elt) {
        return options.series.indexOf(elt.key) >= 0;
    });

    container = d3.select(container);

    var margin = {top: 30, right: 20, bottom: 40, left: 50},
        height = options.height || (parseInt(container.style('height'), 10) - margin.top - margin.bottom),
        width = options.width || (parseInt(container.style('width'), 10) - margin.left - margin.right),
        lastIndex = data.length - 1;
    
    nv.addGraph(function() {
        var chart = nv.models.lineChart();
        
        chart.x(function(d,i) { return d[0]; })
            .y(function(d) { return d[1]; });
        chart.xAxis
          .tickFormat(function(d) {
            return Xhgui.formatDate(new Date(d))
        });
        chart.yAxis
            .tickFormat(d3.format('s'))
            .showMaxMin(false)
            .axisLabel(options.postfix);

        container.append('svg')
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .datum(data)
          .transition().duration(500)
            .call(chart);

        nv.utils.windowResize(chart.update);
        return chart;
    });
};
