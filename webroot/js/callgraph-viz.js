Xhgui.callgraph = function(container, data, options) {
    // Color scale
    var colors = d3.scale.linear()
        .domain([0, 100])
        .range(['#ffe85e', '#b63c71']);

    var textSize = d3.scale.linear()
        .domain([0, 100])
        .range([0.5, 3]);

    // Generate the style props for a node and its label
    var nodeStyle = function(node) {
        var ratio = node.value / data.total * 100;
        return 'fill: ' + colors(ratio) + ';'
    };
    var nodeLabelStyle = function(node) {
        var ratio = node.value / data.total * 100;
        return 'font-size: ' + textSize(ratio) + 'em;'
    }

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
            label: node.name + ' - ' + Xhgui.metricName(data.metric) + ' ' + Xhgui.formatNumber(node.value),
            style: nodeStyle(node),
            labelStyle: nodeLabelStyle(node)
        });
    }
    for (i = 0, len = data.links.length; i < len; i++) {
        var edge = data.links[i];
        var word = edge.callCount === 1 ? ' call' : ' calls';
        g.addEdge(
            null,
            edge.source,
            edge.target,
            {label: edge.callCount + word}
        );
    }

    var renderer = new dagreD3.Renderer();

    var oldDraw = renderer.drawNodes();
    renderer.drawNodes(function(graph, root) {
        var nodes = oldDraw(graph, root);
        return nodes.each(function(u) {
            return;

            var dpoint = graph.node(u);
            var box = d3.select(this).select('rect');

            var width = box.attr('width');
            var height = box.attr('height');

            // Color and size the box based on how heavy it was.
            box.style('fill', function (d) {
                return colors(dpoint.ratio);
            }).attr('width',  width * dpoint.ratio * 0.1)
            .attr('height', height * dpoint.ratio * 0.1);
        });
    });
    renderer.run(g, svg);

    // Setup details view.
    var details = $(options.detailView);
    details.find('.button-close').on('click', function() {
        details.hide();
        details.find('.details-content').empty();
        return false;
    });

    // Bind click events for function calls
    svg.select('.node.enter').on('click', function(d, ev) {
        console.log(d);
        details.show();
    });
};
