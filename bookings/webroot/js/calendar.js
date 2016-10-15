/*jshint laxbreak: true*/
var Calendar = function (config) {
    'use strict';
    var me = {};

    // Variables
    me.day = config.day || (new Date()).getDay();
    me.month = config.month || (new Date()).getMonth();
    me.year = config.year || (new Date()).getFullYear();
    me.renderTo = config.renderTo || null;
    me.userIds = config.userIds || [];
    me.roomIds = config.roomIds || [];
    me.cls = config.cls || "calendar-table";
    me.headerCls = config.headerCls || "calendar-header";
    me.rendered = false;
    me.map = [];

    // Events
    me.onDateChanged = config.onDateChanged || function () {};
    me.onDayChanged = config.onDayChanged || function () {};
    me.onMonthChanged = config.onMonthChanged || function () {};
    me.onYearChanged = config.onYearChanged || function () {};

    // Inject CSS
    $("head").append($("<link/>", {
        "rel": "stylesheet",
        "href": "webroot/css/calendar.css",
        "type": "text/css"
    }));

    $(document).on(
        "click", ".calendar-day-cell", function (ev) {
            var self = ev.target;
            var parent = self.parentElement;
            var ptNumRegex = /^week-([^\s]*)$/
            var weekNum = alphaToNum(parent.classList[0].match(ptNumRegex)[1]);
            var dayNum = alphaToNum(self.classList[1]);
            var dayDate = me.map[weekNum - 1][dayNum - 1];

            if (dayDate === undefined) return;

            me.setDate(me.year, me.month, dayDate);
        }
    );

    var alphaToNum = function (text) {
        var store = {
            one: 1,
            two: 2,
            three: 3,
            four: 4,
            five: 5,
            six: 6,
            seven: 7,
            eight: 8,
            nine: 9
        };

        return store[text] || 0;
    }

    var getCalendarRows = function () {
        var rows = [];

        for (var i = 0; i < 6; i++) {
            var week = "";

            if (i === 0) week = "one";
            if (i === 1) week = "two";
            if (i === 2) week = "three";
            if (i === 3) week = "four";
            if (i === 4) week = "five";
            if (i === 5) week = "six";

            rows.push($("<tr></tr>", {
                class: "week-" + week + " week",
                html: [
                    "<td class=\"calendar-day-cell one\"></td>",
                    "<td class=\"calendar-day-cell two\"></td>",
                    "<td class=\"calendar-day-cell three\"></td>",
                    "<td class=\"calendar-day-cell four\"></td>",
                    "<td class=\"calendar-day-cell five\"></td>",
                    "<td class=\"calendar-day-cell six\"></td>",
                    "<td class=\"calendar-day-cell seven\"></td>",
                ]
            }));
        }

        return rows;
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

            // Set our week to an empty array
            me.map[weekIndex] = [];

            for (dayIndex = 0; dayIndex < days.length; dayIndex++) {
                var day = days[dayIndex];

                if (!(weekIndex === 0 && dayIndex < calendarStartDay) && (dateDay <= lastDay)) {
                    // Map our dates to an array behind the scene.
                    me.map[weekIndex][dayIndex] = dateDay;

                    $(day).text(dateDay);
                    dateDay++;
                } else {
                    $(day).empty();
                }
            }
        }
    };

    me.getDayAt = function(x,y) {
        return me.map[x][y];
    };

    // Gets the last day of the current month
    me.getLastDayOfMonth = function() {
        var day;

        switch (me.month + 1) {
            case 4:
            case 6:
            case 9:
            case 11:
                day = 30;
                break;
            case 2:
                if (me.year % 4 === 0) {
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
            m = 11;
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


    me.getDay = function (dayIndex) {
        dayIndex = dayIndex || me.day;

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
    };

    me.getCellFromDate = function () {
        debugger;
        for (var y = 0; y < me.map.length; y++) {
            for (var x = 0; x < me.map[y].length; x++) {
                if (me.map[y][x] == me.day) {
                    var week = $(".week")[y];
                    var cell = $(week).children(".calendar-day-cell")[x];

                    return cell;
                }
            }
        }
    };

    me.getDate = function () {
        return new Date(me.year, me.month, me.day);
    }

    me.setDate = function (y,m,d) {
        if (y <= 1900 || m < 0 || d < 1 || m > 11 || d > 31) {
            throw "Date out of range";
        }

        // The previous date
        var prevDate = me.getDate();

        me.year = y;
        me.month = m;
        me.day = d;

        // The current date
        var currDate = me.getDate();

        // Has our date changed?
        var dateChanged = false;

        // Year changed
        if (prevDate.getFullYear() !== currDate.getFullYear()) {
            me.onYearChanged();
            dateChanged = true;
        }
        // Month changed
        if (prevDate.getMonth() !== currDate.getMonth()) {
            me.onMonthChanged();
            dateChanged = true;
        }
        // Day changed
        if (prevDate.getDate() !== currDate.getDate()) {
            me.onDayChanged();
            dateChanged = true;
        }

        // No change, no need to do anything.
        if (!dateChanged) return;

        // Our date changed event.
        me.onDateChanged({
            previousDate: prevDate,
            currentDate: currDate
        });

        // Update the UI
        me.update();
    };

    me.update = function () {
        if (!me.rendered) return me.render();

        $(".calendar-month").text(me.getCurrentMonth() + " " + me.getCurrentYear());

        updateWeeks();

        var cell = me.getCellFromDate();
        $("." + me.cls + " .selected").removeClass("selected");
        $(cell).addClass("selected");
    };

    // Renders the calendar
    me.render = function () {
        if (me.rendered) return me.update();

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

        me.rendered = true;

        me.update();
    };

    return me;
};
