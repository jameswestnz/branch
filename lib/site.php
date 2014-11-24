<?php
namespace Branch;

require_once 'singleton.php';

class Site extends \Branch\Singleton {
	/**
	 * includes
	 *
	 * The $includes array determines the code library included in your theme.
	 * Add or remove files to the array as needed. Supports child theme overrides.
	 *
	 * Please note that missing files will produce a fatal error.
	 */
	private $includes = array(
		'lib/vendor/jarednova/timber-library/timber.php',
		'lib/twig.php',
		'lib/skin.php',
		'lib/css.php',
		'lib/breadcrumbs.php',
		'lib/customize.php',
		'lib/shortcodes.php',
		'lib/jetpack.php',
		'lib/vendor/oncletom/wp-less/bootstrap-for-theme.php'
	);
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct(){
		//include libs
		$this->include_libs();
		
		// load skin
		$this->skin();
		
		// load jetpack
		$this->jetpack();
		
		// setup timber/twig
		$this->twig();
	
		// add theme support etc
		add_theme_support('post-formats');
		add_theme_support('post-thumbnails');
		add_theme_support('menus');
		
		// register default menus
		add_action('init', function(){
			register_nav_menus(array(
				'primary' => __('Primary', 'branch')
			));
		});
		
		// filters & actions
		// post types registration
		add_action('init', array($this, 'register_post_types'));
		
		// taxonomy registration
		add_action('init', array($this, 'register_taxonomies'));
		
		// Timber/Twig functions
		add_filter('timber_context', array($this, 'add_to_context'));
		
		// disable Timber updates
		add_filter('site_transient_update_plugins', array($this, 'disable_timber_updates'));
		
		// enable timber caching
		if (!WP_DEBUG){
			$cache = apply_filters('branch_cache', true);
		    \Timber::$cache = $cache;
		}
	}
	
	// can't have the timber plugin updated without us being aware of it
	// this will need to be a bit more "dynamic" in the future, in the fact that it should be dependant on version numbers i.e branch version 2.2 is compatible up to timber 3
	function disable_timber_updates($plugins) {
		if(isset($plugins->response)) {
			try {
		    	unset($plugins->response['timber-library/timber.php']);
		    } catch(Exception $e) {}
	    }
	    return $plugins;
	}
	
	// might make this an autoloader form the lib folder - no point in manually adding to an array, although this method may be more secure?
	function include_libs() {
		foreach ($this->includes as $file) {
			if (!$filepath = locate_template($file)) {
				trigger_error(sprintf(__('Error locating %s for inclusion'), $file), E_USER_ERROR);
			}
			
			require_once $filepath;
		}
		unset($file, $filepath);
	}

	function register_post_types(){
		//this is where you can register custom post types
	}

	function register_taxonomies(){
		//this is where you can register custom taxonomies
	}
	
	/**
	 * add_to_context function.
	 * 
	 * @access public
	 * @param mixed $context
	 * @return void
	 */
	function add_to_context($context){
		global $user_identity;
		$context['site'] = $this;
		$context['user_identity'] = $user_identity;
		
		if ( class_exists( 'BranchBreadcrumbs' ) ) {
			$context['breadcrumbs'] = BranchBreadcrumbs::crumbs();
		}
		
		return $context;
	}
	
	/**
	 * uri function.
	 * 
	 * @access public
	 * @return void
	 */
	public function uri() {
		if(!isset($this->uri)) {
			$this->uri = get_stylesheet_directory_uri();
		}
		
		return $this->uri;
	}
	
	/**
	 * skin function.
	 * 
	 * @access private
	 * @return void
	 */
	private function skin() {
		if(!isset($this->skin)) {
			$this->skin = \Branch\Skin::instance();
		}
		
		return $this->skin;
	}
	
	/**
	 * jetpack function.
	 * 
	 * @access private
	 * @return void
	 */
	private function jetpack() {
		if(!isset($this->jetpack)) {
			$this->jetpack = \Branch\Jetpack::instance($this);
		}
		
		return $this->jetpack;
	}
	
	/**
	 * twig function.
	 * 
	 * @access private
	 * @return void
	 */
	private function twig() {
		if(!isset($this->twig)) {
			$this->twig = \Branch\Twig::instance($this);
		}
		
		return $this->twig;
	}
}