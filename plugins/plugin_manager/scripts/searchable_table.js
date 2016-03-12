$(document).ready(function () {
    /* Make table searchable. Multiple search tables is on same page is possible
     *
     * HOW TO: create wrapper for table with class table-searchable and add a input field with class
     *         search-table to this wrapper. Add the class searchable for all the td's that are searchable. 
     *         Add a tr in the table with class no-result with text to display in case of no results.
     */
    $('.table-searchable').each(function(){
        var parent = $(this);
        var table = $(this).find("table");
        $(this).find('input.search-table').keyup(function () {
            var rex = new RegExp($(this).val(), 'i');
            table.find('tbody tr').hide();
            var any_results = false;
            table.find('tbody tr:not(.no-results)').filter(function () {
                value_of_searchables = $(this).find('.searchable').map(function () { return $( this ).text(); }).get()
                if(rex.test(value_of_searchables)){
                    any_results = true;
                    return true;
                }
                return false;
            }).show();
            if(!any_results){
                table.find('.no-results').show();
            }
        });
    });

    // Fill modal with content from link href
    $(".bs-example-modal-lg").on("show.bs.modal", function(e) {
        var link = $(e.relatedTarget);
        $(this).find(".modal-body").load(link.attr("href"));
    });
    $('.bs-example-modal-lg').on('hide.bs.modal', function (e) {
        e.preventDefault();
        location.reload();
    })
});