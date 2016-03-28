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
	Timber::render('singular-protected.twig', $context);
} else {
	$templates = array('singular.twig', 'index.twig');
	
	if(is_attachment()) {
		if ( false !== strpos( $post->post_mime_type, '/' ) ) {
			list( $type, $subtype ) = explode( '/', $post->post_mime_type );
		} else {
			list( $type, $subtype ) = array( $post->post_mime_type, '' );
		}
		
		array_unshift($templates, 'attachment.twig', 'single-attachment.twig', 'single.twig');
		
		if ( ! empty( $subtype ) ) {
			array_unshift($templates, "{$type}.twig", "{$subtype}.twig", "{$type}_{$subtype}.twig");
		}
	} else if(is_post_type_archive()) {
		array_unshift($templates, 'single-' . $post->post_type . '.twig', 'single.twig');
	} else if(is_single()) {
		array_unshift($templates, 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig');
	} else if(is_page()) {
		array_unshift($templates, 'page-' . $post->post_name . '.twig', 'page-' . $post->ID . '.twig', 'page.twig');
		
		if($page_template = $post->_wp_page_template) array_unshift($templates, $page_template . '.twig');
	}
	
	Timber::render($templates, $context);
}