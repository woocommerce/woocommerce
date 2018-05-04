<?php // @codingStandardsIgnoreLine.
/**
 * WooCommerce Checkout Settings
 *
 * @package WooCommerce/Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_Payment_Gateways', false ) ) {
	return new WC_Settings_Payment_Gateways();
}

/**
 * WC_Settings_Payment_Gateways.
 */
class WC_Settings_Payment_Gateways extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'checkout'; // @todo In future versions this may make more sense as 'payment' however to avoid breakage lets leave this alone until we refactor settings APIs in general.
		$this->label = _x( 'Payments', 'Settings tab label', 'woocommerce' );

		add_action( 'woocommerce_admin_field_payment_gateways', array( $this, 'payment_gateways_setting' ) );
		parent::__construct();
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'' => __( 'Payment methods', 'woocommerce' ),
		);
		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Section being shown.
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = apply_filters(
				'woocommerce_payment_gateways_settings', array(
					array(
						'title' => __( 'Payment methods', 'woocommerce' ),
						'desc'  => __( 'Installed payment methods are listed below. Drag and drop gateways to control their display order on the frontend.', 'woocommerce' ),
						'type'  => 'title',
						'id'    => 'payment_gateways_options',
					),
					array(
						'type' => 'payment_gateways',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'payment_gateways_options',
					),
				)
			);
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		// Load gateways so we can show any global options they may have.
		$payment_gateways = WC()->payment_gateways->payment_gateways();

		if ( $current_section ) {
			foreach ( $payment_gateways as $gateway ) {
				if ( in_array( $current_section, array( $gateway->id, sanitize_title( get_class( $gateway ) ) ), true ) ) {
					if ( isset( $_GET['toggle_enabled'] ) ) { // WPCS: input var ok, CSRF ok.
						$enabled = $gateway->get_option( 'enabled' );

						if ( $enabled ) {
							$gateway->settings['enabled'] = wc_string_to_bool( $enabled ) ? 'no' : 'yes';
						}
					}
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
	 */
	public function payment_gateways_setting() {
		?>
		<tr valign="top">
		<td class="wc_payment_gateways_wrapper" colspan="2">
			<table class="wc_gateways widefat" cellspacing="0">
				<thead>
					<tr>
						<?php
						$default_columns = array(
							'sort'        => '',
							'name'        => __( 'Method', 'woocommerce' ),
							'status'      => __( 'Enabled', 'woocommerce' ),
							'description' => __( 'Description', 'woocommerce' ),
							'action'      => '',
						);

						$columns = apply_filters( 'woocommerce_payment_gateways_setting_columns', $default_columns );

						foreach ( $columns as $key => $column ) {
							echo '<th class="' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
						}
						?>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( WC()->payment_gateways->payment_gateways() as $gateway ) {

							echo '<tr data-gateway_id="' . esc_attr( $gateway->id ) . '">';

							foreach ( $columns as $key => $column ) {
								if ( ! array_key_exists( $key, $default_columns ) ) {
									do_action( 'woocommerce_payment_gateways_setting_column_' . $key, $gateway );
									continue;
								}

								$width = '';

								if ( in_array( $key, array( 'sort', 'status', 'action' ), true ) ) {
									$width = '1%';
								}

								echo '<td class="' . esc_attr( $key ) . '" width="' . esc_attr( $width ) . '">';

								switch ( $key ) {
									case 'sort':
										echo '<input type="hidden" name="gateway_order[]" value="' . esc_attr( $gateway->id ) . '" />';
										break;
									case 'name':
										$method_title = $gateway->get_title() ? $gateway->get_title() : __( '(no title)', 'woocommerce' );
										echo '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $gateway->id ) ) ) . '" class="wc-payment-gateway-method-title">' . wp_kses_post( $gateway->get_method_title() ) . '</a>';
										if ( $method_title !== $gateway->get_method_title() ) {
											echo '<span class="wc-payment-gateway-method-name">&nbsp;&ndash;&nbsp;' . esc_html( $method_title ) . '</span>';
										}
										break;
									case 'description':
										echo wp_kses_post( $gateway->get_method_description() );
										break;
									case 'action':
										if ( wc_string_to_bool( $gateway->enabled ) ) {
											echo '<a class="button alignright" href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $gateway->id ) ) ) . '">' . esc_html__( 'Manage', 'woocommerce' ) . '</a>';
										} else {
											echo '<a class="button alignright" href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $gateway->id ) ) ) . '">' . esc_html__( 'Set up', 'woocommerce' ) . '</a>';
										}
										break;
									case 'status':
										echo '<a class="wc-payment-gateway-method-toggle-enabled" href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $gateway->id ) ) ) . '">';
										if ( wc_string_to_bool( $gateway->enabled ) ) {
											echo '<span class="woocommerce-input-toggle woocommerce-input-toggle--enabled">' . esc_attr__( 'Yes', 'woocommerce' ) . '</span>';
										} else {
											echo '<span class="woocommerce-input-toggle woocommerce-input-toggle--disabled">' . esc_attr__( 'No', 'woocommerce' ) . '</span>';
										}
										echo '</a>';
										break;
								}

								echo '</td>';
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
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$wc_payment_gateways = WC_Payment_Gateways::instance();

		if ( ! $current_section ) {
			WC_Admin_Settings::save_fields( $this->get_settings() );
			$wc_payment_gateways->process_admin_options();
			$wc_payment_gateways->init();
		} else {
			foreach ( $wc_payment_gateways->payment_gateways() as $gateway ) {
				if ( in_array( $current_section, array( $gateway->id, sanitize_title( get_class( $gateway ) ) ), true ) ) {
					do_action( 'woocommerce_update_options_payment_gateways_' . $gateway->id );
					$wc_payment_gateways->init();
				}
			}
		}

		if ( $current_section ) {
			do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section );
		}
	}
}

return new WC_Settings_Payment_Gateways();
