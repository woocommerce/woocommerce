<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Comments
 *
 * Handle comments (reviews and order notes).
 *
 * @class    WC_Comments
 * @version  2.3.0
 * @package  WooCommerce/Classes/Products
 * @category Class
 * @author   WooThemes
 */
class WC_Comments {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		// Rating posts
		add_filter( 'comments_open', array( __CLASS__, 'comments_open' ), 10, 2 );
		add_filter( 'preprocess_comment', array( __CLASS__, 'check_comment_rating' ), 0 );
		add_action( 'comment_post', array( __CLASS__, 'add_comment_rating' ), 1 );
		add_action( 'comment_moderation_recipients', array( __CLASS__, 'comment_moderation_recipients' ), 10, 2 );

		// Clear transients
		add_action( 'wp_update_comment_count', array( __CLASS__, 'clear_transients' ) );

		// Secure order notes
		add_filter( 'comments_clauses', array( __CLASS__, 'exclude_order_comments' ), 10, 1 );
		add_filter( 'comment_feed_where', array( __CLASS__, 'exclude_order_comments_from_feed_where' ) );

		// Secure webhook comments
		add_filter( 'comments_clauses', array( __CLASS__, 'exclude_webhook_comments' ), 10, 1 );
		add_filter( 'comment_feed_where', array( __CLASS__, 'exclude_webhook_comments_from_feed_where' ) );

		// Count comments
		add_filter( 'wp_count_comments', array( __CLASS__, 'wp_count_comments' ), 10, 2 );

		// Delete comments count cache whenever there is a new comment or a comment status changes
		add_action( 'wp_insert_comment', array( __CLASS__, 'delete_comments_count_cache' ) );
		add_action( 'wp_set_comment_status', array( __CLASS__, 'delete_comments_count_cache' ) );

		// Support avatars for `review` comment type
		add_filter( 'get_avatar_comment_types', array( __CLASS__, 'add_avatar_for_review_comment_type' ) );

		// Review of verified purchase
		add_action( 'comment_post', array( __CLASS__, 'add_comment_purchase_verification' ) );
	}

	/**
	 * See if comments are open.
	 *
	 * @since  3.1.0
	 * @param  bool $open
	 * @param  int $post_id
	 * @return bool
	 */
	public static function comments_open( $open, $post_id ) {
		if ( 'product' === get_post_type( $post_id ) && ! post_type_supports( 'product', 'comments' ) ) {
			$open = false;
		}
		return $open;
	}

	/**
	 * Exclude order comments from queries and RSS.
	 *
	 * This code should exclude shop_order comments from queries. Some queries (like the recent comments widget on the dashboard) are hardcoded.
	 * and are not filtered, however, the code current_user_can( 'read_post', $comment->comment_post_ID ) should keep them safe since only admin and.
	 * shop managers can view orders anyway.
	 *
	 * The frontend view order pages get around this filter by using remove_filter('comments_clauses', array( 'WC_Comments' ,'exclude_order_comments'), 10, 1 );
	 * @param  array $clauses
	 * @return array
	 */
	public static function exclude_order_comments( $clauses ) {
		$clauses['where'] .= ( $clauses['where'] ? ' AND ' : '' ) . " comment_type != 'order_note' ";
		return $clauses;
	}

	/**
	 * @deprecated 3.1
	 */
	public static function exclude_order_comments_from_feed_join( $join ) {
		wc_deprecated_function( 'WC_Comments::exclude_order_comments_from_feed_join', '3.1' );
	}

	/**
	 * Exclude order comments from queries and RSS.
	 *
	 * @param  string $where
	 * @return string
	 */
	public static function exclude_order_comments_from_feed_where( $where ) {
		return $where . ( $where ? ' AND ' : '' ) . " comment_type != 'order_note' ";
	}

	/**
	 * Exclude webhook comments from queries and RSS.
	 * @since  2.2
	 * @param  array $clauses
	 * @return array
	 */
	public static function exclude_webhook_comments( $clauses ) {
		$clauses['where'] .= ( $clauses['where'] ? ' AND ' : '' ) . " comment_type != 'webhook_delivery' ";
		return $clauses;
	}

	/**
	 * @deprecated 3.1
	 */
	public static function exclude_webhook_comments_from_feed_join( $join ) {
		wc_deprecated_function( 'WC_Comments::exclude_webhook_comments_from_feed_join', '3.1' );
	}

	/**
	 * Exclude webhook comments from queries and RSS.
	 * @since  2.1
	 * @param  string $where
	 * @return string
	 */
	public static function exclude_webhook_comments_from_feed_where( $where ) {
		return $where . ( $where ? ' AND ' : '' ) . " comment_type != 'webhook_delivery' ";
	}

	/**
	 * Validate the comment ratings.
	 *
	 * @param  array $comment_data
	 * @return array
	 */
	public static function check_comment_rating( $comment_data ) {
		// If posting a comment (not trackback etc) and not logged in
		if ( ! is_admin() && isset( $_POST['comment_post_ID'], $_POST['rating'], $comment_data['comment_type'] ) && 'product' === get_post_type( $_POST['comment_post_ID'] ) && empty( $_POST['rating'] ) && '' === $comment_data['comment_type'] && 'yes' === get_option( 'woocommerce_enable_review_rating' ) && 'yes' === get_option( 'woocommerce_review_rating_required' ) ) {
			wp_die( __( 'Please rate the product.', 'woocommerce' ) );
			exit;
		}
		return $comment_data;
	}

	/**
	 * Rating field for comments.
	 * @param int $comment_id
	 */
	public static function add_comment_rating( $comment_id ) {
		if ( isset( $_POST['rating'] ) && 'product' === get_post_type( $_POST['comment_post_ID'] ) ) {
			if ( ! $_POST['rating'] || $_POST['rating'] > 5 || $_POST['rating'] < 0 ) {
				return;
			}
			add_comment_meta( $comment_id, 'rating', (int) esc_attr( $_POST['rating'] ), true );

			$post_id = isset( $_POST['comment_post_ID'] ) ? (int) $_POST['comment_post_ID'] : 0;
			if ( $post_id ) {
				self::clear_transients( $post_id );
			}
		}
	}

	/**
	 * Modify recipient of review email.
	 * @param array $emails
	 * @param int $comment_id
	 * @return array
	 */
	public static function comment_moderation_recipients( $emails, $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( $comment && 'product' === get_post_type( $comment->comment_post_ID ) ) {
			$emails = array( get_option( 'admin_email' ) );
		}

		return $emails;
	}

	/**
	 * Ensure product average rating and review count is kept up to date.
	 * @param int $post_id
	 */
	public static function clear_transients( $post_id ) {

		if ( 'product' === get_post_type( $post_id ) ) {
			$product = wc_get_product( $post_id );
			self::get_rating_counts_for_product( $product );
			self::get_average_rating_for_product( $product );
			self::get_review_count_for_product( $product );
		}
	}

	/**
	 * Delete comments count cache whenever there is
	 * new comment or the status of a comment changes. Cache
	 * will be regenerated next time WC_Comments::wp_count_comments()
	 * is called.
	 *
	 * @return void
	 */
	public static function delete_comments_count_cache() {
		delete_transient( 'wc_count_comments' );
	}

	/**
	 * Remove order notes and webhook delivery logs from wp_count_comments().
	 *
	 * @since  2.2
	 * @param  object $stats   Comment stats.
	 * @param  int    $post_id Post ID.
	 * @return object
	 */
	public static function wp_count_comments( $stats, $post_id ) {
		global $wpdb;

		if ( 0 === $post_id ) {
			$stats = get_transient( 'wc_count_comments' );

			if ( ! $stats ) {
				$stats = array();

				$count = $wpdb->get_results( "
					SELECT comment_approved, COUNT(*) AS num_comments
					FROM {$wpdb->comments}
					WHERE comment_type NOT IN ('order_note', 'webhook_delivery')
					GROUP BY comment_approved
				", ARRAY_A );

				$total = 0;
				$approved = array(
					'0'            => 'moderated',
					'1'            => 'approved',
					'spam'         => 'spam',
					'trash'        => 'trash',
					'post-trashed' => 'post-trashed',
				);

				foreach ( (array) $count as $row ) {
					// Don't count post-trashed toward totals.
					if ( 'post-trashed' !== $row['comment_approved'] && 'trash' !== $row['comment_approved'] ) {
						$total += $row['num_comments'];
					}
					if ( isset( $approved[ $row['comment_approved'] ] ) ) {
						$stats[ $approved[ $row['comment_approved'] ] ] = $row['num_comments'];
					}
				}

				$stats['total_comments'] = $total;
				$stats['all'] = $total;
				foreach ( $approved as $key ) {
					if ( empty( $stats[ $key ] ) ) {
						$stats[ $key ] = 0;
					}
				}

				$stats = (object) $stats;
				set_transient( 'wc_count_comments', $stats );
			}
		}

		return $stats;
	}

	/**
	 * Make sure WP displays avatars for comments with the `review` type.
	 * @since  2.3
	 * @param  array $comment_types
	 * @return array
	 */
	public static function add_avatar_for_review_comment_type( $comment_types ) {
		return array_merge( $comment_types, array( 'review' ) );
	}

	/**
	 * Determine if a review is from a verified owner at submission.
	 * @param int $comment_id
	 * @return bool
	 */
	public static function add_comment_purchase_verification( $comment_id ) {
		$comment  = get_comment( $comment_id );
		$verified = false;
		if ( 'product' === get_post_type( $comment->comment_post_ID ) ) {
			$verified = wc_customer_bought_product( $comment->comment_author_email, $comment->user_id, $comment->comment_post_ID );
			add_comment_meta( $comment_id, 'verified', (int) $verified, true );
		}
		return $verified;
	}

	/**
	 * Get product rating for a product. Please note this is not cached.
	 *
	 * @since 3.0.0
	 * @param WC_Product $product
	 * @return float
	 */
	public static function get_average_rating_for_product( &$product ) {
		global $wpdb;

		$count = $product->get_rating_count();

		if ( $count ) {
			$ratings = $wpdb->get_var( $wpdb->prepare("
				SELECT SUM(meta_value) FROM $wpdb->commentmeta
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID = %d
				AND comment_approved = '1'
				AND meta_value > 0
			", $product->get_id() ) );
			$average = number_format( $ratings / $count, 2, '.', '' );
		} else {
			$average = 0;
		}

		$product->set_average_rating( $average );

		$data_store = $product->get_data_store();
		$data_store->update_average_rating( $product );

		return $average;
	}

	/**
	 * Get product review count for a product (not replies). Please note this is not cached.
	 *
	 * @since 3.0.0
	 * @param WC_Product $product
	 * @return int
	 */
	public static function get_review_count_for_product( &$product ) {
		global $wpdb;

		$count = $wpdb->get_var( $wpdb->prepare("
			SELECT COUNT(*) FROM $wpdb->comments
			WHERE comment_parent = 0
			AND comment_post_ID = %d
			AND comment_approved = '1'
		", $product->get_id() ) );

		$product->set_review_count( $count );

		$data_store = $product->get_data_store();
		$data_store->update_review_count( $product );

		return $count;
	}

	/**
	 * Get product rating count for a product. Please note this is not cached.
	 *
	 * @since 3.0.0
	 * @param WC_Product $product
	 * @return array of integers
	 */
	public static function get_rating_counts_for_product( &$product ) {
		global $wpdb;

		$counts     = array();
		$raw_counts = $wpdb->get_results( $wpdb->prepare( "
			SELECT meta_value, COUNT( * ) as meta_value_count FROM $wpdb->commentmeta
			LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
			WHERE meta_key = 'rating'
			AND comment_post_ID = %d
			AND comment_approved = '1'
			AND meta_value > 0
			GROUP BY meta_value
		", $product->get_id() ) );

		foreach ( $raw_counts as $count ) {
			$counts[ $count->meta_value ] = absint( $count->meta_value_count );
		}

		$product->set_rating_counts( $counts );

		$data_store = $product->get_data_store();
		$data_store->update_rating_counts( $product );

		return $counts;
	}
}

WC_Comments::init();
