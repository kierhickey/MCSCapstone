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

    var formatDate = function (date) {
        var retVal = "";
        var year = date.getFullYear();
        var month = date.getMonth() + 1;
        var day = date.getDate();

        console.log(date);

        retVal += year;
        retVal += "-";
        retVal += month.length === 1
            ? "0" + month
            : month;
        retVal += "-";
        retVal += day.length === 1
            ? "0" + day
            : day

        return retVal;
    }

    $config = {
        // Rendering
        renderTo: ".calendar-div",
        month: (new Date()).getMonth(),

        // Requests
        userIds: [],
        roomIds: [],
        onDateChanged: function (e) {
            // Do summary
            $.ajax({
                url: "/bookings/api/bookings/summary",
                method: "POST",
                data: {
                    startDate: formatDate(e.currentDate),
                    endDate: formatDate(e.currentDate)
                },
                success: function (response) {
                    console.log(response.responseData);
                },
                failure: function (response) {
                    console.log(response);
                }
            })
        }
    };

    var calendar = new Calendar($config);

    calendar.render();

})(jQuery);
