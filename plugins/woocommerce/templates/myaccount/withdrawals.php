<?php
/**
 * Withdrawals list.
 *
 * Lists all customer orders that have withdrawal requests.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/withdrawals.php.
 *
 * @package WooCommerce\Templates
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;
?>

<h2><?php esc_html_e( 'My withdrawals', 'woocommerce' ); ?></h2>

<?php if ( empty( $withdrawal_orders ) ) : ?>
	<p><?php esc_html_e( 'You have not submitted any withdrawal requests yet.', 'woocommerce' ); ?></p>
<?php else : ?>
	<table class="woocommerce-orders-table woocommerce-MyAccount-withdrawals shop_table shop_table_responsive my_account_withdrawals">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Order', 'woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Date submitted', 'woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Reference', 'woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Status', 'woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Action', 'woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $withdrawal_orders as $data ) : ?>
				<?php
				$order    = $data['order'];
				$requests = $data['requests'];
				$view_url = $order->get_view_order_url();
				?>
				<?php foreach ( $requests as $request ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( $view_url ); ?>">
								<?php echo esc_html( $order->get_order_number() ); ?>
							</a>
						</td>
						<td>
							<?php
							$ts = isset( $request['date_created'] ) ? (int) $request['date_created'] : 0;
							echo esc_html( $ts ? date_i18n( get_option( 'date_format' ), $ts ) : '—' );
							?>
						</td>
						<td><code><?php echo esc_html( $request['request_id'] ?? '' ); ?></code></td>
						<td>
							<?php
							$status = $request['status'] ?? 'pending';
							$labels = array(
								'pending'  => __( 'Pending', 'woocommerce' ),
								'approved' => __( 'Approved', 'woocommerce' ),
								'rejected' => __( 'Rejected', 'woocommerce' ),
							);
							echo esc_html( $labels[ $status ] ?? ucfirst( $status ) );
							?>
						</td>
						<td>
							<a class="button" href="<?php echo esc_url( $view_url ); ?>">
								<?php esc_html_e( 'View order', 'woocommerce' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
