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

    $('button[data-confirm]').click(function(ev) {
        var form = $(this).closest("form");

        if (!$('#dataConfirmModal').length) {
            $('body').append('<div id="dataConfirmModal" class="modal fade" role="dialog" aria-labelledby="dataConfirmLabel"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title">Please Confirm</h4></div><div class="modal-body"></div><div class="modal-footer"><button class="btn btn-sm btn-default" data-dismiss="modal" aria-hidden="true">Cancel</button><button id="dataConfirmOK">OK</button></div></div></div></div>');
        } 
        $('#dataConfirmModal').find('.modal-body').text($(this).attr('data-confirm'));
        $('#dataConfirmOK').attr("class", $(this).attr("class"));
        $('#dataConfirmOK').text($(this).text());
        $('#dataConfirmOK').unbind('click');
        $('#dataConfirmOK').click(function(){
            form.submit();
        });
        $('#dataConfirmModal').modal({show:true});
        return false;
    });
});