<?php
/**
 * Withdrawal request submitted acknowledgment.
 *
 * Shown after a successful withdrawal request submission. Records the
 * date and time of the withdrawal as required by EU Directive 2023/2673.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-request-withdrawal-submitted.php.
 *
 * @package WooCommerce\Templates
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;

$date_created = isset( $request_data['date_created'] ) ? (int) $request_data['date_created'] : 0;
$request_id   = isset( $request_id ) && is_string( $request_id ) ? $request_id : '';
$order_number = ( is_object( $order ) && method_exists( $order, 'get_order_number' ) ) ? $order->get_order_number() : '';
?>
<div class="woocommerce-withdrawal-submitted">
	<h2><?php esc_html_e( 'Withdrawal request received', 'woocommerce' ); ?></h2>
	<p>
		<?php
		esc_html_e(
			'Your withdrawal request has been successfully submitted. The merchant has been notified and will process your request. You will receive a separate confirmation email shortly.',
			'woocommerce'
		);
		?>
	</p>

	<table class="woocommerce-table shop_table">
		<tbody>
			<tr>
				<th><?php esc_html_e( 'Request reference', 'woocommerce' ); ?></th>
				<td><code><?php echo '' !== $request_id ? esc_html( $request_id ) : esc_html__( 'N/A', 'woocommerce' ); ?></code></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Date and time of withdrawal', 'woocommerce' ); ?></th>
				<td>
					<?php
					if ( $date_created > 0 ) {
						echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date_created ) );
					} else {
						echo esc_html__( 'N/A', 'woocommerce' );
					}
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Order number', 'woocommerce' ); ?></th>
				<td><?php echo '' !== $order_number ? esc_html( $order_number ) : esc_html__( 'N/A', 'woocommerce' ); ?></td>
			</tr>
		</tbody>
	</table>

	<p>
		<a class="button" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
			<?php esc_html_e( 'Back to account', 'woocommerce' ); ?>
		</a>
		<a class="button" href="<?php echo esc_url( wc_get_endpoint_url( 'withdrawals', '', wc_get_page_permalink( 'myaccount' ) ) ); ?>">
			<?php esc_html_e( 'View all withdrawals', 'woocommerce' ); ?>
		</a>
	</p>
</div>
