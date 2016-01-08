<?php

/**
 * Plugin Name: WP Term Authors
 * Plugin URI:  https://wordpress.org/plugins/wp-term-authors/
 * Description: Authors for categories, tags, and other taxonomy terms
 * Author:      John James Jacoby
 * Version:     0.2.0
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPL v2 or later
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Instantiate the main WordPress Term Color class
 *
 * @since 0.1.2
 */
function _wp_term_authors() {

	// Setup the main file
	$plugin_path = plugin_dir_path( __FILE__ );

	// Include the main class
	require_once $plugin_path . '/includes/class-wp-term-meta-ui.php';
	require_once $plugin_path . '/includes/class-wp-term-authors.php';
}
add_action( 'plugins_loaded', '_wp_term_authors' );

/**
 * Instantiate the main WordPress Term Author class
 *
 * @since 0.2.0
 */
function _wp_term_authors_init() {

	// Allow term authors to be registered
	do_action( 'wp_register_term_authors' );

	// Instantiate the main class
	new WP_Term_Authors( __FILE__ );
}
add_action( 'init', '_wp_term_authors_init', 88 );
