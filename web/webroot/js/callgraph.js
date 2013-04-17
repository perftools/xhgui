/**
 * Generate a callgraph visualization based on the provided data.
 */
Xhgui.callgraph = function (container, data) {
    var el = $(container),
        width = el.width(),
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

    node.append('circle')
        .attr('r', 4.5);

    node.append('text')
        .attr('dx', function (d) {
            return d.children ? -5 : 5;
        })
        .attr('dy', function (d) {
            return d.depth % 2 == 0 ? -12 : 12;
        })
        .style('text-anchor', function (d) {
            return d.children ? 'end' : 'start';
        })
        .text(function (d) {
            return d.name;
        });
};
