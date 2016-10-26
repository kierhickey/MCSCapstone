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

    public function loadHtmlContent($pdf) {
        $pdf->setBasePath(__DIR__."/../views/pdfsummary/");
        $htmlHeader = file_get_contents(__DIR__."/../views/pdfsummary/summary-header.html");
        $htmlFooter = file_get_contents(__DIR__."/../views/pdfsummary/summary-footer.html");

        $htmlSummaryTable = "<table class='summary-table'>";
        $htmlSummaryTable .= "
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Room</th>
                    <th>Session</th>
                    <th>Price</th>
                </tr>
            </thead><tbody>";

        $bookingController = new Bookings();
        $bookings = $bookingController->bookingsForPeriod();

        foreach ($entries as $entry) {
            $date = $entry["date"];
            $session = $entry["startDate"] + " &ndash; " + $entry["endDate"];
            $price = $entry["isRecurring"] ? 10.00 : 15.00;

            $htmlSummaryTable .= "
            <tr>
                <td>$date</td>
                <td>$location</td>
                <td>$room</td>
                <td>$session</td>
                <td>$price</td>
            </tr>";
        }

        $htmlSummaryTable .= "</tbody></table>";

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
