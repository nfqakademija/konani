'use strict';
function bothFilled()
{
    var lat = $('#form_latitude').val();
    var lng = $('#form_longitude').val();

    if ((lat.length === 0)  || (lng.length === 0) ) {
        return false;
    } else {
        return true;
    }
}

$( '#newTag' ).submit(function( event ) {
    if (!bothFilled()) {
        $('.alert').addClass('alert-danger').removeClass('hidden');
        event.preventDefault();
    } else {
        $('.allert').addClass('hidden');
    }
});
