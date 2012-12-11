window.Xhgui = {};

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

    var svg = d3.select(container).append('svg')
        .attr('width', width)
        .attr('height', height)
            .append('g')
            .attr('transform', "translate(" + width / 2 + "," + height / 2 + ")");

    var g = svg.selectAll('.arc')
        .data(pie(data))
            .enter().append('g')
        .attr('class', 'arc');

    var text;

    g.append('path')
        .attr('d', arc)
        .style('fill', function (d) {
            return color(d.data.value);
        })
        .on('mouseover', function () {
            // get the g element
            var el = d3.select(this.parentNode);
            // Make a 'tooltip' (not done)
            text = el.append('text')
                .attr('transform', function (d) {
                    return "translate(" + arc.centroid(d) + ")";
                })
                .attr({
                    dy: '.35em',
                    'class': 'chart-tooltip'
                })
                .style("text-anchor", "middle")
                .text(function(d) {
                    return d.data.name + ': ' + d.data.value + options.postfix;
                });
        }).on('mouseout', function () {
            text.remove();
        });
};

// Random DOM behavior.
$(document).ready(function () {
	$('.tip').tooltip();
});
