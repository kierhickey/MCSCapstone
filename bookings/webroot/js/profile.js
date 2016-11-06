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
            showPaidColumn: false,
            userId: userId
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
            onDateRangeChanged: function (e) {
                var startDate = e.startDate.curr;
                var endDate = e.endDate.curr;

                if (startDate > endDate) {
                    startDate = e.endDate.curr;
                    endDate = e.startDate.curr;
                }

                // Do summary
                $.ajax({
                    url: "/bookings/api/bookings/summary",
                    method: "POST",
                    data: {
                        startDate: formatDate(startDate),
                        endDate: formatDate(endDate),
                        userId: e.userId === "" ? null : e.userId,
                        roomId: e.roomId === "" ? null : e.roomId
                    },
                    success: function (response) {
                        summaryTable.setStartDate(e.startDate.curr);
                        summaryTable.setEndDate(e.endDate.curr);
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
