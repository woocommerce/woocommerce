<?php
/**
 * Display the Customer History metabox.
 *
 * This template is used to display the customer history metabox on the edit order screen.
 *
 * @see     Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\CustomerHistory
 * @package WooCommerce\Templates
 * @version 11.3.0
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Variables used in this file.
 *
 * @var int                  $orders_count          The number of paid orders placed by the current customer.
 * @var float                $total_spend           The total money spent by the current customer, across every order currency.
 * @var float                $avg_order_value       The average money spent by the current customer, across every order currency.
 * @var string               $tooltip               The tooltip text for the "Total orders" heading.
 * @var array<string, float> $totals_per_currency   The total money spent by the current customer, keyed by order currency. Passed since 11.3.0.
 * @var array<string, int>   $counts_per_currency   The number of paid orders placed by the current customer, keyed by order currency. Passed since 11.3.0.
 * @var array<string, float> $averages_per_currency The average money spent by the current customer, keyed by order currency. Passed since 11.3.0.
 */

// Callers written before 11.3.0 pass only the single total and average.
$totals_per_currency   = is_array( $totals_per_currency ?? null ) ? $totals_per_currency : array();
$counts_per_currency   = is_array( $counts_per_currency ?? null ) ? $counts_per_currency : array();
$averages_per_currency = is_array( $averages_per_currency ?? null ) ? $averages_per_currency : array();

// A customer whose orders are all in the store currency gets a single total. Any other currency is listed and labelled.
$has_several_currencies = count( $totals_per_currency ) > 1;
$show_per_currency      = $has_several_currencies || ( 1 === count( $totals_per_currency ) && ! isset( $totals_per_currency[ get_woocommerce_currency() ] ) );
?>

<div class="customer-history order-attribution-metabox">
	<h4>
		<?php
		esc_html_e( 'Total orders', 'woocommerce' );
		echo wp_kses_post( wc_help_tip( $tooltip ) );
		?>
	</h4>

	<span class="order-attribution-total-orders">
		<?php echo esc_html( $orders_count ); ?>
	</span>

	<h4>
		<?php
		esc_html_e( 'Total revenue', 'woocommerce' );
		echo wp_kses_post(
			wc_help_tip(
				$has_several_currencies
					? __( "This is the Customer Lifetime Value, or the total amount you have earned from this customer's orders. Orders in different currencies are totaled separately.", 'woocommerce' )
					: __( "This is the Customer Lifetime Value, or the total amount you have earned from this customer's orders.", 'woocommerce' )
			)
		);
		?>
	</h4>
<?php if ( $show_per_currency ) : ?>
	<?php foreach ( $totals_per_currency as $currency => $currency_total ) : ?>
		<div class="order-attribution-currency-row" data-currency="<?php echo esc_attr( (string) $currency ); ?>">
			<span class="order-attribution-total-spend"><?php echo wp_kses_post( wc_price( (float) $currency_total, array( 'currency' => (string) $currency ) ) ); ?></span>
			<span class="order-attribution-currency-code"><?php echo esc_html( (string) $currency ); ?></span>
			<?php if ( $has_several_currencies ) : ?>
				<?php $currency_orders_count = (int) ( $counts_per_currency[ $currency ] ?? 0 ); ?>
				<small class="order-attribution-currency-orders">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: number of orders in one currency */
							_n( '(%s order)', '(%s orders)', $currency_orders_count, 'woocommerce' ),
							number_format_i18n( $currency_orders_count )
						)
					);
					?>
				</small>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
<?php else : ?>
	<span class="order-attribution-total-spend">
		<?php echo wp_kses_post( wc_price( $total_spend ) ); ?>
	</span>
<?php endif; ?>

	<h4><?php esc_html_e( 'Average order value', 'woocommerce' ); ?></h4>
<?php if ( $show_per_currency ) : ?>
	<?php foreach ( $averages_per_currency as $currency => $currency_average ) : ?>
		<div class="order-attribution-currency-row" data-currency="<?php echo esc_attr( (string) $currency ); ?>">
			<span class="order-attribution-average-order-value"><?php echo wp_kses_post( wc_price( (float) $currency_average, array( 'currency' => (string) $currency ) ) ); ?></span>
			<span class="order-attribution-currency-code"><?php echo esc_html( (string) $currency ); ?></span>
		</div>
	<?php endforeach; ?>
<?php else : ?>
	<span class="order-attribution-average-order-value">
		<?php echo wp_kses_post( wc_price( $avg_order_value ) ); ?>
	</span>
<?php endif; ?>
</div>
