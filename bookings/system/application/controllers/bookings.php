<?php
require_once __DIR__."/../templates/Template.class.php";
require_once __DIR__."/../data/DateHelper.class.php";

class Bookings extends Controller
{
    public function Bookings()
    {
        // Call parent
        parent::Controller();

        // Load language
        $this->lang->load('crbs', 'english');

        // Set school ID
        $this->school_id = $this->session->userdata('school_id');

        $this->output->enable_profiler(false);

        // Check user is logged in
        if (!$this->userauth->loggedin()) {
            $this->session->set_flashdata('login', $this->load->view('msgbox/error', $this->lang->line('crbs_auth_mustbeloggedin'), true));
            redirect('login', 'location');
        } else {
            $this->loggedin = true;
            $this->authlevel = $this->userauth->GetAuthLevel($this->session->userdata('user_id'));
        }

        // Loading everything we need.
        $this->load->script('bitmask');
        $this->load->model('crud_model', 'crud');
        $this->load->model('rooms_model', 'roomsProvider');
        $this->load->model('periods_model', 'sessionProvider');
        $this->load->model('weeks_model', 'weeksProvider');
        $this->load->model('users_model', 'userProvider');
        $this->load->model('bookings_model', 'bookingsProvider');

        $school['users'] = $this->userProvider->Get();
        $school['days_list'] = $this->sessionProvider->days;
        $school['days_bitmask'] = $this->sessionProvider->days_bitmask;
        $this->school = $school;
    }

    public function getBookingsForPeriod($startDate, $endDate, $userId, $roomId) {
        $bookingsForPeriod = $this->bookingsProvider->getByTimespan($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));

        $filteredBookings = [];
        $recurringBookings = [];

        foreach ($bookingsForPeriod as $booking) {
            $forRoom = true;
            $forUser = true;

            if ($roomId != null && $booking["roomId"] != $roomId) {
                $forRoom = false;
            } else if ($userId != null && $booking["userId"] != $userId) {
                $forUser = false;
            }

            if ($forRoom && $forUser) {
                if ($booking["isRecurring"] === "false") {
                    array_push($filteredBookings, $booking);
                } else {
                    array_push($recurringBookings, $booking);
                }
            }
        }

        $expandedRecurring = [];

        foreach ($recurringBookings as $booking) {
            $dow = $booking["dayNum"];

            $bookingsForDate = DateHelper::GetDatesForDow($dow, $startDate, $endDate);

            foreach ($bookingsForDate as $bookingDate) {
                $bookingCopy = $booking;

                $bookingCopy["bookingDate"] = $bookingDate->format("Y-m-d");

                array_push($expandedRecurring, $bookingCopy);
            }
        }

        $allBookings = array_merge($expandedRecurring, $filteredBookings);

        return $allBookings;
    }

    /**
     * Gives a summary of total bookings made over the past 2 months
     */
    public function bookingsForPeriod()
    {
        if ($_POST["startDate"] != null) {
            $startDate = new DateTime($_POST["startDate"]);
        } else {
            $startDate = new DateTime();
        }

        if ($_POST["endDate"] != null) {
            $endDate = new DateTime($_POST["endDate"]);
        } else {
            $endDate = (new DateTime())->add(new DateInterval("P1M"));
        }

        $userId = $_POST['userId'];
        $roomId = $_POST['roomId'];

        $filteredBookings = $this->getBookingsForPeriod($startDate, $endDate, $userId, $roomId);

        $response = [
            "requestData" => [
                "userId" => $userId,
                "roomId" => $roomId,
                "startDate" => $startDate,
                "endDate" => $endDate
            ],
            "responseData" => $filteredBookings
        ];

        header('Content-Type: application/json');

        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    public function summaryPage() {
        $today = new DateTime();

        $html = new Template("summary", "summary");

        $summaryText = "This page presents a summary of information for "
            ."export! You can filter by room, by person, or by both, and "
            ."export information as it is displayed below!";

        $layout["title"] = "Summary";
        $layout["showtitle"] = "Summary";
        $layout["body"] = $html->toHtml([
            "summaryText" => $summaryText,
            "roomOptions" => [
                    "1" => "bruh",
                    "2" => "does this work?"
            ],
            "userOptions" => [
                "1" => "Administrator",
                "2" => "Testuser1"
            ]
        ]);

        $this->load->view('layout', $layout);
    }

    public function index()
    {
        $uri = $this->uri->uri_to_assoc(3);

        $this->session->set_userdata('uri', $this->uri->uri_string());

        if (!isset($uri['date'])) {
            $uri['date'] = date('Y-m-d');

            $day_num = date('w', strtotime($uri['date']));
        }

        $room_of_user = $this->roomsProvider->GetByUser($this->school_id, $this->session->userdata('user_id'));

        if (isset($uri['room'])) {
            $uri['room'] = $uri['room'];
        } else if ($room_of_user != false) {
            $uri['room'] = $room_of_user->room_id;
        } else {
            $uri['room'] = false;
        }

        $body['html'] = $this->bookingsProvider->html(
            $this->school_id,
            null,
            null,
            strtotime($uri['date']),
            $uri['room'],
            $this->school,
            $uri
        );

        $layout['title'] = 'Bookings';
        $layout['showtitle'] = 'Bookings';
        $layout['body'] = $this->session->flashdata('saved');
        $layout['body'] .= $body['html'];
        $this->load->view('layout', $layout);
    }

    /**
    * This function takes the date that was POSTed and loads the view().
    */
    public function load()
    {
        $style = $this->bookingsProvider->BookingStyle($this->school_id);

        // Validation rules
        $vrules['chosen_date'] = 'max_length[10]|callback__is_valid_date';
        $vrules['room_id'] = 'numeric';
        $this->validation->set_rules($vrules);
        $vfields['chosen_date'] = 'Date';
        $vfields['room_id'] = 'Room';
        $vfields['direction'] = 'Direction';
        $this->validation->set_fields($vfields);

        // Set the error delims to a nice styled red hint under the fields
        $this->validation->set_error_delimiters('<p class="hint error"><span>', '</span></p>');

        if ($this->validation->run() == false) {
            show_error('validation failed');
        } else {
            switch ($style['display']) {
                case 'day':
                    // Display type is one day at a time - all rooms/periods
                    if ($this->input->post('chosen_date')) {
                        $datearr = explode('/', $this->input->post('chosen_date'));

                        if (count($datearr) == 3) {
                            $chosen_date = sprintf('%s-%s-%s', $datearr[2], $datearr[1], $datearr[0]);

                            // Generate our URL from the chosen_date
                            $url = sprintf(
                                'bookings/index/date/%s/direction/%s',
                                $chosen_date,
                                $this->input->post('direction')
                            );

                            redirect($url, 'redirect');
                        } else {
                            show_error('Invalid date');
                        }
                    } else {
                        show_error('no date chosen');
                    }
                    break;
                case 'room':
                    if ($this->input->post('room_id')) {
                        // Generate our URL from the chosen date and room
                        $url = sprintf(
                            'bookings/index/date/%s/room/%s/direction/%s',
                            $this->input->post('chosen_date'),
                            $this->input->post('room_id'),
                            $this->input->post('direction')
                        );

                        redirect($url, 'redirect');
                    } else {
                        show_error('no day selected');
                    }
                break;
            }
        }
    }

    public function book()
    {
        // Convert URI to associative array
        $uri = $this->uri->uri_to_assoc(3);

        // Layout
        $layout['title'] = 'Book a room';
        $layout['showtitle'] = $layout['title'];

        $seg_count = $this->uri->total_segments();

        if ($seg_count != 2 && $seg_count != 12) {
            // Not all info in URI
            $layout['body'] = $this->load->view('msgbox/error', 'Not enough information specified to book a room.', true);
        } else {
            // 12 segments means we have all info - adding a booking
            if ($seg_count == 12) {

                // Create array of data from the URI
                $booking['booking_id'] = 'X';
                $booking['period_id'] = $uri['period'];
                $booking['room_id'] = $uri['room'];
                $booking['date'] = date('d/m/Y', strtotime($uri['date']));

                if ($this->userauth->CheckAuthLevel(ADMINISTRATOR, $this->authlevel)) {
                    $booking['day_num'] = $uri['day'];
                    $booking['week_id'] = $uri['week'];
                } else {
                    $booking['user_id'] = $this->session->userdata('user_id');
                }

                $body['booking'] = $booking;
                $body['hidden'] = $booking;
            } else {
                $body['hidden'] = array();
            }

            // Lookups we need if an admin user
            if ($this->userauth->CheckAuthLevel(ADMINISTRATOR, $this->authlevel)) {
                $body['days'] = $this->sessionProvider->days;
                $body['rooms'] = $this->roomsProvider->Get(null, $this->school_id);
                $body['periods'] = $this->sessionProvider->Get();
                $body['weeks'] = $this->weeksProvider->Get();
                $body['users'] = $this->userProvider->Get();
            }

            $layout['body'] = $this->load->view('bookings/bookings_book', $body, true);

            // Check that the date selected is not in the past
            $today = strtotime(date('Y-m-d'));
            $thedate = strtotime($uri['date']);

            if ($this->userauth->CheckAuthLevel(TEACHER, $this->authlevel)) {
                if ($thedate < $today) {
                    $layout['body'] = $this->load->view('msgbox/error', 'You cannot make a booking in the past.', true);
                }
            }

            // Now see if user is allowed to book in advance
            if ($this->userauth->CheckAuthLevel(TEACHER, $this->authlevel)) {
                $bia = (int) $this->_booking_advance($this->school_id);
                if ($bia > 0) {
                    $date_forward = strtotime("+$bia days", $today);
                    if ($thedate > $date_forward) {
                        $layout['body'] = $this->load->view('msgbox/error', 'You can only book '.$bia.' days in advance.', true);
                    }
                }
            }
        }

        $this->load->view('layout', $layout);
    }

    public function recurring()
    {
        foreach ($this->input->post('recurring') as $booking) {
            $arr = explode('/', $booking);
            $max = count($arr);

            $booking = array();
            for ($i = 0; $i < count($arr); $i = $i + 2) {
                $booking[$arr[$i]] = $arr[$i + 1];
            }
            $bookings[] = $booking;
        }

        $errcount = 0;

        foreach ($bookings as $booking) {
            $data = array();
            $data['user_id'] = $this->input->post('user_id');
            $data['school_id'] = $this->school_id;
            $data['period_id'] = $booking['period'];
            $data['room_id'] = $booking['room'];
            $data['notes'] = $this->input->post('notes');
            $data['week_id'] = $booking['week'];
            $data['day_num'] = $booking['day'];
            if (!$this->bookingsProvider->Add($data)) {
                ++$errcount;
            }
        }
        if ($errcount > 0) {
            $flashmsg = $this->load->view('msgbox/error', 'One or more bookings could not be made.', true);
        } else {
            $flashmsg = $this->load->view('msgbox/info', 'The bookings were created successfully.', true);
        }

        $this->session->set_userdata('notes', $data['notes']);

        // Go back to index
        $this->session->set_flashdata('saved', $flashmsg);

        $uri = $this->session->userdata('uri');
        $uri = ($uri) ? $uri : 'bookings';

        redirect($uri, 'location');
    }

    public function cancel()
    {
        // Get the booking ID from URI
        $uri = $this->session->userdata('uri');
        $booking_id = $this->uri->segment(3);

        if ($this->bookingsProvider->Cancel($this->school_id, $booking_id)) {
            $msg = $this->load->view('msgbox/info', 'The booking has been <strong>cancelled</strong>.', true);
        } else {
            $msg = $this->load->view('msgbox/error', 'An error occured cancelling the booking.', true);
        }

        // Set the response message, and go to the bookings page
        $this->session->set_flashdata('saved', $msg);

        if ($uri == null) {
            $uri = 'bookings';
        }

        redirect($uri, 'redirect');
    }

    public function edit()
    {
        $uri = $this->session->userdata('uri');
        $booking_id = $this->uri->segment(3);

        $booking = $this->bookingsProvider->Get();

        // Lookups we need if an admin user
        if ($this->userauth->CheckAuthLevel(ADMINISTRATOR, $this->authlevel)) {
            $body['days'] = $this->sessionProvider->days;
            $body['rooms'] = $this->roomsProvider->Get($this->school_id, null);
            $body['periods'] = $this->sessionProvider->Get();
            $body['weeks'] = $this->weeksProvider->Get();
            $body['users'] = $this->userProvider->Get();
        }

        $layout['body'] = $this->load->view('bookings/bookings_book', $body, true);

        $this->load->view('layout', $layout);
    }

    /**
     * Saves the booking. If an ID is specified in the POST request, edit the existing booking.
     * @return String The result of attempting to save the booking.
     */
    public function save()
    {
        // Get ID from form
        $booking_id = $this->input->post('booking_id');

        // Validation rules
        $vrules['booking_id'] = 'required';
        $vrules['date'] = 'max_length[10]|callback__is_valid_date';
        $vrules['use'] = 'max_length[100]';
        $this->validation->set_rules($vrules);

        // Pretty it up a bit for error validation message
        $vfields['booking_id'] = 'Booking ID';
        $vfields['date'] = 'Date';
        $vfields['period_id'] = 'Period';
        $vfields['user_id'] = 'User';
        $vfields['room_id'] = 'Room';
        $vfields['week_id'] = 'Week';
        $vfields['day_num'] = 'Day of week';
        $this->validation->set_fields($vfields);

        // Set the error delims to a nice styled red hint under the fields
        $this->validation->set_error_delimiters('<p class="hint error"><span>', '</span></p>');

        if ($this->validation->run() == false) { // Validation failed
            if ($booking_id != 'X') {
                return $this->Edit($booking_id);
            } else {
                return $this->book();
            }
        } else { // Validation succeeded
            // Data that goes into database regardless of booking type
            $data['user_id'] = $this->input->post('user_id');
            $data['school_id'] = $this->school_id;
            $data['period_id'] = $this->input->post('period_id');
            $data['room_id'] = $this->input->post('room_id');
            $data['notes'] = $this->input->post('notes');

            // Hmm.... now to see if it's a static booking or recurring or whatever... :-)
            if ($this->input->post('date')) {
                // Once-only booking

                $date_arr = explode('/', $this->input->post('date'));
                $data['date'] = date('Y-m-d', mktime(0, 0, 0, $date_arr[1], $date_arr[0], $date_arr[2]));
                $data['day_num'] = null;
                $data['week_id'] = null;
            }

            // If week_id and day_num are specified, its recurring
            if ($this->input->post('recurring') && ($this->input->post('week_id') && $this->input->post('day_num'))) {
                // Recurring
                $data['date'] = null;
                $data['day_num'] = $this->input->post('day_num');
                $data['week_id'] = $this->input->post('week_id');
            }

            // Now see if we are editing or adding
            if ($booking_id == 'X') {
                // No ID, adding new record
                if (!$this->bookingsProvider->Add($data)) {
                    $flashmsg = $this->load->view('msgbox/error', sprintf($this->lang->line('dberror'), 'adding', 'booking'), true);
                } else {
                    $flashmsg = $this->load->view('msgbox/info', 'The booking has been made.', true);
                }
            } else {
                // We have an ID, updating existing record
                if (!$this->bookingsProvider->Edit($booking_id, $data)) {
                    $flashmsg = $this->load->view('msgbox/error', sprintf($this->lang->line('dberror'), 'editing', 'booking'), true);
                } else {
                    $flashmsg = $this->load->view('msgbox/info', 'The booking has been updated.', true);
                }
            }

            // Go back to index
            $this->session->set_flashdata('saved', $flashmsg);

            $uri = $this->session->userdata('uri');
            $uri = ($uri) ? $uri : 'bookings';

            redirect($uri, 'location');
        }
    }

    /**
     * The callback for date check validation
     * @param  String  $date The date formatted as d/m/Y
     * @return boolean       True if valid, otherwise false.
     */
    public function callback__is_valid_date($date)
    {
        $datearr = split('/', $date);

        if (count($datearr) == 3) {
            $valid = checkdate($datearr[1], $datarr[0], $datearr[2]); // month, day, year

            if ($valid) {
                $ret = true;
            } else {
                $ret = false;
                $this->validation->set_message('_is_valid_date', 'Invalid date');
            }
        } else {
            $ret = false;
            $this->validation->set_message('_is_valid_date', 'Invalid date');
        }

        return $ret;
    }

    /**
     * Returns the number of days in advance that a booking can be made.
     * @param  int $school_id The school id to check for.
     * @return mixed            A number if the school can Book in Advance, otherwise 'X'
     */
    public function _booking_advance($school_id)
    {
        $query_str = "SELECT bia FROM school WHERE school_id='$school_id' LIMIT 1";
        $query = $this->db->query($query_str);
        if ($query->num_rows() == 1) {
            $row = $query->row();

            return $row->bia;
        } else {
            return 'X';
        }
    }
}
