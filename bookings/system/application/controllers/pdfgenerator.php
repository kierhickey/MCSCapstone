<?php

include_once __DIR__."/../libraries/dompdf/autoload.inc.php";
include_once "bookings.php";

use Dompdf\Dompdf;

class PdfGenerator {
    // In pts
    const PAPER_WIDTH = 525;
    const PAPER_HEIGHT = 842;
    const MARGIN_SIZE_LEFT = 36;
    const MARGIN_SIZE_RIGHT = 36;
    const MARGIN_SIZE_TOP = 36;
    const MARGIN_SIZE_BOTTOM = 36;

    public function sortDate($a, $b) {
        $aDateTemp = explode("-", $a->bookingDate);
        $aDay = intval($aDateTemp[2]);
        $aMonth = intval($aDateTemp[1]);
        $aYear = intval($aDateTemp[0]);

        $bDateTemp = explode("-", $b->bookingDate);
        $bDay = intval($bDateTemp[2]);
        $bMonth = intval($bDateTemp[1]);
        $bYear = intval($bDateTemp[0]);

        return ($aYear >= $bYear) && ($aMonth >= $bMonth) && ($aDay > $bDay);
    }

    public function loadHtmlContent($pdf) {
        $pdf->setBasePath(__DIR__."/../views/pdfsummary/");
        $htmlHeader = file_get_contents(__DIR__."/../views/pdfsummary/summary-header.html");
        $htmlFooter = file_get_contents(__DIR__."/../views/pdfsummary/summary-footer.html");

        $htmlSummaryTable = "<table class='summary-table'>";
        $htmlSummaryTable = $htmlSummaryTable . "
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Room</th>
                    <th>Session</th>
                    <th>Price</th>
                </tr>
            </thead><tbody>";

        $startDate = new DateTime();
        $endDate = (new DateTime())->add(new DateInterval("P1M"));

        if ($_POST["startDate"] != null) {
            $startDate = new DateTime($_POST["startDate"]);
        }

        if ($_POST["endDate"] != null) {
            $endDate = new DateTime($_POST["endDate"]);
        }

        $userId = $_POST['userId'];
        $roomId = $_POST['roomId'];

        $bookingController = new Bookings();
        $bookings = $bookingController->getBookingsForPeriod($startDate, $endDate, $userId, $roomId);

        //usort($bookings, [$this, "sortDate"]);

        $lastDate;

        foreach ($bookings as $entry) {
            $temp = explode("-", $entry["bookingDate"]);
            $date = new DateTime($temp[0]."/".$temp[1]."/".$temp[2]);
            $dateString = $date->format("d/m/Y");
            $session = $entry["bookingStart"] . " &ndash; " . $entry["bookingEnd"];
            $price = $entry["isRecurring"] ? "10.00" : "15.00";
            $location = $entry["location"];
            $room = $entry["roomName"];

            $htmlSummaryTable = $htmlSummaryTable . "
            <tr>
                <td>$dateString</td>
                <td>$location</td>
                <td>$room</td>
                <td>$session</td>
                <td>$price</td>
            </tr>";
        }

        $htmlSummaryTable = $htmlSummaryTable . "</tbody></table>";

        // Replace values in HTML Header
        $htmlHeader = str_replace("{{userFullName}}", "John Doe", $htmlHeader);
        $htmlHeader = str_replace("{{startDate}}", $startDate->format("d/m/Y"), $htmlHeader);
        $htmlHeader = str_replace("{{endDate}}", $endDate->format("d/m/Y"), $htmlHeader);
        $htmlHeader = str_replace("{{dueDate}}", "End of Week", $htmlHeader);

        $pdf->loadHtml($htmlHeader.$htmlSummaryTable.$htmlFooter);
    }

    public function generate() {
        // Create PDF
        $pdf = new Dompdf();

        // Set it to portrait
        $pdf->setPaper('A4', 'portrait');

        // Set all the html content
        $this->loadHtmlContent($pdf);

        // Render
        $pdf->render();

        // Output the generated PDF to Browser
        $pdf->stream("demo", ["Attachment" => false]);
    }
}

?>
