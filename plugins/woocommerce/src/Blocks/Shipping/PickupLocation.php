<?php
namespace Automattic\WooCommerce\Blocks\Shipping;

use WC_Shipping_Method;

/**
 * Local Pickup Shipping Method.
 */
class PickupLocation extends WC_Shipping_Method {

	/**
	 * Pickup locations.
	 *
	 * @var array
	 */
	protected $pickup_locations = [];

	/**
	 * Cost
	 *
	 * @var string
	 */
	protected $cost = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->id                 = 'pickup_location';
		$this->method_title       = __( 'Local pickup', 'woocommerce' );
		$this->method_description = __( 'Allow customers to choose a local pickup location during checkout.', 'woocommerce' );
		$this->init();
	}

	/**
	 * Init function.
	 */
	public function init() {
		$this->enabled          = $this->get_option( 'enabled' );
		$this->title            = $this->get_option( 'title', __( 'Pickup', 'woocommerce' ) );
		$this->tax_status       = $this->get_option( 'tax_status' );
		$this->cost             = $this->get_option( 'cost' );
		$this->supports         = [ 'settings', 'local-pickup' ];
		$this->pickup_locations = get_option( $this->id . '_pickup_locations', [] );
		add_filter( 'woocommerce_attribute_label', array( $this, 'translate_meta_data' ), 10, 3 );
	}

	/**
	 * Checks if a given address is complete.
	 *
	 * @param array $address Address.
	 * @return bool
	 */
	protected function has_valid_pickup_location( $address ) {
		// Normalize address.
		$address_fields = wp_parse_args(
			(array) $address,
			array(
				'city'     => '',
				'postcode' => '',
				'state'    => '',
				'country'  => '',
			)
		);

		// Country is always required.
		if ( empty( $address_fields['country'] ) ) {
			return false;
		}

		// If all fields are provided, we can skip further checks.
		if ( ! empty( $address_fields['city'] ) && ! empty( $address_fields['postcode'] ) && ! empty( $address_fields['state'] ) ) {
			return true;
		}

		// Check validity based on requirements for the country.
		$country_address_fields = wc()->countries->get_address_fields( $address_fields['country'], 'shipping_' );

		foreach ( $country_address_fields as $field_name => $field ) {
			$key = str_replace( 'shipping_', '', $field_name );

			if ( isset( $address_fields[ $key ] ) && true === $field['required'] && empty( $address_fields[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Calculate shipping.
	 *
	 * @param array $package Package information.
	 */
	public function calculate_shipping( $package = array() ) {
		if ( $this->pickup_locations ) {
			foreach ( $this->pickup_locations as $index => $location ) {
				if ( ! $location['enabled'] ) {
					continue;
				}
				$this->add_rate(
					array(
						'id'        => $this->id . ':' . $index,
						// This is the label shown in shipping rate/method context e.g. London (Local Pickup).
						'label'     => wp_kses_post( $this->title . ' (' . $location['name'] . ')' ),
						'package'   => $package,
						'cost'      => $this->cost,
						'meta_data' => array(
							'pickup_location' => wp_kses_post( $location['name'] ),
							'pickup_address'  => $this->has_valid_pickup_location( $location['address'] ) ? wc()->countries->get_formatted_address( $location['address'], ', ' ) : '',
							'pickup_details'  => wp_kses_post( $location['details'] ),
						),
					)
				);
			}
		}
	}

	/**
	 * See if the method is available.
	 *
	 * @param array $package Package information.
	 * @return bool
	 */
	public function is_available( $package ) {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', 'yes' === $this->enabled, $package, $this );
	}

	/**
	 * Translates meta data for the shipping method.
	 *
	 * @param string $label Meta label.
	 * @param string $name Meta key.
	 * @param mixed  $product Product if applicable.
	 * @return string
	 */
	public function translate_meta_data( $label, $name, $product ) {
		if ( $product ) {
			return $label;
		}
		switch ( $name ) {
			case 'pickup_location':
				return __( 'Pickup location', 'woocommerce' );
			case 'pickup_address':
				return __( 'Pickup address', 'woocommerce' );
			case 'pickup_details':
				return __( 'Pickup details', 'woocommerce' );
		}
		return $label;
	}

	/**
	 * Admin options screen.
	 *
	 * See also WC_Shipping_Method::admin_options().
	 */
	public function admin_options() {
		global $hide_save_button;
		$hide_save_button = true;

		wp_enqueue_script( 'wc-shipping-method-pickup-location' );

		echo '<h2>' . esc_html__( 'Local pickup', 'woocommerce' ) . '</h2>';
		echo '<div class="wrap"><div id="wc-shipping-method-pickup-location-settings-container"></div></div>';
	}
}
