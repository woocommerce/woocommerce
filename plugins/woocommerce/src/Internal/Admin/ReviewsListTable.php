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
		if ( current_user_can( 'edit_comment', $item->comment_ID ) ) {
			?>
			<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $item->comment_ID ); ?>"><?php esc_html_e( 'Select review', 'woocommerce' ); ?></label>
			<input
				id="cb-select-<?php echo esc_attr( $item->comment_ID ); ?>"
				type="checkbox"
				name="delete_comments[]"
				value="<?php echo esc_attr( $item->comment_ID ); ?>"
			/>
			<?php
		}
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
		// @TODO Implement in MWC-5333 {agibson 2022-04-12}
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
	 * Prepares reviews for display.
	 */
	public function prepare_items() {

		$comments = get_comments(
			[
				'post_type' => 'product',
			]
		);

		update_comment_cache( $comments );

		$this->items = $comments;
	}

}
