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
 * @var int      $reviewed_count Number of reviews this customer left on this order.
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
