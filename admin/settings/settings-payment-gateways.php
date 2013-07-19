<?php
/**
 * Additional payment gateway settings
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Settings
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
						<?php
							$columns = apply_filters( 'woocommerce_payment_gateways_setting_columns', array(
								'default' => __( 'Default', 'woocommerce' ),
								'gateway' => __( 'Gateway', 'woocommerce' ),
								'status'  => __( 'Status', 'woocommerce' )
							) );

							foreach ( $columns as $key => $column ) {
								echo '<th class="' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
							}
						?>
					</tr>
				</thead>
				<tbody>
		        	<?php
		        	$default_gateway = get_option( 'woocommerce_default_gateway' );

		        	foreach ( $woocommerce->payment_gateways->payment_gateways() as $gateway ) {

		        		echo '<tr>';

		        		foreach ( $columns as $key => $column ) {
							switch ( $key ) {
								case 'default' :
									echo '<td width="1%" class="radio">
				        				<input type="radio" name="default_gateway" value="' . esc_attr( $gateway->id ) . '" ' . checked( $default_gateway, esc_attr( $gateway->id ), false ) . ' />
				        				<input type="hidden" name="gateway_order[]" value="' . esc_attr( $gateway->id ) . '" />
				        			</td>';
								break;
								case 'gateway' :
									echo '<td>
				        				<p><strong>' . $gateway->get_title() . '</strong><br/>
				        				<small>' . __( 'Gateway ID', 'woocommerce' ) . ': ' . esc_html( $gateway->id ) . '</small></p>
				        			</td>';
								break;
								case 'status' :
									echo '<td class="status">';

					        		if ( $gateway->enabled == 'yes' )
					        			echo '<span class="status-enabled tips" data-tip="' . __ ( 'Enabled', 'woocommerce' ) . '">' . __ ( 'Enabled', 'woocommerce' ) . '</span>';
									else
					        			echo '<span class="status-disabled tips" data-tip="' . __ ( 'Disabled', 'woocommerce' ) . '">' . __ ( 'Disabled', 'woocommerce' ) . '</span>';

					        		echo '</td>';
								break;
								default :
									do_action( 'woocommerce_payment_gateways_setting_column_' . $key, $gateway );
								break;
							}
						}

						echo '</tr>';
		        	}
		        	?>
				</tbody>
			</table>
		</td>
	</tr>
	<?php
}

add_action( 'woocommerce_admin_field_payment_gateways', 'woocommerce_payment_gateways_setting' );