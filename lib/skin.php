<?php

class BranchSkin {
	public $name = 'default';
	public $link = '/wp-content/themes/branch/skins/default';
	
	public $paths = array();
	
	/* $skin_paths
	 * lowest order first
	 */
	public $skin_paths = array(
		'/uploads/branch/skins'
	);
	
	public function __construct() {
        $name = 'default';
        if(isset($_POST['customized'])) {
        	$customized = json_decode(stripslashes(html_entity_decode($_POST['customized'])));
        	
        	if(isset($customized->skin)) {
        		$name = $customized->skin;
        	}
        }
        $name = get_theme_mod('skin', $name);
	
		// set name, as passed in contructor
		$this->name = $name;
		
		// get the current theme
		$theme = wp_get_theme();
		
		// could be more dynamic - this assume that the skin resides in the current themes directory, which is not true for branch.
		// deprecate this - not smart
		$this->link = site_url("wp-content/themes/{$theme->stylesheet}/skins/".$name);
		
		// define possible skin paths
        $this->add_skin_path("/themes/{$theme->get('Template')}/skins");
        $this->add_skin_path("/themes/{$theme->stylesheet}/skins");
	
		// load config
		$this->config = $this->load_config();
		
		if(!$this->config) throw new Exception('A skin must have a config file.');
		
		// register skin sidebars
		$this->register_sidebars();
		
        // set assets paths - must be relative to current theme directory
        $this->add_asset_path("../../uploads/branch/skins/{$this->name}");
        $this->add_asset_path("../{$theme->stylesheet}/skins/{$this->name}");
        if(is_child_theme()) $this->add_asset_path("../{$theme->get('Template')}/skins/{$this->name}");
        $this->add_asset_path("../{$theme->stylesheet}/skins/default");
        if(is_child_theme()) $this->add_asset_path("../{$theme->get('Template')}/skins/default");
        
        // set timeber views
        Timber::$dirname = array();
        foreach($this->paths as $path) {
	        Timber::$dirname[] = $path;
        }
		
		return $this;
	}
	
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
	
	private function load_config() {
		$skin_paths = $this->get_skin_dirs($this->name);
		
		$config = null;
	
		foreach($skin_paths as $skin_path) {
			$file = $skin_path . '/config';
			if(file_exists($file)) {
				$config = json_decode(file_get_contents($file), true);
			}
		}
		
		return $config;
	}
	
	public function add_skin_path($path) {
		if(!in_array($path, $this->skin_paths)) {
			$this->skin_paths[] = $path;
		}
	}
	
	public function add_asset_path($path) {
		if(!in_array($path, $this->paths)) {
			$this->paths[] = $path;
		}
	}
	
	/*
	 * get_asset_uri
	 * 
	 * Determines the final asset URI based on the override order defined in __contruct()
	 * Returns first match.
	 */
	public static function get_asset_uri($path) {
		$theme = wp_get_theme();
		foreach(Timber::$dirname as $dir) {
			$levels = 0;
			
			foreach(explode('../', $dir) as $parent) {
				if($parent == '') $levels++;
			}
			
			$theme_dir = WP_CONTENT_DIR . "/themes/{$theme->stylesheet}";
			
			// remove levels
			$theme_dir = explode('/', $theme_dir);
			while($levels > 0) {
				array_pop($theme_dir);
				$levels--;
			}
			
			$theme_dir = implode('/', $theme_dir) . '/';
			$dir = str_replace('../', '', $dir);
			
			// final path & URI
			$uri = '/' . str_replace(ABSPATH, '', $theme_dir . $dir) . $path;
			$dir = realpath($theme_dir . $dir);
			
			$file = realpath($dir . $path);
			
			if(file_exists($file)) {
				return $uri;
			}
		}
	}
	
	/*
	 * get_skins
	 * 
	 * Retrieves all skins
	 */
	public static function get_skins() {
		$skins = array();
		
		// duplicate of construct - however, this needs to be static. Another way to simplify?
		$theme = wp_get_theme();
		$skin_paths = array(
			'/uploads/branch/skins',
			"/themes/{$theme->get('Template')}/skins",
			"/themes/{$theme->stylesheet}/skins"
		);
		
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
		
		return $skins;
	}
	
	/*
	 * get_skin_dirs
	 * 
	 * Retrieves skin paths
	 *
	 * Returns absolute paths - recursing up the tree may not work in symlinked environments
	 */
	public function get_skin_dirs($skin_name) {
		$paths = array();
		
		foreach($this->skin_paths as $skin_path) {
			if($path = realpath(WP_CONTENT_DIR . $skin_path . '/' . $skin_name)) {
				$paths[] = $path;
			}
		}
		
		return $paths;
	}
}

// bind to BranchSite
add_action('branch_construct', function($site){
	$site->skin = new BranchSkin();
}, 10);