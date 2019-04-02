<?php
/**
 * WooCommerce General Settings
 *
 * @package WooCommerce/Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_General', false ) ) {
	return new WC_Settings_General();
}

/**
 * WC_Admin_Settings_General.
 */
class WC_Settings_General extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'general';
		$this->label = __( 'General', 'woocommerce' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {

		$currency_code_options = get_woocommerce_currencies();

		foreach ( $currency_code_options as $code => $name ) {
			$currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
		}

		$woocommerce_default_customer_address_options = array(
			''                 => __( 'No location by default', 'woocommerce' ),
			'base'             => __( 'Shop base address', 'woocommerce' ),
			'geolocation'      => __( 'Geolocate', 'woocommerce' ),
			'geolocation_ajax' => __( 'Geolocate (with page caching support)', 'woocommerce' ),
		);

		if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
			unset( $woocommerce_default_customer_address_options['geolocation'], $woocommerce_default_customer_address_options['geolocation_ajax'] );
		}

		$settings = apply_filters(
			'woocommerce_general_settings', array(

				array(
					'title' => __( 'Store Address', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'This is where your business is located. Tax rates and shipping rates will use this address.', 'woocommerce' ),
					'id'    => 'store_address',
				),

				array(
					'title'    => __( 'Address line 1', 'woocommerce' ),
					'desc'     => __( 'The street address for your business location.', 'woocommerce' ),
					'id'       => 'woocommerce_store_address',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Address line 2', 'woocommerce' ),
					'desc'     => __( 'An additional, optional address line for your business location.', 'woocommerce' ),
					'id'       => 'woocommerce_store_address_2',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'City', 'woocommerce' ),
					'desc'     => __( 'The city in which your business is located.', 'woocommerce' ),
					'id'       => 'woocommerce_store_city',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Country / State', 'woocommerce' ),
					'desc'     => __( 'The country and state or province, if any, in which your business is located.', 'woocommerce' ),
					'id'       => 'woocommerce_default_country',
					'default'  => 'GB',
					'type'     => 'single_select_country',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Postcode / ZIP', 'woocommerce' ),
					'desc'     => __( 'The postal code, if any, in which your business is located.', 'woocommerce' ),
					'id'       => 'woocommerce_store_postcode',
					'css'      => 'min-width:50px;',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'store_address',
				),

				array(
					'title' => __( 'General options', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'general_options',
				),

				array(
					'title'    => __( 'Selling location(s)', 'woocommerce' ),
					'desc'     => __( 'This option lets you limit which countries you are willing to sell to.', 'woocommerce' ),
					'id'       => 'woocommerce_allowed_countries',
					'default'  => 'all',
					'type'     => 'select',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width: 350px;',
					'desc_tip' => true,
					'options'  => array(
						'all'        => __( 'Sell to all countries', 'woocommerce' ),
						'all_except' => __( 'Sell to all countries, except for&hellip;', 'woocommerce' ),
						'specific'   => __( 'Sell to specific countries', 'woocommerce' ),
					),
				),

				array(
					'title'   => __( 'Sell to all countries, except for&hellip;', 'woocommerce' ),
					'desc'    => '',
					'id'      => 'woocommerce_all_except_countries',
					'css'     => 'min-width: 350px;',
					'default' => '',
					'type'    => 'multi_select_countries',
				),

				array(
					'title'   => __( 'Sell to specific countries', 'woocommerce' ),
					'desc'    => '',
					'id'      => 'woocommerce_specific_allowed_countries',
					'css'     => 'min-width: 350px;',
					'default' => '',
					'type'    => 'multi_select_countries',
				),

				array(
					'title'    => __( 'Shipping location(s)', 'woocommerce' ),
					'desc'     => __( 'Choose which countries you want to ship to, or choose to ship to all locations you sell to.', 'woocommerce' ),
					'id'       => 'woocommerce_ship_to_countries',
					'default'  => '',
					'type'     => 'select',
					'class'    => 'wc-enhanced-select',
					'desc_tip' => true,
					'options'  => array(
						''         => __( 'Ship to all countries you sell to', 'woocommerce' ),
						'all'      => __( 'Ship to all countries', 'woocommerce' ),
						'specific' => __( 'Ship to specific countries only', 'woocommerce' ),
						'disabled' => __( 'Disable shipping &amp; shipping calculations', 'woocommerce' ),
					),
				),

				array(
					'title'   => __( 'Ship to specific countries', 'woocommerce' ),
					'desc'    => '',
					'id'      => 'woocommerce_specific_ship_to_countries',
					'css'     => '',
					'default' => '',
					'type'    => 'multi_select_countries',
				),

				array(
					'title'    => __( 'Default customer location', 'woocommerce' ),
					'id'       => 'woocommerce_default_customer_address',
					'desc_tip' => __( 'This option determines a customers default location. The MaxMind GeoLite Database will be periodically downloaded to your wp-content directory if using geolocation.', 'woocommerce' ),
					'default'  => 'geolocation',
					'type'     => 'select',
					'class'    => 'wc-enhanced-select',
					'options'  => $woocommerce_default_customer_address_options,
				),

				array(
					'title'    => __( 'Enable taxes', 'woocommerce' ),
					'desc'     => __( 'Enable tax rates and calculations', 'woocommerce' ),
					'id'       => 'woocommerce_calc_taxes',
					'default'  => 'no',
					'type'     => 'checkbox',
					'desc_tip' => __( 'Rates will be configurable and taxes will be calculated during checkout.', 'woocommerce' ),
				),

				array(
					'title'           => __( 'Enable coupons', 'woocommerce' ),
					'desc'            => __( 'Enable the use of coupon codes', 'woocommerce' ),
					'id'              => 'woocommerce_enable_coupons',
					'default'         => 'yes',
					'type'            => 'checkbox',
					'checkboxgroup'   => 'start',
					'show_if_checked' => 'option',
					'desc_tip'        => __( 'Coupons can be applied from the cart and checkout pages.', 'woocommerce' ),
				),

				array(
					'desc'            => __( 'Calculate coupon discounts sequentially', 'woocommerce' ),
					'id'              => 'woocommerce_calc_discounts_sequentially',
					'default'         => 'no',
					'type'            => 'checkbox',
					'desc_tip'        => __( 'When applying multiple coupons, apply the first coupon to the full price and the second coupon to the discounted price and so on.', 'woocommerce' ),
					'show_if_checked' => 'yes',
					'checkboxgroup'   => 'end',
					'autoload'        => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'general_options',
				),

				array(
					'title' => __( 'Currency options', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'The following options affect how prices are displayed on the frontend.', 'woocommerce' ),
					'id'    => 'pricing_options',
				),

				array(
					'title'    => __( 'Currency', 'woocommerce' ),
					'desc'     => __( 'This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.', 'woocommerce' ),
					'id'       => 'woocommerce_currency',
					'default'  => 'GBP',
					'type'     => 'select',
					'class'    => 'wc-enhanced-select',
					'desc_tip' => true,
					'options'  => $currency_code_options,
				),

				array(
					'title'    => __( 'Currency position', 'woocommerce' ),
					'desc'     => __( 'This controls the position of the currency symbol.', 'woocommerce' ),
					'id'       => 'woocommerce_currency_pos',
					'class'    => 'wc-enhanced-select',
					'default'  => 'left',
					'type'     => 'select',
					'options'  => array(
						'left'        => __( 'Left', 'woocommerce' ),
						'right'       => __( 'Right', 'woocommerce' ),
						'left_space'  => __( 'Left with space', 'woocommerce' ),
						'right_space' => __( 'Right with space', 'woocommerce' ),
					),
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Thousand separator', 'woocommerce' ),
					'desc'     => __( 'This sets the thousand separator of displayed prices.', 'woocommerce' ),
					'id'       => 'woocommerce_price_thousand_sep',
					'css'      => 'width:50px;',
					'default'  => ',',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Decimal separator', 'woocommerce' ),
					'desc'     => __( 'This sets the decimal separator of displayed prices.', 'woocommerce' ),
					'id'       => 'woocommerce_price_decimal_sep',
					'css'      => 'width:50px;',
					'default'  => '.',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'title'             => __( 'Number of decimals', 'woocommerce' ),
					'desc'              => __( 'This sets the number of decimal points shown in displayed prices.', 'woocommerce' ),
					'id'                => 'woocommerce_price_num_decimals',
					'css'               => 'width:50px;',
					'default'           => '2',
					'desc_tip'          => true,
					'type'              => 'number',
					'custom_attributes' => array(
						'min'  => 0,
						'step' => 1,
					),
				),

				array(
					'type' => 'sectionend',
					'id'   => 'pricing_options',
				),

			)
		);

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	/**
	 * Output a color picker input box.
	 *
	 * @param mixed  $name Name of input.
	 * @param string $id ID of input.
	 * @param mixed  $value Value of input.
	 * @param string $desc (default: '') Description for input.
	 */
	public function color_picker( $name, $id, $value, $desc = '' ) {
		echo '<div class="color_box">' . wc_help_tip( $desc ) . '
			<input name="' . esc_attr( $id ) . '" id="' . esc_attr( $id ) . '" type="text" value="' . esc_attr( $value ) . '" class="colorpick" /> <div id="colorPickerDiv_' . esc_attr( $id ) . '" class="colorpickdiv"></div>
		</div>';
	}

	/**
	 * Show a notice showing where the store notice setting has moved.
	 *
	 * @since 3.3.1
	 * @todo remove in next major release.
	 */
	private function store_notice_setting_moved_notice() {
		if ( get_user_meta( get_current_user_id(), 'dismissed_store_notice_setting_moved_notice', true ) ) {
			return;
		}
		?>
		<div id="message" class="updated woocommerce-message inline">
			<a class="woocommerce-message-close notice-dismiss" style="top:0;" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'store_notice_setting_moved' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'woocommerce' ); ?></a>

			<p>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: URL to customizer. */
						__( 'Looking for the store notice setting? It can now be found <a href="%s">in the Customizer</a>.', 'woocommerce' ), esc_url(
							add_query_arg(
								array(
									'autofocus' => array(
										'panel' => 'woocommerce',
									),
									'url'       => wc_get_page_permalink( 'shop' ),
								), admin_url( 'customize.php' )
							)
						)
					), array(
						'a' => array(
							'href'  => array(),
							'title' => array(),
						),
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		$settings = $this->get_settings();

		$this->store_notice_setting_moved_notice();

		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		$settings = $this->get_settings();

		WC_Admin_Settings::save_fields( $settings );
	}
}

return new WC_Settings_General();
