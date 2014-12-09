
jQ = jQuery.noConflict();
jQ(document).ready(function() {

    //guest == 0 footer

    // guest == 1 footer
    var docHeight = jQ("html").height();
    var footerHeight = jQ('.footer').height();
    var footerTop = jQ('.footer').position().top + footerHeight;

    if (footerTop < docHeight) {
        jQ('.footer').css('margin-top', 30 + (docHeight - footerTop) + 'px');
    }
    if ((jQ('.footer').outerHeight(true)-jQ('#footer').outerHeight())<30) {
        jQ('.footer').css('margin-top',30);
    }
});
