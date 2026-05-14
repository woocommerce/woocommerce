<?php
/**
 * Customer Review Order — empty-state thank-you view.
 *
 * Theme-overridable. Copy to `yourtheme/woocommerce/order/customer-review-order-empty.php`.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.8.0
 *
 * @var WC_Order $order          Order being reviewed.
 * @var int      $reviewed_count Number of items reviewed on this order.
 */

defined( 'ABSPATH' ) || exit;

if ( ! $order instanceof WC_Order ) {
	return;
}

$meta_parts = \Automattic\WooCommerce\Internal\OrderReviews\Meta::parts_for_order( $order );
?>
<div class="woocommerce-review-order woocommerce-review-order--empty">
	<p class="woocommerce-breadcrumb woocommerce-review-order__meta">
		<?php echo esc_html( implode( ' · ', $meta_parts ) ); ?>
	</p>

	<h1 class="woocommerce-review-order__empty-title">
		<?php
		if ( $reviewed_count > 0 ) {
			esc_html_e( 'Thank you for your reviews', 'woocommerce' );
		} else {
			// Defensive fallback for direct-URL visits (bookmark, admin-shared link).
			// The email pipeline never schedules or sends when an order has no
			// reviewable items, so customers don't reach this branch via the email.
			esc_html_e( 'Nothing to review here', 'woocommerce' );
		}
		?>
	</h1>

	<p class="woocommerce-review-order__empty-body">
		<?php
		if ( $reviewed_count > 0 ) {
			esc_html_e( 'Your feedback helps other customers make better purchasing decisions.', 'woocommerce' );
		} else {
			esc_html_e( 'There are no products on this order that are open for reviews right now.', 'woocommerce' );
		}
		?>
	</p>
</div>
