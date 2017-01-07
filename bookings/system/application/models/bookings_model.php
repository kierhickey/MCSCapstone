<?php
require_once __DIR__."/../../libraries/Model.php";
require_once __DIR__."/../data/ResultState.class.php";

class Bookings_model extends Model
{
    // Weird table nonsense that shouldn't be here.
    public $table_headings = '';
    public $table_rows = array();

    public function Bookings_model()
    {
        parent::Model();
        $this->CI = &get_instance();
    }

    public function isRecurring($bookingId) {
        $queryString = "SELECT date FROM bookings WHERE booking_id = $bookingId;";

        $query = $this->db->query($queryString);
        if ($query != false) {
            $rArray = $query->result_array();
            $result = $rArray[0]["date"];
        }

        return is_null($result);
    }

    public function markRecurringAsUnpaid($bookingId, $date = NULL, $note = "") {
        if (!$this->isRecurring($bookingId)) { // We can throw it to the other method.
            return $this->markAsPaid($bookingId, $note);
        }

        // Check if paid exists :)
        $queryString = "SELECT COUNT(*) as total FROM payments WHERE booking_id = $bookingId AND for_date = '$date'";

        $query = $this->db->query($queryString);

        if ($query == false) {
            return [
                "status" => 500,
                "error" => "Invalid query - bad booking id?",
                "queryString" => $queryString
            ];
        }

        $num = $query->result_array()[0]["total"];

        if ($num < 1) { // Already paid
            return [
                "status" => 200,
                "message" => "Booking not paid."
            ];
        }

        // A payment entry -- delete it
        $queryString = "DELETE FROM payments
        WHERE booking_id = $bookingId AND for_date = '$date'";

        $query = $this->db->query($queryString);

        if ($query == false) {
            return [
                "status" => 500,
                "error" => "Failed to mark as paid."
            ];
        }

        return [
            "status" => 200,
            "message" => "Booking succesfully marked as paid."
        ];
    }

    public function markAsUnpaid($bookingId, $note = "") {
        if ($this->isRecurring($bookingId)) {
            return [
                "status" => 400,
                "message" => "Attempted to mark recurring booking as paid without target date."
            ];
        }

        // Check if payment exists.
        $queryString = "SELECT COUNT(*) as total FROM payments WHERE booking_id = $bookingId";

        $query = $this->db->query($queryString);

        if ($query == false) {
            return [
                "status" => 500,
                "error" => "Invalid query - bad booking id?",
                "queryString" => $queryString
            ];
        }

        $num = $query->result_array()[0]["total"];

        if ($num != 1) { // Already paid
            return [
                "status" => 200,
                "message" => "Booking not paid.",
                "count" => $num
            ];
        }

        // A payment entries -- make one
        $queryString = "DELETE FROM payments WHERE booking_id = $bookingId";

        $query = $this->db->query($queryString);

        if ($query == false) {
            return [
                "status" => 500,
                "error" => "Failed to mark as unpaid."
            ];
        }

        return [
            "status" => 200,
            "message" => "Booking succesfully marked as unpaid."
        ];
    }

    public function markRecurringPaid($bookingId, $date = NULL, $note = "") {
        if (!$this->isRecurring($bookingId)) { // We can throw it to the other method.
            return $this->markAsPaid($bookingId, $note);
        }

        // Check if paid exists :)
        $queryString = "SELECT COUNT(*) as total FROM payments WHERE booking_id = $bookingId AND for_date = '$date'";

        $query = $this->db->query($queryString);

        if ($query == false) {
            return [
                "status" => 500,
                "error" => "Invalid query - bad booking id?",
                "queryString" => $queryString
            ];
        }

        $num = $query->result_array()[0]["total"];

        if ($num >= 1) { // Already paid
            return [
                "status" => 200,
                "message" => "Booking already paid.",
                "count" => $num
            ];
        }

        // No payment entries -- make one
        $queryString = "INSERT INTO payments(booking_id, for_date, notes)
        VALUES ($bookingId, '$date', '$note')";

        $query = $this->db->query($queryString);

        if ($query == false) {
            return [
                "status" => 500,
                "error" => "Failed to mark as paid."
            ];
        }

        return [
            "status" => 200,
            "message" => "Booking succesfully marked as paid."
        ];
    }

    public function markAsPaid($bookingId, $note = "") {
        if ($this->isRecurring($bookingId)) {
            return [
                "status" => 400,
                "message" => "Attempted to mark recurring booking as paid without target date."
            ];
        }

        // Check if payment exists.
        $queryString = "SELECT COUNT(*) as total FROM payments WHERE booking_id = $bookingId";

        $query = $this->db->query($queryString);

        if ($query == false) {
            return [
                "status" => 500,
                "error" => "Invalid query - bad booking id?",
                "queryString" => $queryString
            ];
        }

        $num = $query->result_array()[0]["total"];

        if ($num >= 1) { // Already paid
            return [
                "status" => 200,
                "message" => "Booking already paid.",
                "count" => $num
            ];
        }

        // No payment entries -- make one
        $queryString = "INSERT INTO payments(booking_id, for_date, notes)
        VALUES ($bookingId, NULL, '$note')";

        $query = $this->db->query($queryString);

        if ($query == false) {
            return [
                "status" => 500,
                "error" => "Failed to mark as paid."
            ];
        }

        return [
            "status" => 200,
            "message" => "Booking succesfully marked as paid."
        ];
    }

    public function getPaymentsForRecurringBooking($bookingId) {
        $queryString = "SELECT booking_id, for_date FROM payments WHERE booking_id = $bookingId";

        $query = $this->db->query($queryString);

        if ($query == false) {
            return [
                "status" => 500,
                "message" => "Unable to complete request"
            ];
        }

        $rArray = $query->result_array();

        $result = [];

        foreach($rArray as $array) {
            $bookingId = $array["booking_id"];
            $date = $array["for_date"];

            if (!isset($result[$bookingId])) {
                $result[$bookingId] = [];
            }

            array_push($result[$bookingId], $date);
        }

        return $result;
    }

    public function getBookingsForPeriod($startDate, $endDate, $userId, $roomId) {
        $bookingsForPeriod = $this->getByTimespan($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));

        $filteredBookings = [];
        $recurringBookings = [];

        $filteredDateSessions = [];

        foreach ($bookingsForPeriod as $booking) {
            $forRoom = true;
            $forUser = true;

            if ($roomId != null && $booking["roomId"] != $roomId) {
                $forRoom = false;
            } else if ($userId != null && $booking["userId"] != $userId) {
                $forUser = false;
            }

            if ($forRoom && $forUser) {
                $bookingRoomId = $booking["roomId"];

                if ($booking["isRecurring"] === "false") {

                    if ($filteredDateSessions[$bookingRoomId . $booking["bookingDate"]] == NULL) {
                        $filteredDateSessions[$bookingRoomId . $booking["bookingDate"]] = [];
                    }

                    // Push to our catalogue for this date
                    array_push($filteredDateSessions[$bookingRoomId.$booking["bookingDate"]], $booking["bookingStart"]);

                    $booking["isRecurring"] = false;
                    array_push($filteredBookings, $booking);
                } else {
                    $booking["isRecurring"] = true;
                    array_push($recurringBookings, $booking);
                }
            }
        }

        $expandedRecurring = [];

        foreach ($recurringBookings as $booking) {
            $dow = $booking["dayNum"];

            $paidDates = $this->getPaymentsForRecurringBooking($booking["bookingId"]);

            $bookingsForDate = DateHelper::GetDatesForDow($dow, $startDate, $endDate);

            foreach ($bookingsForDate as $bookingDate) {
                $bookingCopy = $booking;

                // If the booking has ended
                if ($booking["end_date"] != NULL && $booking["end_date"] <= $bookingDate) {
                    continue;
                }

                // Or if the booking hasn't started
                if ($booking["start_date"] != NULL && $booking["start_date"] > $bookingDate) {
                    continue;
                }

                $date = $bookingDate->format("Y-m-d");
                $roomId = $booking["roomId"];

                if (in_array($booking["bookingStart"], $filteredDateSessions[$roomId . $date])) {
                    continue;
                }

                $bookingCopy["paid"] = in_array($date, $paidDates[$booking["bookingId"]]);
                $bookingCopy["bookingDate"] = $date;

                array_push($expandedRecurring, $bookingCopy);
            }
        }

        $allBookings = array_merge($expandedRecurring, $filteredBookings);

        usort($allBookings, function ($item1, $item2) {
            //echo $item1["bookingDate"];
            //echo $item2["bookingDate"];

            $bookingOneDate = date_create_from_format('Y-m-d', $item1["bookingDate"]);
            $bookingTwoDate = date_create_from_format('Y-m-d', $item2["bookingDate"]);

            $bookingOneLocation = $item1["location"];
            $bookingTwoLocation = $item2["location"];

            $bookingOneSessionStart = $item1["bookingStart"];
            $bookingTwoSessionStart = $item2["bookingStart"];

            $bookingOneRoom = $item1["roomName"];
            $bookingTwoRoom = $item2["roomName"];

            if ($bookingOneDate == $bookingTwoDate) {
                if ($bookingOneLocation == $bookingTwoLocation) {
                    if ($bookingOneRoom == $bookingTwoRoom) {
                        if ($bookingOneSessionStart == $bookingTwoSessionStart) {
                            return 0;
                        }
                        return strcmp($bookingOneSessionStart, $bookingTwoSessionStart);
                    }
                    return strcmp($bookingOneRoom, $bookingTwoRoom);
                }
                return strcmp($bookingOneLocation, $bookingTwoLocation);
            }
            return $bookingOneDate < $bookingTwoDate ? -1 : 1;
        });

        return $allBookings;
    }

    /**
    * Gets all bookings, given a date range
    * @param  Date $startDate The date to start looking from.
    * @param  Date $endDate   The date to stop looking at.
    * @return Booking[]       The bookings for the given date range.
    */
    public function getByTimespan($startDate, $endDate) {
        if ($startDate == null || $endDate == null) {
            throw new Exception("Dates cannot be null.");
        }

        if ($startDate > $endDate) {
            throw new Exception("startDate must be before endDate");
        }

        $schoolId = $this->session->userdata("school_id");

        $queryString = "SELECT b.booking_id AS bookingId
        ,u.user_id AS userId
        ,u.username AS username
        ,(case
            when u.displayname IS NOT NULL AND u.displayname != '' then u.displayname
            when u.firstname IS NOT NULL AND u.firstname != '' then CONCAT(u.firstname, ' ', u.lastname)
            else u.username
        end) AS displayName
        ,b.date AS bookingDate
        ,b.day_num AS dayNum
        ,b.start_date
        ,b.end_date
        ,p.time_start AS bookingStart
        ,p.time_end AS bookingEnd
        ,b.room_id as roomId
        ,r.name as roomName
        ,r.location as location
        ,(case when b.date IS NULL then 'true'
            when b.date IS NOT NULL then 'false'
        end) AS isRecurring
        ,b.price
        ,(case when b.date is NOT NULL then
            (
                case when (SELECT COUNT(*) FROM payments WHERE booking_id = b.booking_id) = 1 then 'true'
                else 'false'
                end
            )
            else 'false'
        end) AS paid
        FROM bookings b
        INNER JOIN periods p
        ON b.period_id = p.period_id
        INNER JOIN users u
        ON b.user_id = u.user_id
        INNER JOIN rooms r
        ON b.room_id = r.room_id
        WHERE
        b.school_id = '$schoolId' AND
        (
            (b.date >= '$startDate' AND b.date <= '$endDate')
            OR b.date IS NULL
        )
        AND b.cancelled != 1
        AND (b.start_date <= '$endDate' OR b.start_date IS NULL)
        AND (b.end_date >= '$startDate' OR b.end_date IS NULL)
        ORDER BY b.date asc, r.location, p.time_start";

        $query = $this->db->query($queryString);

        if ($query != false) {
            $results = $query->result_array();
        } else {
            $results = ["error" => "An error has occurred when fetching the data from the server."];
        }

        foreach ($results as &$booking) {
            if ($booking["paid"] == "true") {
                $booking["paid"] = true;
            } else {
                $booking["paid"] = false;
            }
        }

        return $results;
    }

    public function getById($bookingId) {
        $bookingId = intval($bookingId);

        $queryStr = "SELECT * FROM bookings WHERE booking_id = $bookingId";

        $result = $this->db->query($queryStr);

        if ($result != false) {
            return $result->result_array()[0];
        }
        return null;
    }

    public function GetByDate($school_id = null, $date = null)
    {
        if ($school_id == null) {
            $school_id = $this->session->userdata('school_id');
        }

        if ($date == null) {
            $date = date('Y-m-d');
        }

        $day_num = date('w', strtotime($date));
        $query_str = "SELECT * FROM bookings WHERE school_id='$school_id' AND (date='$date' OR day_num=$day_num)";
        $query = $this->db->query($query_str);
        $result = $query->result_array();

        return $result;
    }

    public function TableAddColumn($td)
    {
        $this->table_headings .= $td;
    }

    public function TableAddRow($data)
    {
        $this->table_rows[] = $data;
    }

    public function Table()
    {
        $table = '<tr>'.$this->table_headings.'</tr>';
        /* foreach($this->table_rows as $row){
        $table .= '<tr>' . $row . '</tr>';
    } */
    return $table;
    }

    public function BookingCell($data, $key, $rooms, $users, $room_id, $url, $booking_date_ymd = '', $holidays = array(), $time_start = null)
    {
        $bookingDate = DateTime::createFromFormat('Y-m-d H:i:s', $booking_date_ymd . ' ' . $time_start);
        $todaysDate = new DateTime();
        $tomorrowsDate = (new DateTime())->add(new DateInterval('P1D'));
        $dayNum = $bookingDate->format('N');

        // Check if there is a booking
        if (isset($data[$key])) {

            // There's a booking for this ID, set var
            $booking = $data[$key];

            $cell['body'] = '';

            $cell['class'] = 'booking-cell ';

            $start_date = new DateTime($booking->start_date);
            $end_date = NULL;

            if ($booking->end_date != NULL) {
                $end_date = new DateTime($booking->end_date);
            }

            if ($start_date >= $bookingDate && ($end_date == NULL || $end_date <= $bookingDate)) {
                if ($bookingDate >= $tomorrowsDate) {
                    // No bookings
                    $book_url = site_url('bookings/book/'.$url);
                    $cell['class'] = 'free';

                    if ($this->userauth->CheckAuthLevel(ADMINISTRATOR, $this->authlevel)) {
                        $cell['body'] .= "<label style='color: #4183D7;'><input class='day_$dayNum' type='checkbox' name='multi[]' value='$url' /> Book</label>";
                    } else {
                        $cell['body'] .= '<a href="'.$book_url.'"><img src="webroot/images/ui/accept.gif" width="16" height="16" alt="Book" title="Book" hspace="4" align="absmiddle" />Book</a>';
                    }

                } else {
                    $cell['class'] = 'past-free';
                    $cell['body'] = '';
                }
                return $this->load->view('bookings/table/bookingcell', $cell, true);
            }

            if ($booking->date == null) {
                // If no date set, then it's a static/timetable/recurring booking
                if ($bookingDate >= $tomorrowsDate) {
                    $cell['class'] .= 'static';
                } else {
                    $cell['class'] .= 'past-static';
                }
            } else {
                // Date is set, it's a once off staff booking
                if ($bookingDate >= $tomorrowsDate) {
                    $cell['class'] .= 'casual';
                } else {
                    $cell['class'] .= 'past-casual';
                }
                $cell['body'] = '';
            }

            // Username info
            if (isset($users[$booking->user_id])) {
                $username = $users[$booking->user_id]->username;
                $displayname = trim($users[$booking->user_id]->displayname);
                if (strlen($displayname) < 2) {
                    $displayname = $username;
                }
                if($this->userauth->CheckAuthLevel(ADMINISTRATOR) || $this->session->userdata("user_id") == $booking->user_id){
                    $cell['body'] .= '<strong>'.$displayname.'</strong>';
                } else {
                    $cell['body'] .= '<em style="color: #d7d7d7;">Booked</em>';
                }
                $user = 1;
            }

            // Any notes?
            if($this->userauth->CheckAuthLevel(ADMINISTRATOR) || $this->session->userdata("user_id") == $booking->user_id){
                if ($booking->notes) {
                    if (isset($user)) {
                        $cell['body'] .= '<br />';
                    }
                    $cell['body'] .= '<span title="'.$booking->notes.'">'.character_limiter($booking->notes, 15).'</span>';
                }
            }

            // Cancel if user is an Admin, Room owner, or Booking owner
            $user_id = $this->session->userdata('user_id');

            if ($this->userauth->CheckAuthLevel(ADMINISTRATOR, $this->authlevel)
            || ($user_id == $booking->user_id)
            || (($user_id == $rooms[$room_id]->user_id) && ($booking->date != null))) {

                if ($bookingDate >= $tomorrowsDate) {
                    $cancel_msg = 'Are you sure you want to cancel this booking?';

                    if ($user_id != $booking->user_id) {
                        $cancel_msg = 'Are you sure you want to cancel this booking?\n\n(**) Please take caution, it is not your own booking!!';
                    }

                    if ($booking->date != null) {
                        $cancel_url = site_url('bookings/cancel/'.$booking->booking_id);
                    } else {
                        $cancel_url = site_url('bookings/cancel/'.$booking->booking_id."/".$booking_date_ymd);
                    }

                    if (!isset($edit)) {
                        $cell['body'] .= '<br />';
                    }

                    $cell['body'] .= '<a onclick="return confirm(\''.$cancel_msg.'\')" href="'.$cancel_url.'" title="Cancel this booking"><img src="webroot/images/ui/delete.gif" width="16" height="16" alt="Cancel" title="Cancel this booking" hspace="8" /></a>';
                }

            }
        } elseif (isset($holidays[$booking_date_ymd])) {
            $cell['class'] = 'holiday';
            $cell['body'] = $holidays[$booking_date_ymd][0]->name;
        } else {
            if ($bookingDate >= $tomorrowsDate) {
                // No bookings
                $book_url = site_url('bookings/book/'.$url);
                $cell['class'] = 'free';
                if ($this->userauth->CheckAuthLevel(ADMINISTRATOR, $this->authlevel)) {
                    $cell['body'] .= "<label style='color: #4183D7;'><input class='day_$dayNum' type='checkbox' name='multi[]' value='$url' /> Book</label>";
                } else {
                    $cell['body'] .= '<a href="'.$book_url.'"><img src="webroot/images/ui/accept.gif" width="16" height="16" alt="Book" title="Book" hspace="4" align="absmiddle" />Book</a>';
                }
            } else {
                $cell['class'] = 'past-free';
                $cell['body'] = '';
            }
        }

        return $this->load->view('bookings/table/bookingcell', $cell, true);
    }

    public function html($school_id, $display, $cols, $date, $room_id, $school, $uri = null)
    {
        if ($school_id == null) {
            $school_id = $this->session->userdata('school_id');
        }

        // Format the date to Ymd
        if ($date == null) {
            $date = Now();
            $date_ymd = date('Y-m-d', $date);
        } else {
            $date_ymd = date('Y-m-d', $date);
        }

        // Today's weekday number
        $day_num = date('w', $date);
        $day_num = ($day_num == 0 ? 7 : $day_num);

        // Get info on the current week
        $this_week = $this->WeekObj($date, $school_id);

        // Init HTML + Jscript variable
        $html = '';
        $jscript = '';

        // Put users into array with their ID as the key
        foreach ($school['users'] as $user) {
            $users[$user->user_id] = $user;
        }

        // Get rooms
        $rooms = $this->Rooms($school_id);
        if ($rooms == false) {
            $html .= $this->load->view('msgbox/error', 'There are no rooms available. Please contact your administrator.', true);

            return $html;
        }

        // Find out which columns to display and which view type we use
        $style = $this->BookingStyle($school_id);
        if (!$style or ($style['cols'] == null or $style['display'] == null)) {
            $html = $this->load->view('msgbox/error', 'No booking style has been configured. Please contact your administrator.', true);

            return $html;
        }

        $cols = $style['cols'];
        $display = $style['display'];

        // Select a default room if none given (first room)
        if ($room_id == null) {
            $room_c = current($rooms);
            $room_id = $room_c->room_id;
            unset($room_c);
        }

        // Load the appropriate select box depending on view style
        switch ($display) {
            case 'room':
            $html .= $this->load->view('bookings/select_room', array('rooms' => $rooms, 'room_id' => $room_id, 'chosen_date' => $date_ymd), true);
            break;
            case 'day':
            $html .= $this->load->view('bookings/select_date', array('chosen_date' => $date), true);
            break;
            default:
            $html .= $this->load->view('msgbox/error', 'Application error: No display type set.', true);

            return $html;
            break;
        }

        // Do we have any info on this week name?
        if ($this_week) {

            // Get dates for each weekday
            if ($display == 'room') {
                $this_date = strtotime('-1 day', strtotime($this_week->date));
                foreach ($school['days_list'] as $d_day_num => $d_day_name) {
                    $weekdates[$d_day_num] = date('Y-m-d', strtotime('+1 day', $this_date));
                    $this_date = strtotime('+1 day', $this_date);
                }
            }

            $week_bar['style'] = sprintf('padding:3px;font-weight:bold;background:#%s;color:#%s', $this_week->bgcol, $this_week->fgcol);

            // Change the week bar depending on view type
            switch ($display) {
                case 'room':
                $week_bar['back_date'] = date('Y-m-d', strtotime('last Week', $date));
                $week_bar['back_text'] = '&lt;&lt; Previous week';
                $week_bar['back_link'] = sprintf('bookings/index/date/%s/room/%s/direction/back', $week_bar['back_date'], $room_id);
                $week_bar['next_date'] = date('Y-m-d', strtotime('next Week', $date));
                $week_bar['next_text'] = 'Next week &gt;&gt;';
                $week_bar['next_link'] = sprintf('bookings/index/date/%s/room/%s/direction/next', $week_bar['next_date'], $room_id);
                $week_bar['longdate'] = 'Week commencing '.date('l jS F Y', strtotime($this_week->date));
                break;
                case 'day':
                $week_bar['longdate'] = date('l jS F Y', $date);
                $week_bar['back_date'] = date('Y-m-d', strtotime('yesterday', $date));
                $week_bar['back_link'] = sprintf('bookings/index/date/%s/direction/back', $week_bar['back_date']);
                $week_bar['next_date'] = date('Y-m-d', strtotime('tomorrow', $date));
                $week_bar['next_link'] = sprintf('bookings/index/date/%s/direction/next', $week_bar['next_date']);
                if (date('Y-m-d') == date('Y-m-d', $date)) {
                    $week_bar['back_text'] = '&lt;&lt; Yesterday';
                    $week_bar['next_text'] = 'Tomorrow &gt;&gt; ';
                } else {
                    $week_bar['back_text'] = '&lt;&lt; Back';
                    $week_bar['next_text'] = 'Next &gt;&gt; ';
                }
                break;
            }
            $week_bar['week_name'] = $this_week->name;
            $html .= $this->CI->load->view('bookings/week_bar', $week_bar, true);
        } else {
            $html .= $this->load->view('msgbox/error', 'A configuration error prevented the timetable from loading: <strong>no week configured</strong>.<br /><br />Please contact your administrator.', true);
            //return $html;
            $err = true;
        }

        // See if our selected date is in a holiday
        if ($display === 'day') {
            // If we are day at a time, it is easy!
            // = get me any holidays where this day is anywhere in it
            $sql = "SELECT *
                    FROM holidays
                    WHERE date_start <= '{$date_ymd}'
                    AND date_end >= '{$date_ymd}'";
        } else {
            // If we are room/week at a time, little bit more complex
            $week_start = date('Y-m-d', strtotime($this_week->date));
            $week_end = date('Y-m-d', strtotime('+'.count($school['days_list']).' days', strtotime($this_week->date)));

            $sql = "SELECT *
                    FROM holidays
                    WHERE
                    /* Starts before this week, ends this week */
                    (date_start <= '$week_start' AND date_end <= '$week_end')
                    /* Starts this week, ends this week */
                    OR (date_start >= '$week_start' AND date_end <= '$week_end')
                    /* Starts this week, ends after this week */
                    OR (date_start >= '$week_start' AND date_end >= '$week_end')";
        }

        $query = $this->db->query($sql);
        $holidays = $query->result();

        // Organise our holidays by date
        $holiday_dates = array();
        $holiday_interval = new DateInterval('P1D');

        foreach ($holidays as $holiday) {
            // Get all dates between date_start & date_end
            $start_dt = new DateTime($holiday->date_start);
            $end_dt = new DateTime($holiday->date_end);
            $end_dt->modify('+1 day');
            $range = new DatePeriod($start_dt, $holiday_interval, $end_dt);
            foreach ($range as $date) {
                $holiday_ymd = $date->format('Y-m-d');
                $holiday_dates[ $holiday_ymd ][] = $holiday;
            }
        }

        if ($display === 'day' && isset($holiday_dates[$date_ymd])) {
            // The date selected IS in a holiday - give them a nice message saying so.
            $holiday = $holiday_dates[ $date_ymd ][0];
            $msg = sprintf(
            'The date you selected is during a holiday priod (%s, %s - %s).',
            $holiday->name,
            date('d/m/Y', strtotime($holiday->date_start)),
            date('d/m/Y', strtotime($holiday->date_end))
        );
        $html .= $this->load->view('msgbox/warning', $msg, true);

        // Let them choose the date afterwards/before
        // If navigating a day at a time, then just go one day.
        // If navigating one room at a time, move by one week
        if ($display === 'day') {
            $next_date = date('Y-m-d', strtotime('+1 day', strtotime($holiday->date_end)));
            $prev_date = date('Y-m-d', strtotime('-1 day', strtotime($holiday->date_start)));
        } elseif ($display === 'room') {
            $next_date = date('Y-m-d', strtotime('+1 week', strtotime($holiday->date_end)));
            $prev_date = date('Y-m-d', strtotime('-1 week', strtotime($holiday->date_start)));
        }

        if (!isset($uri['direction'])) {
            $uri['direction'] = 'forward';
        }

        switch ($uri['direction']) {
            case 'forward':
                default:
                $uri['date'] = $next_date;
                $html .= '<p><strong><a href="'.site_url('bookings/index/date/'.$next_date.'/direction/forward').'">Click here to view immediately after the holiday.</a></strong></p>';
                break;
                case 'back':
                $html .= '<p><strong><a href="'.site_url('bookings/index/date/'.$prev_date.'/direction/back').'">Click here to view immediately before the holiday.</a></strong></p>';
                break;
            }
            //return $html;
            $err = true;
        }

        // Get periods
        $query_str = "SELECT * FROM periods WHERE school_id='$school_id' AND bookable=1 ORDER BY time_start asc";
        $query = $this->db->query($query_str);
        if ($query->num_rows() > 0) {
            $result = $query->result();
            foreach ($result as $period) {
                // Check which days this period is for
                if ($style['display'] == 'day') {
                    $school['days_bitmask']->reverse_mask($period->days);
                    if ($school['days_bitmask']->bit_isset($day_num)) {
                        $periods[$period->period_id] = $period;
                    }
                } else {
                    $periods[$period->period_id] = $period;
                }
                //$days[$day_num] = $school['days_list'][$day_num];
                //$days_available[$day_num] = $school['days_list'][$day_num];
            }
        } else {
            $html .= $this->load->view('msgbox/error', 'There are no periods available. Please see your administrator.', true);
            //return $html;
            $err = true;
        }

        // If this array isn't set, we don't have any periods configured for *this day*
        // If there were no periods at all, user would have been told before reaching this stage.
        if (!isset($periods)) {
            $html .= $this->load->view('msgbox/warning', 'There are no periods configured for this week day. Please choose another date.', true);

            return $html;
        }

        if (isset($err) && $err == true) {
            return $html;
        }

        $count['periods'] = count($periods);
        $count['rooms'] = count($rooms);
        $count['days'] = count($school['days_list']);
        //$col_width = sprintf('%d%%', (round($period_count/10) * 100) / $period_count );
        $col_width = sprintf('%s%%', round(100 / ($count[$cols] + 1)));

        // Open form
        $html .= '<form name="bookings" method="POST" action="'.site_url('bookings/multibook').'">';
        $html .= form_hidden('room_id', $room_id);

        // Here goes, start table
        $html .= '<table border="0" bordercolor="#ffffff" cellpadding="2" cellspacing="2" class="bookings" width="100%">';

        // COLUMNS !!
        $html .= '<tr><td>&nbsp;</td>';

        switch ($cols) {
            case 'periods':
            foreach ($periods as $period) {
                $period->width = $col_width;
                $html .= $this->load->view('bookings/table/cols_periods', $period, true);
            }
            break;
            case 'days':
            $week_start = date('Y-m-d', strtotime($this_week->date));

            for ($i = 1; $i <= count($school['days_list']); $i++) {
                $dayofweek = $school['days_list'][$i];
                $day['width'] = $col_width;
                $day['name'] = $dayofweek;
                $day['date'] = date('d/m', strtotime("+".($i - 1)." days", strtotime($week_start)));
                $day['dayNum'] = $i;
                $day['isAdmin'] = $this->userauth->CheckAuthLevel(ADMINISTRATOR, $this->authlevel);
                $html .= $this->load->view('bookings/table/headings/days', $day, true);
            }
            break;
            case 'rooms':
            foreach ($rooms as $room) {
                // Room name etc
                if ($room->photo != null) {
                    $roomtitle['photo_lg'] = 'webroot/images/roomphotos/640/'.$room->photo;
                    $roomtitle['photo_sm'] = 'webroot/images/roomphotos/160/'.$room->photo;
                    $roomtitle['event'] = 'onmouseover="doTooltip(event,'.$room->room_id.')" onmouseout="hideTip()"';
                    $roomtitle['width'] = 760;
                    $jscript .= "messages[$room->room_id] = new Array('{$roomtitle['photo_sm']}','{$room->location}');\n";
                } else {
                    $roomtitle['width'] = 400;
                    $roomtitle['event'] = '';
                }
                $room->roomtitle = $roomtitle;
                $room->width = $col_width;
                $room->school_id = $school_id;
                //$jscript .= "messages[$room->room_id] = new Array('{$roomtitle['photo_sm']}','{$room->location}');\n";
                $html .= $this->load->view('bookings/table/cols_rooms', $room, true);
            }
            break;
        }    // End switch for cols

        // End COLUMNS row
        //$html .= '</tr>';

        // Get bookings
        //$query_str = "SELECT * FROM bookings WHERE school_id='$school_id' AND ((date >='$date_ymd') OR date Is Null)";
        //$query = $this->db->query($query_str);
        //$results = $query->result_array();

        $bookings = array();

        // Here we go!
        switch ($display) {

            case 'room':

            // ONE ROOM AT A TIME - COLS ARE PERIODS OR DAY NAMES...

            switch ($cols) {

                case 'periods':

                /*
                [P1] [P2] [P3] ...
                [M]
                [T]
                */

                // Columns are periods, so each row is a day name

                foreach ($school['days_list'] as $day_num => $day_name) {

                    // Get booking
                    // TODO: Need to get date("Y-m-d") of THIS weekday (Mon, Tue, Wed) for this week
                    $bookings = array();
                    $query_str = 'SELECT * FROM bookings '
                    ."WHERE school_id='$school_id' "
                    ."AND room_id='$room_id' "
                    ."AND ((day_num=$day_num AND week_id=$this_week->week_id AND start_date <= '$weekdates[$day_num]' AND end_date >= '$weekdates[$day_num]') OR date='$weekdates[$day_num]') ";
                    $query = $this->db->query($query_str);
                    $results = $query->result();
                    if ($query->num_rows() > 0) {
                        foreach ($results as $row) {
                            //echo $row->booking_id;
                            $bookings[$row->period_id] = $row;
                        }
                    }
                    $query->free_result();

                    // Start row
                    $html .= '<tr>';

                    // First cell
                    $day['width'] = $col_width;
                    $day['name'] = $day_name;
                    $html .= $this->load->view('bookings/table/rowinfo/days', $day, true);

                    //$booking_date_ymd = strtotime('+' . ($day_num - 1) . ' days', strtotime($date_ymd));
                    //$booking_date_ymd = date('Y-m-d', $booking_date_ymd);
                    $booking_date_ymd = $weekdates[$day_num];

                    // Now all the other ones to fill in periods
                    foreach ($periods as $period) {

                        // URL
                        $url = 'period/%s/room/%s/day/%s/week/%s/date/%s';
                        $url = sprintf($url, $period->period_id, $room_id, $day_num, $this_week->week_id, $booking_date_ymd);

                        // Check bitmask to see if this period is bookable on this day
                        $school['days_bitmask']->reverse_mask($period->days);
                        if ($school['days_bitmask']->bit_isset($day_num)) {
                            // Bookable
                            $html .= $this->BookingCell($bookings, $period->period_id, $rooms, $users, $room_id, $url, $booking_date_ymd, $holiday_dates, $period->time_start);
                        } else {
                            // Period not bookable on this day, do not show or allow any bookings
                            $html .= '<td align="center">&nbsp;</td>';
                        }
                    }        // Done looping periods (cols)

                    // This day row is finished
                    $html .= '</tr>';
                }

                break;        // End $display 'room' $cols 'periods'

                case 'days':

                /*
                [M] [T] [W] ...
                [P1]
                [P2]
                */

                // Columns are days, so each row is a period

                foreach ($periods as $period) {
                    $bookings = array();
                    // Get booking
                    // TODO: Need to get date("Y-m-d") of THIS weekday (Mon, Tue, Wed) for this week
                    $query_str = 'SELECT * FROM bookings '
                        ."WHERE school_id='$school_id' "
                        ."AND room_id='$room_id' "
                        ."AND period_id='$period->period_id' "
                        ."AND ( (week_id=$this_week->week_id AND start_date <= '$weekdates[7]' AND (end_date >= '$weekdates[7]' OR end_date IS NULL)) OR (date >= '$weekdates[1]' AND date <= '$weekdates[7]' ) )"
                        ."ORDER BY -date DESC"; // Orders asc with nulls last
                    //."AND ((day_num=$day_num AND week_id=$this_week->week_id) OR date='$date_ymd') ";
                    $query = $this->db->query($query_str);
                    $results = $query->result();

                    if ($query->num_rows() > 0) {
                        foreach ($results as $row) {
                            // Our sorting assures that actual bookings (non-recurring) get prioritised.
                            if ($row->date != null) {
                                $this_daynum = date('w', strtotime($row->date));
                                $bookings[$this_daynum] = $row;
                            } else if ($bookings[$row->day_num] == NULL) {
                                $bookings[$row->day_num] = $row;
                            }
                        }
                    }
                    $query->free_result();

                    // Start row
                    $html .= '<tr>';

                    // First cell, info
                    $period->width = $col_width;
                    $html .= $this->load->view('bookings/table/rowinfo/periods', $period, true);

                    //$booking_date_ymd = strtotime('+' . ($day_num - 1) . ' days', strtotime($date_ymd));
                    //$booking_date_ymd = date('Y-m-d', $booking_date_ymd);

                    foreach ($school['days_list'] as $day_num => $day_name) {
                        $booking_date_ymd = $weekdates[$day_num];

                        //$html .= '<td align="center" valign="middle">BOOK</td>';

                        $url = 'period/%s/room/%s/day/%s/week/%s/date/%s';
                        $url = sprintf($url, $period->period_id, $room_id, $day_num, $this_week->week_id, $booking_date_ymd);

                        // Check bitmask to see if this period is bookable on this day
                        $school['days_bitmask']->reverse_mask($period->days);
                        if ($school['days_bitmask']->bit_isset($day_num)) {
                            // Bookable
                            $html .= $this->BookingCell($bookings, $day_num, $rooms, $users, $room_id, $url, $booking_date_ymd, $holiday_dates, $period->time_start);
                        } else {
                            // Period not bookable on this day, do not show or allow any bookings
                            $html .= '<td align="center">&nbsp;</td>';
                        }
                    }

                    // This period row is finished
                    $html .= '</tr>';
                }

                break;        // End $display 'room' $cols 'days'

            }

            break;
            case 'day':

            // ONE DAY AT A TIME - COLS ARE DAY NAMES OR ROOMS

            switch ($cols) {

                case 'periods':

                /*
                [P1] [P2] [P3] ...
                [R1]
                [R2]
                */

                // Columns are periods, so each row is a room

                foreach ($rooms as $room) {
                    $bookings = array();
                    // See if there are any bookings for any period this room.
                    // A booking will either have a date (teacher booking), or a day_num and week_id (static/timetabled)
                    $query_str = 'SELECT * FROM bookings '
                    ."WHERE school_id='$school_id' "
                    ."AND room_id='$room->room_id' "
                    ."AND ((day_num=$day_num AND week_id=$this_week->week_id) OR date='$date_ymd') ";
                    $query = $this->db->query($query_str);
                    $results = $query->result();
                    if ($query->num_rows() > 0) {
                        foreach ($results as $row) {
                            //echo $row->booking_id;
                            $bookings[$row->period_id] = $row;
                        }
                    }
                    $query->free_result();

                    // Start row
                    $html .= '<tr>';

                    $roomtitle = array();
                    if ($room->photo != null) {
                        $roomtitle['photo_lg'] = 'webroot/images/roomphotos/640/'.$room->photo;
                        $roomtitle['photo_sm'] = 'webroot/images/roomphotos/160/'.$room->photo;
                        $roomtitle['event'] = 'onmouseover="doTooltip(event,'.$room->room_id.')" onmouseout="hideTip()"';
                        $roomtitle['width'] = 760;
                        $jscript .= 'messages['.$room->room_id."] = new Array('".$roomtitle['photo_sm']."','".$room->location."');\n";
                    } else {
                        $roomtitle['width'] = 400;
                        $roomtitle['event'] = '';
                    }
                    $room->roomtitle = $roomtitle;
                    $room->width = $col_width;
                    $room->school_id = $school_id;
                    $html .= $this->load->view('bookings/table/rowinfo/rooms', $room, true);

                    foreach ($periods as $period) {
                        $url = 'period/%s/room/%s/day/%s/week/%s/date/%s';
                        $url = sprintf($url, $period->period_id, $room->room_id, $day_num, $this_week->week_id, $date_ymd);

                        // Check bitmask to see if this period is bookable on this day
                        $school['days_bitmask']->reverse_mask($period->days);
                        if ($school['days_bitmask']->bit_isset($day_num)) {
                            // Bookable
                            $html .= $this->BookingCell($bookings, $period->period_id, $rooms, $users, $room->room_id, $url, $date_ymd, $holiday_dates, $period->time_start);
                        } else {
                            // Period not bookable on this day, do not show or allow any bookings
                            $html .= '<td align="center">&nbsp;</td>';
                        }
                    }

                    // End row
                    $html .= '</tr>';
                }

                break;        // End $display 'day' $cols 'periods'

                case 'rooms':

                /*
                [R1] [R2] [R3] ...
                [P1]
                [P2]
                */

                // Columns are rooms, so each row is a period

                foreach ($periods as $period) {
                    $bookings = array();
                    // See if there are any bookings for any period this room.
                    // A booking will either have a date (teacher booking), or a day_num and week_id (static/timetabled)
                    $query_str = 'SELECT * FROM bookings '
                    ."WHERE school_id='$school_id' "
                    ."AND period_id='$period->period_id' "
                    ."AND ((day_num=$day_num AND week_id=$this_week->week_id) OR date='$date_ymd') ";
                    $query = $this->db->query($query_str);
                    $results = $query->result();
                    if ($query->num_rows() > 0) {
                        foreach ($results as $row) {
                            //echo $row->booking_id;
                            $bookings[$row->room_id] = $row;
                        }
                    }
                    $query->free_result();

                    // Start period row
                    $html .= '<tr>';

                    // First cell, info
                    $period->width = $col_width;
                    $html .= $this->load->view('bookings/table/rowinfo/periods', $period, true);

                    foreach ($rooms as $room) {
                        $url = 'period/%s/room/%s/day/%s/week/%s/date/%s';
                        $url = sprintf($url, $period->period_id, $room->room_id, $day_num, $this_week->week_id, $date_ymd);

                        // Check bitmask to see if this period is bookable on this day
                        $school['days_bitmask']->reverse_mask($period->days);
                        if ($school['days_bitmask']->bit_isset($day_num)) {
                            // Bookable
                            $html .= $this->BookingCell($bookings, $room->room_id, $rooms, $users, $room->room_id, $url, $date_ymd, $holiday_dates, $period->time_start);
                        } else {
                            // Period not bookable on this day, do not show or allow any bookings
                            $html .= '<td align="center">&nbsp;</td>';
                        }
                    }

                    // End period row
                    $html .= '</tr>';
                }

                break;        // End $display 'day' $cols 'rooms'

            }

            break;

        }

        $html .= $this->Table();

        // Finish table
        $html .= '</table>';

        // Visual key
        $html .= $this->load->view('bookings/key', null, true);

        // Do javascript for hover DIVs for room information
        if ($jscript != '') {
            $html .= '<script type="text/javascript">'.$jscript.'</script>';
        }

        // Show link to making a booking for admins
        if ($this->userauth->CheckAuthLevel(ADMINISTRATOR, $this->authlevel)) {
            $html .= $this->load->view('bookings/make_recurring', array('users' => $school['users']), true);
        }

        // Finaly return the HTML variable so the controller can then pass it to the view.
        return $html;
    }

    private function sendCancellationNotificationEmail($adminEmail, $user, $booking, $session) {
        $message = file_get_contents(__DIR__."/../templates/email/booking_cancel.html");
        $regex = "/\{\{([^\}]*)\}\}/";
        $matches = [];
        $result = preg_match_all($regex, $message, $matches);

        $myObjs = [
            "user" => $user,
            "booking" => $booking,
            "session" => $session
        ];

        if ($result != false && $result != 0) {
            $matches = array_combine($matches[0], $matches[1]);
            foreach($matches as $key => $value) {
                $exploded = explode('.',$value);
                $targetObj = $myObjs[$exploded[0]];
                $targetProp = $exploded[1];

                if ($targetObj !== null) {
                    $replace = "";

                    if (is_array($targetObj)) {
                        $replace = $targetObj[$targetProp];
                    } else {
                        $replace = $targetObj->$targetProp;
                    }

                    $message = str_replace($key, $replace, $message);
                } else {
                    $message = str_replace($key, "", $message);
                }
            }
        }

        mail($adminEmail, BOOKING_CANCEL_SUBJECT, $message);
    }

    /**
     * This function cancels a booking. If there is an associated payment with this booking, it will not be deleted.
     * If this is a recurring booking, the end date will be note as before the date that was "cancelled".
     * @param [type] $school_id  [description]
     * @param [type] $booking_id [description]
     */
    public function Cancel($school_id, $adminEmail, $user, $booking, $session, $endDate = NULL)
    {
        if ($school_id == null) {
            $school_id = $this->session->userdata('school_id');
        }

        $bookingId = $booking['booking_id'];

        if ($endDate == NULL) {
            $query_str = "DELETE
                          FROM bookings
                          WHERE school_id = $school_id
                          AND booking_id = $bookingId";
        } else {
            $endDateStr = $endDate->format('Y-m-d');
            $query_str = "UPDATE bookings
                          SET end_date = '$endDateStr'
                          WHERE school_id = $school_id
                          AND booking_id = $bookingId";
        }

        $query = $this->db->query($query_str);

        if ($query !== false) {
            if ($adminEmail != "" && $adminEmail != NULL) {
                $this->sendCancellationNotificationEmail($adminEmail, $user, $booking, $session);
            }

            if ($endDate == null) {
                return new ResultState(true, "The booking was successfully cancelled.");
            } else {
                return new ResultState(true, sprintf("The booking was successfully cancelled from %s onwards.", $endDate->format('d/m/Y')));
            }
        }

        return new ResultState(false, "An error occurred while contacting the database.");
    }

    public function BookingStyle($school_id)
    {
        $query_str = "SELECT d_columns,displaytype FROM school WHERE school_id='$school_id' LIMIT 1";
        $query = $this->db->query($query_str);
        if ($query->num_rows() == 1) {
            $row = $query->row();
            $style['cols'] = $row->d_columns;
            $style['display'] = $row->displaytype;

            return $style;
        } else {
            $style = false;
        }
    }

    public function Rooms($school_id)
    {
        $query_str = 'SELECT rooms.*, users.user_id, users.username, users.displayname '
        .'FROM rooms '
        .'LEFT JOIN users ON users.user_id=rooms.user_id '
        ."WHERE rooms.school_id='$school_id' AND rooms.bookable=1 "
        .'ORDER BY name asc';
        $query = $this->db->query($query_str);
        if ($query->num_rows() > 0) {
            $result = $query->result();
            // Put all room data into an array where the key is the room_id
            foreach ($result as $room) {
                $rooms[$room->room_id] = $room;
            }

            return $rooms;
        } else {
            //$html .= $this->load->view('msgbox/error', 'There are no rooms available. Please see your administrator.', True);
            //return $html;
            return false;
        }
    }

    /**
    * Returns an object containing the week information for a given date.
    */
    public function WeekObj($date, $school_id = null)
    {
        if ($school_id == null) {
            $school_id = $this->session->userdata('school_id');
        }
        // First find the monday date of the week that $date is in
        if (date('w', $date) == 1) {
            $nextdate = date('Y-m-d', $date);
        } else {
            $nextdate = date('Y-m-d', strtotime('last Monday', $date));
        }
        // Get week info that this date falls into
        $query_str = 'SELECT * FROM weeks,weekdates '
        .'WHERE weeks.week_id=weekdates.week_id '
        ."AND weekdates.date='$nextdate' "
        ."AND weeks.school_id='$school_id' "
        .'LIMIT 1';
        $query = $this->db->query($query_str);
        if ($query->num_rows() == 1) {
            $row = $query->row();
        } else {
            $row = false;
        }

        return $row;
    }

    public function AddRecurring($data) {
        // Convert date to appropriate string
        $data["start_date"] = $data["start_date"]->format('Y-m-d');

        // If we still have the 'date' value -> get rid of it.
        if (isset($data['date'])) {
            unset($data['date']);
        }

        // Other info for query string
        $sessionId = $data['period_id'];
        $roomNumber = $data['room_id'];
        $dateDayNum = $data['day_num'];

        $queryString = "SELECT COUNT(*) AS total
                        FROM bookings
                        WHERE (date = '$dateString' OR day_num = $dateDayNum)
                        AND end_date >= NOW()
                        AND start_date <= NOW()
                        AND period_id = $sessionId
                        AND room_id = $roomNumber";

        $query = $this->db->query($queryString);

        if ($query == false) {
            return new ResultState(false, "An error occurred while contacting the server."); // Query didn't work -- abort.
        }

        $result = $query->result_array()[0];

        if ($result["total"] > 0) {
            return new ResultState(false, "A booking already exists for this timeslot."); // A booking exists for that timeslot. Dun do eet.
        }

        // Don't do that, that's a dumb idea.
        //return $this->Edit($booking_id, $data);

        // Insert our entry
        $result = $this->db->insert('bookings', $data);

        if ($result != false) {
            return new ResultState(true, "Booking created successfully.");
        } else {
            return new ResultState(false, "Could not create booking; an unexpected error occurred.");
        }
    }

    public function Add($data)
    {
        if ($data['recurring'] == 1) {
            return AddRecurring($data);
        }

        // Get date for dateDayNum
        $date = $data["date"];

        // Convert dates to appropriate strings
        $data["date"] = $data["date"]->format('Y-m-d');
        $data["start_date"] = $data["start_date"]->format('Y-m-d');
        // Get the date string for use in query string
        $dateString = $data["date"];

        // Other info for query string
        $sessionId = $data['period_id'];
        $roomNumber = $data['room_id'];
        $dateDayNum = $date->format('N');

        $queryString = "SELECT COUNT(*) AS total
            FROM bookings
            WHERE (date = '$dateString' OR day_num = $dateDayNum)
            AND end_date > '$dateString'
            AND start_date <= '$dateString'
            AND period_id = $sessionId
            AND room_id = $roomNumber";

        $query = $this->db->query($queryString);

        if ($query == false) {
            return new ResultState(false, "An error occurred while contacting the server."); // Query didn't work -- abort.
        }

        $result = $query->result_array()[0];

        if ($result["total"] > 0) {
            return new ResultState(false, "A booking already exists for this timeslot."); // A booking exists for that timeslot. Dun do eet.
        }

        // Don't do that, that's a dumb idea.
        //return $this->Edit($booking_id, $data);

        // Insert our entry
        $result = $this->db->insert('bookings', $data);

        if ($result != false) {
            return new ResultState(true, "Booking created successfully.");
        } else {
            return new ResultState(false, "Could not create booking; an unexpected error occurred.");
        }
    }

    public function Edit($booking_id, $data)
    {
        // Where it's this booking_id
        $this->db->where('booking_id', $booking_id);
        // Update the info.
        $this->db->set('school_id', $data['school_id']);
        $result = $this->db->update('bookings', $data);

        // Return bool on success
        if ($result != false) {
            return new ResultState(true, sprintf("The booking on %s was updated successfully", $data['date']->format("d/m/Y")));
        } else {
            return new ResultState(false, "An error occurred while updating your booking.");
        }
    }

    public function ByRoomOwner($user_id)
    {
        $maxdate = date('Y-m-d', strtotime('+14 days', Now()));
        $today = date('Y-m-d');
        $query_str = 'SELECT rooms.*, bookings.*, users.username, users.displayname, users.user_id, periods.name as periodname '
        .'FROM bookings '
        .'JOIN rooms ON rooms.room_id=bookings.room_id '
        .'JOIN users ON users.user_id=bookings.user_id '
        .'JOIN periods ON periods.period_id=bookings.period_id '
        ."WHERE rooms.user_id='$user_id' AND bookings.cancelled=0 "
        .'AND bookings.date Is Not NULL '
        ."AND bookings.date <= '$maxdate' "
        ."AND bookings.date >= '$today' "
        .'ORDER BY bookings.date, rooms.name ';
        $query = $this->db->query($query_str);
        if ($query->num_rows() > 0) {
            // We have some bookings
            return $query->result();
        } else {
            return false;
        }
    }

    public function ByUser($user_id)
    {
        $maxdate = date('Y-m-d', strtotime('+14 days', Now()));
        $today = date('Y-m-d');
        // All current bookings for this user between today and 2 weeks' time
        $query_str = 'SELECT rooms.*, bookings.*, periods.name as periodname, periods.time_start, periods.time_end'
        .'FROM bookings '
        .'JOIN rooms ON rooms.room_id=bookings.room_id '
        .'JOIN periods ON periods.period_id=bookings.period_id '
        ."WHERE bookings.user_id='$user_id' AND bookings.cancelled=0 "
        .'AND bookings.date Is Not NULL '
        .'ORDER BY bookings.date asc, periods.time_start asc';
        $query = $this->db->query($query_str);
        if ($query != false && $query->num_rows() > 0) {
            return $query->result();
        } else {
            return false;
        }
    }

    public function TotalNum($user_id, $school_id = null)
    {
        if ($school_id == null) {
            $school_id = $this->session->userdata('school_id');
        }

        $today = date('Y-m-d');

        // All bookings by user, EVER!
        $query_str = "SELECT * FROM bookings WHERE user_id='$user_id'";
        $query = $this->db->query($query_str);
        $total['all'] = $query->num_rows();

        // All bookings by user, for this academic year, up to and including today
        $query_str = 'SELECT * FROM bookings '
        .'JOIN academicyears ON bookings.date >= academicyears.date_start '
        ."WHERE user_id='$user_id' "
        ."AND academicyears.school_id='$school_id' ";
        $query = $this->db->query($query_str);
        $total['yeartodate'] = $query->num_rows();

        // All bookings up to and including today
        $query_str = "SELECT * FROM bookings WHERE user_id='$user_id' AND date <= '$today'";
        $query = $this->db->query($query_str);
        $total['todate'] = $query->num_rows();

        // All "active" bookings (today onwards)
        $query_str = "SELECT * FROM bookings WHERE user_id='$user_id' AND date >= '$today'";
        $query = $this->db->query($query_str);
        $total['active'] = $query->num_rows();

        // All bookings for the current calendar week
        $mondayDate = date('Y-m-d', strtotime('monday this week'));
        $sundayDate = date('Y-m-d', strtotime('sunday this week'));

        $query_str = "SELECT * FROM bookings WHERE user_id='$user_id' AND date >= '$mondayDate' AND date <= '$sundayDate'";
        $query = $this->db->query($query_str);
        $total['week'] = $query->num_rows();

        return $total;
    }
}
