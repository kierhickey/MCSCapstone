<?php
require_once __DIR__."/../templates/Template.class.php";
require_once __DIR__."/../data/DateHelper.class.php";
require_once "pdfgenerator.php";

class Bookings extends Controller
{
    private $errorMsg = NULL;

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
        $this->load->model('school_model', 'schoolProvider');

        $school['users'] = $this->userProvider->Get();
        $school['days_list'] = $this->sessionProvider->days;
        $school['days_bitmask'] = $this->sessionProvider->days_bitmask;
        $this->school = $school;
    }

    private function _markAsPaid($bookingId) {
        if (isset($_POST["forDate"])) {
            return $this->bookingsProvider->markRecurringPaid($bookingId, $_POST["forDate"]);
        }

        return $this->bookingsProvider->markAsPaid($bookingId);
    }

    public function markAsPaid() {
        header('Content-Type: application/json');

        if (!$this->userauth->CheckAuthLevel( ADMINISTRATOR )) {
            echo json_encode([
                "status" => 403,
                "message" => "You do not have the required elevation to access the requested resource."
            ]);
            return;
        }

        if (!isset($_POST["bookingId"])) {
            echo json_encode([
                "status" => 400,
                "message" => "The request made to the server was invalid."
            ]);
            return;
        }

        $result = $this->_markAsPaid($_POST["bookingId"]);

        echo json_encode([
            "requestData" => $_POST,
            "responseData" => $result
        ]);
    }

    public function generatePdf() {
        $pdfGen = new PdfGenerator();

        $schoolInfo = $this->schoolProvider->GetInfo();

        $pricing = [
            "recurringPrice" => floatval($schoolInfo->recurringPrice),
            "casualPrice" => floatval($schoolInfo->casualPrice)
        ];

        $pdfGen->generate($this->userProvider, $this->bookingsProvider, $pricing);
    }

    public function getBookingsForPeriod($startDate, $endDate, $userId, $roomId) {
        if($userId == null && !$this->userauth->CheckAuthLevel( ADMINISTRATOR ) ) {
            return [
                "status" => 403,
                "message" => "You do not have the required elevation to access the requested resource."
            ];
        }

        $allBookings = $this->bookingsProvider->getBookingsForPeriod($startDate, $endDate, $userId, $roomId);

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

      //  $summaryText = "This page presents a summary of information for "
      //      ."export! You can filter by room, by person, or by both, and "
      //      ."export information as it is displayed below!";


      $summaryText ="


<p>To view all confirmed bookings, across all rooms for a <strong> single  client </strong></p>
<ul>
    <li>Set the room list to 'All Rooms'</li>
    <li>Set the User list to the Client name you wish to view</li>
    <li>Click on the date you wish to view and a table will be generated below!</li>
</ul>

                      <p>To view all confirmed bookings, across <strong> all rooms and clients: </strong> </p>
<ul>
    <li>Set the room list to 'All Rooms'</li>
    <li>Set the User list to 'No User'</li>
    <li>Click on the date you wish to view and a table will be generated below!</li>
</ul>


<p>
    <span class='tip-label'>Tip: </span><span class='tip-text'>To select multiple days, hold shift while clicking a start date, then end date!</span>
</p>

<p>
    If you wish to print the Summary Table below, you can do so using the Printer Icon. </br>
    If you wish to generate a PDF invoice summary, click the Download arrow adjacent.
</p>

";


        $layout["title"] = "Summary";
        $layout["showtitle"] = "Summary";
        $layout["body"] = $html->toHtml([
            "summaryText" => $summaryText,
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

        $layout['body'] = $this->session->flashdata('error');

        $seg_count = $this->uri->total_segments();

        if ($seg_count != 2 && $seg_count != 12) {
            // Not all info in URI
            $layout['body'] = $this->load->view('msgbox/error', 'Not enough information specified to book a room.', true);
        } else {
            // 12 segments means we have all info - adding a booking
            if ($seg_count == 12) {
                // Create array of data from the URI
                if ($this->userauth->CheckAuthLevel(ADMINISTRATOR, $this->authlevel)) {
                    $booking['day_num'] = $uri['day'];
                    $booking['week_id'] = $uri['week'];
                } else {
                    $booking['user_id'] = $this->session->userdata('user_id');
                }

                $booking['period_id'] = $uri['period'];
                $booking['room_id'] = $uri['room'];
                $booking['date'] = date('d/m/Y', strtotime($uri['date']));

                $body['hidden'] = $booking;
            } else {
                $body['hidden'] = array();
            }

            $booking['booking_id'] = 'X';
            $body['booking'] = $booking;

            $body["errorMsg"] = $this->errorMsg;

            $this->errorMsg = NULL;

            // Lookups we need if an admin user
            if ($this->userauth->CheckAuthLevel(ADMINISTRATOR, $this->authlevel)) {
                $body['days'] = $this->sessionProvider->days;
                $body['rooms'] = $this->roomsProvider->getAllBasic();
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

    public function multibook() {
        if (isset($_POST['recurringBookings'])) {
            $this->recurring();
        } else {
            $this->saveMulti();
        }
    }

    public function recurring()
    {
        foreach ($this->input->post('multi') as $booking) {
            $arr = explode('/', $booking);
            $max = count($arr);

            $booking = array();
            for ($i = 0; $i < count($arr); $i = $i + 2) {
                $booking[$arr[$i]] = $arr[$i + 1];
            }
            $bookings[] = $booking;
        }

        $errReasons = [];

        foreach ($bookings as $booking) {
            $data = [];
            $data['user_id'] = $this->input->post('user_id');
            $data['school_id'] = $this->school_id;
            $data['period_id'] = $booking['period'];
            $data['room_id'] = $booking['room'];
            $data['notes'] = $this->input->post('notes');
            $data['week_id'] = $booking['week'];
            $data['day_num'] = $booking['day'];
            $data['start_date'] = new DateTime($booking['date']);

            $result = $this->bookingsProvider->AddRecurring($data);

            if (!$result->getResult()) {
                array_push($errReasons, DateHelper::GetDayString($data["day_num"]).": ".$result->getMessage());
            }
        }
        if (count($errReasons) > 0) {
            $string = "<ul><li>".implode('</li><li>', $errReasons)."</li></ul>";
            $flashmsg = $this->load->view('msgbox/error', 'One or more bookings could not be made; ', true);
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

    public function cancel($booking_id, $endDateString = NULL)
    {
        $booking = $this->bookingsProvider->getById($booking_id);
        $user = $this->userProvider->Get($booking["user_id"]);
        $to = $this->schoolProvider->get("admin_cancel_email", $this->school_id)["admin_cancel_email"];
        $session = $this->sessionProvider->Get($booking["period_id"]);
        $result = false;

        if ($endDateString == null) {
            $result = $this->bookingsProvider->Cancel($this->school_id, $to, $user, $booking, $session);
        } else {
            $endDate = new DateTime($endDateString);
            $result = $this->bookingsProvider->Cancel($this->school_id, $to, $user, $booking, $session, $endDate);
        }

        if ($result->getResult()) {
            $msg = $this->load->view('msgbox/info', $result->getMessage(), true);
        } else {
            $msg = $this->load->view('msgbox/error', $result->getMessage(), true);
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

    public function saveMulti() {
        foreach ($this->input->post('multi') as $booking) {
            $arr = explode('/', $booking);
            $max = count($arr);

            $booking = array();
            for ($i = 0; $i < count($arr); $i = $i + 2) {
                $booking[$arr[$i]] = $arr[$i + 1];
            }
            $bookings[] = $booking;
        }

        $errors = [];

        foreach ($bookings as $booking) {
            $dateString = $booking['date'];
            $dateDate = new DateTime($dateString);

            $data = [];
            $data['school_id'] = $this->school_id;
            $data['period_id'] = $booking['period'];
            $data['room_id'] = $booking['room'];
            $data['user_id'] = $this->input->post('user_id');
            $data['date'] = $dateDate;
            $data["start_date"] = $dateDate;
            $data['notes'] = $this->input->post('notes');

            debug_log($data);

            $result = $this->bookingsProvider->Add($data);

            if (!$result->getResult()) {
                array_push($errors, $dateString.": ".$result->getMessage());
            }
        }
        if (count($errors) > 0) {
            $string = "<ul><li>".implode('</li><li>', $errors)."</li></ul>";
            $flashmsg = $this->load->view('msgbox/error', ['message' => "One or more bookings could not be made; ".$string], true);
        } else {
            $flashmsg = $this->load->view('msgbox/info', 'The bookings were created successfully.', true);
        }

        // Go back to index
        $this->session->set_flashdata('saved', $flashmsg);

        $uri = $this->session->userdata('uri');
        $uri = ($uri) ? $uri : 'bookings';

        redirect($uri, 'location');
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

        //
        // Date validation
        // Valid format: dd/mm/yyyy
        //
        $academicYear = $this->weeksProvider->GetAcademicYear();

        $academicYear = [
            "start" => new DateTime($academicYear["date_start"]),
            "end" => new DateTime($academicYear["date_end"])
        ];

        $dateValid = true; //Assume innocent
        $dateDate;
        $dateFailReason = "";

        // Reference Data
        $today = new DateTime();

        try {
            $date = $this->input->post('date');
            $dateArr = explode("/", $date);

            // There are three parts of the date
            if ($dateValid && count($dateArr) != 3) {
                $dateValid = false;
                $dateFailReason = "Invalid date format";
            }

            $year = intval($dateArr[2]);
            $month = intval($dateArr[1]);
            $day = intval($dateArr[0]);

            $roomId = $this->input->post("room_id");

            // Date is valid
            if ($dateValid && !checkdate($month, $day, $year)) {
                $dateValid = false;
                $dateFailReason = "Invalid date format";
            }

            // Date isn't in the past
            $dateDate = new DateTime("$month/$day/$year");

            if ($dateValid && $dateDate <= $today) {
                $dateValid = false;
                $dateFailReason = "Date is in the past";
            }

            //Date is within current academic year
            if ($dateValid && ($dateDate < $academicYear["start"] || $dateDate > $academicYear["end"])) {
                $dateValid = false;
                $dateFailReason = "Date not within the currently configured business year.";
            }
        } catch (Exception $ex) {
            $dateValid = false;
        }

        // End Date Validation

        // Set the error delims to a nice styled red hint under the fields
        $this->validation->set_error_delimiters('<p class="hint error"><span>', '</span></p>');

        if ($this->validation->run() == false) { // Validation failed
            if ($booking_id != 'X' && $booking_id != false) {
                return $this->Edit($booking_id);
            } else {
                return $this->book();
            }
        } else if (!$dateValid) {
            if ($booking_id != 'X' && $booking_id != false) {
                return $this->Edit($booking_id);
            } else {
                $this->errorMsg = "The date '$date' is not a valid date. $dateFailReason.";
                return $this->book();
            }
        } else { // Validation succeeded
            // Data that goes into database regardless of booking type
            $data['user_id'] = $this->input->post('user_id');
            $data['school_id'] = $this->school_id;
            $data['period_id'] = $this->input->post('period_id');
            $data['room_id'] = $this->input->post('room_id');
            $data['notes'] = $this->input->post('notes');
            $data['date'] = $dateDate;
            $data["start_date"] = $dateDate;

            // Hmm.... now to see if it's a static booking or recurring or whatever... :-)

            $recurring = false;

            if (!$this->input->post('recurring')) {
                // Once-only booking
                $date_arr = explode('/', $this->input->post('date'));
                $data['day_num'] = null;
                $data['week_id'] = null;
            } else {
                // Recurring
                $recurring = true;
                $data['day_num'] = $this->input->post('day_num');
                $data['week_id'] = $this->input->post('week_id');
            }

            // Now see if we are editing or adding
            if ($booking_id == 'X') {
                // No ID, adding new record
                if ($recurring) {
                    $result = $this->bookingsProvider->AddRecurring($data);
                } else {
                    $result = $this->bookingsProvider->Add($data);
                }
                if (!$result->getResult()) {
                    $flashmsg = $this->load->view('msgbox/error', $result->getMessage(), true);
                } else {
                    $flashmsg = $this->load->view('msgbox/info', $result->getMessage(), true);
                }
            } else {
                // We have an ID, updating existing record
                $result = $this->bookingsProvider->Edit($booking_id, $data);
                if (!$result->getResult()) {
                    $flashmsg = $this->load->view('msgbox/error', $result->getMessage(), true);
                } else {
                    $flashmsg = $this->load->view('msgbox/info', $result->getMessage(), true);
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
            echo "I DO ACTUALLY CHECK SHIT APPARENTLY";
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
