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

    container = d3.select(container);

    var svg = container.append('svg')
        .attr('width', width)
        .attr('height', height)
            .append('g')
            .attr('transform', "translate(" + width / 2 + "," + height / 2 + ")");

    var g = svg.selectAll('.arc')
        .data(pie(data))
            .enter().append('g')
        .attr('class', 'arc');

    var popover = container.append('div');

    popover.attr('class', 'popover top')
        .append('div').attr('class', 'arrow');

    var popoverContent = popover.append('div').attr('class', 'popover-content');

    function stop() {
        d3.event.stopPropagation();
    }

    function hide() {
        popover.transition().style('opacity', 0);
        d3.select(document).on('mouseout', false);
    }

    g.append('path')
        .attr('d', arc)
        .style('fill', function (d) {
            return color(d.data.value);
        })
        .on('mouseover', function (d, i) {
            var sliceX, sliceY, top, left, tooltipHeight, tooltipWidth,
                label, position;

            position = arc.centroid(d, i);
            label = '<strong>' + d.data.name + '</strong><br />' +
                d.data.value + options.postfix;

            popoverContent.html(label);
            popover.style({
                display: 'block',
                opacity: 1
            });

            tooltipWidth = parseInt(popover.style('width'), 10);
            tooltipHeight = parseInt(popover.style('height'), 10);

            // Recalculate base on outer transform.
            sliceX = position[0] + (height / 2);
            sliceY = position[1] + (width / 2);

            // Recalculate based on width/height of tooltip.
            // arrow is 10x10px
            top = sliceY - (tooltipHeight / 2) - 5;
            left = sliceX - (tooltipWidth / 2) + 5;

            popover.style({
                top: top + 'px',
                left: left + 'px'
            });

            d3.select(document).on('mouseout', hide);
        });

    // stop flickering tooltips.
    svg.on('mouseout', stop);
    popover.on('mouseout', stop);
};

// Random DOM behavior.
$(document).ready(function () {
	$('.tip').tooltip();
});
