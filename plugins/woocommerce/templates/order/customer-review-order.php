<?php
/**
 * Customer Review Order page.
 *
 * Theme-overridable. Copy to `yourtheme/woocommerce/order/customer-review-order.php`.
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

$meta_parts = \Automattic\WooCommerce\Internal\OrderReviews\Meta::parts_for_order( $order );

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

// Batched lookup; without this each decide() call would issue its own query.
\Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility::preload_for_items( $items, $order );

// Skipped rows are counted so the disabled-products notice can render above the form.
$decisions          = array();
$has_unreviewed_row = false;
$skipped_count      = 0;
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
		++$skipped_count;
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

// Empty-state: no actionable rows remain.
if ( ! $has_unreviewed_row ) {
	$reviewed_count = 0;
	foreach ( $decisions as $entry ) {
		if ( $entry['decision']['comment'] instanceof WP_Comment ) {
			++$reviewed_count;
		}
	}

	wc_get_template(
		'order/customer-review-order-empty.php',
		array(
			'order'          => $order,
			'reviewed_count' => $reviewed_count,
		)
	);
	return;
}//end if

$order_key       = (string) $order->get_order_key();
$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';
?>
<div class="woocommerce-review-order">
	<p class="woocommerce-breadcrumb woocommerce-review-order__meta">
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

	<?php if ( $skipped_count > 0 ) : ?>
		<div
			class="woocommerce-info woocommerce-review-order__notice"
			role="status"
		>
			<div class="woocommerce-review-order__notice-body">
				<p class="woocommerce-review-order__notice-title">
					<?php esc_html_e( "Don't see all your products?", 'woocommerce' ); ?>
				</p>
				<p class="woocommerce-review-order__notice-text">
					<?php esc_html_e( 'Some products may not be available for review because the store has disabled reviews for them.', 'woocommerce' ); ?>
				</p>
			</div>
			<button
				type="button"
				class="woocommerce-review-order__notice-dismiss"
				aria-label="<?php esc_attr_e( 'Dismiss this notice', 'woocommerce' ); ?>"
			>
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	<?php endif; ?>

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
				class="woocommerce-review-order__submit button<?php echo esc_attr( $wp_button_class ); ?>"
			>
				<?php esc_html_e( 'Submit reviews', 'woocommerce' ); ?>
			</button>
		</div>
	</form>

	<div class="woocommerce-review-order__success" hidden>
		<h1 class="woocommerce-review-order__empty-title">
			<?php esc_html_e( 'Thank you for your reviews', 'woocommerce' ); ?>
		</h1>
		<p class="woocommerce-review-order__empty-body">
			<?php esc_html_e( 'Your feedback helps other customers make better purchasing decisions.', 'woocommerce' ); ?>
		</p>
	</div>
</div>
