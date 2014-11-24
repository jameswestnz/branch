<?php
namespace Branch;

class Skin extends \Branch\Singleton {
	public function __construct() {
		// get the current theme
		$theme = wp_get_theme();
	
		// load config
		$this->config();
        
        // CSS compiling
        $this->css($this);
		
		// load customize class
		$this->customize($this);
		
		// register skin sidebars
		$this->register_sidebars();
		
		// add image sizes
		$this->add_image_sizes();
        
        // set timber views
        // user can upload twig files to /wp-content/branch/site to override/add to all skins, or twigs will be loaded from $this->path()
        \Timber::$dirname = array(
	        $this->find_relative_path(WP_CONTENT_DIR . '/branch/site', $this->path()),
			$this->find_relative_path($this->theme_path(), $this->path())
        );
        
        // register actions & filters
		add_action( 'init', array( $this, 'register_menu_locations' )); // skin menu locations
		
		return $this;
	}
	
	/**
	 * theme_path function.
	 * 
	 * @access private
	 * @return void
	 */
	private function theme_path() {
		$theme = wp_get_theme();
		
		if(!isset($this->theme_path)) {
			if(is_child_theme()) {
				$this->theme_path = WP_CONTENT_DIR . '/themes/' . $theme->get('Template');
			} else {
				$this->theme_path = WP_CONTENT_DIR . '/themes/' . $theme->stylesheet;
			}
		}
		
		return $this->theme_path;
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
	
	/*
	 * get_skin_dirs
	 * 
	 * Retrieves skin paths
	 *
	 * Returns absolute paths - recursing up the tree may not work in symlinked environments
	 */
	private function get_skin_dirs($skin_name) {
		$paths = array();
		
		foreach($this->skin_paths as $skin_path) {
			if($path = realpath(WP_CONTENT_DIR . $skin_path . '/' . $skin_name)) {
				$paths[] = $path;
			}
		}
		
		return $paths;
	}
	
	/**
	 * name function.
	 * 
	 * @access public
	 * @return void
	 */
	public function name() {
		if(!isset($this->name)) {
			// load from database - we'll set to default if none exists
			$name = get_theme_mod('skin', 'default');
			
			// now check if we've been passed a skin in $_POST['customized']
	        if(isset($_POST['customized'])) {
	        	$customized = json_decode(stripslashes(html_entity_decode($_POST['customized'])));
	        	
	        	// have we set a skin? Make sure it's not the same
	        	if(isset($customized->skin) && $customized->skin != $name) {
	        		$name = $customized->skin;
	        	}
	        }
		
			// set
			$this->name = $name;
		}
		
		return $this->name;
	}
	
	/**
	 * path function.
	 * 
	 * @access public
	 * @return void
	 */
	public function path() {
		if(!isset($this->path)) {
			// get the current theme
			$theme = wp_get_theme();
			
			// check for skins outside of theme directories
			if(file_exists(WP_CONTENT_DIR . "/branch/skins/{$this->name()}")) {
				$this->path = WP_CONTENT_DIR . "/branch/skins/{$this->name()}";
			}
			
			// check child theme directory
			if(file_exists(WP_CONTENT_DIR . "/themes/{$theme->stylesheet}/skins/{$this->name()}")) {
				$this->path = WP_CONTENT_DIR . "/themes/{$theme->stylesheet}/skins/{$this->name()}";
			}
			
			// check parent theme directory
			if(file_exists(WP_CONTENT_DIR . "/themes/{$theme->get('Template')}/skins/{$this->name()}")) {
				$this->path = WP_CONTENT_DIR . "/themes/{$theme->get('Template')}/skins/{$this->name()}";
			}
		}
		
		return $this->path;
	}
	
	/**
	 * uri function.
	 * 
	 * @access public
	 * @return void
	 */
	public function uri() {
		if(!isset($this->uri)) {
			// get the current theme
			$theme = wp_get_theme();
			
			// check for skins outside of theme directories
			if(file_exists(WP_CONTENT_DIR . "/branch/skins/{$this->name()}")) {
				$this->uri = WP_CONTENT_URL . "/branch/skins/{$this->name()}";
			}
			
			// check child theme directory
			if(file_exists(WP_CONTENT_DIR . "/themes/{$theme->stylesheet}/skins/{$this->name()}")) {
				$this->uri = WP_CONTENT_URL . "/themes/{$theme->stylesheet}/skins/{$this->name()}";
			}
			
			// check parent theme directory
			if(file_exists(WP_CONTENT_DIR . "/themes/{$theme->get('Template')}/skins/{$this->name()}")) {
				$this->uri = WP_CONTENT_URL . "/themes/{$theme->get('Template')}/skins/{$this->name()}";
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
			$theme = wp_get_theme();
			
			$skin_paths = array(
				'/branch/skins',
				'/themes/' . $theme->get('Template') . '/skins',
				'/themes/' . $theme->stylesheet . '/skins'
			);
			
			$skins = array();
			
			foreach($skin_paths as $skin_path) {
				$skin_path = WP_CONTENT_DIR . $skin_path;
				$dirs = array_filter(glob($skin_path . '/*'), 'is_dir');
			
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
			
			// still nothing - need to throw an exception
			if($this->config === null) {
				throw new Exception('A skin must have a config file.');
			}
		}
		
		return $this->config;
	}
	
	/**
	 * customize function.
	 * 
	 * @access public
	 * @return void
	 */
	public function customize() {
		if(!isset($this->customize)) {
			$this->customize = \Branch\Customize::instance($this);
		}
		
		return $this->customize;
	}
	
	/**
	 * css function.
	 * 
	 * @access public
	 * @return void
	 */
	public function css() {
		if(!isset($this->css)) {
			$this->css = \Branch\CSS::instance($this);
		}
		
		return $this->css;
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