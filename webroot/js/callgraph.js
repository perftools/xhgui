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
        height = 1000;

    var force = d3.layout.force()
        .charge(function(d) {
            return -50 * Math.log(d.ratio);
        })
        .linkDistance(function(d) {
            return d.target.weight;
        })
        .size([width, height]);

    var svg = d3.select(container).append('svg')
        .attr('class', 'callgraph')
        .attr('width', width)
        .attr('height', height);

    // Append the defs and markers
    var defs = svg.append('svg:defs');

    defs.append('svg:marker')
        .attr({
            id: 'arrowhead',
            viewBox: '0 0 10 10',
            refX: 10,
            refY: 6,
            markerUnits: 'strokeWidth',
            markerHeight: 4,
            markerWidth: 4,
            orient: 'auto'
        }).append('path')
            .attr('d', 'M 0 0 L 10 5 L 0 10 z');

    defs.append('svg:marker')
        .attr({
            id: 'arrowhead-active',
            viewBox: '0 0 10 10',
            refX: 10,
            refY: 6,
            markerUnits: 'strokeWidth',
            markerHeight: 4,
            markerWidth: 4,
            orient: 'auto'
        }).append('path')
            .attr('d', 'M 0 0 L 10 5 L 0 10 z');

    // Fix the main() node
    data.nodes[0].fixed = true;
    data.nodes[0].x = width / 2;
    data.nodes[0].y = 60;

    for (var i = 0, len = data.nodes.length; i < len; i++) {
        data.nodes[i].ratio = Math.ceil(data.nodes[i].value / data.totalTime * 100);
    }

    var nodes = force.nodes(data.nodes)
        .links(data.links)
        .start();

    var linkGroup = svg.selectAll('.link-g')
        .data(data.links)
        .enter()
        .append('g')
        .attr('class', 'link-g');

    var link = linkGroup.append('line')
        .style('stroke-width', function (d) {
            return Math.max(0.75, Math.log(d.target.ratio));
        })
        .attr({
            'class': 'link',
            'marker-end': "url(#arrowhead)"
        });

    // Text displayed by connecting lines.
    var linkText = linkGroup.append('text')
        .style('display', function(d) {
            return d.target.ratio > 5 ? 'block' : 'none';
        })
        .text(function(d) {
            var text = d.target.callCount == 1 ? ' call' : ' calls';
            return d.target.callCount + text;
        });

    // Color scale
    var colors = d3.scale.linear()
        .domain([0, 100])
        .range(['#ffe85e', '#b63c71']);

    var gnodes = svg.selectAll('g.node')
        .data(data.nodes)
        .enter().append('g')
        .attr('class', 'node')
        .call(force.drag);

    // Make nodes that are moved sticky.
    gnodes.on('mousedown', function (d) {
        d.fixed = true;
    });

    // Make an easier to traverse link list.
    var linkMap = {};
    link.each(function (linkData) {
        var sourceIndex = linkData.source.index;
        var childLinks = link.filter(function (d) {
            return d.source.index == sourceIndex;
        });
        if (childLinks[0].length) {
            linkMap[sourceIndex] = childLinks;
        }
    });

    function getChildLinks(parent) {
        if (linkMap[parent.index] === undefined) {
            return [];
        }
        // Use a simple queue to walk the graph
        // without using recursion, as we could easily
        // blow the browser stack frame limit
        var queue = linkMap[parent.index].slice()
        var linkSet = [];
        var visited = {};
        visited[parent.index] = true;

        if (queue === undefined) {
            return linkSet;
        }
        while (queue.length) {
            var currentSet = queue.shift();
            if (!currentSet.length) {
                return linkSet;
            }
            // 'recurse' and append into the queue.
            d3.selectAll(currentSet).each(function (datum) {
                var nextIndex = datum.target.index;
                if (visited[nextIndex]) {
                    return;
                }
                linkSet.push(this);
                visited[nextIndex] = true;
                var nextNodes = linkMap[nextIndex];
                if (nextNodes && nextNodes.length) {
                    queue = queue.concat(linkMap[nextIndex]);
                }
            });
        }
        return linkSet;
    }

    // Append dots and text.
    var circle = gnodes.append('circle')
        .attr('r', function (d) {
            return d.ratio * 0.5;
        })
        .style('fill', function (d) {
            return colors(d.ratio);
        })
        // Mouse effects to highlight subtrees
        .on('mouseover', function (d, ev) {
            var node = nodes.nodes()[d.index];
            var childLinks = getChildLinks(node);
            d3.selectAll(childLinks).attr({
                'class': 'link active',
                'marker-end': "url(#arrowhead-active)"
            });
        }).on('mouseout', function (d, ev) {
            var node = nodes.nodes()[d.index];
            var childLinks = getChildLinks(node);
            d3.selectAll(childLinks).attr({
                'class': 'link',
                'marker-end': "url(#arrowhead)"
            });
        });

    var text = gnodes.append('text')
        .style({
            'display': function (d) {
                return d.ratio > 15 ? 'block' : 'none';
            }
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

        // Position call count text along line.
        // 5 gives a decent margin.
        linkText.attr('x', function(d) {
            return d.source.x - 5 + (d.target.x - d.source.x) / 2;
        }).attr('y', function(d) {
            return d.source.y - 5 + (d.target.y - d.source.y) / 2;
        });

        circle.attr("cx", function(d) { return d.x; })
            .attr("cy", function(d) { return d.y; });

        text.attr("x", function(d) { return d.x; })
            .attr("y", function(d) { return d.y; });
    });

    // Set tooltips on circles.
    Xhgui.tooltip(el, {
        bindTo: gnodes,
        positioner: function (d, i) {
            // Use the circle's bbox to position the tooltip.
            var position = this.getBBox();

            return {
                // 7 = 1/2 width of arrow
                x: position.x + (position.width / 2) - 7,
                // 45 = fudge factor.
                y: position.y - 45
            };
        },
        formatter: function (d, i) {
            var urlName = '&symbol=' + encodeURIComponent(d.name);
            var label = '<h5>' + d.name + '</h5>' +
                '<strong>Wall time:</strong> ' + d.ratio + '% ' +
                ' (' + Xhgui.formatNumber(d.value) + ' <span class="units">µs</span>) ' +
                '<br />' +
                '<strong>Call count:</strong> ' + d.callCount +
                '<br />' +
                ' <a href="' + options.baseUrl + urlName + '">View symbol</a> <br />';
            return label;
        }
    });

};
