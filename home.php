<?php
/**
 * The main template file
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists
 *
 * @package 	WordPress
 * @subpackage 	Branch
 * @since 		Branch 0.1
 */
$context = Timber::get_context();

$templates = array('home.twig', 'index.twig');

if($page_id = get_option('page_for_posts')) {
	$context['post'] = get_post($page_id);
	$context['post']->title = $context['post']->post_title;
}

// need something for fron-page.php... see here: https://developer.wordpress.org/files/2014/10/template-hierarchy.png

$context['posts'] = Timber::get_posts();
Timber::render($templates, $context);