var rooms = [];

$.ajax({
        url: "/bookings/api/rooms",
        method: "GET",
        success: function (response) {
            rooms = response;
            console.log(response);
            var roomSelector = getRoomOptions();
            console.log(roomSelector);
            $(".roomBookSelect").append(roomSelector);
            
            var idMatches = window.location.href.match(/room\/([^\/]*)\//);

            if (idMatches) {
                var room_num = idMatches[1];
                $("select.roomBookSelect").val(room_num);
}
            

        }
    });




function getRoomOptions() {
    
        var uniqueLocations = [];

        for (var i = 0; i < rooms.length; i++) {
            if (uniqueLocations.indexOf(rooms[i].location) < 0) {
                uniqueLocations.push(rooms[i].location);
            }
        }


        var optGroups = [];
        var options = [];

        for (var a = 0; a < uniqueLocations.length; a++) {
            for (var i = 0; i <rooms.length; i++) {
                var room = rooms[i];

                if (room.location !== uniqueLocations[a]) continue;

                options.push($("<option></option>", {
                    class: "room-option",
                    value: room.roomId,
                    text: room.name
                }));

                if (uniqueLocations.length === 1) return options;
            }

            optGroups.push($("<optgroup></optgroup>", {
                class: 'room-optgroup',
                label: uniqueLocations[a],
                html: options
            }));

            options = [];
        }
        return optGroups;
    };


