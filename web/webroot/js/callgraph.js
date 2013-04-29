/**
 * Generate a callgraph visualization based on the provided data.
 *
 * @param String container
 * @param Array data The profile data.
 * @param Object options Additional options
 */
Xhgui.callgraph = function (container, data, options) {

    var flatten = function (root) {
        var nodes = [], i = 0;

        function recurse(node) {
            if (node.children) {
                node.size = node.children.reduce(function (p, v) {
                    return p + recurse(v);
                }, 0);
            }
            if (!node.id) {
                node.id = ++i;
            }
            nodes.push(node);
            return node.size;
        }

        root.size = recurse(root);
        return nodes;
    };

    var el = d3.select(container),
        width = parseInt(el.style('width'), 10),
        height = 1000;

    var force = d3.layout.force()
        .charge(function(d) {
            return d._children ? -d.size / 100 : -30;
            // return -50 * Math.log(d.value);
        })
        .linkDistance(function(d) {
            return d.target._children ? 80 : 30;
            // return 5 * d.target.value;
        })
        .size([width, height]);

    var svg = d3.select(container).append('svg')
        .attr('class', 'callgraph')
        .attr('width', width)
        .attr('height', height);

    var nodes = flatten(data),
        links = d3.layout.tree().links(nodes);

    force.nodes(nodes)
        .links(links)
        .start();

    var link = svg.selectAll('.link')
        .data(links, function (d) {
            return d.target.id;
        })
        .enter().append('line')
            .style('stroke-width', function (d) {
                return Math.max(1, Math.log(d.target.value));
            })
            .attr('class', 'link');

    // Color scale
    var colors = d3.scale.linear()
        .domain([0, 100])
        .range(['#ffffff', '#b63c71']);

    var gnodes = svg.selectAll('g.node')
        .data(nodes)
        .enter().append('g')
        .attr('class', 'node')
        .call(force.drag);

    // Append dots and text.
    var circle = gnodes.append('circle')
        .attr('class', 'node')
        .attr('r', function (d) {
            return d.value * 0.5;
        })
        .style('fill', function (d) {
            return colors(d.value);
        });

    var text = gnodes.append('text')
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

    // Position lines / dots.
    force.on("tick", function() {
        link.attr("x1", function(d) {
                return d.source.x;
            })
            .attr("y1", function(d) {
                return d.source.y;
            })
            .attr("x2", function(d) { return d.target.x; })
            .attr("y2", function(d) { return d.target.y; });

        circle.attr("cx", function(d) { return d.x; })
            .attr("cy", function(d) { return d.y; });

        text.attr("x", function(d) { return d.x; })
            .attr("y", function(d) { return d.y; });
    });

    // Set tooltips on circles.
    Xhgui.tooltip(el, {
        bindTo: circle,
        positioner: function (d, i) {
            // Use the circle's bbox to position the tooltip.
            var position = this.getBBox();

            return {
                // 7 = 1/2 width of arrow
                x: position.x + (position.width / 2) - 7,
                // 20 = fudge factor.
                y: position.y - 20
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

};
