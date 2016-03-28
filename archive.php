<?php
/**
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package 	WordPress
 * @subpackage 	Branch
 * @since 		Branch 0.1
 */

$templates = array('archive.twig');

$data = Timber::get_context();

$data['title'] = 'Archive';
if (is_day()){
	$data['title'] = 'Archive: '.get_the_date( 'D M Y' );
	array_unshift($templates, 'date.twig');
} else if (is_month()){
	$data['title'] = 'Archive: '.get_the_date( 'M Y' );
	array_unshift($templates, 'date.twig');
} else if (is_year()){
	$data['title'] = 'Archive: '.get_the_date( 'Y' );
	array_unshift($templates, 'date.twig');
} else if (is_tag()){
	$data['title'] = single_tag_title('', false);
	array_unshift($templates, 'tag-'.get_query_var('tag').'.twig', 'tag-'.get_query_var('tag_id').'.twig', 'tag.twig');
} else if (is_author()){
	global $wp_query;
	
	array_unshift($templates, "author.twig");

	if (isset($wp_query->query_vars['author'])){
		$author = new TimberUser($wp_query->query_vars['author']);
		$data['author'] = $author;
		$data['title'] = 'Author Archives: ' . $author->name();
		array_unshift($templates, "author-{$author->user_nicename}.twig", "author-{$author->id}.twig");
	}
} else if (is_category()){
	$data['title'] = single_cat_title('', false);
	array_unshift($templates, 'category-'.get_query_var('category_name').'.twig', 'category-'.get_query_var('cat').'.twig', 'category.twig');
} else if (is_tax()){
	array_unshift($templates, 'taxonomy-'.get_query_var('taxonomy').'-'.get_query_var('term').'.twig', 'taxonomy-'.get_query_var('taxonomy').'.twig', 'taxonomy.twig');
} else if (is_post_type_archive()){
	$data['title'] = post_type_archive_title('', false);
	array_unshift($templates, 'archive-'.get_post_type().'.twig');
}

if(is_paged()) {
	$templates[] = 'paged.twig';
}

$templates[] = 'index.twig';

$data['posts'] = Timber::get_posts();

Timber::render($templates, $data);