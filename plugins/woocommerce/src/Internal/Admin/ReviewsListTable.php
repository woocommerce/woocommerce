<?php
/**
 * Product > Reviews
 */

namespace Automattic\WooCommerce\Internal\Admin;

use WP_List_Table;

/**
 * Handles the Product Reviews page.
 */
class ReviewsListTable extends WP_List_Table {

	/**
	 * Returns the columns for the table.
	 *
	 * @return array Table columns and their headings.
	 */
	public function get_columns() {
		return [
			'cb'       => '<input type="checkbox" />',
			'type'     => _x( 'Type', 'review type', 'woocommerce' ),
			'author'   => __( 'Author', 'woocommerce' ),
			'rating'   => __( 'Rating', 'woocommerce' ),
			'comment'  => _x( 'Review', 'column name', 'woocommerce' ),
			'response' => __( 'Product', 'woocommerce' ),
			'date'     => _x( 'Submitted on', 'column name', 'woocommerce' ),
		];
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @return string Name of the primary colum.
	 */
	protected function get_primary_column_name() {
		return 'comment';
	}

	/**
	 * Renders the checkbox column.
	 *
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_cb( $item ) {
		// @TODO Implement in MWC-5335 {agibson 2022-04-12}
	}

	/**
	 * Renders the review column.
	 *
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_comment( $item ) {
		// @TODO Implement in MWC-5339 {agibson 2022-04-12}
	}

	/**
	 * Renders the author column.
	 *
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_author( $item ) {
		// @TODO Implement in MWC-5336 {agibson 2022-04-12}
	}

	/**
	 * Renders the "submitted on" column.
	 *
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_date( $item ) {
		// @TODO Implement in MWC-5338 {agibson 2022-04-12}
	}

	/**
	 * Renders the product column.
	 *
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_response( $item ) {
		// @TODO Implement in MWC-5337 {agibson 2022-04-12}
	}

	/**
	 * Renders the type column.
	 *
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_type( $item ) {
		echo esc_html(
			'review' === $item->comment_type ?
			'&#9734;&nbsp;' . __( 'Review', 'woocommerce' ) :
			__( 'Reply', 'woocommerce' )
		);
	}

	/**
	 * Renders the rating column.
	 *
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_rating( $item ) {
		$rating = get_comment_meta( $item->comment_ID, 'rating', true );

		if ( ! empty( $rating ) && is_numeric( $rating ) ) {
			$rating = (int) $rating;
			$accessibility_label = sprintf(
				/* translators: 1: number representing a rating */
				__( '%1$s out of 5', 'woocommerce' ),
				$rating
			);
			$stars = str_repeat( '&#9733;', $rating );
			$stars .= str_repeat( '&#9734;', 5 - $rating );
			?>
			<span aria-label="<?php echo esc_attr( $accessibility_label ); ?>"><?php echo esc_html( $stars ); ?></span>
			<?php
		}
	}

	/**
	 * Renders any custom columns.
	 *
	 * @param object|array $item        Review or reply being rendered.
	 * @param string       $column_name Name of the column being rendered.
	 */
	protected function column_default( $item, $column_name ) {
		// @TODO Implement in MWC-5362 {agibson 2022-04-12}
	}

	/**
	 * Returns a list of sortable columns. Key is the column ID and value is which database column
	 * we perform the sorting on. (`rating` uses a unique key instead, as that requires sorting
	 * by meta value.)
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return [
			'author'   => 'comment_author',
			'response' => 'comment_post_ID',
			'date'     => 'comment_date_gmt',
			'type'     => 'comment_type',
			'rating'   => 'rating',
		];
	}

	/**
	 * Builds the `orderby` and `order` arguments based on the current request.
	 *
	 * @return array
	 */
	protected function get_sort_arguments() : array {
		$orderby = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ?? '' ) );
		$order   = sanitize_text_field( wp_unslash( $_REQUEST['order'] ?? '' ) );

		$args = [];

		if ( ! in_array( $orderby, $this->get_sortable_columns(), true ) ) {
			$orderby = 'comment_date_gmt';
		}

		// If ordering by "rating", then we need to adjust to sort by meta value.
		if ( 'rating' === $orderby ) {
			$orderby          = 'meta_value_num';
			$args['meta_key'] = 'rating';
		}

		if ( ! in_array( strtolower( $order ), [ 'asc', 'desc' ], true ) ) {
			$order = 'desc';
		}

		return wp_parse_args(
			[
				'orderby' => $orderby,
				'order'   => strtolower( $order ),
			],
			$args
		);
	}

	/**
	 * Prepares reviews for display.
	 */
	public function prepare_items() {

		$args = [
			'post_type' => 'product',
		];

		// Include the order & orderby arguments.
		$args = wp_parse_args( $this->get_sort_arguments(), $args );

		$comments = get_comments( $args );

		update_comment_cache( $comments );

		$this->items = $comments;
	}

}
