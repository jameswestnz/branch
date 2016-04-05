<?php
/**
 * Search results page
 *
 * @package 	WordPress
 * @subpackage 	Branch
 * @since 		Branch 0.1
 */

$templates = array('templates/search.twig', 'templates/index.twig');
$context = Timber::get_context();

$context['title'] = 'Search results for '. get_search_query();
$context['posts'] = Timber::get_posts();

Timber::render($templates, $context);