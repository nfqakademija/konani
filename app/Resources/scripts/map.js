'use strict';

/* global map */
/* global google */
var geocoder = new google.maps.Geocoder();

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

$(document).ready(function() {

    $('#newTag').submit(function (event) {
        if (!bothFilled()) {
            $('.alert').addClass('alert-danger').removeClass('hidden');
            event.preventDefault();
        } else {
            $('.alert').addClass('hidden');
        }
    });

    $(document).ajaxStart(function () {
        $('#ajax_loading').show();
    });

    $(document).ajaxStop(function () {
        $('#ajax_loading').hide();
    });

    $('#search_form').submit(function(event) {
        console.log('CLICK');
        event.preventDefault();
        geocoder.geocode({ 'address': $('#search_query').val() }, function (results, status) {
            if (status === google.maps.GeocoderStatus.OK) {

                var lat = results[0].geometry.location.lat().toString().substr(0, 12);
                var lng = results[0].geometry.location.lng().toString().substr(0, 12);

                var myLatLng = new google.maps.LatLng(lat, lng);

                $('#search_query').val(results[0].formatted_address);

                map.setCenter(myLatLng);
                map.setZoom(14);

            } else {
                $('#location-search').addClass('red');
            }
        });
    });
});