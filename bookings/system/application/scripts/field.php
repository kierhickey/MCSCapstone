<?php

if (!function_exists('field')) {
	function field($validation, $database = NULL, $last = ''){
		$value = (isset($validation)) ? $validation : ( (isset($database)) ? $database : $last);
		return $value;
	}
}

?>
