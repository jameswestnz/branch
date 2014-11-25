<?php
namespace Branch;

class CSS extends \Branch\Singleton {
	private $variables = array();
	
	public function __construct($skin) {
		// make skin available to future calls
		$this->skin = $skin;
        
        add_action('init', array( $this, 'enqueue' ) );
        
        // change compiler
        add_filter('wp_less_compiler', function() {
	        return get_template_directory() . '/lib/vendor/oyejorge/less.php/lessc.inc.php';
        });
		
        // TODO: setup garbage collector
        //$this->compiler()->install();
        //$this->compiler()->uninstall();
	}
	
	public function compiler() {
		if(!isset($this->compiler)) {
			$this->compiler = \WPLessPlugin::getInstance();
		}
		
		return $this->compiler;
	}
	
	public function enqueue() {
		global $wp_styles;
			
		// only enqueue theme styles to front-end site
		if(!is_admin()) {
			$this->add_customizer_variables();
			
		    $lessConfig = $this->compiler()->getConfiguration();
		
		    // compiles in the active theme, in a ‘compiled-css’ subfolder
		    $lessConfig->setUploadDir($this->skin->path() . '/tmp');
		    $lessConfig->setUploadUrl($this->skin->uri() . '/tmp');
		    
		    // change compile path if we're in customizer
		    if(isset($_POST['customized'])) {
			    $lessConfig->setUploadDir($this->skin->path() . '/tmp/customized');
			    $lessConfig->setUploadUrl($this->skin->uri() . '/tmp/customized');
		    }
		
			wp_enqueue_style('branch-main', str_replace(WP_CONTENT_URL, '', $this->skin->uri()) . '/skin.less');
		
			// set default global variables
			$this->add_variables(array(
				'skin_uri' => '"' . $this->skin->uri() . '"'
			));
			
			$this->compiler()->setVariables($this->variables);
			
			// ensure xdebug.max_nesting_level is high enough
			ini_set('xdebug.max_nesting_level', 200);
			
			$this->compiler()->dispatch();
		}
	}
	
	private function add_variables($vars) {
		$this->variables = array_merge($vars, $this->variables);
		return $this->variables;
	}
	
	private function add_customizer_variables() {
		if(!isset($this->skin->config()['customize']) || !isset($this->skin->config()['customize']['sections'])) return;
			
		$fonts = array();
		
		if(isset($this->skin->config()['fonts']) && !empty($this->skin->config()['fonts'])) {
			foreach($this->skin->config()['fonts'] as $font) {
				if(!isset($font['id']) || !isset($font['name'])) continue;
				
				$fonts[$font['id']] = $font;
			}
		}
		
		$vars = array();
		
		foreach($this->skin->config()['customize']['sections'] as $section) {
			if(isset($section['fields'])) {
				foreach($section['fields'] as $field) {
					if(!isset($field['id']) || !isset($field['default']) || !isset($field['type']) || !isset($field['css']) || $field['css'] !== true) continue;
					
					$key = preg_replace('/[^a-zA-Z\_]+/', '', str_replace('-', '_', $field['id']));
					
					$value = get_theme_mod($field['id'], $field['default']);
					
					// get value from $_POST['customized'] if it exists
					if(isset($_POST['customized'])) {// && 
						$customized = json_decode( wp_unslash( $_POST['customized'] ), true );
						
						if(isset($customized[$field['id']])) {
							$value = $customized[$field['id']];
						}
					}
					
					// in case the value is empty...
					if(empty($value)) {
						$value = $field['default'];
					}
					
					if($key != '' && $value != '') {
						switch($field['type']) {
							case 'font';
								$name = isset($fonts[$value]['css_name']) ? $fonts[$value]['css_name'] : $fonts[$value]['name'];
								
								// set the font name variable
								$vars[$key . '_name'] = '"' . $name . '"';
								
								// set the URI variable
								if(isset($fonts[$value]['uri'])) $vars[$key . '_uri'] = '"' . $fonts[$value]['uri'] . '"';
							break;
							
							default:
								$vars[$key] = $value;
							break;
						}
					}
				}
			}
		}
		
		return $this->add_variables($vars);
	}
}