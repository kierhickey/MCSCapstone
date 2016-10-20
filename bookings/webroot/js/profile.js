(function ($) {
    var userId = $(".id-value").val();

    var roomsLoaded = false;

    var rooms = [];

    $.ajax({
        url: "/bookings/api/rooms",
        method: "GET",
        success: function (response) {
            roomsLoaded = true;
            rooms = response;

            createPage();
        }
    });

    var formatDate = function (date) {
        var retVal = "";
        var year = date.getFullYear();
        var month = date.getMonth() + 1;
        var day = date.getDate();

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
    };

    var createPage = function () {
        if (!roomsLoaded) return;

        var summConfig = {
            renderTo: ".selected-summary",
        }

        var summaryTable = new SummaryTable(summConfig);

        var config = {
            // Rendering
            renderTo: ".calendar-container",
            month: (new Date()).getMonth(),

            // Requests
            userId: userId,
            rooms: rooms,
            userFilterEnabled: false,
            onDateChanged: function (e) {
                // Do summary
                $.ajax({
                    url: "/bookings/api/bookings/summary",
                    method: "POST",
                    data: {
                        startDate: formatDate(e.currentDate),
                        endDate: formatDate(e.currentDate),
                        userId: e.userId === "" ? null : e.userId,
                        roomId: e.roomId === "" ? null : e.roomId
                    },
                    success: function (response) {
                        response.responseData.each(function (booking) {
                            booking.bookingDate = e.currentDate;
                        });
                        summaryTable.setDate(e.currentDate);
                        summaryTable.setBookings(response.responseData);
                    },
                    failure: function (response) {
                        console.log(response);
                    }
                })
            }
        };

        var calendar = new Calendar(config);

        calendar.render();
    };

})(jQuery);