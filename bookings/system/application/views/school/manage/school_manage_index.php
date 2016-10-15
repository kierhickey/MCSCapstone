<?php
echo $this->session->flashdata('saved');
#if( $this->userauth->CheckAuthLevel( TEACHER ) ){ echo 'teacher'; }
#if( $this->userauth->CheckAuthLevel( ADMINISTRATOR ) ){ echo 'admin'; }


// Menu for all users
$i = 0;
$menu[$i] = [
	'text' => 'Bookings',
	'icon' => 'material/bookings-white-x48.svg',
	'href' => site_url('bookings')
];

$i++;
$menu[$i] = [
	'text' => 'My Profile',
	'icon' => 'material/profile-white-x24.svg',
	'href' => site_url('profile')
];

$i++;




// Menu items for Administrators

$i = 0;
$school[$i] = [
	'text' => 'Business Details',
	'icon' => 'material/business-info-white-x48.svg',
	'href' => site_url('school/details')
];

$i++;
$school[$i] = [
	'text' => 'The Business Day',
	'icon' => 'material/business-day-white-x48.svg',
	'href' => site_url('periods')
];

$i++;
$school[$i] = [
	'text' => 'Week Cycle',
	'icon' => 'material/week-cycle-white-x48.svg',
	'href' => site_url('weeks')
];

$i++;
$school[$i] = [
	'text' => 'Holidays',
	'icon' => 'material/holiday-white-x48.svg',
	'href' => site_url('holidays')
];

$i++;
$school[$i] = [
	'text' => 'Rooms',
	'icon' => 'material/rooms-white-x48.svg',
	'href' => site_url('rooms')
];

$i++;
$school[$i] = [
	'text' => 'Departments',
	'icon' => 'material/business-white-x24.svg',
	'href' => site_url('departments')
];


$i = 0;

/*
$i++;
$admin[$i]['text'] = 'Reports';
$admin[$i]['icon'] = 'school_manage_reports.gif';
$admin[$i]['href'] = site_url('reports');
*/

$i++;
$admin[$i] = [
	'text' => 'Users',
	'icon' => 'material/users-white-x48.svg',
	'href' => site_url('users')
];

$i++;
$admin[$i] = [
	'text' => 'Summary',
	'icon' => 'material/chart-white-x24.svg',
	'href' => site_url("bookings/summary")
];

/*$i++;
$admin[$i]['text'] = 'Settings';
$admin[$i]['icon'] = 'school_manage_settings.gif';
$admin[$i]['href'] = site_url('settings');*/



// Start echoing the admin menu
$i = 0;


// Print Normal menu
dotable($menu);



// Check if user is admin
if($this->userauth->CheckAuthLevel(ADMINISTRATOR, $this->authlevel)){
	echo '<h3 class="page-subtitle">Business-related</h3>';
	dotable($school);
	echo '<h3 class="page-subtitle">Management</h3>';
	dotable($admin);
}




function dotable($array){
	$col = 0;

	echo '<ul class="dashboard">';

	foreach($array as $link){
		echo '<li class="dashboard-tile">'.
				'<a class="tile-link" href="'.$link['href'].'">'.
				'<div class="dashboard-tile-inner">'.
					'<img class="tile-icon" src="webroot/images/ui/'.$link['icon'].'" alt="'.$link['text'].'"/>'.
					'<br />'.
					'<span class="tile-text">'.
						$link['text'].
					'</span>'.
				'</div>'.
				'</a>'.
			 '</li>';
		$col++;
	}

	echo "</ul>";
}
?>
