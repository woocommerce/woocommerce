<?php

namespace Automattic\WooCommerce\Internal\Admin\ProductReviews;

use WP_Comment_Query;
use WP_Screen;

/**
 * Tweaks the WordPress comments page to exclude reviews.
 */
class ReviewsCommentsOverrides {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'comments_list_table_query_args', array( $this, 'exclude_reviews_from_comments' ) );
	}

	/**
	 * Excludes product reviews from showing in the comments page.
	 *
	 * @param array|mixed $args {@see WP_Comment_Query} query args.
	 * @return array
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function exclude_reviews_from_comments( $args ): array {
		$screen = get_current_screen();

		// We only wish to intervene if the edit comments screen has been requested.
		if ( ! $screen instanceof WP_Screen || 'edit-comments' !== $screen->id ) {
			return $args;
		}

		if ( ! empty( $args['post_type'] ) && $args['post_type'] !== 'any' ) {
			$post_types = (array) $args['post_type'];
		} else {
			$post_types = get_post_types();
		}

		$index = array_search( 'product', $post_types );

		if ( $index !== false ) {
			unset( $post_types[ $index ] );
		}

		if ( ! is_array( $args ) ) {
			$args = [];
		}

		$args['post_type'] = $post_types;

		return $args;
	}

}
