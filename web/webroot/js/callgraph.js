/**
 * Generate a callgraph visualization based on the provided data.
 *
 * @param String container
 * @param Array data The profile data.
 * @param Object options Additional options
 */
Xhgui.callgraph = function (container, data, options) {
    var el = d3.select(container),
        width = parseInt(el.style('width'), 10),
        height = 2000;

    var cluster = d3.layout.cluster()
        .size([height, width - 160]);

    var diagonal = d3.svg.diagonal()
        .projection(function (d) { return [d.y, d.x]; });

    var svg = d3.select(container).append('svg')
        .attr('class', 'callgraph')
        .attr('width', width)
        .attr('height', height)
            .append('g').attr('transform','translate(40,0)');

    var nodes = cluster.nodes(data),
        links = cluster.links(nodes);

    var link = svg.selectAll('.link')
        .data(links)
        .enter().append('path')
            .attr('class', 'link')
            .attr('d', diagonal);

    var node = svg.selectAll('.node')
        .data(nodes)
        .enter().append('g')
            .attr('class', 'node')
            .attr('transform', function (d) {
                return "translate(" + d.y + "," + d.x + ")";
            });

    // Color scale
    var colors = d3.scale.linear()
        .domain([0, 100])
        .range(['#ffffff', '#b63c71']);

    var circle = node.append('circle')
        .attr('r', function (d) {
            return 1.2 * d.value;
        })
        .style('fill', function (d) {
            return colors(d.value);
        });

    // Set tooltips on circles.
    Xhgui.tooltip(el, {
        bindTo: node,
        positioner: function (d, i) {
            // Use the transform property to
            // find where the node is.
            var transform = this.getAttribute('transform');
            var match = transform.match(/translate\((.*),(.*)\)/);
            var xOffset = parseFloat(match[1]);
            var yOffset = parseFloat(match[2]);

            return {
                // 7 = 1/2 width of arrow, 40 = canvas translate
                x: xOffset + 40 - 7,
                // 30 = fudge factor.
                y: yOffset - 30
            };
        },
        formatter: function (d, i) {
            var urlName = '&symbol=' + encodeURIComponent(d.name);
            var label = '<strong>' + d.name +
                '</strong> ' + d.value + '% ' +
                '<a href="' + options.baseUrl + urlName + '">view</a>';
            return label;
        }
    });

    node.append('text')
        .attr({
            dx: 0,
            dy: 0
        })
        .style('display', function (d) {
            return d.value > 15 ? 'block' : 'none';
        })
        .style({
            'text-anchor': 'middle',
            'vertical-align': 'middle'
        })
        .text(function (d) {
            return d.name;
        });

};
