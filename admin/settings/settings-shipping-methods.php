<?php
/**
 * Additional shipping settings
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Settings
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Output shipping method settings.
 *
 * @access public
 * @return void
 */
function woocommerce_shipping_methods_setting() {
	global $woocommerce;

	$default_shipping_method = esc_attr( get_option('woocommerce_default_shipping_method') );
	?>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php _e( 'Shipping Methods', 'woocommerce' ) ?></th>
	    <td class="forminp">
	    	<p class="description" style="margin-top: 0;"><?php _e( 'Drag and drop methods to control their display order.', 'woocommerce' ); ?></p>
			<table class="wc_shipping widefat" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e( 'Default', 'woocommerce' ); ?></th>
						<th><?php _e( 'Shipping Method', 'woocommerce' ); ?></th>
						<th><?php _e( 'Status', 'woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
			    	<?php
			    	foreach ( $woocommerce->shipping->load_shipping_methods() as $method ) {

				    	echo '<tr>
				    		<td width="1%" class="radio">
				    			<input type="radio" name="default_shipping_method" value="' . $method->id . '" ' . checked( $default_shipping_method, $method->id, false ) . ' />
				    			<input type="hidden" name="method_order[]" value="' . $method->id . '" />
				    			<td>
				    				<p><strong>' . $method->get_title() . '</strong><br/>
				    				<small>' . __( 'Method ID', 'woocommerce' ) . ': ' . $method->id . '</small></p>
				    			</td>
				    			<td>';

			    		if ($method->enabled == 'yes')
			    			echo '<img src="' . $woocommerce->plugin_url() . '/assets/images/success@2x.png" width="16 height="14" alt="yes" />';
						else
							echo '<img src="' . $woocommerce->plugin_url() . '/assets/images/success-off@2x.png" width="16" height="14" alt="no" />';

			    		echo '</td>
			    		</tr>';

			    	}
			    	?>
				</tbody>
			</table>
		</td>
	</tr>
	<?php
}

add_action( 'woocommerce_admin_field_shipping_methods', 'woocommerce_shipping_methods_setting' );