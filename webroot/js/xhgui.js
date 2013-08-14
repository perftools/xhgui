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
            return '' + parseInt(text.replace(/,/g, ''), 10);
        }
    });

    $('.datepicker').datepicker();


    // Bind events for expandable search forms.
    var searchForm = $('.search-form'),
        searchExpand = $('.search-expand');

    searchExpand.on('click', function () {
        searchExpand.fadeOut('fast', function () {
            searchForm.slideDown('fast');
        });
        return false;
    });

    $('.search-collapse').on('click', function () {
        searchForm.slideUp('fast', function () {
            searchExpand.show();
        });
        return false;
    });

});
