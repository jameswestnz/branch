<?php
/**
 * The template for comments popup pages (Not Found)
 *
 * @package  WordPress
 * @subpackage  Branch
 * @since    Branch 0.1
 */

$context = Timber::get_context();
Timber::render('templates/comments-popup.twig', $context);