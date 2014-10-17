<?php
// timbertwig override
class BranchTwig extends TimberTwig {
	
	// Before you do this...
	// DO YOU REALLY NEED THIS FUNCTION IN THE VIEW???
	// ...
	// ...
	// ...
	// Really?
	// Don't belive you...
	// ...
	// Surely it can go in you're template's .php file?
	// ...
	// ok...
	// But, let's be smart about this - instead of using function() (or fn()), maybe check the docs on how to create your own twig function
	// or... allow the PHP function in this array:
	var $allowed_php_functions = array(
	);

    /**
     * @param Twig_Environment $twig
     * @return Twig_Environment
     */
    function add_twig_filters($twig) {
        /* image filters */
        $twig->addFilter('resize', new Twig_Filter_Function(array('TimberImageHelper', 'resize')));
        $twig->addFilter('letterbox', new Twig_Filter_Function(array('TimberImageHelper', 'letterbox')));
        $twig->addFilter('tojpg', new Twig_Filter_Function(array('TimberImageHelper', 'img_to_jpg')));
        $twig->addFilter('get_src_from_attachment_id', new Twig_Filter_Function('twig_get_src_from_attachment_id'));

        /* debugging filters */
        $twig->addFilter('docs', new Twig_Filter_function('twig_object_docs'));
        $twig->addFilter('get_class', new Twig_Filter_Function('get_class'));
        $twig->addFilter('get_type', new Twig_Filter_Function('get_type'));
        $twig->addFilter('print_r', new Twig_Filter_Function(function($arr){
            return print_r($arr, true);
        }));
        $twig->addFilter('print_a', new Twig_Filter_Function(function($arr){
            return '<pre>' . self::object_docs($arr, true) . '</pre>';
        }));

        /* other filters */
        $twig->addFilter('stripshortcodes', new Twig_Filter_Function('strip_shortcodes'));
        $twig->addFilter('array', new Twig_Filter_Function(array($this, 'to_array')));
        $twig->addFilter('string', new Twig_Filter_Function(array($this, 'to_string')));
        $twig->addFilter('excerpt', new Twig_Filter_Function('wp_trim_words'));
        $twig->addFilter('function', new Twig_Filter_Function(array($this, 'exec_function')));
        $twig->addFilter('path', new Twig_Filter_Function('twig_get_path'));
        $twig->addFilter('pretags', new Twig_Filter_Function(array($this, 'twig_pretags')));
        $twig->addFilter('sanitize', new Twig_Filter_Function('sanitize_title'));
        $twig->addFilter('shortcodes', new Twig_Filter_Function('do_shortcode'));
        $twig->addFilter('time_ago', new Twig_Filter_Function(array($this, 'time_ago')));
        $twig->addFilter('twitterify', new Twig_Filter_Function(array('TimberHelper', 'twitterify')));
        $twig->addFilter('twitterfy', new Twig_Filter_Function(array('TimberHelper', 'twitterify')));
        $twig->addFilter('wp_body_class', new Twig_Filter_Function(array($this, 'body_class')));
        $twig->addFilter('wpautop', new Twig_Filter_Function('wpautop'));
        $twig->addFilter('relative', new Twig_Filter_Function(function ($link) {
            return TimberURLHelper::get_rel_url($link, true);
        }));
        $twig->addFilter('date', new Twig_Filter_Function(array($this, 'intl_date')));

        $twig->addFilter('truncate', new Twig_Filter_Function(function ($text, $len) {
            return TimberHelper::trim_words($text, $len);
        }));

        /* actions and filters */
        $twig->addFunction(new Twig_SimpleFunction('action', function ($context) {
            $args = func_get_args();
            array_shift($args);
            $args[] = $context;
            call_user_func_array('do_action', $args);
        }, array('needs_context' => true)));

        $twig->addFilter(new Twig_SimpleFilter('apply_filters', function () {
            $args = func_get_args();
            $tag = current(array_splice($args, 1, 1));

            return apply_filters_ref_array($tag, $args);
        }));
        $twig->addFunction(new Twig_SimpleFunction('function', array(&$this, 'exec_function')));
        $twig->addFunction(new Twig_SimpleFunction('fn', array(&$this, 'exec_function')));

        /* TimberObjects */
        $twig->addFunction(new Twig_SimpleFunction('TimberPost', function ($pid, $PostClass = 'TimberPost') {
            if (is_array($pid) && !TimberHelper::is_array_assoc($pid)) {
                foreach ($pid as &$p) {
                    $p = new $PostClass($p);
                }
                return $pid;
            }
            return new $PostClass($pid);
        }));
        $twig->addFunction(new Twig_SimpleFunction('TimberImage', function ($pid, $ImageClass = 'TimberImage') {
            if (is_array($pid) && !TimberHelper::is_array_assoc($pid)) {
                foreach ($pid as &$p) {
                    $p = new $ImageClass($p);
                }
                return $pid;
            }
            return new $ImageClass($pid);
        }));
        $twig->addFunction(new Twig_SimpleFunction('TimberTerm', function ($pid, $TermClass = 'TimberTerm') {
            if (is_array($pid) && !TimberHelper::is_array_assoc($pid)) {
                foreach ($pid as &$p) {
                    $p = new $TermClass($p);
                }
                return $pid;
            }
            return new $TermClass($pid);
        }));
        $twig->addFunction(new Twig_SimpleFunction('TimberUser', function ($pid, $UserClass = 'TimberUser') {
            if (is_array($pid) && !TimberHelper::is_array_assoc($pid)) {
                foreach ($pid as &$p) {
                    $p = new $UserClass($p);
                }
                return $pid;
            }
            return new $UserClass($pid);
        }));

        /* bloginfo and translate */
        $twig->addFunction('bloginfo', new Twig_SimpleFunction('bloginfo', function ($show = '', $filter = 'raw') {
            return get_bloginfo($show, $filter);
        }));
        $twig->addFunction('__', new Twig_SimpleFunction('__', function ($text, $domain = 'default') {
            return __($text, $domain);
        }));

        $twig = apply_filters('get_twig', $twig);

        return $twig;
    }

    /**
     * @param string $function_name
     * @return mixed
     */
    function exec_function($function_name) {
    	$function_name = trim($function_name);
    	
    	if(!in_array($function_name, $this->allowed_php_functions)) {
	    	return false;
    	}
    	
        $args = func_get_args();
        array_shift($args);
        return call_user_func_array($function_name, ($args));
    }
}