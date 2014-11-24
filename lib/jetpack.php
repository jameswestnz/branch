<?php
namespace Branch;

class Jetpack extends \Branch\Singleton {
	public function __construct($site) {
		if(!class_exists('Jetpack')) {
			// include jetpack class
			require_once locate_template('lib/vendor/Automattic/jetpack/class.jetpack.php');
		}
		
		if(!class_exists('Jetpack_User_Agent_Info')) {
			// include jetpack  user-agentclass
			require_once locate_template('lib/vendor/Automattic/jetpack/class.jetpack-user-agent.php');
		}
		
		// Custom CSS
		if(!class_exists('Jetpack_Custom_CSS')) {
			// include jetpack custom css class
			require_once locate_template('lib/vendor/Automattic/jetpack/modules/custom-css.php');
		
			// not sure what this does? copied from Jetpack core
			//add_action( 'init', array( 'Jetpack_Custom_CSS', 'disable' ), 11 );
		}
		
		// Widget Visibility
		if(!class_exists('Jetpack_Widget_Conditions')) {
			// include jetpack custom css class
			require_once locate_template('lib/vendor/Automattic/jetpack/modules/widget-visibility.php');
		}
		
		// Fix asset locations
		// need to modify plugins_url as we're not a plugin
		add_filter( 'plugins_url', function($url, $path, $plugin){
			if(strpos($url, 'lib/vendor/Automattic/jetpack') === false) return $url;
			
			$url = explode('lib/vendor/Automattic/jetpack', $url);
			$url = get_template_directory_uri() . '/lib/vendor/Automattic/jetpack' . $url[1];
			
			return $url;
		}, 3, 100);
	}
}