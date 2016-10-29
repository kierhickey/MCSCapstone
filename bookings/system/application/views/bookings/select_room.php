<form action="<?php echo site_url('bookings/load') ?>" method="POST">
<?php echo form_hidden('chosen_date', $chosen_date) ?>
<table>
	<tr>
		<td valign="middle">
			<label for="room_id">
				<?php
				$url = site_url('rooms/info/'.$this->school_id.'/'.$room_id);
				if(isset($roomphoto[$room_id])){
					$width = 760;
				} else {
					$width = 400;
				}
				?>
				<strong>
					<a onclick="window.open('<?php echo $url ?>','','width=<?php echo $width ?>,height=360,scrollbars');return false;" href="<?php echo $url ?>" title="View Room Information">Room</a>:
				</strong>
			</label>
		</td>
		<td valign="middle">
            <select name="room_id" class="roomBookSelect" onchange="this.form.submit()"></select>
		</td>
		<td> &nbsp; <input type="submit" value=" Load " /></td>
	</tr>
</table>
    <script src="webroot/js/room_select.js" type="application/javascript"></script>
</form>

<br />
