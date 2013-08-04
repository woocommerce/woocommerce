<?php
/**
 * WooCommerce Shipping Settings
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Settings_Payment_Gateways' ) ) :

/**
 * WC_Settings_Payment_Gateways
 */
class WC_Settings_Payment_Gateways extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'payment_gateways';
		$this->label = __( 'Gateways', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_admin_field_payment_gateways', array( $this, 'payment_gateways_setting' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''         => __( 'Payment Gateways', 'woocommerce' )
		);

		// Load shipping methods so we can show any global options they may have
		$payment_gateways = WC()->payment_gateways->payment_gateways();

		foreach ( $payment_gateways as $gateway ) {

			$title = empty( $gateway->method_title ) ? ucwords( $gateway->id ) : ucwords( $gateway->method_title );

			$sections[ strtolower( get_class( $gateway ) ) ] = esc_html( $title );
		}

		return $sections;
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		return apply_filters( 'woocommerce_payment_gateways_settings', array(

			array( 'title' => __( 'Payment Gateways', 'woocommerce' ), 'desc' => __( 'Installed payment gateways are displayed below. Drag and drop payment gateways to control their display order on the checkout.', 'woocommerce' ), 'type' => 'title', 'id' => 'payment_gateways_options' ),

			array(
				'type' 		=> 'payment_gateways',
			),

			array( 'type' => 'sectionend', 'id' => 'payment_gateways_options' ),

		)); // End payment_gateway settings
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		// Load shipping methods so we can show any global options they may have
		$payment_gateways = WC()->payment_gateways->payment_gateways();

		if ( $current_section ) {
 			foreach ( $payment_gateways as $gateway ) {
				if ( strtolower( get_class( $gateway ) ) == $current_section ) {
					$gateway->admin_options();
					break;
				}
			}
 		} else {
			$settings = $this->get_settings();

			WC_Admin_Settings::output_fields( $settings );
		}
	}

	/**
	 * Output payment gateway settings.
	 *
	 * @access public
	 * @return void
	 */
	public function payment_gateways_setting() {
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

			        	foreach ( WC()->payment_gateways->payment_gateways() as $gateway ) {

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

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;

		if ( ! $current_section ) {

			$settings = $this->get_settings();

			WC_Admin_Settings::save_fields( $settings );
			WC()->payment_gateways->process_admin_options();

		} elseif ( class_exists( $current_section ) ) {

			$current_section_class = new $current_section();

			do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section_class->id );

			WC()->payment_gateways()->init();
		}
	}
}

endif;

return new WC_Settings_Payment_Gateways();