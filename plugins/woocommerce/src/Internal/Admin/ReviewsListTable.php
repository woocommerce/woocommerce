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
	 * Gets the name of the default primary column.
	 *
	 * @return string Name of the primary colum.
	 */
	protected function get_primary_column_name() {
		return 'comment';
	}

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

}
