<?php

namespace Automattic\WooCommerce\Internal\Admin\ProductReviews;

/**
 * A utility class for handling comments that are product reviews.
 */
class ReviewsUtil {

	/**
	 * Modifies the moderation URLs in the email notifications for product reviews.
	 *
	 * @param string $message The email notification message.
	 * @param int    $comment_id The comment ID.
	 * @return string The modified email notification message.
	 */
	public static function modify_product_review_moderation_urls( $message, $comment_id ) {
		$comment = get_comment( $comment_id );

		// Only modify URLs for product reviews.
		if ( ! $comment || get_post_type( $comment->comment_post_ID ) !== 'product' ) {
			return $message;
		}

		// Replace the WordPress comment moderation URLs with WooCommerce product review URLs.
		$product_reviews_url = admin_url( 'edit.php?post_type=product&page=product-reviews' );

		// Replace the moderation panel URL (this is the "show all reviews pending" link).
		$message = str_replace(
			admin_url( 'edit-comments.php?comment_status=moderated#wpbody-content' ),
			$product_reviews_url . '&comment_status=moderated',
			$message
		);

		return $message;
	}

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
