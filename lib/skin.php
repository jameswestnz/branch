<?php

class BranchSkin {
	public $name = 'default';
	public $link = '/wp-content/themes/branch/skins/default';
	
	public function __construct($name='default') {
		$this->name = $name;
		
		// could be more dynamic - this assume that the skin resides in the current themes directory, which is not true for branch.
		$theme = wp_get_theme();
		$this->link = site_url('/wp-content/themes/' . $theme->stylesheet . '/skins/'.$name);
		
        // set timeber views
        Timber::$dirname = array();
        
        // uploaded overiddes
		Timber::$dirname[] = '../../uploads/branch/skins/' . $this->name;
		
		// skin specific
		Timber::$dirname[] = '../' . $theme->stylesheet . '/skins/' . $this->name;
		
		// parent theme skin default
		if(is_child_theme()) Timber::$dirname[] = '../branch/skins/' . $this->name;// skin specific
		
		// default to default
		Timber::$dirname[] = '../' . $theme->stylesheet . '/skins/default';
		
		// parent theme default
		if(is_child_theme()) Timber::$dirname[] = '../branch/skins/default';// skin specific
		
		return $this;
	}
	
	public static function get_asset_uri($path) {
		foreach(Timber::$dirname as $dir) {
			chdir(realpath(__DIR__ . '/..'));
			$dir = realpath($dir);
			
			$file = $dir . $path;
			
			if(file_exists($file)) {
				return '/' . str_replace(ABSPATH, '', $file);
			}
		}
	}
}