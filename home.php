<?php
/**
 * Proxies through to index or singlular based on front page type
 *
 * @package 	WordPress
 * @subpackage 	Branch
 * @since 		Branch 0.1
 */
$page_for_posts = new TimberPost();
if($page_for_posts->id == get_option('page_for_posts')) {
	require_once(locate_template('archive.php'));
} else if(is_front_page() && 'page' == get_option('show_on_front')) {
	locate_template('singular.php', true);
} else {
	locate_template('index.php', true);
}