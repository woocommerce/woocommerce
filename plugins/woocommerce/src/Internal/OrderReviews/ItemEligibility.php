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
 * Decides how each Review Order line item should be rendered and supplies
 * any pre-fill data for the row form.
 *
 * Two outcomes for a row:
 *
 * - `form` — render the editable form row (`customer-review-order-row.php`),
 *   optionally pre-filled with the rating + text the customer has already
 *   submitted for this product **on this order**.
 * - `skip` — render nothing (e.g. the product has reviews disabled).
 *
 * Reviews left for a *different* order are not surfaced here: a customer who
 * buys the same product again gets a fresh form row, because their experience
 * the second time around may be different from the first.
 *
 * @internal Just for internal use.
 *
 * @since 10.8.0
 */
class ItemEligibility {

	/**
	 * Render the editable form row.
	 *
	 * @since 10.8.0
	 */
	public const STATUS_FORM = 'form';

	/**
	 * Render nothing (e.g. comments closed on the product).
	 *
	 * @since 10.8.0
	 */
	public const STATUS_SKIP = 'skip';

	/**
	 * Commentmeta key storing the order this review was submitted for.
	 *
	 * @since 10.8.0
	 */
	public const ORDER_META_KEY = '_review_order_id';

	/**
	 * Commentmeta key storing the variation id this review was submitted for.
	 *
	 * Always present on reviews written through the Review Order page. Simple
	 * products store `0`. Lets variable-product orders distinguish "Small" from
	 * "Medium" rows that share a parent product.
	 *
	 * @since 10.9.0
	 */
	public const VARIATION_META_KEY = '_review_variation_id';

	/**
	 * Commentmeta key storing a snapshot of the variation's attribute summary
	 * (e.g. `"Size: Small, Colour: Red"`) at the moment the review was written.
	 *
	 * Captured at write time so historical reviews stay readable even if the
	 * variation is later retired or its attribute taxonomies change.
	 *
	 * @since 10.9.0
	 */
	public const VARIATION_SUMMARY_META_KEY = '_review_variation_summary';

	/**
	 * Per-request cache for the "did this email review this product (and this
	 * variation) on this order" lookup, keyed by
	 * `order_id|product_id|variation_id|email`. Value is a `WP_Comment` when
	 * one matches, or `null` when the slot has been checked and nothing
	 * matches (so a second call doesn't re-query).
	 *
	 * @var array<string, ?WP_Comment>
	 */
	private static array $review_cache = array();

	/**
	 * Set of `order_id|email` pairs that have already been bulk-preloaded in
	 * this request, so a repeated `preload_for_items()` call (e.g. once from
	 * the Endpoint and once from the page template) doesn't re-run the query.
	 *
	 * @var array<string, true>
	 */
	private static array $preloaded = array();

	/**
	 * Register the default filter callbacks the OrderReviews feature ships with.
	 *
	 * Auto-called by the WC dependency container after instantiation.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_filter(
			'woocommerce_review_order_eligible_items',
			array( self::class, 'exclude_fully_refunded_items' ),
			10,
			2
		);

		// Surface the variation summary captured at submission time on the
		// single-product Reviews tab, so a review for "Size: Small" doesn't
		// render indistinguishably from one for "Size: Medium" on the parent
		// product page.
		add_action(
			'woocommerce_review_before_comment_text',
			array( self::class, 'render_variation_summary' )
		);
	}

	/**
	 * Echo the variation summary snapshot for a review comment, when present.
	 *
	 * Wired onto `woocommerce_review_before_comment_text` so the snapshot
	 * stored in `_review_variation_summary` (set by the Customer Review
	 * Request submission flow) appears immediately above the review body on
	 * the single-product Reviews tab. Comments without the meta render
	 * unchanged.
	 *
	 * @since 10.9.0
	 *
	 * @param \WP_Comment $comment Review comment being rendered.
	 */
	public static function render_variation_summary( \WP_Comment $comment ): void {
		$summary = (string) get_comment_meta( (int) $comment->comment_ID, self::VARIATION_SUMMARY_META_KEY, true );
		if ( '' === $summary ) {
			return;
		}

		echo '<p class="woocommerce-review__variation-summary">' . esc_html( $summary ) . '</p>';
	}

	/**
	 * Pre-fill the per-request review cache for a set of items in one query.
	 *
	 * Call this from the template before iterating items so each subsequent
	 * `decide()` / `prefill_for_item()` call hits the cache instead of running
	 * its own `get_comments()` query.
	 *
	 * @since 10.8.0
	 *
	 * @param iterable<WC_Order_Item_Product|mixed> $items Order line items.
	 * @param WC_Order                              $order Order being reviewed.
	 */
	public static function preload_for_items( iterable $items, WC_Order $order ): void {
		$email    = $order->get_billing_email();
		$order_id = $order->get_id();
		if ( '' === $email || $order_id <= 0 ) {
			return;
		}

		$preload_key = $order_id . '|' . $email;
		if ( isset( self::$preloaded[ $preload_key ] ) ) {
			return;
		}

		$product_ids = array();
		$slots       = array();
		foreach ( $items as $item ) {
			if ( $item instanceof WC_Order_Item_Product ) {
				$pid = (int) $item->get_product_id();
				$vid = (int) $item->get_variation_id();
				if ( $pid > 0 ) {
					$product_ids[ $pid ]                                       = $pid;
					$slots[ self::cache_key( $order_id, $pid, $vid, $email ) ] = true;
				}
			}
		}

		if ( empty( $product_ids ) ) {
			return;
		}

		self::$preloaded[ $preload_key ] = true;

		// Default every (product, variation) slot to null so subsequent reads don't re-query.
		foreach ( $slots as $slot_key => $_ ) {
			self::$review_cache[ $slot_key ] = null;
		}

		// Scope to this order's reviews only: a customer who buys the same
		// product on a later order shouldn't see their old review here.
		$comments = get_comments(
			array(
				'post__in'           => array_values( $product_ids ),
				'author_email'       => $email,
				'type'               => 'review',
				'status'             => 'approve',
				'include_unapproved' => array( $email ),
				'orderby'            => 'comment_date_gmt',
				'order'              => 'DESC',
				'meta_query'         => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded by post__in + author_email.
					array(
						'key'   => self::ORDER_META_KEY,
						'value' => (string) $order_id,
					),
				),
			)
		);

		if ( is_array( $comments ) ) {
			foreach ( $comments as $comment ) {
				if ( ! $comment instanceof WP_Comment ) {
					continue;
				}
				$vid = (int) get_comment_meta( (int) $comment->comment_ID, self::VARIATION_META_KEY, true );
				$key = self::cache_key( $order_id, (int) $comment->comment_post_ID, $vid, $email );
				if ( isset( $slots[ $key ] ) && null === self::$review_cache[ $key ] ) {
					self::$review_cache[ $key ] = $comment;
				}
			}
		}
	}

	/**
	 * Reset the per-request cache. Test helper.
	 *
	 * @since 10.8.0
	 * @internal
	 */
	public static function reset_cache(): void {
		self::$review_cache = array();
		self::$preloaded    = array();
	}

	/**
	 * Decide how an order line item should render on the Review Order page.
	 *
	 * Returns one of the STATUS_* constants plus the matched comment (when
	 * one exists for this order) and the product id.
	 *
	 * @since 10.8.0
	 *
	 * @param WC_Order_Item_Product $item  Order line item.
	 * @param WC_Order              $order Order being reviewed.
	 * @return array{status:string, comment:?WP_Comment, product_id:int, variation_id:int}
	 */
	public static function decide( WC_Order_Item_Product $item, WC_Order $order ): array {
		$product_id   = (int) $item->get_product_id();
		$variation_id = (int) $item->get_variation_id();
		$result       = array(
			'status'       => self::STATUS_FORM,
			'comment'      => null,
			'product_id'   => $product_id,
			'variation_id' => $variation_id,
		);

		if ( $product_id <= 0 || ! comments_open( $product_id ) ) {
			$result['status'] = self::STATUS_SKIP;
			return $result;
		}

		$result['comment'] = self::find_existing_review( $product_id, $variation_id, $order );
		return $result;
	}

	/**
	 * Pre-fill payload for a line item: rating, text, and comment id.
	 *
	 * Returns zero/empty values when no review exists for this order's row,
	 * so callers can use it unconditionally.
	 *
	 * @since 10.8.0
	 *
	 * @param WC_Order_Item_Product $item  Order line item.
	 * @param WC_Order              $order Order being reviewed.
	 * @return array{rating:int, text:string, comment_id:int}
	 */
	public static function prefill_for_item( WC_Order_Item_Product $item, WC_Order $order ): array {
		$existing = self::find_existing_review(
			(int) $item->get_product_id(),
			(int) $item->get_variation_id(),
			$order
		);
		if ( ! $existing instanceof WP_Comment ) {
			return array(
				'rating'     => 0,
				'text'       => '',
				'comment_id' => 0,
			);
		}

		$rating = (int) get_comment_meta( (int) $existing->comment_ID, 'rating', true );
		if ( $rating < 0 || $rating > 5 ) {
			$rating = 0;
		}

		return array(
			'rating'     => $rating,
			'text'       => (string) $existing->comment_content,
			'comment_id' => (int) $existing->comment_ID,
		);
	}

	/**
	 * Render the variation's attribute summary as a single flat line.
	 *
	 * Used both at write time (snapshotted into `_review_variation_summary`)
	 * and at render time by the Review Order row template, so the two places
	 * always agree on what label the customer sees and what the comment
	 * stores. Restricted to actual variation attribute slugs so personalisation
	 * / add-on / engraving / gift-message meta from third-party plugins isn't
	 * accidentally folded into the public review snapshot. Returns an empty
	 * string for simple products or when the variation product can no longer
	 * be loaded to identify its attribute slugs.
	 *
	 * Keys in the line item meta are stored without the `attribute_` prefix
	 * (see `WC_Order_Item_Product::set_variation()`), so we strip the prefix
	 * from the live variation's attribute keys to match.
	 *
	 * @since 10.9.0
	 *
	 * @param WC_Order_Item_Product $item Order line item.
	 */
	public static function format_variation_summary( WC_Order_Item_Product $item ): string {
		$variation_id = (int) $item->get_variation_id();
		if ( $variation_id <= 0 ) {
			return '';
		}

		$variation = wc_get_product( $variation_id );
		if ( ! $variation instanceof \WC_Product_Variation ) {
			return '';
		}

		$attributes = array();
		foreach ( array_keys( (array) $variation->get_variation_attributes() ) as $attribute_key ) {
			$slug = str_replace( 'attribute_', '', (string) $attribute_key );
			if ( '' === $slug ) {
				continue;
			}
			$value = $item->get_meta( $slug, true );
			if ( '' === $value || null === $value ) {
				continue;
			}
			$attributes[ $slug ] = $value;
		}

		if ( empty( $attributes ) ) {
			return '';
		}

		return (string) wc_get_formatted_variation( $attributes, true );
	}

	/**
	 * Whether an order has at least one item the customer can still review.
	 *
	 * Walks the same eligible-items list and per-item decisions the page
	 * renders, so the answer matches what `customer-review-order.php` would
	 * show: items with `STATUS_SKIP` (reviews disabled on the product, or
	 * site-wide via `woocommerce_enable_reviews`) and items already reviewed
	 * on this order are excluded. Any remaining `STATUS_FORM` row without a
	 * matching review counts as actionable.
	 *
	 * Callers in the email pipeline use this to short-circuit scheduling and
	 * sending when the customer would otherwise land on the empty-state page.
	 *
	 * @since 10.9.0
	 *
	 * @param WC_Order $order Order being inspected.
	 * @return bool True when at least one item is still reviewable.
	 */
	public static function has_actionable_items( WC_Order $order ): bool {
		/**
		 * Filter the eligible items considered when deciding whether the
		 * Customer Review Request email should fire for an order.
		 *
		 * Same hook the page template, submission handler, and endpoint use,
		 * so all four entry points agree on the eligible-items set.
		 *
		 * @since 10.9.0
		 *
		 * @param WC_Order_Item[] $items Order line items.
		 * @param WC_Order        $order The order being inspected.
		 */
		$items = (array) apply_filters( 'woocommerce_review_order_eligible_items', $order->get_items(), $order );
		self::preload_for_items( $items, $order );

		foreach ( $items as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$decision = self::decide( $item, $order );
			if ( self::STATUS_SKIP === $decision['status'] ) {
				continue;
			}
			if ( ! ( $decision['comment'] instanceof WP_Comment ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Drop fully-refunded line items from the eligible-items list.
	 *
	 * Default callback wired onto `woocommerce_review_order_eligible_items`
	 * so the page never shows a row for a product the customer no longer
	 * owns. A line item is considered fully refunded when the absolute
	 * refunded quantity is greater than or equal to the item's ordered
	 * quantity. Fractional quantities are honoured.
	 *
	 * @since 10.8.0
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

			$refunded_qty = (float) abs( (float) $order->get_qty_refunded_for_item( $item->get_id() ) );
			$ordered_qty  = (float) $item->get_quantity();

			if ( $ordered_qty > 0 && $refunded_qty >= $ordered_qty ) {
				continue;
			}

			$filtered[ $key ] = $item;
		}

		return $filtered;
	}

	/**
	 * Look up the customer's review for a specific (product, variation) row on
	 * this order.
	 *
	 * @since 10.8.0
	 *
	 * @param int      $product_id   Product id.
	 * @param int      $variation_id Variation id (0 for simple products).
	 * @param WC_Order $order        Order being reviewed.
	 * @return WP_Comment|null
	 */
	private static function find_existing_review( int $product_id, int $variation_id, WC_Order $order ): ?WP_Comment {
		$email    = $order->get_billing_email();
		$order_id = (int) $order->get_id();
		if ( '' === $email || $order_id <= 0 || $product_id <= 0 ) {
			return null;
		}

		$key = self::cache_key( $order_id, $product_id, $variation_id, $email );
		if ( array_key_exists( $key, self::$review_cache ) ) {
			return self::$review_cache[ $key ];
		}

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
				'meta_query'         => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded by post_id + author_email.
					'relation' => 'AND',
					array(
						'key'   => self::ORDER_META_KEY,
						'value' => (string) $order_id,
					),
					array(
						'key'   => self::VARIATION_META_KEY,
						'value' => (string) $variation_id,
					),
				),
			)
		);

		if ( ! is_array( $comments ) || empty( $comments ) ) {
			self::$review_cache[ $key ] = null;
			return null;
		}

		$first = reset( $comments );
		$found = $first instanceof WP_Comment ? $first : null;

		self::$review_cache[ $key ] = $found;
		return $found;
	}

	/**
	 * Build the per-request cache key.
	 *
	 * @param int    $order_id     Order id.
	 * @param int    $product_id   Product id.
	 * @param int    $variation_id Variation id (0 for simple products).
	 * @param string $email        Customer email.
	 */
	private static function cache_key( int $order_id, int $product_id, int $variation_id, string $email ): string {
		return $order_id . '|' . $product_id . '|' . $variation_id . '|' . $email;
	}
}
