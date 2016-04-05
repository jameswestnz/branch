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

$templates = array('templates/index.twig');

if(is_home()) array_unshift($templates, 'templates/home.twig');

$context['posts'] = Timber::get_posts();
Timber::render($templates, $context);