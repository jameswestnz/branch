<?php
/**
 * Proxies through to index or singlular based on front page type
 *
 * @package 	WordPress
 * @subpackage 	Branch
 * @since 		Branch 0.1
 */
if(is_front_page() && 'page' == get_option('show_on_front')) {
	locate_template('singular.php', true);
} else {
	locate_template('index.php', true);
}