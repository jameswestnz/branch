<?php	
// check for site class
$site_path = 'lib/branch.php';
if (!$filepath = locate_template($site_path)) {
	trigger_error(sprintf(__('Error locating %s for inclusion'), $site_path), E_USER_ERROR);
}
require_once $filepath;

// contruct theme class
add_action('after_setup_theme', array('Branch\Branch', 'instance'), 10);