<?php
/**
 * Email Addresses
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails
 * @version     2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?><table id="addresses" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top;" border="0">

	<tr>

		<td class="td" style="text-align:left; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" valign="top" width="50%">

			<h3><?php _e( 'Billing address', 'woocommerce' ); ?></h3>

			<p class="text"><?php echo $order->get_formatted_billing_address(); ?></p>

		</td>

		<?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && ( $shipping = $order->get_formatted_shipping_address() ) ) : ?>

		<td class="td" style="text-align:left; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" valign="top" width="50%">

			<h3><?php _e( 'Shipping address', 'woocommerce' ); ?></h3>

			<p class="text"><?php echo $shipping; ?></p>

		</td>

		<?php endif; ?>

	</tr>

</table>
