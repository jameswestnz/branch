<?php
/**
 * The template for displaying 404 pages (Not Found)
 *
 * @package  WordPress
 * @subpackage  Branch
 * @since    Branch 0.1
 */

$context = Timber::get_context();
Timber::render('templates/404.twig', $context);