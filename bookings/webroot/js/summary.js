(function ($) {
    // $.ajax({
    //     url: "/bookings/api/users/",
    //     method: "GET",
    // });
    //
    // $.ajax({
    //     url: "/bookings/api/rooms/",
    //     method: "GET",
    // });

    $config = {
        // Rendering
        renderTo: ".calendar-div",
        month: (new Date()).getMonth(),

        // Requests
        userIds: [],
        roomIds: []
    };

    var calendar = new Calendar($config);

    calendar.render();

})(jQuery);
