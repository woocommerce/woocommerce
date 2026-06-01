<?php
/**
 * Withdrawal request form - Step 1: select order and review.
 *
 * Shows eligible orders for withdrawal OR the order review form for a specific order.
 * This is the entry point of the two-step confirmation process required by
 * EU Directive 2023/2673.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-request-withdrawal.php.
 *
 * @package WooCommerce\Templates
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-withdrawal-request-form">
	<h2><?php esc_html_e( 'Withdraw from contract', 'woocommerce' ); ?></h2>
	<p>
		<?php
		esc_html_e(
			'You have the right to withdraw from this contract within 14 days without giving any reason. To exercise your right of withdrawal, please use the form below.',
			'woocommerce'
		);
		?>
	</p>

	<?php if ( empty( $eligible_orders ) ) : ?>
		<p><?php esc_html_e( 'You currently have no orders eligible for withdrawal.', 'woocommerce' ); ?></p>
		<p>
			<a class="button" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
				<?php esc_html_e( 'Back to account', 'woocommerce' ); ?>
			</a>
		</p>
	<?php elseif ( $order ) : ?>
		<p><strong><?php esc_html_e( 'Order details', 'woocommerce' ); ?></strong></p>
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
						$completed = $order->get_date_completed() ? $order->get_date_completed() : $order->get_date_created();
						echo esc_html( wc_format_datetime( $completed ) );
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Order total', 'woocommerce' ); ?></th>
					<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
				</tr>
			</tbody>
		</table>

		<form method="post" class="woocommerce-form withdrawal-form">
			<?php wp_nonce_field( 'woocommerce-request_withdrawal', '_wpnonce' ); ?>
			<input type="hidden" name="action" value="request_withdrawal" />
			<input type="hidden" name="order_id" value="<?php echo esc_attr( (string) $order->get_id() ); ?>" />
			<input type="hidden" name="step" value="" />

			<p>
				<label for="withdrawal_reason"><?php esc_html_e( 'Reason for withdrawal (optional)', 'woocommerce' ); ?></label>
				<textarea
					id="withdrawal_reason"
					name="withdrawal_reason"
					rows="4"
					style="width:100%;"
					placeholder="<?php esc_attr_e( 'You may optionally provide a reason for your withdrawal. This is not required.', 'woocommerce' ); ?>"
				></textarea>
			</p>

			<p>
				<button type="submit" class="button alt" name="woocommerce_request_withdrawal_submit">
					<?php esc_html_e( 'Continue', 'woocommerce' ); ?>
				</button>
			</p>
		</form>
	<?php else : ?>
		<p><?php esc_html_e( 'Please select an order to withdraw from:', 'woocommerce' ); ?></p>
		<table class="woocommerce-orders-table shop_table shop_table_responsive">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Order', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Date', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Action', 'woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $eligible_orders as $eligible_order ) : ?>
					<tr>
						<td><?php echo esc_html( $eligible_order->get_order_number() ); ?></td>
						<td>
							<?php
							$date = $eligible_order->get_date_completed() ? $eligible_order->get_date_completed() : $eligible_order->get_date_created();
							echo esc_html( wc_format_datetime( $date ) );
							?>
						</td>
						<td><?php echo wp_kses_post( $eligible_order->get_formatted_order_total() ); ?></td>
						<td>
							<a class="button" href="<?php echo esc_url( wc_get_endpoint_url( 'request-withdrawal', $eligible_order->get_id(), wc_get_page_permalink( 'myaccount' ) ) ); ?>">
								<?php esc_html_e( 'Select', 'woocommerce' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
