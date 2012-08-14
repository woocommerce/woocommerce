<?php
/**
 * Additional payment gateway settings
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Settings
 * @version     1.6.4
 */

/**
 * Output payment gateway settings.
 *
 * @access public
 * @return void
 */
function woocommerce_payment_gateways_setting() {
	global $woocommerce;
	?>
	<tr valign="top">
	    <td class="forminp" colspan="2">
			<table class="wc_gateways widefat" cellspacing="0">
				<thead>
					<tr>
						<th width="1%"><?php _e('Default', 'woocommerce'); ?></th>
						<th><?php _e('Gateway', 'woocommerce'); ?></th>
						<th><?php _e('Status', 'woocommerce'); ?></th>
					</tr>
				</thead>
				<tbody>
		        	<?php
		        	foreach ( $woocommerce->payment_gateways->payment_gateways() as $gateway ) :

		        		$default_gateway = get_option('woocommerce_default_gateway');

		        		echo '<tr>
		        			<td width="1%" class="radio">
		        				<input type="radio" name="default_gateway" value="' . $gateway->id . '" ' . checked( $default_gateway, $gateway->id, false ) . ' />
		        				<input type="hidden" name="gateway_order[]" value="' . $gateway->id . '" />
		        			</td>
		        			<td>
		        				<p><strong>' . $gateway->get_title() . '</strong><br/>
		        				<small>' . __('Gateway ID', 'woocommerce') . ': ' . $gateway->id . '</small></p>
		        			</td>
		        			<td>';

		        		if ( $gateway->enabled == 'yes' )
		        			echo '<img src="' . $woocommerce->plugin_url() . '/assets/images/success.png" alt="yes" />';
						else
							echo '<img src="' . $woocommerce->plugin_url() . '/assets/images/success-off.png" alt="no" />';

		        		echo '</td>
		        		</tr>';

		        	endforeach;
		        	?>
				</tbody>
			</table>
		</td>
	</tr>
	<?php
}

add_action( 'woocommerce_admin_field_payment_gateways', 'woocommerce_payment_gateways_setting' );