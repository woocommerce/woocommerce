<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Comments
 *
 * Handle comments (reviews and order notes)
 *
 * @class 		WC_Post_types
 * @version		2.2.0
 * @package		WooCommerce/Classes/Products
 * @category	Class
 * @author 		WooThemes
 */
class WC_Comments {

	/**
	 * Hook in methods
	 */
	public static function init() {
		// Rating posts
		add_filter( 'preprocess_comment', array( __CLASS__, 'check_comment_rating' ), 0 );
		add_action( 'comment_post', array( __CLASS__, 'add_comment_rating' ), 1 );

		// clear transients
		add_action( 'wp_update_comment_count', array( __CLASS__, 'clear_transients' ) );

		// Secure order notes
		add_filter( 'comments_clauses', array( __CLASS__, 'exclude_order_comments' ), 10, 1 );
		add_action( 'comment_feed_join', array( __CLASS__, 'exclude_order_comments_from_feed_join' ) );
		add_action( 'comment_feed_where', array( __CLASS__, 'exclude_order_comments_from_feed_where' ) );

		// Secure webhook comments
		add_filter( 'comments_clauses', array( __CLASS__, 'exclude_webhook_comments' ), 10, 1 );
		add_action( 'comment_feed_join', array( __CLASS__, 'exclude_webhook_comments_from_feed_join' ) );
		add_action( 'comment_feed_where', array( __CLASS__, 'exclude_webhook_comments_from_feed_where' ) );

		// Count comments
		add_filter( 'wp_count_comments', array( __CLASS__, 'wp_count_comments' ), 10, 2 );
	}

	/**
	 * Exclude order comments from queries and RSS
	 *
	 * This code should exclude shop_order comments from queries. Some queries (like the recent comments widget on the dashboard) are hardcoded
	 * and are not filtered, however, the code current_user_can( 'read_post', $comment->comment_post_ID ) should keep them safe since only admin and
	 * shop managers can view orders anyway.
	 *
	 * The frontend view order pages get around this filter by using remove_filter('comments_clauses', array( 'WC_Comments' ,'exclude_order_comments'), 10, 1 );
	 *
	 * @param array $clauses
	 * @return array
	 */
	public static function exclude_order_comments( $clauses ) {
		global $wpdb, $typenow;

		if ( is_admin() && in_array( $typenow, wc_get_order_types() ) && current_user_can( 'manage_woocommerce' ) ) {
			return $clauses; // Don't hide when viewing orders in admin
		}

		if ( ! $clauses['join'] ) {
			$clauses['join'] = '';
		}

		if ( ! strstr( $clauses['join'], "JOIN $wpdb->posts" ) ) {
			$clauses['join'] .= " LEFT JOIN $wpdb->posts ON comment_post_ID = $wpdb->posts.ID ";
		}

		if ( $clauses['where'] ) {
			$clauses['where'] .= ' AND ';
		}

		$clauses['where'] .= " $wpdb->posts.post_type NOT IN ('" . implode( "','", wc_get_order_types() ) . "') ";

		return $clauses;
	}

	/**
	 * Exclude order comments from queries and RSS
	 *
	 * @param string $join
	 * @return string
	 */
	public static function exclude_order_comments_from_feed_join( $join ) {
		global $wpdb;

		if ( ! strstr( $join, $wpdb->posts ) ) {
			$join = " LEFT JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID ";
		}

		return $join;
	}

	/**
	 * Exclude order comments from queries and RSS
	 *
	 * @param string $where
	 * @return string
	 */
	public static function exclude_order_comments_from_feed_where( $where ) {
		global $wpdb;

		if ( $where ) {
			$where .= ' AND ';
		}

		$where .= " $wpdb->posts.post_type NOT IN ('" . implode( "','", wc_get_order_types() ) . "') ";

		return $where;
	}

	/**
	 * Exclude webhook comments from queries and RSS
	 *
	 * @since 2.2
	 * @param array $clauses
	 * @return array
	 */
	public static function exclude_webhook_comments( $clauses ) {
		global $wpdb;

		if ( ! $clauses['join'] ) {
			$clauses['join'] = '';
		}

		if ( ! strstr( $clauses['join'], "JOIN $wpdb->posts" ) ) {
			$clauses['join'] .= " LEFT JOIN $wpdb->posts ON comment_post_ID = $wpdb->posts.ID ";
		}

		if ( $clauses['where'] ) {
			$clauses['where'] .= ' AND ';
		}

		$clauses['where'] .= " $wpdb->posts.post_type <> 'shop_webhook' ";

		return $clauses;
	}

	/**
	 * Exclude webhook comments from queries and RSS
	 *
	 * @since 2.2
	 * @param string $join
	 * @return string
	 */
	public static function exclude_webhook_comments_from_feed_join( $join ) {
		global $wpdb;

		if ( ! strstr( $join, $wpdb->posts ) ) {
			$join = " LEFT JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID ";
		}

		return $join;
	}

	/**
	 * Exclude webhook comments from queries and RSS
	 *
	 * @since 2.1
	 * @param string $where
	 * @return string
	 */
	public static function exclude_webhook_comments_from_feed_where( $where ) {
		global $wpdb;

		if ( $where ) {
			$where .= ' AND ';
		}

		$where .= " $wpdb->posts.post_type <> 'shop_webhook' ";

		return $where;
	}

	/**
	 * Validate the comment ratings.
	 *
	 * @param array $comment_data
	 * @return array
	 */
	public static function check_comment_rating( $comment_data ) {
		// If posting a comment (not trackback etc) and not logged in
		if ( isset( $_POST['rating'] ) && empty( $_POST['rating'] ) && '' === $comment_data['comment_type'] && 'yes' === get_option( 'woocommerce_review_rating_required' ) ) {
			wp_die( __( 'Please rate the product.', 'woocommerce' ) );
			exit;
		}
		return $comment_data;
	}

	/**
	 * Rating field for comments.
	 *
	 * @param mixed $comment_id
	 */
	public static function add_comment_rating( $comment_id ) {
		if ( isset( $_POST['rating'] ) ) {
			if ( ! $_POST['rating'] || $_POST['rating'] > 5 || $_POST['rating'] < 0 ) {
				return;
			}

			add_comment_meta( $comment_id, 'rating', (int) esc_attr( $_POST['rating'] ), true );
		}
	}

	/**
	 * Clear transients for a review.
	 *
	 * @param mixed $post_id
	 */
	public static function clear_transients( $post_id ) {
		$post_id = absint( $post_id );
		delete_transient( 'wc_average_rating_' . $post_id );
		delete_transient( 'wc_rating_count_' . $post_id );
		delete_transient( 'wc_rating_count_' . $post_id . '_1' );
		delete_transient( 'wc_rating_count_' . $post_id . '_2' );
		delete_transient( 'wc_rating_count_' . $post_id . '_3' );
		delete_transient( 'wc_rating_count_' . $post_id . '_4' );
		delete_transient( 'wc_rating_count_' . $post_id . '_5' );
	}

	/**
	 * Remove order notes from wp_count_comments()
	 *
	 * @since 2.2
	 * @param object $stats
	 * @param int $post_id
	 * @return object
	 */
	public static function wp_count_comments( $stats, $post_id ) {
		global $wpdb;

		if ( 0 === $post_id ) {

			$count = wp_cache_get( 'comments-0', 'counts' );
			if ( false !== $count ) {
				return $count;
			}

			$count = $wpdb->get_results( "SELECT comment_approved, COUNT( * ) AS num_comments FROM {$wpdb->comments} WHERE comment_type != 'order_note' GROUP BY comment_approved", ARRAY_A );

			$total = 0;
			$approved = array( '0' => 'moderated', '1' => 'approved', 'spam' => 'spam', 'trash' => 'trash', 'post-trashed' => 'post-trashed' );

			foreach ( (array) $count as $row ) {
				// Don't count post-trashed toward totals
				if ( 'post-trashed' != $row['comment_approved'] && 'trash' != $row['comment_approved'] ) {
					$total += $row['num_comments'];
				}
				if ( isset( $approved[ $row['comment_approved'] ] ) ) {
					$stats[ $approved[ $row['comment_approved'] ] ] = $row['num_comments'];
				}
			}

			$stats['total_comments'] = $total;
			foreach ( $approved as $key ) {
				if ( empty( $stats[ $key ] ) ) {
					$stats[ $key ] = 0;
				}
			}

			$stats = (object) $stats;
			wp_cache_set( 'comments-0', $stats, 'counts' );
		}

		return $stats;
	}
}

WC_Comments::init();
