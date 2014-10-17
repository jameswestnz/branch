<?php
// Check for Timber Plugin
if (!class_exists('Timber')){
	add_action( 'admin_notices', function(){
		echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . admin_url('plugins.php#timber') . '">' . admin_url('plugins.php') . '</a></p></div>';
	});
	return;
}

/**
 * includes
 *
 * The $branch_includes array determines the code library included in your theme.
 * Add or remove files to the array as needed. Supports child theme overrides.
 *
 * Please note that missing files will produce a fatal error.
 */
$branch_includes = array(
  'lib/twig.php',
  'lib/skin.php',
  'lib/breadcrumbs.php',
  'lib/site.php',
);

foreach ($branch_includes as $file) {
  if (!$filepath = locate_template($file)) {
    trigger_error(sprintf(__('Error locating %s for inclusion', 'branch'), $file), E_USER_ERROR);
  }

  require_once $filepath;
}
unset($file, $filepath);

// contruct theme class
new BranchSite();