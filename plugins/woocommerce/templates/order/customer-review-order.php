<?php
/**
 * Customer Review Order page
 *
 * Read-only landing page surfaced from the Customer Review Request email.
 * Lists the eligible line items from a completed order so the customer can
 * review what they purchased.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/customer-review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.8.0
 *
 * @var WC_Order $order Order being reviewed.
 */

defined( 'ABSPATH' ) || exit;

if ( ! $order instanceof WC_Order ) {
	return;
}

$date_created    = $order->get_date_created();
$customer_name   = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
$customer_email  = $order->get_billing_email();
$order_number    = $order->get_order_number();
$order_date_text = $date_created ? wc_format_datetime( $date_created ) : '';

if ( '' !== $order_date_text ) {
	$order_summary = sprintf(
		/* translators: 1: order number, 2: order date */
		__( 'Order #%1$s (%2$s)', 'woocommerce' ),
		$order_number,
		$order_date_text
	);
} else {
	$order_summary = sprintf(
		/* translators: %s: order number */
		__( 'Order #%s', 'woocommerce' ),
		$order_number
	);
}

$meta_parts = array_filter(
	array(
		$customer_name,
		$customer_email,
		$order_summary,
	)
);

/**
 * Filter the eligible items rendered on the Review Order page.
 *
 * Defaults to the order's line items. Extensions can use this to hide items
 * that have already been reviewed or are otherwise ineligible.
 *
 * @since 10.8.0
 *
 * @param WC_Order_Item[] $items Order line items.
 * @param WC_Order        $order The order being reviewed.
 */
$items = (array) apply_filters( 'woocommerce_review_order_eligible_items', $order->get_items(), $order );

// Single batched lookup of every existing review by this customer for the
// items below. Without this each decide() call would issue its own query.
\Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility::preload_for_items( $items, $order );

// Pre-compute one decision per item so we know whether to render the form
// (any item still missing a review for this order) or fall through to the
// empty-state thank-you (every renderable item already has a review tied
// to this order).
$decisions          = array();
$has_unreviewed_row = false;
foreach ( $items as $item ) {
	if ( ! $item instanceof WC_Order_Item_Product ) {
		continue;
	}
	$product = $item->get_product();
	if ( ! $product instanceof WC_Product ) {
		continue;
	}

	$decision = \Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility::decide( $item, $order );
	if ( \Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility::STATUS_SKIP === $decision['status'] ) {
		continue;
	}

	if ( ! ( $decision['comment'] instanceof WP_Comment ) ) {
		$has_unreviewed_row = true;
	}

	$decisions[] = array(
		'item'     => $item,
		'product'  => $product,
		'decision' => $decision,
	);
}//end foreach

// Empty-state: no actionable rows remain. The Endpoint already stamped the
// completion meta before we got here, so this branch is purely the view.
if ( ! $has_unreviewed_row ) {
	$reviewed_count = 0;
	$rating_total   = 0;
	$rating_n       = 0;

	if ( '' !== $customer_email ) {
		$comment_ids = array();
		foreach ( $decisions as $entry ) {
			$existing_review = $entry['decision']['comment'] ?? null;
			if ( $existing_review instanceof WP_Comment ) {
				$comment_ids[] = (int) $existing_review->comment_ID;
			}
		}
		if ( ! empty( $comment_ids ) ) {
			update_meta_cache( 'comment', $comment_ids );
		}

		// Multiple line items can map to the same review (same parent
		// product on different variations or quantity-split lines). Count
		// each underlying comment once so the customer-facing summary
		// matches what they actually wrote.
		$counted = array();
		foreach ( $decisions as $entry ) {
			$existing_review = $entry['decision']['comment'] ?? null;
			if ( ! $existing_review instanceof WP_Comment ) {
				continue;
			}
			$cid = (int) $existing_review->comment_ID;
			if ( isset( $counted[ $cid ] ) ) {
				continue;
			}
			$counted[ $cid ] = true;
			++$reviewed_count;
			$rating = (int) get_comment_meta( $cid, 'rating', true );
			if ( $rating > 0 ) {
				$rating_total += $rating;
				++$rating_n;
			}
		}//end foreach
	}//end if

	$average_rating = $rating_n > 0 ? round( $rating_total / $rating_n, 1 ) : 0.0;

	wc_get_template(
		'order/customer-review-order-empty.php',
		array(
			'order'          => $order,
			'reviewed_count' => $reviewed_count,
			'average_rating' => $average_rating,
		)
	);
	return;
}//end if

// Single batched lookup of every existing review by this customer for the
// items below. Without this each decide() call would issue its own query.
\Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility::preload_for_items( $items, $order );

// The Endpoint has already validated the URL key against the order key, so the
// canonical value on the order is the right thing to echo into the form post.
$order_key = (string) $order->get_order_key();
?>
<div class="woocommerce-review-order">
	<p class="woocommerce-review-order__meta">
		<?php echo esc_html( implode( ' · ', $meta_parts ) ); ?>
	</p>

	<h1 class="woocommerce-review-order__title">
		<?php esc_html_e( 'Review your order', 'woocommerce' ); ?>
	</h1>

	<p class="woocommerce-review-order__intro">
		<?php esc_html_e( 'Loved something? Not so much? Share a quick review for what you bought. Feel free to skip any product.', 'woocommerce' ); ?>
	</p>

	<p class="woocommerce-review-order__legend">
		<?php esc_html_e( '* Mandatory fields', 'woocommerce' ); ?>
	</p>

	<form
		class="woocommerce-review-order__form"
		method="post"
		action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
		data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
		novalidate
	>
		<input type="hidden" name="action" value="<?php echo esc_attr( 'woocommerce_submit_order_reviews' ); ?>" />
		<input type="hidden" name="order_id" value="<?php echo esc_attr( (string) $order->get_id() ); ?>" />
		<input type="hidden" name="key" value="<?php echo esc_attr( $order_key ); ?>" />
		<?php wp_nonce_field( 'woocommerce_submit_order_reviews', '_wcnonce' ); ?>

		<ul class="woocommerce-review-order__items">
			<?php
			$row_index = 0;
			foreach ( $decisions as $entry ) {
				$item     = $entry['item'];
				$product  = $entry['product'];
				$decision = $entry['decision'];

				$prefill = \Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility::prefill_for_item( $item, $order );

				wc_get_template(
					'order/customer-review-order-row.php',
					array(
						'item'            => $item,
						'product'         => $product,
						'order'           => $order,
						'row_index'       => $row_index,
						'existing_rating' => $prefill['rating'],
						'existing_text'   => $prefill['text'],
					)
				);
				++$row_index;
			}
			?>
		</ul>

		<div class="woocommerce-review-order__actions">
			<button
				type="submit"
				class="woocommerce-review-order__submit button"
			>
				<?php esc_html_e( 'Submit reviews', 'woocommerce' ); ?>
			</button>
		</div>
	</form>
</div>
