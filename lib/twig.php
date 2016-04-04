<?php
namespace Branch;

class Twig extends \Branch\Singleton {
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct() {
		// remove actions defined in timber-twig.php as we want to hijack
		remove_all_actions( 'timber/twig/filters' );
		
		// extended class at bottom of this file
		$TimberTwig = new TimberTwig();
		add_action( 'timber/twig/filters', array( $TimberTwig, 'add_timber_filters_deprecated' ) );
		add_action( 'timber/twig/filters', array( $TimberTwig, 'add_timber_filters' ) );
		
		// and add our own functions
		add_filter( 'timber/twig', array( $this, 'modify_twig' ) );
	}
	
	/**
	 * add_twig_filters function.
	 * 
	 * @access public
	 * @param mixed $twig
	 * @return void
	 */
	public function modify_twig($twig){
		$allowed_internal_methods = array(
			'sprintf',
			'in_array'
		);
		
		// allow all "user" functions, essentially any public function in the wordpress scope.
		// We're saying no to "internal" PHP functions as they shouldn't be needed, and potentially dangerous
		$twig->registerUndefinedFunctionCallback(function($name) use ($allowed_internal_methods){
			$allowed = array_merge($allowed_internal_methods, get_defined_functions()['user']);
			
		    if (in_array($name, $allowed)) {
		        return new \Twig_Function_Function($name);
		    }
    
			return false;
		});
        
        // custom functions
		$twig->addFunction(new \Twig_SimpleFunction('get_comment_time', function ($comment_id) {
			global $comment;
			$args = func_get_args();
			array_shift($args);
			$comment = get_comment($comment_id, OBJECT);
			return call_user_func_array('get_comment_time', $args);
        }));
        
		$twig->addFunction(new \Twig_SimpleFunction('get_avatar_url', function ($avatar) {
			preg_match("/src='(.*?)'/i", $avatar, $matches);
			return $matches[1];
        }));
        
		$twig->addFunction(new \Twig_SimpleFunction('get_sidebar', function ($index) {
			return \Branch\Twig::get_sidebar($index);
        }));
        
		$twig->addFunction(new \Twig_SimpleFunction('set_post', function ($current_post) {
			global $post;
			$post = $current_post;
			return $post;
        }));
        
		$twig->addFunction(new \Twig_SimpleFunction('get_asset_uri', function ($uri) {
			return \Branch\Skin::instance()->uri() . $uri;
        }));
        
		$twig->addFunction(new \Twig_SimpleFunction('get_menu', function ($location) {
			return new \TimberMenu($location);
        }));
        
		return $twig;
	}
	
	/**
	 * get_sidebar function.
	 * 
	 * @access public
	 * @param mixed $index
	 * @return void
	 */
	function get_sidebar($index) {
		if(isset($_GLOBAL['branch_sidebars']) && isset($_GLOBAL['branch_sidebars'][$index])) {
			return $_GLOBAL['branch_sidebars'][$index];
		}
	
		$sidebar_widgets = wp_get_sidebars_widgets();
		if(!empty($sidebar_widgets) && isset($sidebar_widgets[$index]) && count($sidebar_widgets[$index])) {
			$_GLOBAL['branch_sidebars'][$index] = \Timber::get_widgets($index);
			return $_GLOBAL['branch_sidebars'][$index];
		}
		return false;
	}
}

// need more control over what goes on in TimberTwig... lets extend and hijack
class TimberTwig extends \TimberTwig {
}