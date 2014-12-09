/*$(document).ready(function() {

    var field = $('.location-search');

    $(field).keyup(function() {
        delay(function(){
            search();
        }, 1000 );
    });

    function search() {
        var val = $(field).val();

        link = getLink(val);

        $.post(link,function(data) {
            fullAddress = data['results'][0]['formatted_address'];
            lat = data['results'][0]['geometry']['location']['lat'];
            lng = data['results'][0]['geometry']['location']['lng'];
            $(field).val(fullAddress);
            $('.latitude').val(lat);
            $('.longitude').val(lng);
        });

    }

    var delay = (function(){
        var timer = 0;
        return function(callback, ms){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
        };
    })();

    function getLink(location) {
        var link = "https://maps.googleapis.com/maps/api/geocode/json?address="+location+"&sensor=false";
        return link;
    }
});
*/