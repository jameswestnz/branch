<?php
namespace Branch;

if ( class_exists( 'WPSEO_Breadcrumbs' ) ) {
	class Breadcrumbs extends \Branch\Singleton {
		/**
		 * @var	object	Instance of this class
		 */
		public static $instance;
		
		private $crumbs = [];
	
		private function __construct(){
			add_action('wpseo_breadcrumb_single_link', array($this, 'hijack_items'));
		}
		
		public static function crumbs() {
			if ( ! ( self::$instance instanceof self ) ) {
				self::$instance = new self();
			}
			
			// get yoast seo to generate the crumbs
			// this will trigger the required actions for us to hiject
			WPSEO_Breadcrumbs::breadcrumb('', '', false);
			
			// now return the hijacked items
			return self::$instance->crumbs;
		}
		
		public function hijack_items($link_output, $link=null) {
			$this->crumbs[] = $link_output;
		}
	}
}