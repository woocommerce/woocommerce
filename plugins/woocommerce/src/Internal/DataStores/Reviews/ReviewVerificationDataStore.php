<?php
/**
 * ReviewVerificationDataStore class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DataStores\Reviews;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the "verified owner" status of product reviews in bulk.
 *
 * The per-review badge check (wc_review_is_from_verified_owner()) runs one purchase-history query
 * per review missing its 'verified' meta — an N+1 that scales with review count. This resolves every
 * unverified review of a product in a single query and persists the same 'verified' meta, so the
 * per-review checks become O(1) reads with results identical to the per-review path.
 */
class ReviewVerificationDataStore {

	/**
	 * Whether the bulk verification path applies to this product.
	 *
	 * @param int $product_id The product (post) ID.
	 * @return bool
	 */
	public function is_enabled( int $product_id ): bool {
		/**
		 * Filters whether to resolve the "verified owner" status for a product's reviews in bulk.
		 * Return false to keep the per-review path only.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $enabled    Whether to resolve verified-owner status in bulk.
		 * @param int  $product_id The product (post) ID whose reviews are being resolved.
		 */
		if ( ! apply_filters( 'woocommerce_prime_review_verification_meta', true, $product_id ) ) {
			return false;
		}

		if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
			return false;
		}

		// An extension short-circuiting wc_customer_bought_product() owns the result; the bulk query
		// would bypass it and persist a different answer, so defer to the per-review path when hooked.
		if ( has_filter( 'woocommerce_pre_customer_bought_product' ) ) {
			return false;
		}

		// The lookup-tables path has its own caching/versioning; defer to the per-review path there.
		return ! has_filter( 'woocommerce_customer_bought_product_use_lookup_tables' );
	}

	/**
	 * Resolve and persist "verified owner" status for a product's unverified reviews.
	 *
	 * @param int $product_id The product (post) ID to resolve.
	 * @param int $limit      Maximum reviews to resolve in this run (bounds the IN() list and memory).
	 * @return int Number of reviews fetched for this run — a full batch signals a caller to follow up.
	 */
	public function backfill( int $product_id, int $limit ): int {
		$product_id = absint( $product_id );
		$limit      = max( 1, $limit );
		if ( ! $this->is_enabled( $product_id ) ) {
			return 0;
		}

		$comments = array_filter(
			(array) get_comments(
				array(
					'post_id'    => $product_id,
					'type'       => 'review',
					'status'     => 'approve',
					'number'     => $limit,
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- One-off off-hours backfill.
						array(
							'key'     => 'verified',
							'compare' => 'NOT EXISTS',
						),
					),
				)
			),
			static fn( $comment ) => $comment instanceof \WP_Comment
		);

		if ( empty( $comments ) ) {
			return 0;
		}

		$this->resolve_and_persist( $comments, $product_id );

		// Report the fetched batch size (not the resolved subset) so a full batch reliably triggers a
		// follow-up even if some rows were already primed between the fetch and the meta read.
		return count( $comments );
	}

	/**
	 * Resolve the given review comments in one query and persist their 'verified' meta.
	 *
	 * @param \WP_Comment[] $comments   Review comments to resolve.
	 * @param int           $product_id The product (post) ID being reviewed.
	 * @return void
	 */
	private function resolve_and_persist( array $comments, int $product_id ): void {
		$pending  = array();
		$user_ids = array();
		$emails   = array();

		foreach ( $comments as $comment ) {
			if ( 'review' !== $comment->comment_type ) {
				continue;
			}
			$comment_id = (int) $comment->comment_ID;
			if ( '' !== get_comment_meta( $comment_id, 'verified', true ) ) {
				continue;
			}

			// Mirror add_comment_purchase_verification(): prefer the user id over the email.
			$user_id = absint( $comment->user_id );
			$email   = $user_id ? '' : $comment->comment_author_email;

			// Mirror wc_customer_bought_product(): resolve a guest email to a user id.
			$resolved_id = $user_id;
			if ( ! $resolved_id && $email && is_email( $email ) ) {
				$user        = get_user_by( 'email', $email );
				$resolved_id = $user ? absint( $user->ID ) : 0;
			}

			// Lowercase so the PHP match below is case-insensitive, matching the DB collation the
			// per-review wc_customer_bought_product() relies on (else a case difference between the
			// review email and the order billing email would persist a wrong "not verified").
			$email = ( $email && is_email( $email ) ) ? strtolower( $email ) : '';

			$pending[ $comment_id ] = array(
				'user_id' => $resolved_id,
				'email'   => $email,
			);

			if ( $resolved_id ) {
				$user_ids[ $resolved_id ] = $resolved_id;
			}
			if ( '' !== $email ) {
				$emails[ $email ] = $email;
			}
		}

		if ( empty( $pending ) ) {
			return;
		}

		$bought = $this->query_bought_identities( $product_id, $user_ids, $emails );

		foreach ( $pending as $comment_id => $identity ) {
			$verified = ( $identity['user_id'] && isset( $bought['customer_ids'][ $identity['user_id'] ] ) )
				|| ( '' !== $identity['email'] && isset( $bought['emails'][ $identity['email'] ] ) );

			// Persist the same way the per-review path does, so later reads are O(1).
			add_comment_meta( (int) $comment_id, 'verified', (int) $verified, true );
		}
	}

	/**
	 * Resolve, in a single query, which of the given reviewer identities have a paid order
	 * containing the product. Mirrors the non-lookup-table branches of
	 * wc_customer_bought_product() (both HPOS and legacy post-based storage).
	 *
	 * @since 11.0.0
	 *
	 * @param int      $product_id The product (post) ID being reviewed.
	 * @param int[]    $user_ids   Candidate customer user ids.
	 * @param string[] $emails     Candidate billing emails.
	 * @return array{customer_ids: array<int,int>, emails: array<string,string>} Matched identities, keyed for O(1) lookup.
	 */
	private function query_bought_identities( int $product_id, array $user_ids, array $emails ): array {
		global $wpdb;

		$matched = array(
			'customer_ids' => array(),
			'emails'       => array(),
		);

		if ( empty( $user_ids ) && empty( $emails ) ) {
			return $matched;
		}

		$escape = static function ( $value ): string {
			$escaped = esc_sql( (string) $value );
			return is_string( $escaped ) ? $escaped : '';
		};

		$statuses = array_map( $escape, wc_get_is_paid_statuses() );
		$user_ids = array_map( 'absint', $user_ids );
		$emails   = array_map( $escape, $emails );

		$emails_in = empty( $emails ) ? '' : "'" . implode( "','", $emails ) . "'";
		$ids_in    = empty( $user_ids ) ? '' : implode( ',', $user_ids );

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$statuses    = array_map( static fn( $status ) => "wc-$status", $statuses );
			$order_table = OrdersTableDataStore::get_orders_table_name();

			$identity_clause = array();
			if ( '' !== $ids_in ) {
				$identity_clause[] = "orders.customer_id IN ( $ids_in )";
			}
			if ( '' !== $emails_in ) {
				$identity_clause[] = "orders.billing_email IN ( $emails_in )";
			}
			$identity_clause = implode( ' OR ', $identity_clause );

			$sql = "SELECT DISTINCT orders.customer_id AS customer_id, orders.billing_email AS billing_email
				FROM $order_table AS orders
					INNER JOIN {$wpdb->prefix}woocommerce_order_items AS items ON orders.id = items.order_id
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS itemmeta ON items.order_item_id = itemmeta.order_item_id
				WHERE orders.status IN ( '" . implode( "','", $statuses ) . "' )
					AND itemmeta.meta_key   IN ( '_product_id', '_variation_id' )
					AND itemmeta.meta_value = '" . absint( $product_id ) . "'
					AND ( $identity_clause )";
		} else {
			$identity_clause = array();
			if ( '' !== $ids_in ) {
				$identity_clause[] = "pm_customer.meta_value IN ( $ids_in )";
			}
			if ( '' !== $emails_in ) {
				$identity_clause[] = "pm_email.meta_value IN ( $emails_in )";
			}
			$identity_clause = implode( ' OR ', $identity_clause );

			$sql = "SELECT DISTINCT pm_customer.meta_value AS customer_id, pm_email.meta_value AS billing_email
				FROM {$wpdb->posts} AS posts
					INNER JOIN {$wpdb->prefix}woocommerce_order_items AS items ON posts.ID = items.order_id
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS itemmeta ON items.order_item_id = itemmeta.order_item_id
					LEFT JOIN {$wpdb->postmeta} AS pm_customer ON posts.ID = pm_customer.post_id AND pm_customer.meta_key = '_customer_user'
					LEFT JOIN {$wpdb->postmeta} AS pm_email ON posts.ID = pm_email.post_id AND pm_email.meta_key = '_billing_email'
				WHERE posts.post_type   = 'shop_order'
					AND posts.post_status IN ( 'wc-" . implode( "','wc-", $statuses ) . "' )
					AND itemmeta.meta_key   IN ( '_product_id', '_variation_id' )
					AND itemmeta.meta_value = '" . absint( $product_id ) . "'
					AND ( $identity_clause )";
		}

		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		foreach ( (array) $rows as $row ) {
			$customer_id = absint( $row['customer_id'] );
			if ( $customer_id ) {
				$matched['customer_ids'][ $customer_id ] = $customer_id;
			}
			$billing_email = strtolower( (string) $row['billing_email'] );
			if ( '' !== $billing_email ) {
				$matched['emails'][ $billing_email ] = $billing_email;
			}
		}

		return $matched;
	}
}
