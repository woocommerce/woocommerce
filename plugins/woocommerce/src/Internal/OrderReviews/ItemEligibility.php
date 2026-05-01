<?php
/**
 * ItemEligibility class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderReviews;

use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WP_Comment;

/**
 * Decides how each Review Order line item should be rendered.
 *
 * The Review Order template asks this helper for each line item and
 * dispatches to the appropriate partial:
 *
 * - `form`     — render the editable form row (`customer-review-order-row.php`).
 * - `reviewed` — render the locked "Reviewed" row showing the existing review.
 * - `skip`     — render nothing (e.g. the product has reviews disabled).
 *
 * @internal Just for internal use.
 *
 * @since 10.8.0
 */
class ItemEligibility {

	public const STATUS_FORM     = 'form';
	public const STATUS_REVIEWED = 'reviewed';
	public const STATUS_SKIP     = 'skip';

	/**
	 * Per-request cache for the "did this email review this product" lookup.
	 * Keyed by `email|product_id`; value is `WP_Comment|null`.
	 *
	 * @var array<string, ?WP_Comment>
	 */
	private static array $review_cache = array();

	/**
	 * Pre-fill the per-request review cache for a set of items in a single query.
	 *
	 * Call this from the template before iterating items so each subsequent
	 * `decide()` call hits the cache instead of running its own
	 * `get_comments()` query (avoids the N+1 pattern on multi-item orders).
	 *
	 * @param iterable<WC_Order_Item_Product|mixed> $items Order line items.
	 * @param WC_Order                              $order Order being reviewed.
	 */
	public static function preload_for_items( iterable $items, WC_Order $order ): void {
		$email = $order->get_billing_email();
		if ( '' === $email ) {
			return;
		}

		$product_ids = array();
		foreach ( $items as $item ) {
			if ( $item instanceof WC_Order_Item_Product ) {
				$pid = $item->get_product_id();
				if ( $pid ) {
					$product_ids[ $pid ] = $pid;
				}
			}
		}

		if ( empty( $product_ids ) ) {
			return;
		}

		// Approved + the customer's own pending review only; spam/trash
		// shouldn't count and re-block a legitimate new submission.
		$comments = get_comments(
			array(
				'post__in'           => array_values( $product_ids ),
				'author_email'       => $email,
				'type'               => 'review',
				'status'             => 'approve',
				'include_unapproved' => array( $email ),
				'orderby'            => 'comment_date_gmt',
				'order'              => 'DESC',
			)
		);

		// Default every product id to null so decide() doesn't re-query.
		foreach ( $product_ids as $pid ) {
			self::$review_cache[ $email . '|' . $pid ] = null;
		}

		if ( is_array( $comments ) ) {
			foreach ( $comments as $comment ) {
				if ( ! $comment instanceof WP_Comment ) {
					continue;
				}
				$cache_key = $email . '|' . (int) $comment->comment_post_ID;
				if ( null === ( self::$review_cache[ $cache_key ] ?? null ) ) {
					self::$review_cache[ $cache_key ] = $comment;
				}
			}
		}
	}

	/**
	 * Reset the per-request cache. Test helper.
	 *
	 * @internal
	 */
	public static function reset_cache(): void {
		self::$review_cache = array();
	}

	/**
	 * Decide how an order line item should render on the Review Order page.
	 *
	 * Returns one of the STATUS_* constants plus the matched comment (when
	 * STATUS_REVIEWED) and the product id.
	 *
	 * @param WC_Order_Item_Product $item  Order line item.
	 * @param WC_Order              $order Order being reviewed.
	 * @return array{status:string, comment:?WP_Comment, product_id:int}
	 */
	public static function decide( WC_Order_Item_Product $item, WC_Order $order ): array {
		$product_id = $item->get_product_id();
		$result     = array(
			'status'     => self::STATUS_FORM,
			'comment'    => null,
			'product_id' => $product_id,
		);

		if ( ! $product_id || ! comments_open( $product_id ) ) {
			$result['status'] = self::STATUS_SKIP;
			return $result;
		}

		$customer_email = $order->get_billing_email();
		$existing       = self::find_existing_review( $product_id, $customer_email );

		/**
		 * Filter the "already reviewed" decision for an item on the Review Order page.
		 *
		 * Return true to lock the row to the existing-review variant; return
		 * false to keep the form row even if a matching comment exists.
		 *
		 * @since 10.8.0
		 *
		 * @param bool     $already_reviewed Default decision based on existing comments.
		 * @param int      $product_id       Product id being inspected.
		 * @param WC_Order $order            The order being reviewed.
		 * @param string   $customer_email   Billing email used to match existing reviews.
		 */
		$already_reviewed = (bool) apply_filters(
			'woocommerce_review_order_item_already_reviewed',
			null !== $existing,
			$product_id,
			$order,
			$customer_email
		);

		if ( $already_reviewed ) {
			$result['status']  = self::STATUS_REVIEWED;
			$result['comment'] = $existing;
		}

		return $result;
	}

	/**
	 * Drop fully-refunded line items from the eligible-items list.
	 *
	 * Default callback wired onto `woocommerce_review_order_eligible_items`
	 * so the page never shows a row for a product the customer no longer
	 * owns. A line item is considered fully refunded when the absolute
	 * refunded quantity equals the item's quantity.
	 *
	 * @param WC_Order_Item[] $items Order line items.
	 * @param WC_Order        $order Order being reviewed.
	 * @return WC_Order_Item[]
	 */
	public static function exclude_fully_refunded_items( array $items, WC_Order $order ): array {
		$filtered = array();
		foreach ( $items as $key => $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				$filtered[ $key ] = $item;
				continue;
			}

			$refunded_qty = (int) abs( $order->get_qty_refunded_for_item( $item->get_id() ) );
			$ordered_qty  = (int) $item->get_quantity();

			if ( $ordered_qty > 0 && $refunded_qty >= $ordered_qty ) {
				continue;
			}

			$filtered[ $key ] = $item;
		}

		return $filtered;
	}

	/**
	 * Look up the customer's most recent review for a product, by email.
	 *
	 * @param int    $product_id Product id.
	 * @param string $email      Customer email (from the order).
	 * @return WP_Comment|null
	 */
	private static function find_existing_review( int $product_id, string $email ): ?WP_Comment {
		if ( '' === $email ) {
			return null;
		}

		$cache_key = $email . '|' . $product_id;
		if ( array_key_exists( $cache_key, self::$review_cache ) ) {
			return self::$review_cache[ $cache_key ];
		}

		// Approved + the customer's own pending review only; spam/trash
		// shouldn't count and re-block a legitimate new submission.
		$comments = get_comments(
			array(
				'post_id'            => $product_id,
				'author_email'       => $email,
				'type'               => 'review',
				'status'             => 'approve',
				'include_unapproved' => array( $email ),
				'number'             => 1,
				'orderby'            => 'comment_date_gmt',
				'order'              => 'DESC',
			)
		);

		if ( ! is_array( $comments ) || empty( $comments ) ) {
			self::$review_cache[ $cache_key ] = null;
			return null;
		}

		$first = reset( $comments );
		$found = $first instanceof WP_Comment ? $first : null;

		self::$review_cache[ $cache_key ] = $found;
		return $found;
	}
}
