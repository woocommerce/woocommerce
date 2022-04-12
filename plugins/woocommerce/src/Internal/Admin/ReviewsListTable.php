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
		// @TODO Implement in MWC-5334 {agibson 2022-04-12}
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

	/**
	 * Returns a list of available bulk actions.
	 *
	 * @global string $comment_status
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		global $comment_status;

		$actions = [];

		if ( in_array( $comment_status, [ 'all', 'approved' ], true ) ) {
			$actions['unapprove'] = __( 'Unapprove', 'woocommerce' );
		}

		if ( in_array( $comment_status, [ 'all', 'moderated' ], true ) ) {
			$actions['approve'] = __( 'Approve', 'woocommerce' );
		}

		if ( in_array( $comment_status, [ 'all', 'moderated', 'approved', 'trash' ], true ) ) {
			$actions['spam'] = _x( 'Mark as spam', 'review', 'woocommerce' );
		}

		if ( 'trash' === $comment_status ) {
			$actions['untrash'] = __( 'Restore', 'woocommerce' );
		} elseif ( 'spam' === $comment_status ) {
			$actions['unspam'] = _x( 'Not spam', 'review', 'woocommerce' );
		}

		if ( in_array( $comment_status, [ 'trash', 'spam' ], true ) || ! EMPTY_TRASH_DAYS ) {
			$actions['delete'] = __( 'Delete permanently', 'woocommerce' );
		} else {
			$actions['trash'] = __( 'Move to Trash', 'woocommerce' );
		}

		return $actions;
	}

}
