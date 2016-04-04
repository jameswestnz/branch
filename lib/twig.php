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
        add_action('twig_apply_filters', array($this, 'add_twig_filters'));
	}
	
	/**
	 * add_twig_filters function.
	 * 
	 * @access public
	 * @param mixed $twig
	 * @return void
	 */
	public function add_twig_filters($twig){
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
			'is_front_page',
			'get_theme_mod',
			'bloginfo',
			'have_posts',
			'single_post_title',
			'post_class',
			'the_posts_pagination',
			'paginate_links',
			'get_option',
			'wp_login_url',
			'is_paged'
		);
		
		foreach($auto_add_functions as $name) {
			$twig->addFunction(new \Twig_SimpleFunction($name, function () use($name) {
				return call_user_func_array($name, func_get_args());
	        }));
		}
        
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

		$twig = apply_filters( 'timber/twig', $twig );
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