var SummaryTable = function (config) {

    // Inject CSS
    $("head").append(
        [
            $("<link/>", {
                "rel": "stylesheet",
                "href": "webroot/css/summary.css",
                "type": "text/css"
            }),
            $("<link/>", {
                "rel": "stylesheet",
                "media": "print",
                "href": "webroot/css/summary-print.css",
                "type": "text/css"
            }),
        ]
    );

    var _formatDate = function (date) {
        var y,m,d;
        var retVal = "";

        y = date.getFullYear();
        m = date.getMonth() + 1;
        d = date.getDate();

        retVal += d.length === 1
            ? "0" + d
            : d;
        retVal += "/";
        retVal += m.length === 1
            ? "0" + m
            : m;
        retVal += "/";
        retVal += y

        return retVal;
    };

    /**
     * Gets the day suffix for the given day
     * @param  {[type]} day [description]
     * @return {[type]}     [description]
     */
    var _getDaySuffix = function (day) {
        var nst = /([^1]1|^1)$/;
        var nnd = /([^1]2|^2)$/;
        var nrd = /([^1]3|^3)$/;

        if (nst.test(day)) return "st";
        if (nnd.test(day)) return "nd";
        if (nrd.test(day)) return "rd";
        return "th";
    }

    /**
     * Gets the date as "{DayName} the {d}{suffix} of {monthName}, {y}}"
     * @param  {[type]} date [description]
     * @return {[type]}      [description]
     */
    var _dateAsReadable = function (date) {
        var y = date.getFullYear();
        var m = date.getMonth();
        var d = date.getDate();
        var dow = date.getDay();

        var days = [
            "Sunday",
            "Monday",
            "Tuesday",
            "Wednesday",
            "Thursday",
            "Friday",
            "Saturday"
        ];
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
        ]

        var dayName = days[dow];
        var suffix = _getDaySuffix(d);
        var monthName = months[m];

        return dayName + " the " + d + "<sup>" + suffix + "</sup> of " + monthName + ", " + y;
    };

    var _dateRangeAsReadable = function(startDate, endDate) {
        var y = startDate.getFullYear();
        var m = startDate.getMonth();
        var sD = startDate.getDate();
        var eD = endDate.getDate()
        var sDow = startDate.getDay();
        var eDow = endDate.getDay();

        var days = [
            "Sunday",
            "Monday",
            "Tuesday",
            "Wednesday",
            "Thursday",
            "Friday",
            "Saturday"
        ];
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
        ]

        var startDayName = days[sDow];
        var endDayName = days[eDow];
        var startSuffix = _getDaySuffix(sD);
        var endSuffix = _getDaySuffix(eD);
        var monthName = months[m];

        return startDayName + " the " + sD + "<sup>" + startSuffix + "</sup>" + " to " + endDayName + " the " + eD + "<sup>" + endSuffix + "</sup>" + " of " + monthName + ", " + y;
    }

    var createRow = function (booking) {
        return $("<tr></tr>", {
            class: "booking-row",
            id: booking.bookingId,
            html: [
                $("<td></td>", {
                    class: "booking-username booking-cell",
                    text: booking.username
                }),
                $("<td></td>", {
                    class: "booking-displayname booking-cell",
                    text: booking.displayname
                }),
                $("<td></td>", {
                    class: "booking-end booking-cell",
                    text: booking.location
                }),
                $("<td></td>", {
                    class: "booking-end booking-cell",
                    text: booking.roomName
                }),
                $("<td></td>", {
                    class: "booking-start booking-cell",
                    text: booking.bookingStart
                }),
                $("<td></td>", {
                    class: "booking-end booking-cell",
                    text: booking.bookingEnd
                }),
                $("<td></td>", {
                    class: "booking-paid booking-cell",
                    text: booking.paid.toLowerCase() === "true" ? "Paid" : "Not Paid"
                }),
            ]
        });
    }

    var sorts = [];

    var props = ["username", "displayname", "bookingStart", "bookingEnd"];

    for (var i = 0; i < props.length; i++) {
        // Create closure to prevent only the last property working
        (function (prop) {
            sorts[prop] = [];
            sorts[prop]["asc"] = function (a,b) {
                if (a[prop] < b[prop]) return -1;
                if (a[prop] > b[prop]) return 1;
                return 0;
            };
            sorts[prop]["desc"] = function (a,b) {
                if (a[prop] > b[prop]) return -1;
                if (a[prop] < b[prop]) return 1;
                return 0;
            };
        })(props[i]);
    }

    return {
        renderTo: config.renderTo || null,
        bookings: config.bookings || [],
        rendered: false,
        cls: config.cls || "summary-table",
        headerCls: config.headerCls || "summary-header",
        bodyCls: config.bodyCls || "summary-body",
        emptyText: "No bookings for the selected date...",
        startDate: new Date(),
        endDate: new Date(),

        init: function () {
            var me = this;

            $(document).on("click", ".header-cell", function (e) {
                me.headerClick(e);
            });
        },

        setBookings(bookings) {
            var me = this;

            me.bookings = bookings || [];
            me.update();
        },

        getRows: function() {
            var me = this;
            var rows = [];

            for (var i = 0; i < me.bookings.length; i++) {
                rows.push(createRow(me.bookings[i]));
            }

            if (rows.length === 0) {
                rows.push($("<tr></tr>", {
                    html: $("<td></td>", {
                        class: "summary-empty",
                        colspan: 7,
                        text: me.emptyText
                    })
                }));
            }

            return rows;
        },

        headerClick: function (e) {
            var me = this;
            var header = e.target;
            var classes = e.target.classList;
            var sortTarget = classes[0].match(/header-(.*)/)[1];
            var asc = true;

            if (classes.contains("asc")) {
                $(header).removeClass("asc");
                $(header).addClass("desc");

                asc = false;
            } else if (classes.contains("desc")) {
                $(header).removeClass("desc");
                $(header).addClass("asc");
            } else {
                $("." + me.headerCls + " .asc").removeClass("asc");
                $("." + me.headerCls + " .desc").removeClass("desc");

                $(header).addClass("asc");
            }


            switch (sortTarget) {
                case "start":
                    sortTarget = "bookingStart";
                    break;
                case "end":
                    sortTarget = "bookingEnd";
                    break;
                default:
                    break;
            }

            var sort = sorts[sortTarget][asc ? "asc" : "desc"];
            me.bookings.sort(sort);

            me.update();
        },

        setStartDate: function (d) {
            var me = this;

            me.startDate = d;
            me.update();
        },

        setEndDate: function (d) {
            var me = this;

            me.endDate = d;
            me.update();
        },

        getStartDate: function () {
            var me = this;

            return me.startDate;
        },

        getEndDate: function () {
            var me = this;

            return me.endDate;
        },

        getHeaderText: function () {
            var me = this;
            var startDate = me.getStartDate();
            var endDate = me.getEndDate();

            if (startDate > endDate) {
                var temp = endDate;
                endDate = startDate;
                startDate = temp;
            }

            if (startDate.getDate() == endDate.getDate()) {
                return "Bookings for " + _dateAsReadable(startDate);
            }
            return "Bookings for " + _dateRangeAsReadable(startDate, endDate);
        },

        update: function () {
            var me = this;

            if (!me.rendered) return me.render();

            var rows = me.getRows();

            $("." + me.bodyCls).empty();
            $("." + me.bodyCls).append(rows);
            $("." + me.headerCls + "-title").html(me.getHeaderText());
        },

        render: function () {
            var me = this;

            if (me.rendered) {
                me.update();
                return;
            }

            me.init();

            var rows = me.getRows();

            var summTable = $("<table></table>", {
                class: me.cls,
                html: [
                    $("<thead></thead>", {
                        class: me.headerCls,
                        html: [
                            $("<tr></tr>", {
                                html: $("<th></th>", {
                                    colspan: 7,
                                    class: me.headerCls + "-inner",
                                    html: [
                                        $("<span></span>", {
                                            class: me.headerCls + "-title",
                                            html: me.getHeaderText()
                                        }),
                                        $("<div></div>", {
                                            class: "header-button",
                                            html: $("<img/>", {
                                                class: "header-button-icon",
                                                src: "webroot/images/ui/material/download-white-x24.svg"
                                            }),
                                            on: {
                                                click: function () {

                                                }
                                            }
                                        }),
                                        $("<div></div>", {
                                            class: "header-button",
                                            html: $("<img/>", {
                                                class: "header-button-icon",
                                                src: "webroot/images/ui/material/print-white-x24.svg"
                                            }),
                                            on: {
                                                click: function () {
                                                    window.print();
                                                }
                                            }
                                        })
                                    ]
                                })
                            }),
                            $("<tr></tr>", {
                                class: "summary-col-headers",
                                html: [
                                    $("<th></th>", {
                                        class: "header-username header-cell",
                                        text: "Username"
                                    }),
                                    $("<th></th>", {
                                        class: "header-displayname header-cell",
                                        text: "Display Name"
                                    }),
                                    $("<th></th>", {
                                        class: "header-location header-cell",
                                        text: "Location"
                                    }),
                                    $("<th></th>", {
                                        class: "header-room-name header-cell",
                                        text: "Room Name"
                                    }),
                                    $("<th></th>", {
                                        class: "header-start header-cell",
                                        text: "Start Time"
                                    }),
                                    $("<th></th>", {
                                        class: "header-end header-cell",
                                        text: "End Time"
                                    }),
                                    $("<th></th>", {
                                        class: "header-paid header-cell",
                                        text: "Paid"
                                    }),
                                ]
                            })
                        ]
                    }),
                    $("<tbody></tbody>", {
                        class: me.bodyCls,
                        html: rows
                    })
                ]
            });

            $(me.renderTo).empty();
            $(me.renderTo).append(summTable);

            me.rendered = true;
        }
    };
}
