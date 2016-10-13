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
        roomIds: [],
        onDateChanged: function (e) {
            console.log("Previous Date:" + e.previousDate);
            console.log("Current Date:" + e.currentDate);
        }
    };

    var calendar = new Calendar($config);

    calendar.render();

})(jQuery);
