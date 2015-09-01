<?php

/**
 * Plugin Name: WP Term Authors
 * Plugin URI:  https://wordpress.org/plugins/wp-term-author/
 * Description: Authors for categories, tags, and other taxonomy terms
 * Author:      John James Jacoby
 * Version:     0.1.2
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPL v2 or later
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Term_Authors' ) ) :
/**
 * Main WP Term Author class
 *
 * @link https://make.wordpress.org/core/2013/07/28/potential-roadmap-for-taxonomy-meta-and-post-relationships/ Taxonomy Roadmap
 *
 * @since 0.1.2
 */
final class WP_Term_Authors {

	/**
	 * @var string Plugin version
	 */
	public $version = '0.1.2';

	/**
	 * @var string Database version
	 */
	public $db_version = 201509010001;

	/**
	 * @var string Database version
	 */
	public $db_version_key = 'wpdb_term_authors_version';

	/**
	 * @var string File for plugin
	 */
	public $file = '';

	/**
	 * @var string URL to plugin
	 */
	public $url = '';

	/**
	 * @var string Path to plugin
	 */
	public $path = '';

	/**
	 * @var string Basename for plugin
	 */
	public $basename = '';

	/**
	 * @var boolean Whether to use fancy authors
	 */
	public $fancy = false;

	/**
	 * Hook into queries, admin screens, and more!
	 *
	 * @since 0.1.2
	 */
	public function __construct() {

		// Setup plugin
		$this->file     = __FILE__;
		$this->url      = plugin_dir_url( $this->file );
		$this->path     = plugin_dir_path( $this->file );
		$this->basename = plugin_basename( $this->file );
		$this->fancy    = apply_filters( 'wp_fancy_term_authors', true );

		// Queries
		add_action( 'create_term', array( $this, 'add_term_author' ), 10, 2 );
		add_action( 'edit_term',   array( $this, 'add_term_author' ), 10, 2 );

		// Get visible taxonomies
		$taxonomies = $this->get_taxonomies();

		// Always hook these in, for ajax actions
		foreach ( $taxonomies as $value ) {

			// Unfancy gets the column
			add_filter( "manage_edit-{$value}_columns",          array( $this, 'add_column_header' ) );
			add_filter( "manage_{$value}_custom_column",         array( $this, 'add_column_value'  ), 10, 3 );
			add_filter( "manage_edit-{$value}_sortable_columns", array( $this, 'sortable_columns'  ) );

			add_action( "{$value}_add_form_fields",  array( $this, 'term_author_add_form_field'  ) );
			add_action( "{$value}_edit_form_fields", array( $this, 'term_author_edit_form_field' ) );
		}

		// @todo ajax actions
		//add_action( 'wp_ajax_reauthorsing_terms', array( $this, 'ajax_reauthorsing_terms' ) );

		// Only blog admin screens
		if ( is_blog_admin() || doing_action( 'wp_ajax_inline_save_tax' ) ) {
			add_action( 'admin_init',         array( $this, 'admin_init' ) );
			add_action( 'load-edit-tags.php', array( $this, 'edit_tags'  ) );
		}
	}

	/**
	 * Administration area hooks
	 *
	 * @since 0.1.2
	 */
	public function admin_init() {

		// Check for DB update
		$this->maybe_upgrade_database();
	}

	/**
	 * Administration area hooks
	 *
	 * @since 0.1.2
	 */
	public function edit_tags() {

		// Enqueue javascript
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_head',            array( $this, 'admin_head'      ) );

		// Quick edit
		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_term_author' ), 10, 3 );
	}

	/** Assets ****************************************************************/

	/**
	 * Enqueue quick-edit JS
	 *
	 * @since 0.1.2
	 */
	public function enqueue_scripts() {

	}

	/**
	 * Align custom `authors` column
	 *
	 * @since 0.1.2
	 */
	public function admin_head() {

		// Add the help tab
		get_current_screen()->add_help_tab(array(
			'id'      => 'wp_term_authors_help_tab',
			'title'   => __( 'Term Author', 'wp-term-author' ),
			'content' => '<p>' . __( 'Set term author to help identify who created or owns each term.', 'wp-term-author' ) . '</p>',
		) ); ?>

		<style type="text/css">
			.column-author {
				width: 94px;
			}
		</style>

		<?php
	}

	/**
	 * Return the taxonomies used by this plugin
	 *
	 * @since 0.1.2
	 *
	 * @param array $args
	 * @return array
	 */
	private static function get_taxonomies( $args = array() ) {

		// Parse arguments
		$r = wp_parse_args( $args, array(
			'show_ui' => true
		) );

		// Get & return the taxonomies
		return get_taxonomies( $r );
	}

	/** Columns ***************************************************************/

	/**
	 * Add the "Color" column to taxonomy terms list-tables
	 *
	 * @since 0.1.2
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function add_column_header( $columns = array() ) {
		$columns['author'] = __( 'Author', 'term-author' );

		return $columns;
	}

	/**
	 * Output the value for the custom column, in our case: `author`
	 *
	 * @since 0.1.2
	 *
	 * @param string $empty
	 * @param string $custom_column
	 * @param int    $term_id
	 *
	 * @return mixed
	 */
	public function add_column_value( $empty = '', $custom_column = '', $term_id = 0 ) {

		// Bail if no taxonomy passed or not on the `author` column
		if ( empty( $_REQUEST['taxonomy'] ) || ( 'author' !== $custom_column ) || ! empty( $empty ) ) {
			return;
		}

		// Get the author
		$author = $this->get_term_author( $term_id );
		$retval     = '&mdash;';

		// Output HTML element if not empty
		if ( ! empty( $author ) ) {
			$retval = esc_attr( $author );
		}

		echo $retval;
	}

	/**
	 * Allow sorting by `author`
	 *
	 * @since 0.1.2
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function sortable_columns( $columns = array() ) {
		$columns['author'] = 'author';

		return $columns;
	}

	/**
	 * Add `author` to term when updating
	 *
	 * @since 0.1.2
	 *
	 * @param  int     $term_id
	 * @param  string  $taxonomy
	 */
	public function add_term_author( $term_id = 0, $taxonomy = '' ) {

		// Bail if not updating author
		$author = ! empty( $_POST['term-author'] )
			? $_POST['term-author']
			: '';

		self::set_term_author( $term_id, $taxonomy, $author );
	}

	/**
	 * Set author of a specific term
	 *
	 * @since 0.1.2
	 *
	 * @param  int     $term_id
	 * @param  string  $taxonomy
	 * @param  string  $author
	 * @param  bool    $clean_cache
	 */
	public static function set_term_author( $term_id = 0, $taxonomy = '', $author = '', $clean_cache = false ) {

		// No author, so delete
		if ( empty( $author ) ) {
			delete_term_meta( $term_id, 'author' );

		// Update author value
		} else {
			update_term_meta( $term_id, 'author', $author );
		}

		// Maybe clean the term cache
		if ( true === $clean_cache ) {
			clean_term_cache( $term_id, $taxonomy );
		}
	}

	/**
	 * Return the author of a term
	 *
	 * @since 0.1.2
	 *
	 * @param int $term_id
	 */
	public function get_term_author( $term_id = 0 ) {
		return get_term_meta( $term_id, 'author', true );
	}

	/**
	 * Return author options for use in a dropdown
	 *
	 * @since 0.1.2
	 */
	protected function get_term_author_options() {
		$options = wp_get_term_authors();

		// Start an output buffer
		ob_start();

		// Loop through authors and make them into option tags
		foreach ( $options as $option_id => $option ) : ?>

			<option value="<?php echo esc_attr( $option_id ); ?>">
				<?php echo esc_html( $option ); ?>
			</option>

		<?php endforeach;

		// Return the output buffer
		return ob_get_clean();
	}

	/** Markup ****************************************************************/

	/**
	 * Output the "term-author" form field when adding a new term
	 *
	 * @since 0.1.2
	 */
	public function term_author_add_form_field() {
		?>

		<div class="form-field term-author-wrap">
			<label for="term-author">
				<?php esc_html_e( 'Author', 'wp-term-author' ); ?>
			</label>
			<select name="term-author" id="term-author">
				<?php echo $this->get_term_author_options(); ?>
			</select>
			<p class="description">
				<?php esc_html_e( 'The author is used to determine which users can see which terms.', 'wp-term-author' ); ?>
			</p>
		</div>

		<?php
	}

	/**
	 * Output the "term-author" form field when editing an existing term
	 *
	 * @since 0.1.2
	 *
	 * @param object $term
	 */
	public function term_author_edit_form_field( $term = false ) {
		?>

		<tr class="form-field term-author-wrap">
			<th scope="row" valign="top">
				<label for="term-author">
					<?php esc_html_e( 'Author', 'wp-term-author' ); ?>
				</label>
			</th>
			<td>
				<select name="term-author" id="term-author">
					<?php echo $this->get_term_author_options(); ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'The author is used to determine which users can see which terms.', 'wp-term-author' ); ?>
				</p>
			</td>
		</tr>

		<?php
	}

	/**
	 * Output the "term-author" quick-edit field
	 *
	 * @since 0.1.2
	 *
	 * @param  $term
	 */
	public function quick_edit_term_author( $column_name = '', $screen = '', $name = '' ) {

		// Bail if not the `author` column on the `edit-tags` screen for a visible taxonomy
		if ( ( 'author' !== $column_name ) || ( 'edit-tags' !== $screen ) || ! in_array( $name, $this->get_taxonomies() ) ) {
			return false;
		} ?>

		<fieldset>
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php esc_html_e( 'Author', 'wp-term-author' ); ?></span>
					<span class="input-text-wrap">
						<select name="term-author">
							<?php echo $this->get_term_author_options(); ?>
						</select>
					</span>
				</label>
			</div>
		</fieldset>

		<?php
	}

	/** Database Alters *******************************************************/

	/**
	 * Should a database update occur
	 *
	 * Runs on `init`
	 *
	 * @since 0.1.2
	 */
	private function maybe_upgrade_database() {

		// Check DB for version
		$db_version = get_option( $this->db_version_key );

		// Needs
		if ( $db_version < $this->db_version ) {
			$this->upgrade_database( $db_version );
		}
	}

	/**
	 * Modify the `term_taxonomy` table and add an `author` column to it
	 *
	 * @since 0.1.2
	 *
	 * @param  int    $old_version
	 *
	 * @global object $wpdb
	 */
	private function upgrade_database( $old_version = 0 ) {
		global $wpdb;

		$old_version = (int) $old_version;

		// The main column alter
		if ( $old_version < 201509010001 ) {
			// Nothing to do here yet
		}

		// Update the DB version
		update_option( $this->db_version_key, $this->db_version );
	}
}
endif;

/**
 * Instantiate the main WordPress Term Color class
 *
 * @since 0.1.2
 */
function _wp_term_authors() {

	// Bail if no term meta
	if ( ! function_exists( 'add_term_meta' ) ) {
		return;
	}

	new WP_Term_Authors();
}
add_action( 'init', '_wp_term_authors', 98 );
