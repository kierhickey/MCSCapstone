<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// API
$route['api/bookings/summary']      = 'bookings/bookingsForPeriod';
$route['api/users']                 = 'users/getAllBasic';
$route['api/rooms']                 = 'rooms/getAllBasic';
$route['api/pdf']                   = 'bookings/generatePdf';

// Bookings
$route['bookings']					= 'bookings/index';
$route['summary']                   = 'bookings/summaryPage';

// ClassroomBookings-specific
$route['contact']					= 'site/contact';
$route['faq']						= 'site/faq';
$route['features']					= 'site/features';

// School Management
$route['dashboard']				    = 'school/manage';
$route['default_controller']		= 'school';

// Help
$route['help']						= 'help/index';
$route['help/:any']                 = 'help/index';
$route['help/contents']				= 'help/contents';

// Holidays
$route['holidays']					= 'holidays/index';

// Sessions
$route['periods']					= 'periods/index';

// Profile
$route['profile']					= 'profile/index';
$route['profile/(:num)']            = 'profile/index/$1';

// Remote
$route['remote']					= 'remote/index';

// Reports
$route['reports']					= 'reports/index';

// Rooms
$route['rooms']						= 'rooms/index';
$route['rooms/fields']				= 'rooms/fields_index';
$route['rooms/fields/add']		    = 'rooms/fields_add';
$route['rooms/fields/delete']		= 'rooms/fields_delete';
$route['rooms/fields/delete/:any']	= 'rooms/fields_delete';
$route['rooms/fields/edit']			= 'rooms/fields_edit';
$route['rooms/fields/edit/:any']	= 'rooms/fields_edit';
$route['rooms/fields/save']		    = 'rooms/fields_save';

// Miscellaneous
$route['scaffolding_trigger']		= "scaffolding";
$route['timetable']					= 'timetable/index';
$route['users']						= 'users/index';
$route['weeks']						= 'weeks/index';
$route['weekswizard']				= 'weekswizard/index';

?>
