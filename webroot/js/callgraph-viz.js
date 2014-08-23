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
        .layout(layout)
        .run(g, svg);

    // Bind click events for function calls
    var nodes = svg.selectAll('.node');
    nodes.on('click', function(d, ev) {
        nodes.classed('active', false);
        d3.select(this).classed('active', true);
        details.show();
        var xhr = $.get(options.baseUrl + '&symbol=' + d)
        xhr.done(function(response) {
            details.find('.details-content').html(response);
            Xhgui.tableSort(details.find('.table-sort'));
        });
    });

};
