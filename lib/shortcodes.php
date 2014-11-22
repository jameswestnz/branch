<?php
function shortcode_load_twig($atts) {
	$files = $atts['files'];
	$files = explode(',', $files);
	unset($atts['files']);
	
	return Timber::compile($files, $atts);
}
add_shortcode( 'load_twig', 'shortcode_load_twig' );