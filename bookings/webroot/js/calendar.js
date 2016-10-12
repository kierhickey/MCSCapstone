// Calendar class;
var Calendar = function ($config) {
    var me = this;

    me.day = $config.day || (new Date()).getDay();
    me.month = $config.month || (new Date()).getMonth();
    me.year = $config.year || (new Date()).getFullYear();
    me.renderTo = $config.renderTo || null;
    me.userIds = $config.userIds || [];
    me.roomIds = $config.roomIds || [];
    me.cls = $config.cls || "calendar-table";
    me.headerCls = $config.headerCls || "calendar-header";

    // Inject CSS
    $("head").append($("<link/>", {
        "rel": "stylesheet",
        "href": "webroot/css/calendar.css",
        "type": "text/css"
    }));

    // Gets the last day of the current month
    me.getLastDayOfMonth = function() {
        switch (me.month) {
            case 4:
            case 6:
            case 9:
            case 11:
                day = 30;
                break;
            case 2:
                if (date.getYear() % 4 === 0) {
                    day = 29;
                } else {
                    day = 28;
                }
                break;
            default:
                day = 31;
                break;
        }

        return day;
    };

    // Displays the next month
    me.nextMonth = function () {
        var y,m;

        y = me.year;

        if (me.month === 11) {
            m = 0;
            y = me.year + 1;
        } else {
            m = me.month + 1;
        }

        me.setDate(y, m, 1);

        me.update();
    };

    // Displays the previous month
    me.prevMonth = function () {
        var y,m;

        y = me.year;

        if (me.month === 0) {
            m = 12;
            y = me.year - 1;
        } else {
            m = me.month - 1;
        }

        me.setDate(y, m, 1);

        me.update();
    };

    me.getCurrentMonth = function () {
        var months = [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
            "September",
            "October",
            "November",
            "December"
        ];

        return months[me.month];
    };

    me.getCurrentYear = function() {
        return me.year;
    };

    var getCalendarRows = function () {
        var rows = [];

        for (var i = 0; i < 5; i++) {
            var week = "";

            if (i === 0) week = "one";
            if (i === 1) week = "two";
            if (i === 2) week = "three";
            if (i === 3) week = "four";
            if (i === 4) week = "five";

            rows.push($("<tr></tr>", {
                class: "week-" + week + " week",
                html: [
                    "<td class=\"calendar-day-cell\"></td>",
                    "<td class=\"calendar-day-cell\"></td>",
                    "<td class=\"calendar-day-cell\"></td>",
                    "<td class=\"calendar-day-cell\"></td>",
                    "<td class=\"calendar-day-cell\"></td>",
                    "<td class=\"calendar-day-cell\"></td>",
                    "<td class=\"calendar-day-cell\"></td>",
                ]
            }));
        }

        return rows;
    };

    me.getDay = function (dayIndex = me.day) {
        var days = [
            "Sunday",
            "Monday",
            "Tuesday",
            "Wednesday",
            "Thursday",
            "Friday",
            "Saturday"
        ];

        return days[dayIndex];
    }

    me.setDate = function (y,m,d) {
        if (y <= 1900 || m < 0 || d < 0 || m > 11 || d > 30) {
            throw new Exception("Date out of range");
        }

        me.year = y;
        me.month = m;
        me.day = d;

        $(me.cls).trigger(new Event("datechanged"));

        me.update();
    };

    var updateWeeks = function () {
        var weeks = $(".week");
        var weekIndex = 0;

        var trueStartDay = (new Date(me.year, me.month, 1)).getDay();
        var calendarStartDay = trueStartDay === 0
            ? 6
            : trueStartDay - 1;

        var dateDay = 1;
        var lastDay = me.getLastDayOfMonth();

        for (weekIndex = 0; weekIndex < weeks.length; weekIndex++) {
            var week = weeks[weekIndex];
            var days = $(week).children(".calendar-day-cell");
            var dayIndex = 0;

            for (dayIndex = 0; dayIndex < days.length; dayIndex++) {
                var day = days[dayIndex];

                if (!(weekIndex === 0 && dayIndex < calendarStartDay) && (dateDay <= lastDay)) {
                    debugger;
                    $(day).text(dateDay);
                    dateDay++;
                } else {
                    $(day).empty();
                }
            }
        }
    };

    me.update = function () {
        $(".calendar-month").text(me.getCurrentMonth() + " " + me.getCurrentYear());

        updateWeeks();
    };

    // Renders the calendar
    me.render = function () {
        var calendarRows = getCalendarRows();
        var calendar = $("<table></table>", {
            class: me.cls,
            html: [
                $("<thead></thead>", {
                    html: [
                        $("<tr></tr>", {
                            class: me.headerCls,
                            html: [
                                $("<th></th>", {
                                    colspan: 7,
                                    html: [
                                        $("<a></a>", {
                                            class: "calendar-prev-arrow",
                                            html: "&lt;",
                                            on: {
                                                click: me.prevMonth
                                            }
                                        }),
                                        $("<span></span>", {
                                            class: "calendar-month",
                                        }),
                                        $("<a></a>", {
                                            class: "calendar-next-arrow",
                                            html: "&gt;",
                                            on: {
                                                click: me.nextMonth
                                            }
                                        })
                                    ]
                                })
                            ]
                        }),

                        $("<tr></tr>", {
                            class: "calendar-day-gutter",
                            html: $("<th></th>", {
                                colspan: 7,
                                html: [
                                    "<div class=\"calendar-day\">Monday</div>",
                                    "<div class=\"calendar-day\">Tuesday</div>",
                                    "<div class=\"calendar-day\">Wednesday</div>",
                                    "<div class=\"calendar-day\">Thursday</div>",
                                    "<div class=\"calendar-day\">Friday</div>",
                                    "<div class=\"calendar-day\">Saturday</div>",
                                    "<div class=\"calendar-day\">Sunday</div>",
                                ]
                            })
                        }),
                    ]
                }),

                $("<tbody></tbody>", {
                    class: "calendar-container",
                    html: calendarRows
                })
            ]
        });

        $(me.renderTo).empty();
        $(me.renderTo).append(calendar);

        me.update();
    };
};
