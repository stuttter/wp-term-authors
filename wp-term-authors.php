<?php

/**
 * Plugin Name: WP Term Authors
 * Plugin URI:  https://wordpress.org/plugins/wp-term-authors/
 * Author:      John James Jacoby
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: Authors for categories, tags, and other taxonomy terms
 * Version:     2.0.0
 * Text Domain: wp-term-authors
 * Domain Path: /assets/lang/
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Include the required files & dependencies
 *
 * @since 0.1.2
 */
function _wp_term_authors() {

	// Setup the main file
	$plugin_path = plugin_dir_path( __FILE__ );

	// Classes
	require_once $plugin_path . '/includes/class-wp-term-meta-ui.php';
	require_once $plugin_path . '/includes/class-wp-term-authors.php';
}
add_action( 'plugins_loaded', '_wp_term_authors' );

/**
 * Instantiate the main class
 *
 * @since 0.2.0
 */
function _wp_term_authors_init() {
	new WP_Term_Authors( __FILE__ );
}
add_action( 'init', '_wp_term_authors_init', 88 );
