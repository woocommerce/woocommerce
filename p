<?php
/**
 * Admin Orders screen.
 *
 * @package WooCommerce\Admin
 */

defined( 'ABSPATH' ) || exit;

require_once 'class-wc-admin-orders.php';

$orders = new WC_Admin_Orders();

if ( isset( $_GET['s'] ) ) {
	$search_query = sanitize_text_field( $_GET['s'] );
	$orders->search_orders( $search_query );
	$search_results = $orders->get_search_results();

	if ( $search_results ) {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Search results', 'woocommerce' ); ?></h1>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Order #', 'woocommerce' ); ?></th>
						<th><?php echo esc_html__( 'Customer', 'woocommerce' ); ?></th>
						<th><?php echo esc_html__( 'Date', 'woocommerce' ); ?></th>
						<th><?php echo esc_html__( 'Total', 'woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $search_results as $order ) { ?>
						<tr>
							<td><?php echo esc_html( $order->get_order_number() ); ?></td>
							<td><?php echo esc_html( $order->get_customer_name() ); ?></td>
							<td><?php echo esc_html( $order->get_date_created()->format( get_option( 'date_format' ) ) ); ?></td>
							<td><?php echo esc_html( $order->get_total() ); ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<?php
	} else {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Search results', 'woocommerce' ); ?></h1>
			<p><?php echo esc_html__( 'No search results found.', 'woocommerce' ); ?></p>
		</div>
		<?php
	}
}