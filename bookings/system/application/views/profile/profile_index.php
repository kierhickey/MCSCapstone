<?php
echo $this->session->flashdata('saved');

$icondata[0] = array('profile/edit', 'Edit my details', 'user_edit.gif' );
$this->load->view('partials/iconbar', $icondata);
?>

<?php if($myroom){ ?>
<h3>Staff bookings in my rooms</h3>
<table class="bookings-table">
	<tr>
		<th>Date</th>
		<th>Room</th>
		<th>User</th>
		<th>Time</th>
		<th>Notes</th>
	</tr>
<?php
foreach($myroom as $booking){
	$string = '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</tr>';
	if($booking->notes){ $booking->notes = '('.$booking->notes.')'; }
	if(!$booking->displayname){ $booking->displayname = $booking->username;}
	echo sprintf($string, date("d/m/Y", strtotime($booking->date)), $booking->name, $booking->displayname, $booking->periodname, $booking->notes);
}
?>
</table>
<?php } ?>



<?php if($mybookings){ ?>
<h3>Bookings</h3>
<table class="bookings-table">
	<colgroup>
		<col span="1" class="col-date" />
		<col span="1" class="col-room" />
		<col span="1" class="col-time" />
		<col span="1" class="col-notes" />
	</colgroup>
	<tr>
		<th>Date</th>
		<th>Room</th>
		<th>Time</th>
		<th>Notes</th>
	</tr>
<?php
foreach($mybookings as $booking){
	$string = '<tr>
				<td>%s</td>
				<td>%s</td>
				<td>%s - %s</td>
				<td>%s</td>
			   </tr>';
	if($booking->notes){ $notes = '('.$booking->notes.')'; } else {$notes ='';}
	echo sprintf($string, date("d/m/Y", strtotime($booking->date)), $booking->name, $booking->time_start, $booking->time_end, $notes);
}
?>
</table>
<?php } ?>


<h3>Total bookings</h3>
<ul>
	<li>Number of bookings made this week: <?php echo $total['week'] ?></li>
	<li>Number of bookings this year: <?php echo $total['yeartodate'] ?></li>
	<li>Number of bookings ever made: <?php echo $total['all'] ?></li>
	<li>Number of current active bookings: <?php echo $total['active'] ?></li>
</ul>
