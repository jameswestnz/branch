<?php
/**
 * The Template for displaying all single posts & pages
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;

if(post_password_required($post->ID)) {
	Timber::render('templates/singular-protected.twig', $context);
} else {
	$templates = array('templates/singular.twig', 'templates/index.twig');
	
	if(is_attachment()) {
		if ( false !== strpos( $post->post_mime_type, '/' ) ) {
			list( $type, $subtype ) = explode( '/', $post->post_mime_type );
		} else {
			list( $type, $subtype ) = array( $post->post_mime_type, '' );
		}
		
		array_unshift($templates, 'templates/attachment.twig', 'templates/single-attachment.twig', 'templates/single.twig');
		
		if ( ! empty( $subtype ) ) {
			array_unshift($templates, "templates/{$type}.twig", "templates/{$subtype}.twig", "templates/{$type}_{$subtype}.twig");
		}
	} else if(is_post_type_archive()) {
		array_unshift($templates, 'templates/single-' . $post->post_type . '.twig', 'templates/single.twig');
	} else if(is_single()) {
		array_unshift($templates, 'templates/single-' . $post->ID . '.twig', 'templates/single-' . $post->post_type . '.twig', 'templates/single.twig');
	} else if(is_page()) {
		array_unshift($templates, 'templates/page-' . $post->post_name . '.twig', 'templates/page-' . $post->ID . '.twig', 'templates/page.twig');
		
		if($page_template = $post->_wp_page_template) array_unshift($templates, $page_template);
	}
	
	Timber::render($templates, $context);
}