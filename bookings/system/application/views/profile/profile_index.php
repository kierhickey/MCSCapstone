<?php
echo $this->session->flashdata('saved');

$icondata[0] = array('profile/edit', 'Edit my details', 'user_edit.gif' );
$this->load->view('partials/iconbar', $icondata);
?>





<h3>Bookings</h3>
<p>To see your confirmed bookings for an individual day for all rooms: </p>
<ul>
    <li>Set the room list to "All Rooms"</li>
    <li>Click on the date you wish to view and a table will be generated below!</li>
    <li>If you wish to print the table, you can either download a PDF view, or simply print this page!</li>
</ul>
<p>
    <span class="tip-label">Tip: </span><span class="tip-text">To select multiple days, hold shift while clicking start date, then end date!</span>
</p>

<p>
    If you wish to print the Summary Table below, you can do so using the Printer Icon. <br/> 
    If you wish to generate a PDF invoice summary, click the Download arrow adjacent. 
</p>

<div class="calendar-container">
    <img class="loader" src="webroot/images/ui/loaders/oval.svg" alt="Loading..." />

</div>
<div class="selected-summary">

</div>
<script src="webroot/js/modal.js" type="application/javascript"></script>
<script src="webroot/js/calendar.js" type="application/javascript"></script>
<script src="webroot/js/summary-table.js" type="application/javascript"></script>
<script src="webroot/js/profile.js" type="application/javascript"></script>
