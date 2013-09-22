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
Xhgui.formatNumber = d3.format('n');


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
    
    options.bindTo.on('mouseover', function (d, i) {
        var content, position;
        nv.tooltip.cleanup();

        // Get the tooltip content.
        content = options.formatter.call(this, d, i);

        // Get the tooltip position.
        position = options.positioner.call(this, d, i);
        
        nv.tooltip.show([position.x, position.y], content, null, null, this.parentNode);

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
        .tickFormat(d3.format('s'))
        .showMaxMin(false);

      chart.tooltipContent(function(key, x, y, e, graph) {
            var value = e.series.values[e.pointIndex].value;
            return '<div class="top"><strong>'+x+'</strong>'
                   +'<br />'+Xhgui.formatNumber(value)+' '+options.postfix+'</div>';
      });

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
        
        chart.tooltipContent(function(key, x, y, e, graph) { 
            var value = e.series.values[e.pointIndex][1];
            return '<div class="top"><strong>'+x+'</strong>'
                   +'<br />'+Xhgui.formatNumber(value)+' '+options.postfix+'</div>';
        });
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
