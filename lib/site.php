<?php
namespace Branch;

require_once 'singleton.php';

class Site extends \Branch\Singleton {
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct(){
		if (!class_exists('Timber')){
			echo 'Timber not activated. Make sure you activate the plugin in <a href="/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
			return;
		}
		
		add_action('branch_construct', array($this, 'include_libs'), 99);
		add_action('branch_construct', array($this, 'init'), 100);
		
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
		
		do_action('branch_construct');
	}
	
	public function init() {
		// load skin
		$this->skin();
		
		// setup timber/twig
		$this->twig();
	
		// add theme support etc
		add_theme_support('post-formats');
		add_theme_support('post-thumbnails');
		add_theme_support('menus');
		
		// enable timber caching
		if (!WP_DEBUG){
			$cache = apply_filters('branch_cache', true);
		    \Timber::$cache = $cache;
		}
	}
	
	// can't have the timber plugin updated without us being aware of it
	// this will need to be a bit more "dynamic" in the future, in the fact that it should be dependant on version numbers i.e branch version 2.2 is compatible up to timber 3
	public function disable_timber_updates($plugins) {
		if(isset($plugins->response)) {
			try {
		    	unset($plugins->response['timber-library/timber.php']);
		    } catch(Exception $e) {}
	    }
	    return $plugins;
	}
	
	// might make this an autoloader form the lib folder
	public function include_libs() {
		$includes = apply_filters('branch_includes', glob(get_template_directory() . '/lib/*.php'));
		$includes = array_diff($includes, array(get_template_directory() . '/lib/site.php'));
		
		foreach ($includes as $file) {
			$file = str_replace(get_template_directory(), '', $file);
			if (!$filepath = locate_template($file, true)) {
				trigger_error(sprintf(__('Error locating %s for inclusion'), $file), E_USER_ERROR);
			}
		}
		unset($file, $filepath);
	}

	public function register_post_types(){
		//this is where you can register custom post types
	}

	public function register_taxonomies(){
		//this is where you can register custom taxonomies
	}
	
	/**
	 * add_to_context function.
	 * 
	 * @access public
	 * @param mixed $context
	 * @return void
	 */
	public function add_to_context($context){
		global $user_identity;
		$context['site'] = $this;
		$context['site']->url = get_bloginfo('url');
		$context['site']->name = get_bloginfo();
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