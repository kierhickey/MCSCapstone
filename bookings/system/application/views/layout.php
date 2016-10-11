<?php

$mobile = $this->agent->is_mobile();

if($this->loggedin){
	$menu[1] = [
		"url" => site_url("dashboard"),
		"img" => "dashboard-white-x24.svg",
		"title" => "Dashboard"
	];

	$menu[2] = [
		"url" => site_url("logout"),
		"img" => "power-white-x24.svg",
		"title" => "Logout"
	];

	if($this->userauth->CheckAuthLevel(ADMINISTRATOR)){ $icon = 'user_administrator.gif'; } else { $icon = 'user_teacher.gif'; }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>MCS Bookings | <?php echo $title ?></title>
	<base href="<?php echo $this->config->config['base_url'] ?>" />
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta name="description" content="Melbourne Consulting Suites booking system. Credit to Craig Rodway's ClassroomBookings software for the backbone." />
	<meta name="author" content="Craig Rodway" />
	<link rel="stylesheet" type="text/css" media="screen" href="webroot/style.css" />
	<link rel="stylesheet" type="text/css" media="print" href="webroot/print.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="webroot/sorttable.css" />
	<script type="text/javascript" src="webroot/js/prototype.lite.js"></script>
	<script type="text/javascript" src="webroot/js/util.js"></script>
	<script type="text/javascript" src="webroot/js/sorttable.js"></script><?php
	$js_cpicker = array('weeks', 'school');
	if(in_array($this->uri->segment(1), $js_cpicker)){
		echo "\n".'<link rel="stylesheet" type="text/css" media="screen" href="webroot/cpicker/js_color_picker_v2.css" />';
		echo "\n".'<script type="text/javascript" src="webroot/cpicker/color_functions.js"></script>';
		echo "\n".'<script type="text/javascript" src="webroot/cpicker/js_color_picker_v2.js"></script>';
	}
	$js_datepicker = array('holidays', 'weeks', 'bookings');
	if(in_array($this->uri->segment(1), $js_datepicker)){
		echo "\n".'<link rel="stylesheet" type="text/css" media="screen" href="webroot/datepicker.css" />';
		echo "\n".'<script type="text/javascript" src="webroot/js/datepicker.js"></script>';
	}
	$js_imagepreview = array('rooms','bookings');
	if(in_array($this->uri->segment(1), $js_imagepreview)){
		echo "\n".'<script type="text/javascript" src="webroot/js/imagepreview.js"></script>';
	}
	?>
</head>
<body>
	<div class="header-fix"></div>
	<header>
		<div class="container">
			<div class="title">
				<?php
					if($this->session->userdata('schoolname')){
						echo '<a href="'.$this->session->userdata('schoolurl').'">'.$this->session->userdata('schoolname').'</a>';
					} else {
						// There must be a way to make this not hard-coded
						echo '<a href="'.$this->config->item('base_url').'">Melbourne Consulting Suites</a>';
					}
				?>
			</div>
			<div class="nav-box">
				<?php if(!$this->loggedin){ echo '<br /><br />'; } ?>
				<?php
					$i=0;
					if(isset($menu)){
						foreach($menu as $link){
							$url = $link["url"];
							$title = $link["title"];
							$img = $link["img"];

							echo "<div class='nav-item'>
										<a class='nav-item-link' href='$url' title='$title'>
											<img class='nav-item-img' src='webroot/images/ui/material/$img' alt='$title' />
											<span class='nav-item-text'>$title</span>
										</a>
									</div>";
						}
					}

				?>
				<?php if($this->loggedin){
						$url = site_url('profile');
				?>
					<div class="profile nav-item">
						<a class="nav-item-link" href="<?php echo $url ?>" title="Profile">
							<img class="nav-item-img" src="webroot/images/ui/material/profile-white-x24.svg" alt="Profile" />
						</a>
					</div>
				<?php } ?>
			</div>
		</div>
	</header>
	<div class="container">

		<?php if(isset($midsection)){ ?>
		<div class="mid-section" align="center">
			<h1 style="font-weight:normal"><?php echo $midsection ?></h1>
		</div>
		<?php } ?>

		<div class="content_area">
			<?php if(isset($showtitle)){ echo '<h2 class="page-title">'.$showtitle.'</h2>'; } ?>
			<?php echo $body ?>
		</div>

		<div class="footer">
		<br />

			<div id="footer">
				<?php
				if(isset($menu)){ foreach( $menu as $link ){
					echo "\n".'<a href="'.$link['href'].'" title="'.$link['title'].'">'.$link['text'].'</a>'."\n";
					echo '<img src="webroot/images/blank.png" width="16" height="10" alt=" " />'."\n";
				} }
				?>
				<br /><br /><span style="font-size:90%;color:#678;">&copy; Copyright 2006 Craig Rodway.<br /></span><br />
			<br />
			</div>
		</div>
	</div>
<div id="tipDiv" style="position:absolute; visibility:hidden; z-index:100"></div>
</body>
</html>
