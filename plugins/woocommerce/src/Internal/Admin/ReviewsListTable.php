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
	 * Prepare reviews for display
	 */
	public function prepare_items() {

		$comments = get_comments(
			[
				'post_type'  => 'product',
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
