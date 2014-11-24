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
		// remove timber twig_apply_filters
        remove_all_actions('twig_apply_filters');
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
        /* image filters */
        $twig->addFilter('resize', new \Twig_Filter_Function(array('TimberImageHelper', 'resize')));
        $twig->addFilter('letterbox', new \Twig_Filter_Function(array('TimberImageHelper', 'letterbox')));
        $twig->addFilter('tojpg', new \Twig_Filter_Function(array('TimberImageHelper', 'img_to_jpg')));
        $twig->addFilter('get_src_from_attachment_id', new \Twig_Filter_Function('twig_get_src_from_attachment_id'));

        /* debugging filters */
        $twig->addFilter('docs', new \Twig_Filter_Function('twig_object_docs'));
        $twig->addFilter('get_class', new \Twig_Filter_Function('get_class'));
        $twig->addFilter('get_type', new \Twig_Filter_Function('get_type'));
        $twig->addFilter('print_r', new \Twig_Filter_Function(function($arr){
            return print_r($arr, true);
        }));
        $twig->addFilter('print_a', new \Twig_Filter_Function(function($arr){
            return '<pre>' . TimberTwig::object_docs($arr, true) . '</pre>';
        }));

        /* other filters */
        $twig->addFilter('stripshortcodes', new \Twig_Filter_Function('strip_shortcodes'));
        $twig->addFilter('array', new \Twig_Filter_Function(array('TimberTwig', 'to_array')));
        $twig->addFilter('string', new \Twig_Filter_Function(array('TimberTwig', 'to_string')));
        $twig->addFilter('excerpt', new \Twig_Filter_Function('wp_trim_words'));
        $twig->addFilter('function', new \Twig_Filter_Function(array($this, 'exec_function')));
        $twig->addFilter('path', new \Twig_Filter_Function('twig_get_path'));
        $twig->addFilter('pretags', new \Twig_Filter_Function(array('TimberTwig', 'twig_pretags')));
        $twig->addFilter('sanitize', new \Twig_Filter_Function('sanitize_title'));
        $twig->addFilter('shortcodes', new \Twig_Filter_Function('do_shortcode'));
        $twig->addFilter('time_ago', new \Twig_Filter_Function(array('TimberTwig', 'time_ago')));
        $twig->addFilter('twitterify', new \Twig_Filter_Function(array('TimberHelper', 'twitterify')));
        $twig->addFilter('twitterfy', new \Twig_Filter_Function(array('TimberHelper', 'twitterify')));
        $twig->addFilter('wp_body_class', new \Twig_Filter_Function(array('TimberTwig', 'body_class')));
        $twig->addFilter('wpautop', new \Twig_Filter_Function('wpautop'));
        $twig->addFilter('relative', new \Twig_Filter_Function(function ($link) {
            return \TimberURLHelper::get_rel_url($link, true);
        }));
        $twig->addFilter('date', new \Twig_Filter_Function(array('TimberTwig', 'intl_date')));

        $twig->addFilter('truncate', new \Twig_Filter_Function(function ($text, $len) {
            return \TimberHelper::trim_words($text, $len);
        }));

        /* actions and filters */
        $twig->addFunction(new \Twig_SimpleFunction('action', function ($context) {
            $args = func_get_args();
            array_shift($args);
            $args[] = $context;
            call_user_func_array('do_action', $args);
        }, array('needs_context' => true)));

        $twig->addFilter(new \Twig_SimpleFilter('apply_filters', function () {
            $args = func_get_args();
            $tag = current(array_splice($args, 1, 1));

            return apply_filters_ref_array($tag, $args);
        }));
        $twig->addFunction(new \Twig_SimpleFunction('function', array(&$this, 'exec_function')));
        $twig->addFunction(new \Twig_SimpleFunction('fn', array(&$this, 'exec_function')));

        /* TimberObjects */
        $twig->addFunction(new \Twig_SimpleFunction('TimberPost', function ($pid, $PostClass = 'TimberPost') {
            if (is_array($pid) && !\TimberHelper::is_array_assoc($pid)) {
                foreach ($pid as &$p) {
                    $p = new \$PostClass($p);
                }
                return $pid;
            }
            return new \$PostClass($pid);
        }));
        $twig->addFunction(new \Twig_SimpleFunction('TimberImage', function ($pid, $ImageClass = 'TimberImage') {
            if (is_array($pid) && !\TimberHelper::is_array_assoc($pid)) {
                foreach ($pid as &$p) {
                    $p = new \$ImageClass($p);
                }
                return $pid;
            }
            return new \$ImageClass($pid);
        }));
        $twig->addFunction(new \Twig_SimpleFunction('TimberTerm', function ($pid, $TermClass = 'TimberTerm') {
            if (is_array($pid) && !\TimberHelper::is_array_assoc($pid)) {
                foreach ($pid as &$p) {
                    $p = new \$TermClass($p);
                }
                return $pid;
            }
            return new \$TermClass($pid);
        }));
        $twig->addFunction(new \Twig_SimpleFunction('TimberUser', function ($pid, $UserClass = 'TimberUser') {
            if (is_array($pid) && !TimberHelper::is_array_assoc($pid)) {
                foreach ($pid as &$p) {
                    $p = new \$UserClass($p);
                }
                return $pid;
            }
            return new \$UserClass($pid);
        }));

        /* bloginfo and translate */
        $twig->addFunction('bloginfo', new \Twig_SimpleFunction('bloginfo', function ($show = '', $filter = 'raw') {
            return get_bloginfo($show, $filter);
        }));
        $twig->addFunction('__', new \Twig_SimpleFunction('__', function ($text, $domain = 'default') {
            return __($text, $domain);
        }));
        
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
			'bloginfo'
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

        $twig = apply_filters('get_twig', $twig);
        
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

    /**
     * @param string $function_name
     * @return mixed
     */
    public function exec_function($function_name) {
    	$function_name = trim($function_name);
    	
    	$allowed = apply_filters('twig_allowed_php_functions', array());
    	
    	if(!in_array($function_name, $allowed)) {
	    	return false;
    	}
    	
        $args = func_get_args();
        array_shift($args);
        return call_user_func_array($function_name, ($args));
    }
}