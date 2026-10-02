<?php
/**
 * Display the Customer History metabox.
 *
 * This template is used to display the customer history metabox on the edit order screen.
 *
 * @see     Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\CustomerHistory
 * @package WooCommerce\Templates
 * @version 11.2.0
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Variables used in this file.
 *
 * @var int    $orders_count The number of paid orders placed by the current customer.
 * @var array  $currency_totals   Money spent by this customer, keyed by the currency they paid in.
 * @var array  $currency_counts   Order count per currency, keyed the same way.
 * @var array  $currency_averages Average order value per currency, keyed the same way.
 * @var string $tooltip      The tooltip text for the "Total orders" heading.
 */
// Preserve callers that still supply only the original single-total arguments.
if ( ! isset( $currency_totals ) ) {
	$store             = get_woocommerce_currency();
	$currency_totals   = isset( $total_spend ) ? array( $store => (float) $total_spend ) : array();
	$currency_counts   = array( $store => (int) ( $orders_count ?? 0 ) );
	$currency_averages = isset( $avg_order_value ) ? array( $store => (float) $avg_order_value ) : array();
}
$multi_currency = count( $currency_totals ) > 1;
?>

<div class="customer-history order-attribution-metabox">
	<h4>
		<?php
		esc_html_e( 'Total orders', 'woocommerce' );
		echo wp_kses_post( wc_help_tip( $tooltip ) );
		?>
	</h4>

	<span class="order-attribution-total-orders">
		<?php echo esc_html( (string) $orders_count ); ?>
	</span>

	<h4>
		<?php
		esc_html_e( 'Total order value', 'woocommerce' );
		echo wp_kses_post(
			wc_help_tip(
				$multi_currency
					? __( 'Total order value after refunds, including tax and shipping, before payment processing fees. This customer paid in more than one currency, so each is totalled separately rather than added together.', 'woocommerce' )
					: __( 'Total order value after refunds, including tax and shipping, before payment processing fees.', 'woocommerce' )
			)
		);
		?>
	</h4>
	<?php if ( ! $currency_totals ) : ?>
		<span class="order-attribution-total-spend"><?php echo wp_kses_post( wc_price( 0 ) ); ?></span>
	<?php else : ?>
		<?php foreach ( $currency_totals as $currency => $total ) : ?>
			<div class="order-attribution-currency-row">
				<span data-currency="<?php echo esc_attr( $currency ); ?>" class="order-attribution-total-spend">
					<?php echo wp_kses_post( wc_price( $total, array( 'currency' => $currency ) ) ); ?>
				</span>
				<?php if ( $multi_currency ) : ?>
					<small>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: number of orders placed in this currency. */
								_n( '%s order', '%s orders', $currency_counts[ $currency ], 'woocommerce' ),
								number_format_i18n( $currency_counts[ $currency ] )
							)
						);
						?>
					</small>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>

	<h4><?php esc_html_e( 'Average order value', 'woocommerce' ); ?></h4>
	<?php if ( ! $currency_averages ) : ?>
		<span class="order-attribution-average-order-value"><?php echo wp_kses_post( wc_price( 0 ) ); ?></span>
	<?php else : ?>
		<?php foreach ( $currency_averages as $currency => $average ) : ?>
			<div class="order-attribution-currency-row">
				<span data-currency="<?php echo esc_attr( $currency ); ?>" class="order-attribution-average-order-value">
					<?php echo wp_kses_post( wc_price( $average, array( 'currency' => $currency ) ) ); ?>
				</span>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
