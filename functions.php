<?php
// Check for Timber Plugin
if (!class_exists('Timber')){
	add_action( 'admin_notices', function(){
		echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . admin_url('plugins.php#timber') . '">' . admin_url('plugins.php') . '</a></p></div>';
	});
	return;
}

// check for site class
$site_path = 'lib/site.php';
if (!$filepath = locate_template($site_path)) {
	trigger_error(sprintf(__('Error locating %s for inclusion'), $site_path), E_USER_ERROR);
}
require_once $filepath;

// contruct theme class
add_action('branch', array('Branch\Site', 'instance'), 10);

// fire as an action so it can be unregistered and overridden
do_action('branch');