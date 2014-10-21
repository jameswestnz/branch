<?php

class BranchSkin {
	public $name = 'default';
	public $link = '/wp-content/themes/branch/skins/default';
	
	public $paths = array();
	
	public function __construct($name='default') {
	
		$this->name = $name;
		
		// could be more dynamic - this assume that the skin resides in the current themes directory, which is not true for branch.
		$theme = wp_get_theme();
		$this->link = site_url('wp-content/themes/' . $theme->stylesheet . '/skins/'.$name);
		
        // set assets paths - must be relative to current theme directory
        $this->add_asset_path("../../uploads/branch/skins/{$this->name}");
        $this->add_asset_path("../{$theme->stylesheet}/skins/{$this->name}");
        $this->add_asset_path("../branch/skins/{$this->name}");
        $this->add_asset_path("../{$theme->stylesheet}/skins/default");
        $this->add_asset_path("../branch/skins/default");
        
        // set timeber views
        Timber::$dirname = array();
        foreach($this->paths as $path) {
	        Timber::$dirname[] = $path;
        }
		
		return $this;
	}
	
	private function add_asset_path($path) {
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
}