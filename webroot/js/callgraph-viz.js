Xhgui.callgraph = function(container, data, option) {
    var el = d3.select(container),
        width = parseInt(el.style('width'), 10),
        height = 1000;

    var svg = d3.select(container).append('svg')
        .attr('class', 'callgraph')
        .attr('width', width)
        .attr('height', height);

    var g = new dagreD3.Digraph();
    for (var i = 0, len = data.nodes.length; i < len; i++) {
        var node = data.nodes[i];
        g.addNode(node.name, {
            label: node.name,
            ratio: Math.ceil(node.value / data.total * 100)
        });
    }
    for (i = 0, len = data.links.length; i < len; i++) {
        var edge = data.links[i];
        g.addEdge(
            null,
            edge.source,
            edge.target,
            {label: 'Called ' + edge.callCount + ' times'}
        );
    }

    // Color scale
    var colors = d3.scale.linear()
        .domain([0, 100])
        .range(['#ffe85e', '#b63c71']);

    var renderer = new dagreD3.Renderer();

    var oldDraw = renderer.drawNodes();
    renderer.drawNodes(function(graph, root) {
        var nodes = oldDraw(graph, root);
        return nodes.each(function(u) {
            var dpoint = graph.node(u);
            var box = d3.select(this).select('rect');
            box.style('fill', function (d) {
                return colors(dpoint.ratio);
            });
        });
    });

    renderer.run(g, svg);
};
