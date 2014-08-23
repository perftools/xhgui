Xhgui.callgraph = function(container, data, options) {
    // Color scale
    var colors = d3.scale.linear()
        .domain([0, 100])
        .range(['#fff', '#b63c71']);

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


    // Setup details view.
    var details = $(options.detailView);
    details.find('.button-close').on('click', function() {
        details.hide();
        details.find('.details-content').empty();
        return false;
    });

    // Lay out the graph more tightly than the defaults.
    var layout = dagreD3.layout()
          .nodeSep(30)
          .rankSep(30)
          .rankDir("TB");

    // Render the graph.
    var renderer = new dagreD3.Renderer()
        .layout(layout);

    var oldEdge = renderer.drawEdgePaths();
    renderer.drawEdgePaths(function(g, root) {
        var node = oldEdge(g, root);
        node.attr('data-value', function(d) {
            return d;
        });
        return node;
    });

    renderer.run(g, svg);

    // Bind click events for function calls
    var nodes = svg.selectAll('.node');
    nodes.on('click', function(d, edge) {
        nodes.classed('active', false);
        d3.select(this).classed('active', true);
        details.show();
        var xhr = $.get(options.baseUrl + '&symbol=' + d)
        xhr.done(function(response) {
            details.find('.details-content').html(response);
            Xhgui.tableSort(details.find('.table-sort'));
        });
        highlightSubtree(d);
    });


    // Collects and iterates the subtree of nodes/edges and highlights them.
    var highlightSubtree = function(root) {
        var i, len;
        var subtree = [root];
        var nodes = [root];
        while (nodes.length > 0) {
            var node = nodes.shift();
            var childNodes = g.successors(node);
            if (childNodes.length == 0) {
                break;
            }
            // Append into the 'queue' so we can collect *all the nodes*
            nodes = nodes.concat(childNodes);

            // Collect the entire subtree so we can find and highlight edges.
            subtree = subtree.concat(childNodes);
        }

        var edges = [];
        // Find the outgoing edges for each node in the subtree.
        for (i = 0, len = subtree.length; i < len; i++) {
            node = subtree[i];
            edges = edges.concat(g.outEdges(node));
        }

        // Clear width.
        svg.selectAll('g.edgePath path').style('stroke-width', 1);

        // Highlight each edge in the subtree.
        for (i = 0, len = edges.length; i < len; i++) {
            svg.select('g.edgePath[data-value=' + edges[i] + '] path').style('stroke-width', 5);
        }
    };
};
