<?php
namespace Branch;

class Skin extends \Branch\Singleton {
	public function __construct() {
		// get the current theme
		$theme = wp_get_theme();
	
		// load config
		$this->config();
		
		// register skin sidebars
		$this->register_sidebars();
		
		// add image sizes
		$this->add_image_sizes();
        
        // set timber views
        // user can upload twig files to /wp-content/branch/site to override/add to all skins, or twigs will be loaded from $this->path()
        \Timber::$locations = $this->paths();
        
        // we've set the absolute skin path above, so tell timber not to look any deeper
        \Timber::$dirname = '';
        
        // register actions & filters
		add_action( 'init', array( $this, 'register_menu_locations' )); // skin menu locations
		
		add_action( 'init', function(){
			$class = __NAMESPACE__ . '\\Skin';
			$skin = $class::instance();
			
			// get all template files
			$templates = array();
			foreach(glob($skin->path() . '/template-*.twig') as $file) {
				$parts = explode('/', $file);
				$filename = array_pop($parts);
				$name = ucwords(str_replace('-', ' ', str_replace('.twig', '', str_replace('template-', '', $filename))));
				$templates[$filename] = $name;
			}
			
			// Wordpress doesn't provide a way to add templates - so inject into the cache prior to get_page_templates() being called
			$theme = wp_get_theme();
			$cache_hash = md5( $theme->theme_root . '/' . $theme->stylesheet );
			wp_cache_add('page_templates-' . $cache_hash, $templates, 'themes', 1800);
		});
		
		// load languages from current skin, child theme, and parent theme
		load_theme_textdomain('branch', get_template_directory() . '/skin/languages');
		load_theme_textdomain(wp_get_theme()->Name, get_stylesheet_directory() . '/skin/languages');
		load_theme_textdomain($this->name(), $this->dir() . '/languages');
		
		return $this;
	}
	
	/**
	 * theme_dir function.
	 * 
	 * @access private
	 * @return void
	 */
	private function theme_dir() {
		if(!isset($this->theme_dir)) {
			if(is_child_theme()) {
				$this->theme_dir = get_stylesheet_directory();
			} else {
				$this->theme_dir = get_template_directory();
			}
		}
		
		return $this->theme_dir;
	}
	
	/**
	 * register_sidebars function.
	 * 
	 * @access private
	 * @return void
	 */
	private function register_sidebars() {
		if(!isset($this->config['sidebars'])) return;
	
		foreach($this->config['sidebars'] as $sidebar) {
			$config = array();
			
			foreach($sidebar as $key => $config_item) {
				switch($key) {
					case 'name':
						$config[$key] = _($config_item);
					break;
					
					default:
						$config[$key] = $config_item;
					break;
				}
			}
			
			register_sidebar($config);
		}
	}
	
	/**
	 * add_image_sizes function.
	 * 
	 * @access private
	 * @return void
	 */
	private function add_image_sizes() {
		if(!isset($this->config['images']) || !isset($this->config['images']['sizes'])) return;
	
		foreach($this->config['images']['sizes'] as $size) {
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
	public function register_menu_locations() {
		if(!isset($this->config['menus']['locations'])) return;
		
		$locations = array();
		
		foreach($this->config['menus']['locations'] as $location) {
			$locations[$location['id']] = __($location['name'], 'branch');
		}
		
		register_nav_menus($locations);
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
			$this->name = get_theme_mod('skin_name', wp_get_theme()->Name);
		}
		
		return $this->name;
	}
	
	/**
	 * path function.
	 * 
	 * @access public
	 * @return void
	 */
	public function dir() {
		if(!isset($this->dir)) {
			// check for skins outside of theme directories
			foreach($this->skin_roots() as $skin_path) {
				if(file_exists($skin_path['dir'] . "/{$this->name()}")) {
					$this->dir = $skin_path['dir'] . "/{$this->name()}";
				}
			}
			
			// check child theme directory
			if(file_exists(get_stylesheet_directory() . "/skin")) {
				$this->dir = get_stylesheet_directory() . "/skin";
			}
			
			// check parent theme directory
			if(file_exists(get_template_directory() . "/skin")) {
				$this->dir = get_template_directory() . "/skin";
			}
		}
		
		return $this->dir;
	}
	
	public function path() {
		return $this->dir();
	}
	
	/**
	 * template_paths function.
	 * 
	 * @access public
	 * @return void
	 */
	 
	public function paths() {
		if(!isset($this->paths)) {
			// check for skins outside of theme directories
			foreach($this->skin_roots() as $skin_path) {
				if(file_exists($skin_path['dir'] . "/{$this->name()}")) {
					$this->paths[] = $skin_path['dir'] . "/{$this->name()}";
				}
			}
			
			// check child theme directory
			if(file_exists(get_stylesheet_directory() . "/skin")) {
				$this->paths[] = get_stylesheet_directory() . "/skin";
			}
			
			// check parent theme directory
			if(file_exists(get_template_directory() . "/skin")) {
				$this->paths[] = get_template_directory() . "/skin";
			}
		}
		
		return $this->paths;
	}
	
	/**
	 * uri function.
	 * 
	 * @access public
	 * @return void
	 */
	public function uri() {
		if(!isset($this->uri)) {
			// check for skins outside of theme directories
			foreach($this->skin_roots() as $skin_path) {
				if(file_exists($skin_path['dir'] . "/{$this->name()}")) {
					$this->uri = $skin_path['uri'] . "/{$this->name()}";
				}
			}
			
			// check child theme directory
			if(file_exists(get_stylesheet_directory() . "/skin")) {
				$this->uri = get_stylesheet_directory_uri() . "/skin";
			}
			
			// check parent theme directory
			if(file_exists(get_template_directory() . "/skin")) {
				$this->uri = get_template_directory_uri() . "/skin";
			}
		}
		
		return $this->uri;
	}
	
	/**
	 * skins function.
	 * 
	 * @access public
	 * @return void
	 */
	public function skins() {
		if(!isset($this->skins)) {
			$skins = array();
			
			foreach($this->skin_roots() as $skin_path) {
				$dirs = array_filter(glob($skin_path['dir'] . '/*'), 'is_dir');
			
				foreach($dirs as $dir) {
					$name = basename($dir);
					$skins[$name] = array(
						'name' => $name,
						'path' => realpath($dir)
					);
				}
			}
			
			$this->skins = $skins;
		}
		
		return $this->skins;
	}
	
	/**
	 * skin_roots function.
	 * 
	 * @access public
	 * @return void
	 */
	public function skin_roots() {
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
		if(!isset($this->config)) {
			$file = $this->path() . '/config.json';
			$option = 'skin_config_' . $this->name();
			
			$config = get_option($option, null);
			
			// if we have config in the database, we have a modified time, the config file exists, and was modified is less than or equal to the the database time; return the stored config
			if($config !== null && isset($config['modified']) && file_exists($file) && filemtime($file) <= $config['modified']) {
				$this->config = $config;
			}
			else
			
			// else, if we have not saved config, or modified was not set, or the file was modified recently; set and update database
			if($config === null || !isset($config['modified']) || (file_exists($file) && filemtime($file) > $config['modified'])) {
				if($this->config = json_decode(file_get_contents($file), true)) {
					$this->config['modified'] = filemtime($file);
					
					update_option($option, $this->config);
				}
			}
			
			// if the config is still null, but we had config in the database, let's fall back to that
			if($this->config === null && $config !== null) {
				$this->config = $config;
			}
			
			// still nothing
			if($this->config === null) {
				$this->config = array();
			}
		}
		
		return $this->config;
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
}