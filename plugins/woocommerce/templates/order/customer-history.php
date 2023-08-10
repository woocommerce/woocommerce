<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Variables used in this file.
 *
 * @var int   $order_count   The number of paid orders placed by the current customer.
 * @var float $total_spent   The total money spent by the current customer.
 * @var float $average_spent The average money spent by the current customer.
 */
?>

<div class="customer-history order-source-attribution-metabox">
	<h4>
		<?php
		esc_html_e( 'Total orders', 'woocommerce' );
		echo wc_help_tip(
			__( 'Total number of orders for this customer, including the current one.', 'woocommerce' )
		); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		?>
	</h4>

	<span class="order-source-attribution-total-orders">
		<?php echo esc_html( $order_count ); ?>
	</span>

	<h4>
		<?php
		esc_html_e( 'Total revenue', 'woocommerce' );
		echo wc_help_tip(
			__( "This is the Customer Lifetime Value, or the total amount you have earned from this customer's orders.", 'woocommerce' )
		); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		?>
	</h4>
	<span class="order-source-attribution-total-spend">
		<?php echo wc_price( $total_spent ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?>
	</span>

	<h4><?php esc_html_e( 'Average order value', 'woocommerce' ); ?></h4>
	<span class="order-source-attribution-average-order-value">
		<?php echo wc_price( $average_spent ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?>
	</span>
</div>
