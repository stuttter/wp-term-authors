<?php

/**
 * Term Authors Class
 *
 * @since 0.1.3
 *
 * @package TermAuthors/Includes/Class
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Term_Authors' ) ) :
/**
 * Main WP Term Author class
 *
 * @since 0.1.2
 */
final class WP_Term_Authors extends WP_Term_Meta_UI {

	/**
	 * @var string Plugin version
	 */
	public $version = '0.1.4';

	/**
	 * @var string Database version
	 */
	public $db_version = 201511090001;

	/**
	 * @var string Database version
	 */
	public $db_version_key = 'wpdb_term_authors_version';

	/**
	 * @var string Metadata key
	 */
	public $meta_key = 'author';

	/**
	 * Hook into queries, admin screens, and more!
	 *
	 * @since 0.1.2
	 */
	public function __construct( $file = '' ) {

		// Setup the labels
		$this->labels = array(
			'singular'    => esc_html__( 'Author',  'wp-term-authors' ),
			'plural'      => esc_html__( 'Authors', 'wp-term-authors' ),
			'description' => esc_html__( 'The author is the user that created this term.', 'wp-term-authors' )
		);

		// Call the parent and pass the file
		parent::__construct( $file );
	}

	/**
	 * Add the help tabs
	 *
	 * @since 0.1.2
	 */
	public function help_tabs() {
		get_current_screen()->add_help_tab(array(
			'id'      => 'wp_term_authors_help_tab',
			'title'   => __( 'Term Author', 'wp-term-authors' ),
			'content' => '<p>' . __( 'Set term author to help identify who created or owns each term.', 'wp-term-authors' ) . '</p>',
		) );
	}

	/**
	 * Align custom `author` column
	 *
	 * @since 0.1.1
	 */
	public function admin_head() {
		?>

		<style type="text/css">
			.column-author {
				width: 94px;
			}
		</style>

		<?php
	}

	/**
	 * Output the value for the custom column, in our case: `author`
	 *
	 * @since 0.1.2
	 *
	 * @param string $meta
	 *
	 * @return mixed
	 */
	public function format_output( $meta = '' ) {
		$user = get_user_by( 'id', $meta );

		if ( ! empty( $user->first_name ) && ! empty( $user->last_name ) ) {
			$display_name = "{$user->first_name} {$user->last_name}";
		} else {
			$display_name = $user->display_name;
		}

		return $display_name;
	}

	/**
	 * Return author options for use in a dropdown
	 *
	 * @since 0.1.2
	 *
	 * @param array $args
	 */
	private function get_term_author_options( $args = array() ) {

		// Parse arguments
		$r = wp_parse_args( $args, $this->get_default_user_query_args() );

		// Copy arguments for users query
		$user_args = $r;
		unset( $user_args['no_author'], $user_args['selected'] );

		// Get users of this site that could be authors
		$users = get_users( $user_args );

		// Start an output buffer
		ob_start();

		// No author option
		if ( ! empty( $r['no_author'] ) ) :  ?>

			<option value="0"><?php echo esc_html( $r['no_author'] ); ?></option>

		<?php endif;

		// Loop through users
		foreach ( $users as $user ) :
			if ( ! empty( $user->first_name ) && ! empty( $user->last_name ) ) {
				$display_name = "{$user->first_name} {$user->last_name}";
			} else {
				$display_name = $user->display_name;
			} ?>

			<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $r['selected'], $user->ID ); ?>>
				<?php echo esc_html( $display_name ); ?>
			</option>

		<?php endforeach;

		// Return the output buffer
		return ob_get_clean();
	}

	/**
	 * Return the default user query arguments
	 *
	 * @since 0.1.2
	 *
	 * @return array
	 */
	private function get_default_user_query_args() {
		return apply_filters( 'wp_term_author_default_user_args', array(
			'no_author'   => esc_html__( '&mdash; No author &mdash;', 'wp-term-authors' ),
			'selected'    => get_current_user_id(),
			'count_total' => false,
			'orderby'     => 'display_name'
		) );
	}

	/**
	 * Output the "term-author" form field when editing an existing term
	 *
	 * @since 0.1.2
	 *
	 * @param object $term
	 */
	public function form_field( $term = false ) {

		// Get the meta value
		$value = isset( $term->term_id )
			?  $this->get_meta( $term->term_id )
			: ''; ?>

		<select name="term-author" id="term-author" data-author-id="<?php echo esc_attr( $value ); ?>">
			<?php echo $this->get_term_author_options( array(
				'selected' => (int) $value
			) ); ?>
		</select>

		<?php
	}

	/**
	 * Output the "term-author" quick-edit field
	 *
	 * @since 0.1.2
	 */
	public function quick_edit_form_field() {
		?>

		<select name="term-author">
			<?php echo $this->get_term_author_options( array(
				'selected' => 0
			) ); ?>
		</select>

		<?php
	}
}
endif;
