<?php

namespace Automattic\WooCommerce\Internal\Admin\ProductReviews;

/**
 * A utility class for handling comments that are product reviews.
 */
class ReviewsUtil {

	/**
	 * Removes product reviews from the edit-comments page to fix the "Mine" tab counter.
	 *
	 * @param array|mixed       $clauses A compacted array of comment query clauses.
	 * @param \WP_Comment_Query $comment_query The WP_Comment_Query instance being filtered.
	 *
	 * @return array|mixed
	 */
	public static function comments_clauses_without_product_reviews( $clauses, $comment_query ) {
		global $wpdb;

		if ( ! empty( $comment_query->query_vars['post_type'] ) ) {
			$post_type = $comment_query->query_vars['post_type'];
			if ( ! is_array( $post_type ) ) {
				$post_type = explode( ',', $post_type );
			}
			if ( in_array( 'product', $post_type, true ) ) {
				return $clauses;
			}
		}

		/**
		 * Any comment queries with these values are likely to be custom handling where we don't want to change default behavior.
		 * This may change for the `type` query vars in the future if we break out review replies as their own type.
		 */
		foreach ( array( 'ID', 'parent', 'parent__in', 'post_author__in', 'post_author', 'post_name', 'type', 'type__in', 'type__not_in', 'post_type__in', 'comment__in', 'comment__not_in' ) as $arg ) {
			if ( ! empty( $comment_query->query_vars[ $arg ] ) ) {
				return $clauses;
			}
		}

		if ( ! empty( $comment_query->query_vars['post_id'] ) && absint( $comment_query->query_vars['post_id'] ) > 0 ) {
			if ( 'product' === get_post_type( absint( $comment_query->query_vars['post_id'] ) ) ) {
				return $clauses;
			}
		}

		if ( ! empty( $comment_query->query_vars['post__in'] ) ) {
			$post_ids = wp_parse_id_list( $comment_query->query_vars['post__in'] );
			_prime_post_caches( $post_ids, false, false );
			foreach ( $post_ids as $post_id ) {
				if ( 'product' === get_post_type( $post_id ) ) {
					return $clauses;
				}
			}
		}

		$clauses['join']  .= " LEFT JOIN {$wpdb->posts} AS wp_posts_to_exclude_reviews ON comment_post_ID = wp_posts_to_exclude_reviews.ID ";
		$clauses['where'] .= ( trim( $clauses['where'] ) ? ' AND ' : '' ) . " wp_posts_to_exclude_reviews.post_type NOT IN ('product') ";

		return $clauses;
	}
}
