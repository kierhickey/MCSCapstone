<?php
echo $this->session->flashdata('saved');

$icondata[0] = array('profile/edit', 'Edit my details', 'user_edit.gif' );
$this->load->view('partials/iconbar', $icondata);
?>





<h3>Bookings</h3>

<p>To see your confirmed bookings for an individual day for all rooms: </p>
    <ul> 
    <li>    Set the room list to "No Room" </li>
     <li>Click on the date you wish to view, a table will be generated below! </li>
    </ul>

    
    
    
    
<p>**To see your confirmed bookings for a week, click on the button labelled "View Week bookings"**</p>

<div class="calendar-container">
    <img class="loader" src="webroot/images/ui/loaders/oval.svg" alt="Loading..." />
    
</div>
<div class="selected-summary">

</div>
<script src="webroot/js/calendar.js" type="application/javascript"></script>
<script src="webroot/js/summaryTable.js" type="application/javascript"></script>
<script src="webroot/js/profile.js" type="application/javascript"></script>