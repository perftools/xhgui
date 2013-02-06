// Utilitarian DOM behavior.
$(document).ready(function () {
    $('.tip').tooltip();
    var tables = $('.table-sort');
    tables.stickyTableHeaders();
    tables.tablesorter({
        textExtraction: function(node) {
            if (node.className.match(/text/)) {
                return node.innerText;
            }
            var text = node.innerText || node.textContent;
            return '' + parseInt(text.replace(',', ''), 10);
        }
    });

    $('.datepicker').datepicker();
});
