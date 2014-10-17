<?php

// Check for Timber Plugin
if (!class_exists('Timber')){
	add_action( 'admin_notices', function(){
		echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . admin_url('plugins.php#timber') . '">' . admin_url('plugins.php') . '</a></p></div>';
	});
	return;
}

class BranchSkin {
	public $name = 'default';
	public $link = '/wp-content/themes/branch/skins/default';
	
	public function __construct($name='default') {
		$this->name = $name;
		
		// could be more dynamic - this assume that the skin resides in the current themes directory, which is not true for branch.
		$theme = wp_get_theme();
		$this->link = site_url('/wp-content/themes/' . $theme->stylesheet . '/skins/'.$name);
		
        // set timeber views
        Timber::$dirname = array();
        
        // uploaded overiddes
		Timber::$dirname[] = '../../uploads/branch/skins/' . $this->name;
		
		// skin specific
		Timber::$dirname[] = '../' . $theme->stylesheet . '/skins/' . $this->name;
		
		// parent theme skin default
		if(is_child_theme()) Timber::$dirname[] = '../branch/skins/' . $this->name;// skin specific
		
		// default to default
		Timber::$dirname[] = '../' . $theme->stylesheet . '/skins/default';
		
		// parent theme default
		if(is_child_theme()) Timber::$dirname[] = '../branch/skins/default';// skin specific
		
		return $this;
	}
	
	public static function get_asset_uri($path) {
		foreach(Timber::$dirname as $dir) {
			chdir(__DIR__);
			$dir = realpath($dir);
			
			$file = $dir . $path;
			
			if(file_exists($file)) {
				return '/' . str_replace(ABSPATH, '', $file);
			}
		}
	}
}

if ( class_exists( 'WPSEO_Breadcrumbs' ) ) {
	class BranchBreadcrumbs {
		/**
		 * @var	object	Instance of this class
		 */
		public static $instance;
		
		private $crumbs = [];
	
		private function __construct(){
			add_action('wpseo_breadcrumb_single_link', array($this, 'hijack_items'));
		}
		
		public static function crumbs() {
			if ( ! ( self::$instance instanceof self ) ) {
				self::$instance = new self();
			}
			
			// get yoast seo to generate the crumbs
			// this will trigger the required actions for us to hiject
			WPSEO_Breadcrumbs::breadcrumb('', '', false);
			
			// now return the hijacked items
			return self::$instance->crumbs;
		}
		
		public function hijack_items($link_output, $link=null) {
			$this->crumbs[] = $link_output;
		}
	}
}

// Theme class
class BranchSite extends TimberSite {

	var $includes = array(
		'lib/twig.php',           // Custom Twig class
	);

	function __construct(){
		//include libs
		self::include_libs();
		
		// setup timber/twig
		$branchTwig = new BranchTwig();
		// remove timber twig_apply_filters
        remove_all_actions('twig_apply_filters');
        
        // add custom twig_apply_filters
        add_action('twig_apply_filters', array($branchTwig, 'add_twig_filters'));
        
        // set skin
        $this->skin = new BranchSkin('default');
	
		// add theme support etc
		add_theme_support('post-formats');
		add_theme_support('post-thumbnails');
		add_theme_support('menus');
		add_filter('timber_context', array($this, 'add_to_context'));
		add_filter('get_twig', array($this, 'add_to_twig'));
		add_filter('site_transient_update_plugins', array($this, 'disable_timber_updates'));
		add_action('init', array($this, 'register_post_types'));
		add_action('init', array($this, 'register_taxonomies'));
		add_action('widgets_init', array($this, 'register_sidebars'));
		
		parent::__construct();
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

	function add_to_context($context){
		global $user_identity;
		$context['menu'] = new TimberMenu();
		$context['site'] = $this;
		$context['user_identity'] = $user_identity;
		
		if ( class_exists( 'BranchBreadcrumbs' ) ) {
			$context['breadcrumbs'] = BranchBreadcrumbs::crumbs();
		}
		
		return $context;
	}
	
	function get_sidebar($index) {
		if(isset($_GLOBAL['branch_sidebars']) && isset($_GLOBAL['branch_sidebars'][$index])) {
			return $_GLOBAL['branch_sidebars'][$index];
		}
	
		$sidebar_widgets = wp_get_sidebars_widgets();
		if(count($sidebar_widgets[$index])) {
			$_GLOBAL['branch_sidebars'][$index] = Timber::get_widgets($index);
			return $_GLOBAL['branch_sidebars'][$index];
		}
		return false;
	}

	function add_to_twig($twig){
		/* this is where you can add your own fuctions to twig */
		$twig->addExtension(new Twig_Extension_StringLoader());
		$twig->addFilter('myfoo', new Twig_Filter_Function('myfoo'));
		
		// add wordpress additional functions, filters, actions
		$auto_add_functions = array(
			'_n',
			'sprintf',
			'cancel_comment_reply_link',
			'comment_author',
			'comment_author_email',
			'comment_author_url',
			'comment_id_fields',
			'comment_form_title',
			'get_permalink',
			'wp_logout_url',
			'comments_open',
			'is_user_logged_in',
			'get_avatar',
			'get_comments_number',
			'number_format_i18n',
			'get_the_title',
			'get_comment_date',
			'htmlspecialchars',
			'get_comment_link',
			'current_user_can',
			'get_edit_comment_link',
			'is_home',
			'is_front_page'
		);
		
		foreach($auto_add_functions as $name) {
			$twig->addFunction(new Twig_SimpleFunction($name, function () use($name) {
				return call_user_func_array($name, func_get_args());
	        }));
		}
        
        // custom functions
		$twig->addFunction(new Twig_SimpleFunction('get_comment_time', function ($comment_id) {
			global $comment;
			$args = func_get_args();
			array_shift($args);
			$comment = get_comment($comment_id, OBJECT);
			return call_user_func_array('get_comment_time', $args);
        }));
        
		$twig->addFunction(new Twig_SimpleFunction('get_avatar_url', function ($avatar) {
			preg_match("/src='(.*?)'/i", $avatar, $matches);
			return $matches[1];
        }));
        
		$twig->addFunction(new Twig_SimpleFunction('get_sidebar', function ($index) {
			return BranchSite::get_sidebar($index);
        }));
        
		$twig->addFunction(new Twig_SimpleFunction('set_post', function ($current_post) {
			global $post;
			$post = $current_post;
			return $post;
        }));

        
		$twig->addFunction(new Twig_SimpleFunction('get_asset_uri', function ($uri) {
			return BranchSkin::get_asset_uri($uri);
        }));
        
		return $twig;
	}

	function register_sidebars() {
		register_sidebar(array(
			'name'          => __('Primary'),
			'id'            => 'sidebar-primary',
			'before_widget' => '<section class="widget %1$s %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		));
	}
}

// contruct theme class
new BranchSite();