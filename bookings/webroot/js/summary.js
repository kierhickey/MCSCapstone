(function ($) {
    var usersLoaded = false;
    var roomsLoaded = false;

    var users = [];
    var rooms = [];

    $.ajax({
        url: "/bookings/api/users",
        method: "GET",
        success: function (response) {
            usersLoaded = true;
            users = response;

            createPage();
        }
    });

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
        if (!(usersLoaded && roomsLoaded)) return;

        var summConfig = {
            renderTo: ".selected-summary",
        }

        var summaryTable = new SummaryTable(summConfig);

        var config = {
            // Rendering
            renderTo: ".calendar-container",
            month: (new Date()).getMonth(),

            // Requests
            users: users,
            rooms: rooms,
            onDateRangeChanged: function (e) {
                var getStart, getEnd;

                if (e.startDate.curr > e.endDate.curr) {
                    getStart = e.endDate.curr;
                    getEnd = e.startDate.curr;
                } else {
                    getStart = e.startDate.curr;
                    getEnd = e.endDate.curr;
                }

                // Do summary
                $.ajax({
                    url: "/bookings/api/bookings/summary",
                    method: "POST",
                    data: {
                        startDate: formatDate(getStart),
                        endDate: formatDate(getEnd),
                        userId: e.userId === "" ? null : e.userId,
                        roomId: e.roomId === "" ? null : e.roomId
                    },
                    success: function (response) {
                        console.log(response);
                        summaryTable.setStartDate(e.startDate.curr);
                        summaryTable.setEndDate(e.endDate.curr);
                        summaryTable.setBookings(response.responseData);
                        summaryTable.userId = e.userId;
                        summaryTable.roomId = e.roomId;
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
