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

	<?php if ( ! empty( $items ) ) : ?>
		<ul class="woocommerce-review-order__items">
			<?php foreach ( $items as $item ) : ?>
				<?php
				if ( ! $item instanceof WC_Order_Item_Product ) {
					continue;
				}
				$product = $item->get_product();
				if ( ! $product instanceof WC_Product ) {
					continue;
				}
				$product_link = $product->is_visible() ? get_permalink( $product->get_id() ) : '';
				$product_name = $item->get_name();
				$image_html   = $product->get_image( 'woocommerce_thumbnail' );
				?>
				<li class="woocommerce-review-order__item">
					<p class="woocommerce-review-order__item-title">
						<?php if ( $product_link ) : ?>
							<a href="<?php echo esc_url( $product_link ); ?>"><?php echo esc_html( $product_name ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $product_name ); ?>
						<?php endif; ?>
					</p>
					<div class="woocommerce-review-order__item-row">
						<div class="woocommerce-review-order__item-image">
							<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_image() returns escaped HTML. ?>
						</div>
						<div class="woocommerce-review-order__item-form-placeholder"></div>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
