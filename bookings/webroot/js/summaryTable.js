var SummaryTable = function (config) {

    // Inject CSS
    $("head").append($("<link/>", {
        "rel": "stylesheet",
        "href": "webroot/css/summary.css",
        "type": "text/css"
    }));

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
    }

    var createRow = function (booking) {
        return $("<tr></tr>", {
            class: "booking-row",
            id: booking.bookingId,
            html: [
                $("<td></td>", {
                    class: "booking-date",
                    text: _formatDate(booking.bookingDate)
                }),
                $("<td></td>", {
                    class: "booking-username",
                    text: booking.username
                }),
                $("<td></td>", {
                    class: "booking-displayname",
                    text: booking.displayname
                }),
                $("<td></td>", {
                    class: "booking-start",
                    text: booking.bookingStart
                }),
                $("<td></td>", {
                    class: "booking-end",
                    text: booking.bookingEnd
                }),
                $("<td></td>", {
                    class: "booking-paid",
                    text: booking.paid || false
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

    // sorts["username"] = function (a,b) {
    //     if (a.username < b.username) return -1;
    //     if (a.username > b.username) return 1;
    //     return 0;
    // };
    //
    // sorts["displayname"] = function (a,b) {
    //     if (a.displayname < b.displayname) return -1;
    //     if (a.displayname > b.displayname) return 1;
    //     return 0;
    // };
    //
    // sorts["start"] = function (a,b) {
    //     if (a.bookingStart < b.bookingStart) return -1;
    // }

    return {
        renderTo: config.renderTo || null,
        bookings: config.bookings || [],
        rendered: false,
        cls: config.cls || "summary-table",
        headerCls: config.headerCls || "summary-header",
        bodyCls: config.bodyCls || "summary-body",
        emptyText: "No bookings for the selected date...",

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
                        colspan: 6,
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

        update: function () {
            var me = this;

            if (!me.rendered) return me.render();

            var rows = me.getRows();

            $("." + me.bodyCls).empty();
            $("." + me.bodyCls).append(rows);
        },

        render: function () {
            var me = this;

            if (me.rendered) return me.update();

            me.init();

            var rows = me.getRows();

            var summTable = $("<table></table>", {
                class: me.cls,
                html: [
                    $("<thead></thead>", {
                        class: me.headerCls,
                        html: $("<tr></tr>", {
                            html: [
                                $("<th></th>", {
                                    class: "header-date header-cell",
                                    text: "Date"
                                }),
                                $("<th></th>", {
                                    class: "header-username header-cell",
                                    text: "Username"
                                }),
                                $("<th></th>", {
                                    class: "header-displayname header-cell",
                                    text: "Display Name"
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
