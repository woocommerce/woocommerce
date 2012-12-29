<?php
/**
 * My Orders
 *
 * Shows recent orders on the account page
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;

$customer_orders = get_posts( array(
    'numberposts' => $order_count,
    'meta_key'    => '_customer_user',
    'meta_value'  => get_current_user_id(),
    'post_type'   => 'shop_order',
    'post_status' => 'publish'
) );

if ( $customer_orders ) : ?>

	<h2><?php echo apply_filters( 'woocommerce_my_account_my_orders_title', __( 'Recent Orders', 'woocommerce' ) ); ?></h2>

	<table class="shop_table my_account_orders">

		<thead>
			<tr>
				<th class="order-number"><span class="nobr"><?php _e( 'Order', 'woocommerce' ); ?></span></th>
				<th class="order-shipto"><span class="nobr"><?php _e( 'Ship to', 'woocommerce' ); ?></span></th>
				<th class="order-total"><span class="nobr"><?php _e( 'Total', 'woocommerce' ); ?></span></th>
				<th class="order-status" colspan="2"><span class="nobr"><?php _e( 'Status', 'woocommerce' ); ?></span></th>
			</tr>
		</thead>

		<tbody><?php
			foreach ( $customer_orders as $customer_order ) {
				$order = new WC_Order();

				$order->populate( $customer_order );

				$status = get_term_by( 'slug', $order->status, 'shop_order_status' );

				?><tr class="order">
					<td class="order-number" width="1%">
						<a href="<?php echo esc_url( add_query_arg('order', $order->id, get_permalink( woocommerce_get_page_id( 'view_order' ) ) ) ); ?>">
							<?php echo $order->get_order_number(); ?>
						</a> &ndash; <time title="<?php echo esc_attr( strtotime( $order->order_date ) ); ?>"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ); ?></time>
					</td>
					<td class="order-shipto">
						<address><?php if ( $address = $order->get_formatted_shipping_address() ) echo $address; else echo '&ndash;'; ?></address>
					</td>
					<td class="order-total" width="1%">
						<?php echo $order->get_formatted_order_total(); ?>
					</td>
					<td class="order-status" style="text-align:left; white-space:nowrap;">
						<?php echo ucfirst( __( $status->name, 'woocommerce' ) ); ?>

						<?php if ( in_array( $order->status, array( 'pending', 'failed' ) ) ) : ?>

							<a href="<?php echo esc_url( $order->get_cancel_order_url() ); ?>" class="cancel" title="<?php _e( 'Click to cancel this order', 'woocommerce' ); ?>">(<?php _e( 'Cancel', 'woocommerce' ); ?>)</a>

						<?php endif; ?>
					</td>
					<td class="order-actions" style="text-align:right; white-space:nowrap;">
						<?php
							$actions = array();

							if ( in_array( $order->status, array( 'pending', 'failed' ) ) )
								$actions[] = array(
									'url'  => $order->get_checkout_payment_url(),
									'name' => __( 'Pay', 'woocommerce' )
								);

							$actions[] = array(
								'url'  => add_query_arg( 'order', $order->id, get_permalink( woocommerce_get_page_id( 'view_order' ) ) ),
								'name' => __( 'View', 'woocommerce' )
							);

							$actions = apply_filters( 'woocommerce_my_account_my_orders_actions', $actions, $order );

							foreach( $actions as $action ) {
								echo '<a href="' . esc_url( $action['url'] ) . '" class="button ' . sanitize_title( $action['name'] ) . '">' . esc_html( $action['name'] ) . '</a>';
							}
						?>
					</td>
				</tr><?php
			}
		?></tbody>

	</table>

<?php endif; ?>