<?php
/**
 * Withdrawal request form - Step 2: confirmation.
 *
 * Final review and submit of the withdrawal request. Required by
 * EU Directive 2023/2673 to ensure the consumer can review and confirm
 * their withdrawal before it is submitted.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-request-withdrawal-confirm.php.
 *
 * @package WooCommerce\Templates
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $order ) || ! ( $order instanceof WC_Order ) ) {
	echo '<p>' . esc_html__( 'Invalid order.', 'woocommerce' ) . '</p>';
	return;
}
?>

<div class="woocommerce-withdrawal-confirm">
	<h2><?php esc_html_e( 'Confirm your withdrawal', 'woocommerce' ); ?></h2>
	<p>
		<?php
		esc_html_e(
			'Please review the information below and confirm your withdrawal request. By submitting, you are exercising your statutory right of withdrawal from this contract.',
			'woocommerce'
		);
		?>
	</p>

	<table class="woocommerce-table shop_table">
		<tbody>
			<tr>
				<th><?php esc_html_e( 'Order number', 'woocommerce' ); ?></th>
				<td><?php echo esc_html( $order->get_order_number() ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Order date', 'woocommerce' ); ?></th>
				<td>
					<?php
					$date = $order->get_date_completed() ? $order->get_date_completed() : $order->get_date_created();
					echo esc_html( wc_format_datetime( $date ) );
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Order total', 'woocommerce' ); ?></th>
				<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
			</tr>
		</tbody>
	</table>

	<form method="post" class="woocommerce-form withdrawal-confirm-form">
		<?php wp_nonce_field( 'woocommerce-request_withdrawal', '_wpnonce' ); ?>
		<input type="hidden" name="action" value="request_withdrawal" />
		<input type="hidden" name="order_id" value="<?php echo esc_attr( (string) $order->get_id() ); ?>" />
		<input type="hidden" name="step" value="confirm" />
		<?php
		$reason = '';
		if ( function_exists( 'WC' ) && WC()->session ) {
			$data = WC()->session->get( 'woocommerce_withdrawal_request_data', array() );
			if ( isset( $data['reason'] ) ) {
				$candidate = (string) $data['reason'];
				$candidate = trim( wp_strip_all_tags( $candidate ) );
				$candidate = preg_replace( '/\s+/', ' ', $candidate );
				if ( is_string( $candidate ) && strlen( $candidate ) > 1000 ) {
					$candidate = substr( $candidate, 0, 1000 );
				}
				$reason = $candidate;
			}
		}
		?>
		<input type="hidden" name="withdrawal_reason" value="<?php echo esc_attr( $reason ); ?>" />

		<p>
			<label>
				<input type="checkbox" name="withdrawal_confirm" value="1" required />
				<?php esc_html_e( 'I confirm that I wish to withdraw from this contract.', 'woocommerce' ); ?>
			</label>
		</p>

		<p>
			<button type="submit" class="button alt" name="woocommerce_request_withdrawal_confirm">
				<?php esc_html_e( 'Submit withdrawal request', 'woocommerce' ); ?>
			</button>
			<a class="button" href="<?php echo esc_url( wc_get_endpoint_url( 'request-withdrawal', $order->get_id(), wc_get_page_permalink( 'myaccount' ) ) ); ?>">
				<?php esc_html_e( 'Cancel', 'woocommerce' ); ?>
			</a>
		</p>
	</form>
</div>
