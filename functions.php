<?php
// add support for specific plugins
// roots/soil
add_theme_support('soil-clean-up');
add_theme_support('soil-disable-asset-versioning');
add_theme_support('soil-disable-trackbacks');
add_theme_support('soil-jquery-cdn');
add_theme_support('soil-js-to-footer');
add_theme_support('soil-nice-search');
add_theme_support('soil-relative-urls');
	
// check for site class
$site_path = 'lib/site.php';
if (!$filepath = locate_template($site_path)) {
	trigger_error(sprintf(__('Error locating %s for inclusion'), $site_path), E_USER_ERROR);
}
require_once $filepath;

// contruct theme class
add_action('after_setup_theme', array('Branch\Site', 'instance'), 10);