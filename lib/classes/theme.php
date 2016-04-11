<?php
namespace Branch;

class Theme extends \Branch\Singleton {
	public function __construct() {
		$this->init();
		
		return $this;
	}
	
	/**
	 * __ get function.
	 * 
	 * Provides access to wp_get_theme vars
	 * 
	 * @access private
	 * @return void
	 */
	public function __get($name) {
        if (array_key_exists($name, $this->_data))
		{
			return $this->_data[$name];
		}
		else if($wp_get_theme = wp_get_theme()->$name)
		{
			return $wp_get_theme;
		}

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
	}
	
	private function init() {        
        // set timber views
        // user can upload twig files to /wp-content/branch/site to override/add to all skins, or twigs will be loaded from $this->path()
        \Timber::$locations = $this->paths();
        
        // we've set the absolute skin path above, so tell timber not to look any deeper
        \Timber::$dirname = '';
        
        // register actions & filters
		// post types registration
		//add_action('init', array('Branch\Theme', 'register_post_types'));
		
		// taxonomy registration
		//add_action('init', array('Branch\Theme', 'register_taxonomies'));
		
		// sidebars
		add_action('init', array('Branch\Theme', 'register_sidebars'));
		
		// image sizes
		add_action('init', array('Branch\Theme', 'add_image_sizes'));
		
		// menu locations
		add_action('init', array('Branch\Theme', 'register_menu_locations'));
		
		// register page templates
		add_action( 'init', array('Branch\Theme', 'register_page_templates'));
		
		// load textdomains
		add_action('after_setup_theme', array('Branch\Theme', 'load_textdomains'));
		
		// enqueue scripts
		add_action('wp_enqueue_scripts', array('Branch\Theme', 'enqueue_scripts'));
		
		// enqueue styles
		add_action('wp_enqueue_scripts', array('Branch\Theme', 'enqueue_styles'));
		
		// customiser controls
 -		add_action('customize_register', array( 'Branch\Theme' , 'register_customize' ) );
	}
	
	public function skin() {
		if(!isset($this->skin)) {
			$this->skin = !($skin = get_theme_mod('skin', false)) ? false : $this->skins()[$skin];
		}
		
		return $this->skin;
	}
	
	public function is_child() {
		return is_child_theme();
	}
	
	private function page_templates() {
		$templates = array();
		
		foreach($this->paths() as $path) {
			foreach(glob($path . '/templates/template-*.twig') as $file) {
				$filename = basename($file);
				$name = ucwords(str_replace('-', ' ', str_replace('.twig', '', str_replace('template-', '', $filename))));
				if(!isset($templates[$filename])) $templates[$filename] = $name;
			}
		}
		
		return $templates;
	}
	
	/**
	 * dir function.
	 * 
	 * @access private
	 * @return string
	 */
	public function dir() {
		return ($this->is_child()) ? get_stylesheet_directory() : get_template_directory();
	}
	
	/**
	 * name function.
	 * 
	 * @access public
	 * @return void
	 */
	public function name() {
		if(!isset($this->name)) {
			// set
			$this->name = get_theme_mod('skin_name', wp_get_theme()->stylesheet);
		}
		
		return $this->name;
	}
	
	/**
	 * template_paths function.
	 * 
	 * @access public
	 * @return void
	 */
	 
	public function paths() {
		if(!isset($this->paths)) {
			$paths = array(
				$this->dir()
			);
			
			if($this->is_child()) $paths[] = get_template_directory();
			if($this->skin()) array_unshift($paths, $this->skin()['path']);
			
			$this->paths = $paths;
		}
		
		return $this->paths;
	}
	
	/**
	 * skins function.
	 * 
	 * @access public
	 * @return void
	 */
	public function skins() {
		if(!isset($this->skins)) {
			$themes = array();
			
			foreach($this->skin_roots() as $theme_path) {
				$dirs = array_filter(glob($theme_path['dir'] . '/*'), 'is_dir');
			
				foreach($dirs as $dir) {
					$name = basename($dir);
					$themes[realpath($dir)] = array(
						'name' => $name,
						'path' => realpath($dir)
					);
				}
			}
			
			$this->skins = $themes;
		}
		
		return $this->skins;
	}
	
	/**
	 * skin_roots function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function skin_roots() {
		return apply_filters('branch_skin_roots', array(
			array(
				'dir' => WP_CONTENT_DIR . '/skins',
				'uri' => WP_CONTENT_URL . '/skins'
			)
		));
	}
	
	/**
	 * config function.
	 * 
	 * @access public
	 * @return void
	 */
	public function config() {
		$files = $this->config_files();
		$option = 'config_' . $this->name();
		
		$config = get_theme_mod($option, null);
		
		// if we have config in the database, we have a modified time, the config file exists, and was modified is less than or equal to the the database time; return the stored config
		if($config !== null && isset($config['modified']) && $files[0]['modified'] <= $config['modified']) {
			$config = $config;
		}
		else
		
		// else, if we have not saved config, or modified was not set, or the file was modified recently; set and update database
		if($config === null || !isset($config['modified']) || ($files[0]['modified'] > $config['modified'])) {
			if($config = $this->merge_configs()) {
				$config['modified'] = $files[0]['modified'];
				
				set_theme_mod($option, $config);
			}
		}
		
		// if the config is still null, but we had config in the database, let's fall back to that
		if((!isset($config) || $config === null) && isset($config) && $config !== null) {
			$config = $config;
		}
		
		// still nothing
		if(!isset($config) || $config === null) {
			$config = array();
		}
		
		return $config;
	}
	
	private function merge_configs() {
		$configs = $this->config_files();
		$return = array();
		
		foreach($configs as $config) {
			if($array = json_decode(file_get_contents($config['path']), true)) {
				$return = array_merge_recursive($array, $return);
			}
		}
		
		return $return;
	}
	
	private function config_files() {
		$config_files = array();
		
		foreach($this->paths() as $path) {
			$file = $path . '/branch.json';
			if(file_exists($file)) {
				$config_files[] = array(
					'path'		=> $file,
					'modified'	=> filemtime($file)
				);
			}
		}
		
		usort($config_files, function($a, $b){
			return ($a['modified'] < $b['modified']);
		});
		
		return $config_files;
	}
	
	/**
	 * 
	 * Find the relative file system path between two file system paths
	 *
	 * @param  string  $frompath  Path to start from
	 * @param  string  $topath    Path we want to end up in
	 *
	 * @return string             Path leading from $frompath to $topath
	 */
	private function find_relative_path( $frompath, $topath ) {
	    $from = explode( DIRECTORY_SEPARATOR, $frompath ); // Folders/File
	    $to = explode( DIRECTORY_SEPARATOR, $topath ); // Folders/File
	    $relpath = '';
	 
	    $i = 0;
	    // Find how far the path is the same
	    while ( isset($from[$i]) && isset($to[$i]) ) {
	        if ( $from[$i] != $to[$i] ) break;
	        $i++;
	    }
	    $j = count( $from ) - 1;
	    // Add '..' until the path is the same
	    while ( $i <= $j ) {
	        if ( !empty($from[$j]) ) $relpath .= '..'.DIRECTORY_SEPARATOR;
	        $j--;
	    }
	    // Go to folder from where it starts differing
	    while ( isset($to[$i]) ) {
	        if ( !empty($to[$i]) ) $relpath .= $to[$i].DIRECTORY_SEPARATOR;
	        $i++;
	    }
	    
	    // Strip last separator
	    return substr($relpath, 0, -1);
	}
	
	// STATIC
	
	/**
	 * enqueue_scripts function.
	 * 
	 * Enqueue scripts from branch.json files
	 * 
	 * @access private
	 * @return void
	 */
	public static function enqueue_scripts() {
		if(empty(self::instance()->config()['scripts'])) return;
		
		foreach(self::instance()->config()['scripts'] as $script) {
			wp_deregister_script( $script['handle'] );
			wp_enqueue_script( $script['handle'], self::instance()->get_asset_uri($script['src']), $script['deps'] );
		}
	}
	
	/**
	 * enqueue_styles function.
	 * 
	 * Enqueue styles from branch.json files
	 * 
	 * @access private
	 * @return void
	 */
	public static function enqueue_styles() {
		if(empty(self::instance()->config()['styles'])) return;
		
		foreach(self::instance()->config()['styles'] as $style) {
			wp_deregister_style( $style['handle'] );
			wp_enqueue_style( $style['handle'], self::instance()->get_asset_uri($style['src']), $style['deps'] );
		}
	}
	
	/**
	 * enqueue_styles function.
	 * 
	 * Enqueue styles from branch.json files
	 * 
	 * @access private
	 * @return void
	 */
	public static function load_textdomains() {
		// current theme
		load_theme_textdomain(self::instance()->Name, self::instance()->dir() . '/languages');
		
		// parent theme
		if(self::instance()->is_child) load_theme_textdomain(self::instance()->Template, get_template_directory() . '/languages');
		
		// skin
		if(self::instance()->skin) load_theme_textdomain(self::instance()->skin['name'], self::instance()->skin['dir'] . '/languages');
	}
	
	/**
	 * register_sidebars function.
	 * 
	 * @access private
	 * @return void
	 */
	public static function register_sidebars() {
		$config = self::instance()->config();
		if(!isset($config['sidebars'])) return;
	
		foreach($config['sidebars'] as $sidebar) {
			$args = array();
			
			foreach($sidebar as $key => $config_item) {
				switch($key) {
					case 'name':
						$args[$key] = _($config_item);
					break;
					
					default:
						$args[$key] = $config_item;
					break;
				}
			}
			
			register_sidebar($args);
		}
	}
	
	/**
	 * add_image_sizes function.
	 * 
	 * @access private
	 * @return void
	 */
	public static function add_image_sizes() {
		$config = self::instance()->config();
		if(!isset($config['images']) || !isset($config['images']['sizes'])) return;
	
		foreach($this->config()['images']['sizes'] as $size) {
			if(!isset($size['id'])) continue;
			
			$options = array(
				$size['id'],
				(isset($size['width'])) ? $size['width'] : null,
				(isset($size['height'])) ? $size['height'] : null,
				(isset($size['crop'])) ? $size['crop'] : null,
			);
			
			call_user_func_array('add_image_size', $options);
		}
	}
	
	/**
	 * register_menu_locations function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function register_menu_locations() {
		$config = self::instance()->config();
		if(!isset($config['menus']['locations'])) return;
		
		$locations = array();
		
		foreach($config['menus']['locations'] as $location) {
			$locations[$location['id']] = __($location['name'], 'branch');
		}
		
		register_nav_menus($locations);
	}
	
	/**
	 * register_page_templates function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function register_page_templates() {
		$class = __NAMESPACE__ . '\\Theme';
		$theme = $class::instance();
		
		// get all template files
		$page_templates = $theme->page_templates();
		
		// Wordpress doesn't provide a way to add templates - so inject into the cache prior to get_page_templates() being called
		$cache_hash = md5( $theme->theme_root . '/' . $theme->Stylesheet );
		wp_cache_add('page_templates-' . $cache_hash, $page_templates, 'themes', 1800);
	}
	
	public static function get_asset_uri($uri) {
		foreach(self::instance()->paths() as $path) {
			if(file_exists($path . $uri)) {
				$uri = explode(WP_CONTENT_DIR, $path . $uri)[1];
				return WP_CONTENT_URL . $uri;
			}
		}
		
		return $uri;
	}
	
	public static function register_customize($wp_customize) {
		$class = __NAMESPACE__ . '\\Theme';
		$theme = $class::instance();
		
		$wp_customize->add_panel( 'branch', 
			array(
				'title' => __( 'Appearance', 'branch' ),
				'priority' => 2,
				'capability' => 'edit_theme_options',
			) 
		);
		
		// skin selector
		$wp_customize->add_section( 'branch_skins', 
			array(
				'title'			=> __( 'Select Skin', 'branch' ),
				'priority'		=> 0,
				'capability'	=> 'edit_theme_options',
				'description' => __('Override your theme files usings skins in /wp-content/skins', 'branch'), //Descriptive tooltip
				'panel' => 'branch'
			) 
		);
		
		$wp_customize->add_setting( 'skin',
			array(
				'default'		=> 'default',
				'type'			=> 'theme_mod',
				'capability'	=> 'edit_theme_options',
				'transport'		=> 'refresh'
			) 
		);
		
		$skins = array();
		
		foreach($theme->skins() as $skin) {
			$skins[$skin['path']] = $skin['name'];
		}
		
		$wp_customize->add_control(
		    'skin',
		    array(
		        'type' => 'select',
		        'section' => 'branch_skins',
		        'choices' => $skins
		    )
		);
	}
}