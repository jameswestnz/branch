<?php
namespace Branch;

require_once 'classes/singleton.php';

class Branch extends \Branch\Singleton {
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct(){
		if(!$this->check_timber()) return;
			
		// run an action so construct can be hooked
		//add_action('branch/init', array($this, 'init'));
		//do_action('branch/init');
		$this->init();
	}
	
	private function init() {		
		// warm up
		$this->include_libs();
		$this->add_theme_support();
		$this->theme();// init theme
		$this->twig();// init twig
		
		// enable timber caching
		if (!WP_DEBUG){
			$cache = apply_filters('branch/cache', true);
		    \Timber::$cache = $cache;
		}
		
		// register default menus
		// needs to come from config
		add_action('init', function(){
			register_nav_menus(array(
				'primary' => __('Primary', 'branch')
			));
		});
		
		// filters & actions		
		// disable Timber updates
		add_filter('site_transient_update_plugins', array($this, 'disable_timber_updates'));
	}
	
	private function check_timber() {
		$timber = class_exists('Timber');
		$message = 'Timber not activated. Make sure you activate the plugin in <a href="'.WP_SITEURL.'/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
		
		if(!$timber && is_admin()) {
			add_action( 'admin_notices', function(){
				?>
			    <div class="notice notice-success is-dismissible">
			        <p><?= $message ?></p>
			    </div>
			    <?php
			});
		} else if(!$timber) {
			exit($message);
		}
		
		return $timber;
	}
	
	public function add_theme_support() {
		foreach(apply_filters('branch/theme/support', array(
			// WP core
			'post-formats',
			'post-thumbnails',
			'menus',
			
			// roots/soil
			'soil-clean-up',
			'soil-disable-asset-versioning',
			'soil-disable-trackbacks',
			'soil-js-to-footer',
			'soil-nice-search',
			'soil-relative-urls'
		)) as $feature) {
			add_theme_support($feature);
		}
	}
	
	// can't have the timber plugin updated without us being aware of it
	public function disable_timber_updates($plugins) {
		if(isset($plugins->response)) {
			try {
		    	unset($plugins->response['timber-library/timber.php']);
		    } catch(Exception $e) {}
	    }
	    return $plugins;
	}
	
	// might make this an autoloader form the lib folder
	private function include_libs() {
		$includes = apply_filters('branch/includes', glob(__DIR__ . '/classes/*.php'));
		
		foreach ($includes as $file) {
			$file = str_replace(get_template_directory(), '', $file);
			if (!$filepath = locate_template($file, true)) {
				trigger_error(sprintf(__('Error locating %s for inclusion'), $file), E_USER_ERROR);
			}
		}
		unset($file, $filepath);
	}
	
	/**
	 * theme function.
	 * 
	 * @access private
	 * @return void
	 */
	private function theme() {		
		return \Branch\theme::instance();
	}
	
	/**
	 * twig function.
	 * 
	 * @access private
	 * @return void
	 */
	private function twig() {		
		return \Branch\Twig::instance($this);
	}
	
	public static function render() {
		$instance = self::instance();
		$context = \Timber::get_context();
		$templates = array(
			'templates/index.twig'
		);
		
		// Archive, including Site front page (when posts)
		if( is_day() || is_month() || is_year() || is_tag() || is_author() || is_category() || is_tax() || is_post_type_archive() ) :
			$context['posts'] = Timber::get_posts();
			
			if ( is_day() || is_month() || is_year() ) :
				array_unshift($templates, 'templates/date.twig');
			elseif ( is_tag() ) :
				array_unshift($templates, 'templates/tag-'.get_query_var('tag').'.twig', 'templates/tag-'.get_query_var('tag_id').'.twig', 'templates/tag.twig');
			elseif ( is_author() ) :
				global $wp_query;
				
				array_unshift($templates, "templates/author.twig");
			
				if ( isset($wp_query->query_vars['author']) ) :
					$author = new TimberUser($wp_query->query_vars['author']);
					$context['author'] = $author;
					array_unshift($templates, "templates/author-{$author->user_nicename}.twig", "templates/author-{$author->id}.twig");
				endif;
			elseif ( is_category() ) :
				array_unshift($templates, 'templates/category-'.get_query_var('category_name').'.twig', 'templates/category-'.get_query_var('cat').'.twig', 'templates/category.twig');
			elseif ( is_tax() ) :
				array_unshift($templates, 'templates/taxonomy-'.get_query_var('taxonomy').'-'.get_query_var('term').'.twig', 'templates/taxonomy-'.get_query_var('taxonomy').'.twig', 'templates/taxonomy.twig');
			elseif ( is_post_type_archive() ) :
				array_unshift($templates, 'templates/archive-'.get_post_type().'.twig');
			endif;
		
		// Home & Site front page is treated slightly differently to standard archives.
		elseif ( is_home() || ( is_front_page() && 'page' != get_option('show_on_front') ) ) :
			$post = new \TimberPost();
			$context['post'] = $post;
			array_unshift($templates, 'templates/home.twig');
			
			if( is_front_page() ) :
				array_unshift($templates, 'templates/front-page.twig');
			endif;
		
		// Singular, including Site front page (when page)
		elseif ( ( is_front_page() && 'page' == get_option('show_on_front') ) || is_post_type_archive() || is_attachment() || is_single() || is_page() || is_singular() ) :
			$post = new \TimberPost();
			$context['post'] = $post;
		
			if(post_password_required($post->ID)) :
				// force the use of only this template
				$templates = 'templates/singular-protected.twig';
			else:
				// always try for singular
				array_unshift($templates, 'templates/singular.twig');
				
				if(is_attachment()) :
					if ( false !== strpos( $post->post_mime_type, '/' ) ) :
						list( $type, $subtype ) = explode( '/', $post->post_mime_type );
					else:
						list( $type, $subtype ) = array( $post->post_mime_type, '' );
					endif;
					
					array_unshift($templates, 'templates/attachment.twig', 'templates/single-attachment.twig', 'templates/single.twig');
					
					if ( !empty( $subtype ) ) :
						array_unshift($templates, "templates/{$type}.twig", "templates/{$subtype}.twig", "templates/{$type}_{$subtype}.twig");
					endif;
				elseif ( is_post_type_archive() ) :
					array_unshift($templates, 'templates/single-' . $post->post_type . '.twig', 'templates/single.twig');
				elseif ( is_single() ) :
					array_unshift($templates, 'templates/single-' . $post->ID . '.twig', 'templates/single-' . $post->post_type . '.twig', 'templates/single.twig');
				elseif ( is_page() ) :
					array_unshift($templates, 'templates/page-' . $post->post_name . '.twig', 'templates/page-' . $post->ID . '.twig', 'templates/page.twig');
				endif;
				
				if( is_front_page() ) :
					array_unshift($templates, 'templates/front-page.twig');
				endif;
		
				// if the post has page templates
				if( $page_template = $post->_wp_page_template ) array_unshift($templates, $page_template);
			endif;
		
		// Comments popup page
		elseif ( is_comments_popup() ) :
			array_unshift($templates, 'templates/comments-popup.twig');
		// Error 404 page
		elseif ( is_404() ) :
			array_unshift($templates, 'templates/404.twig');
		// Search results page
		elseif ( is_search() ) :
			array_unshift($templates, 'templates/search.twig');
		endif;
		
		return \Timber::render($templates, $context);
	}
}