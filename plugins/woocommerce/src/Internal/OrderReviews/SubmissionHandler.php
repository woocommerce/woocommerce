<?php
/**
 * SubmissionHandler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderReviews;

use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Order;

/**
 * Handles the AJAX submission of the Review Order form.
 *
 * One comment per rated row, with per-row outcome reported back so a single
 * row's failure cannot block the rest. Guests submit with the order key;
 * logged-in customers must own the order.
 *
 * @internal Just for internal use.
 *
 * @since 10.8.0
 */
class SubmissionHandler {

	/**
	 * Action name registered with admin-ajax.
	 */
	public const ACTION = 'woocommerce_submit_order_reviews';

	/**
	 * Order meta stamped with the time the Review Order page first had no
	 * actionable rows left.
	 *
	 * Set by the submission handler once every eligible item has a review by
	 * this customer (approved or pending moderation), and also by the Endpoint
	 * when the page is loaded with no actionable rows (e.g. all items are
	 * already-reviewed or skipped because reviews are disabled on the products).
	 */
	public const COMPLETED_META_KEY = '_wc_review_request_completed_at';

	/**
	 * Wire the AJAX endpoints.
	 *
	 * Auto-called by the WC dependency container after instantiation.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Entry point fired by `admin-ajax.php`.
	 *
	 * Sends a JSON response and exits.
	 */
	public function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce is checked below.
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$key      = isset( $_POST['key'] ) && is_string( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$nonce    = isset( $_POST['_wcnonce'] ) && is_string( $_POST['_wcnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wcnonce'] ) ) : '';
		// Row-level fields are sanitized inside process_rows(); the array as a whole only needs unslashing.
		$rows_in = isset( $_POST['reviews'] ) && is_array( $_POST['reviews'] ) ? wp_unslash( $_POST['reviews'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, self::ACTION ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce' ) ), 403 );
		}

		$order = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order instanceof WC_Order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'woocommerce' ) ), 404 );
		}

		if ( '' === $key || ! hash_equals( $order->get_order_key(), $key ) ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'woocommerce' ) ), 404 );
		}

		// Logged-in user must own the order. Guests with the right key still pass.
		if ( $order->get_customer_id() && is_user_logged_in() && get_current_user_id() !== $order->get_customer_id() ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'woocommerce' ) ), 404 );
		}

		// Reuse the same eligibility filter the page-load endpoint uses so the
		// submit path can never run on an order whose status no longer permits it.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- documented on Endpoint::is_authorised().
		$eligible_statuses = (array) apply_filters(
			'woocommerce_review_order_eligible_statuses',
			array( OrderStatus::COMPLETED ),
			$order
		);

		if ( ! in_array( $order->get_status(), $eligible_statuses, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'woocommerce' ) ), 404 );
		}

		$results = $this->process_rows( $order, $rows_in );

		$this->maybe_mark_order_complete( $order );

		/**
		 * Fires after the Review Order form has been processed.
		 *
		 * @since 10.8.0
		 *
		 * @param WC_Order $order   The order.
		 * @param array    $results Per-row outcomes — see `SubmissionHandler::process_rows()`.
		 */
		do_action( 'woocommerce_review_order_submitted', $order, $results );

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Process the submitted row payload and return per-row outcomes.
	 *
	 * @param WC_Order $order  Order being reviewed.
	 * @param array    $rows_in Raw `$_POST['reviews']` value.
	 * @return array<int, array{product_id:int, status:string, comment_id?:int, error?:string}>
	 */
	private function process_rows( WC_Order $order, array $rows_in ): array {
		$results      = array();
		$item_index   = $this->index_eligible_order_items( $order );
		$author_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$author_email = $order->get_billing_email();
		$author_ip    = $order->get_customer_ip_address();
		$author_agent = $order->get_customer_user_agent();
		$require_mod  = (bool) get_option( 'comment_moderation' );

		// Drop any per-request memoisation a prior caller may have populated,
		// then preload the eligibility cache so the per-row decide() calls
		// below don't issue one already-reviewed query each. Reset matters
		// inside the suite (multiple submissions in one PHP process) and is
		// a no-op in production (admin-ajax runs in a fresh process).
		ItemEligibility::reset_cache();
		ItemEligibility::preload_for_items( $item_index, $order );

		foreach ( $rows_in as $row_index => $row ) {
			$row_index = (int) $row_index;
			$row       = is_array( $row ) ? $row : array();

			$rating = isset( $row['rating'] ) ? (int) $row['rating'] : 0;
			if ( 0 === $rating ) {
				// Empty rating means the customer chose to skip this row; allowed.
				continue;
			}

			$product_id    = isset( $row['product_id'] ) ? absint( $row['product_id'] ) : 0;
			$order_item_id = isset( $row['order_item_id'] ) ? absint( $row['order_item_id'] ) : 0;
			// $rows_in was already unslashed in handle(); avoid double-unslashing.
			$text = isset( $row['text'] ) && is_string( $row['text'] ) ? trim( wp_kses_post( $row['text'] ) ) : '';

			$result = array(
				'product_id' => $product_id,
				'status'     => 'error',
			);

			if ( $rating < 1 || $rating > 5 ) {
				$result['error']       = 'invalid_rating';
				$results[ $row_index ] = $result;
				continue;
			}

			// invalid_row also covers fully-refunded line items: index_eligible_order_items()
			// runs them through woocommerce_review_order_eligible_items, which strips them.
			if ( ! $product_id || ! $order_item_id || ! isset( $item_index[ $order_item_id ] ) ) {
				$result['error']       = 'invalid_row';
				$results[ $row_index ] = $result;
				continue;
			}

			$item = $item_index[ $order_item_id ];

			// Variable products: the row template posts the variation id,
			// while $item->get_product_id() returns the parent. Accept either.
			$line_product_id   = (int) $item->get_product_id();
			$line_variation_id = (int) $item->get_variation_id();
			if ( $product_id !== $line_product_id && $product_id !== $line_variation_id ) {
				$result['error']       = 'product_mismatch';
				$results[ $row_index ] = $result;
				continue;
			}

			// Reviews always attach to the parent product so they show on the
			// product page regardless of which variation was bought.
			$review_post_id = $line_product_id;

			// Reject submissions for products whose review form was never
			// rendered (comments disabled on the product).
			$decision = ItemEligibility::decide( $item, $order );
			if ( ItemEligibility::STATUS_SKIP === $decision['status'] ) {
				$result['error']       = 'reviews_not_open';
				$results[ $row_index ] = $result;
				continue;
			}

			// Only attribute the comment to a WP user when the current request is
			// authenticated as that user. Guests reaching the page via the order
			// key are not authenticated, so the comment stays unattributed (0).
			$customer_id     = (int) $order->get_customer_id();
			$current_user_id = get_current_user_id();
			$comment_user_id = ( $current_user_id > 0 && $current_user_id === $customer_id ) ? $current_user_id : 0;

			// If the customer already has a review tied to this order for this
			// product, update it in place instead of stacking duplicates. The
			// existing comment id comes from the server-side lookup, not the
			// client, so a tampered POST can't target someone else's review.
			$existing = $decision['comment'] instanceof \WP_Comment ? $decision['comment'] : null;

			if ( $existing instanceof \WP_Comment ) {
				$update_ok = wp_update_comment(
					wp_slash(
						array(
							'comment_ID'       => (int) $existing->comment_ID,
							'comment_content'  => $text,
							'comment_approved' => $require_mod ? 0 : 1,
						)
					)
				);
				if ( false === $update_ok || is_wp_error( $update_ok ) ) {
					$result['error']       = 'update_failed';
					$results[ $row_index ] = $result;
					continue;
				}

				update_comment_meta( (int) $existing->comment_ID, 'rating', $rating );

				$result['comment_id']  = (int) $existing->comment_ID;
				$result['status']      = $require_mod ? 'pending_moderation' : 'ok';
				$results[ $row_index ] = $result;
				continue;
			}

			$comment_data = array(
				'comment_post_ID'      => $review_post_id,
				'comment_author'       => '' !== $author_name ? $author_name : __( 'Anonymous', 'woocommerce' ),
				'comment_author_email' => $author_email,
				'comment_author_IP'    => $author_ip,
				'comment_agent'        => $author_agent,
				'comment_content'      => $text,
				'comment_type'         => 'review',
				'comment_approved'     => $require_mod ? 0 : 1,
				'user_id'              => $comment_user_id,
			);

			$comment_id = wp_insert_comment( wp_slash( $comment_data ) );
			if ( ! $comment_id ) {
				$result['error']       = 'insert_failed';
				$results[ $row_index ] = $result;
				continue;
			}

			add_comment_meta( $comment_id, 'rating', $rating, true );
			add_comment_meta( $comment_id, 'verified', 1, true );
			add_comment_meta( $comment_id, ItemEligibility::ORDER_META_KEY, (int) $order->get_id(), true );

			$result['comment_id']  = (int) $comment_id;
			$result['status']      = $require_mod ? 'pending_moderation' : 'ok';
			$results[ $row_index ] = $result;
		}//end foreach

		return $results;
	}

	/**
	 * Set the completed-at meta when every eligible item has a review by this
	 * customer (approved or pending moderation), whether posted in this
	 * submission or an earlier one. Spam/trash comments are excluded.
	 *
	 * @param WC_Order $order Order being reviewed.
	 */
	private function maybe_mark_order_complete( WC_Order $order ): void {
		// Recording the moment the order first became fully reviewed; never overwrite.
		if ( $order->get_meta( self::COMPLETED_META_KEY ) ) {
			return;
		}

		$customer_email = $order->get_billing_email();
		if ( '' === $customer_email ) {
			return;
		}

		// Build the same eligible-row set the page uses, then count required
		// reviews per parent product. Same product appearing on N rows needs
		// N reviews, not 1.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- documented at the page-template invocation site.
		$eligible_items = (array) apply_filters( 'woocommerce_review_order_eligible_items', $order->get_items(), $order );

		$required_reviews = array();
		foreach ( $eligible_items as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$product_id = (int) $item->get_product_id();
			if ( $product_id > 0 ) {
				$required_reviews[ $product_id ] = ( $required_reviews[ $product_id ] ?? 0 ) + 1;
			}
		}

		if ( empty( $required_reviews ) ) {
			return;
		}

		// Single grouped lookup, fetching the comment objects directly so we
		// can read comment_post_ID without a follow-up query per row. Limit
		// to approved + pending-moderation so spam/trash never count as
		// completion. number=>0 disables the default 20-row cap so this still
		// works for orders with many reviewable items.
		$comments = get_comments(
			array(
				'post__in'     => array_keys( $required_reviews ),
				'author_email' => $customer_email,
				'type'         => 'review',
				'status'       => array( 'approve', 'hold' ),
				'number'       => 0,
			)
		);

		if ( ! is_array( $comments ) || empty( $comments ) ) {
			return;
		}

		$review_counts = array();
		foreach ( $comments as $comment ) {
			if ( $comment instanceof \WP_Comment ) {
				$post_id                   = (int) $comment->comment_post_ID;
				$review_counts[ $post_id ] = ( $review_counts[ $post_id ] ?? 0 ) + 1;
			}
		}

		foreach ( $required_reviews as $product_id => $required ) {
			if ( ( $review_counts[ $product_id ] ?? 0 ) < $required ) {
				return;
			}
		}

		$order->update_meta_data( self::COMPLETED_META_KEY, (string) time() );
		$order->save();
	}

	/**
	 * Map order_item_id => `WC_Order_Item_Product` for fast row lookup,
	 * filtered through `woocommerce_review_order_eligible_items` so the
	 * handler agrees with the page on which items are reviewable. The
	 * default callback excludes fully-refunded items.
	 *
	 * @param WC_Order $order Order being reviewed.
	 * @return array<int, \WC_Order_Item_Product>
	 */
	private function index_eligible_order_items( WC_Order $order ): array {
		/**
		 * Filter the eligible items considered by the Review Order
		 * submission handler.
		 *
		 * Same hook the page uses; documented in
		 * `templates/order/customer-review-order.php`.
		 *
		 * @since 10.8.0
		 *
		 * @param \WC_Order_Item[] $items Order line items.
		 * @param WC_Order         $order The order being reviewed.
		 */
		$items = (array) apply_filters( 'woocommerce_review_order_eligible_items', $order->get_items(), $order );

		$index = array();
		foreach ( $items as $item ) {
			if ( $item instanceof \WC_Order_Item_Product ) {
				$index[ $item->get_id() ] = $item;
			}
		}
		return $index;
	}
}
