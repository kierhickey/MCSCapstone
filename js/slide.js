$(function() {
    var slideNavWidth = $(".slide-nav").width();
    var tmt = 0;
    $('.slides').slidesjs({
        play: {
            active: false,
            effect: "slide",
            interval: 7000,
            auto: true,
            swap: true,
            pauseOnHover: false,
            restartDelay: 2500
        },
        pagination: {
            active: false
        },
        navigation: {
            active: true,
            // previous button: class="slidesjs-previous slidesjs-navigation"
            // next button: class="slidesjs-next slidesjs-navigation"
            effect: "slide"
        },
        height: 400,
        width: 940,
        callback: {
            loaded: function (number) {
                $('.slidesjs-pagination, .slidesjs-navigation').hide(0);
            }
        }
    });

    $(".slides-next").on("click", function () {
        clearTimeout(tmt);
        $(".loading-bar").stop();
        $(".slidesjs-next").trigger("click");
    });

    $(".slides-prev").on("click", function () {
        clearTimeout(tmt);
        $(".loading-bar").stop();
        $(".slidesjs-previous").trigger("click");
    });
});
