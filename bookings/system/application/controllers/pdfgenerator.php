<?php

require_once __DIR__."/../libraries/dompdf/autoload.inc.php";
require_once __DIR__."/../models/bookings_model.php";
require_once __DIR__."/../models/users_model.php";

use Dompdf\Dompdf;

class PdfGenerator {
    // In pts
    const PAPER_WIDTH = 525;
    const PAPER_HEIGHT = 842;
    const MARGIN_SIZE_LEFT = 36;
    const MARGIN_SIZE_RIGHT = 36;
    const MARGIN_SIZE_TOP = 36;
    const MARGIN_SIZE_BOTTOM = 36;

    public function __construct() {}

    public function loadHtmlContent($pdf, $userProvider, $bookingsProvider) {
        $pdf->setBasePath(__DIR__."/../views/pdfsummary/");

        $htmlSummaryTable = "<table class='summary-table'>";
        $htmlSummaryTable = $htmlSummaryTable . "
            <thead>
                <tr>
                    <th>Date</th>
                    <th style='width: 120px;'>User</th>
                    <th>Location</th>
                    <th>Room</th>
                    <th style='width: 130px;'>Session</th>
                    <th>Price</th>
                    <th style='width: 70px;'>Recurring</th>
                    <th>Paid</th>
                </tr>
            </thead><tbody>";

        $startDate = new DateTime();
        $endDate = (new DateTime())->add(new DateInterval("P1M"));
        // Tue+Oct+18+2016+00:00:00+GMT+1100+(AUS+Eastern+Standard+Time)
        if ($_POST["startDate"] != null) {
            $startDate = date_create_from_format('D M d Y H:i:s e+', $_POST["startDate"]);
        }

        if ($_POST["endDate"] != null) {
            $endDate = date_create_from_format('D M d Y H:i:s e+', $_POST["endDate"]);
        }

        $userId = $_POST['userId'];
        $roomId = $_POST['roomId'];
        $name = "";

        if ($userId != null) {
            $user = $userProvider->getBasic($userId);
            $name = $user["displayName"];

            $htmlHeader = file_get_contents(__DIR__."/../views/pdfsummary/summary-header.html");
            $htmlFooter = file_get_contents(__DIR__."/../views/pdfsummary/summary-footer.html");
        } else {
            $htmlHeader = file_get_contents(__DIR__."/../views/pdfsummary/summary-header-nouser.html");
            $htmlFooter = file_get_contents(__DIR__."/../views/pdfsummary/summary-footer-nouser.html");
        }

        $bookings = $bookingsProvider->getBookingsForPeriod($startDate, $endDate, $userId, $roomId);

        $lastDate;
        $subTotal = 0;
        $amountPaid = 0;

        foreach ($bookings as $entry) {
            $date = new DateTime(str_replace("-", "/", $entry["bookingDate"]));
            $displayName = $entry["displayName"];
            $dateString = $date->format("d/m/Y");
            $session = $entry["bookingStart"] . " &ndash; " . $entry["bookingEnd"];
            $price = $entry["price"];
            $location = $entry["location"];
            $room = $entry["roomName"];
            $recurring = $entry['isRecurring'] ? "Yes" : "No";
            $paid = $entry["paid"] ? "P" : "NP";

            $tr = "<tr>";

            $subTotal += $price;

            // * 1.1 for GST
            if ($paid == "P") {
                $amountPaid += $price * 1.1;
            }

            if ($lastDate != $dateString) {
                $tr = "<tr class='new-date'>";
            }

            $htmlSummaryTable = $htmlSummaryTable .
            $tr.
                "<td>$dateString</td>
                <td>$displayName</td>
                <td>$location</td>
                <td>$room</td>
                <td style='width: 120px;'>$session</td>
                <td>$price.00</td>
                <td style='width: 70px;'>$recurring</td>
                <td>$paid</td>
            </tr>";

            $lastDate = $dateString;
        }

        $gst = $subTotal / 10;
        $owing = $subTotal + $gst - $amountPaid;

        $htmlSummaryTable = $htmlSummaryTable . "</tbody></table>";

        // Replace values in HTML Header
        $htmlHeader = str_replace("{{userFullName}}", $name, $htmlHeader);
        $htmlHeader = str_replace("{{startDate}}", $startDate->format("d/m/Y"), $htmlHeader);
        $htmlHeader = str_replace("{{endDate}}", $endDate->format("d/m/Y"), $htmlHeader);
        $htmlHeader = str_replace("{{dueDate}}", "End of Week", $htmlHeader);

        // Replace values in HTML footer
        $htmlFooter = str_replace("{{subTotal}}", $subTotal, $htmlFooter);
        $htmlFooter = str_replace("{{gst}}", $gst, $htmlFooter);
        $htmlFooter = str_replace("{{amountPaid}}", $amountPaid, $htmlFooter);
        $htmlFooter = str_replace("{{owing}}", $owing, $htmlFooter);

        $pdf->loadHtml($htmlHeader.$htmlSummaryTable.$htmlFooter);
    }

    public function generate($userProvider, $bookingsProvider) {
        // Create PDF
        $pdf = new Dompdf();

        // Set it to portrait
        $pdf->setPaper('A4', 'portrait');

        // Set all the html content
        $this->loadHtmlContent($pdf, $userProvider, $bookingsProvider, $pricing);

        // Render
        $pdf->render();

        // Output the generated PDF to Browser
        $pdf->stream("summary", ["Attachment" => false]);
    }
}

?>
