<?php
/**
 * REST API Store Address Settings Model
 *
 * Handles store address settings group for REST API endpoints.
 *
 * @package WooCommerce\RestApi\Models
 * @since   4.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Store Address Settings Model class.
 *
 * @package WooCommerce\RestApi\Models
 */
class WC_REST_Store_Address_Model extends WC_REST_Settings_Model {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'store_address',
			__( 'Store Address', 'woocommerce' ),
			__( 'This is where your business is located. Tax rates and shipping rates will use this address.', 'woocommerce' ),
			10
		);
	}

	/**
	 * Get the settings definitions for this group.
	 *
	 * @return array Array of setting definitions.
	 */
	public function get_settings_definitions() {
		$settings = $this->get_settings_by_group_id( 'WC_Settings_General', 'store_address' );

		$order_mapping = array(
			'woocommerce_default_country' => 10,
			'woocommerce_store_city'      => 20,
			'woocommerce_store_postcode'  => 30,
			'woocommerce_store_address'   => 40,
			'woocommerce_store_address_2' => 50,
		);

		foreach ( $settings as &$setting ) {
			if ( isset( $setting['id'] ) && isset( $order_mapping[ $setting['id'] ] ) ) {
				$setting['order'] = $order_mapping[ $setting['id'] ];
			}
		}

		return $settings;
	}

	/**
	 * Get options for specific field types.
	 *
	 * @param string $field_type Field type.
	 * @param string $field_id   Field ID.
	 * @return array Field options.
	 */
	protected function get_field_options( $field_type, $field_id ) {
		if ( 'single_select_country' === $field_type ) {
			return $this->get_country_state_options();
		}

		return parent::get_field_options( $field_type, $field_id );
	}

	/**
	 * Get country/state options for single select country field.
	 *
	 * @return array Country/state options.
	 */
	private function get_country_state_options() {
		$countries             = WC()->countries->get_countries();
		$states                = WC()->countries->get_states();
		$country_state_options = array();

		foreach ( $countries as $country_code => $country_name ) {
			$country_states = $states[ $country_code ] ?? array();

			if ( empty( $country_states ) ) {
				$country_state_options[ $country_code ] = $country_name;
			} else {
				foreach ( $country_states as $state_code => $state_name ) {
					$country_state_options[ $country_code . ':' . $state_code ] = $country_name . ' — ' . $state_name;
				}
			}
		}

		return $country_state_options;
	}

	/**
	 * Validate setting value based on business logic.
	 * Override parent method to add store address specific validation.
	 *
	 * @param string $setting_id Setting ID.
	 * @param mixed  $value      Value to validate.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_setting_value( $setting_id, $value ) {
		switch ( $setting_id ) {
			case 'woocommerce_default_country':
				return $this->validate_country_setting( $value );

			case 'woocommerce_store_city':
				return $this->validate_city_setting( $value );

			case 'woocommerce_store_postcode':
				return $this->validate_postcode_setting( $value );

			case 'woocommerce_store_address':
			case 'woocommerce_store_address_2':
				return $this->validate_address_setting( $value );

			default:
				return parent::validate_setting_value( $setting_id, $value );
		}
	}

	/**
	 * Validate country setting.
	 *
	 * @param string $value Country value.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_country_setting( $value ) {
		if ( empty( $value ) ) {
			return new WP_Error(
				'invalid_country',
				__( 'Country is required for store address.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Check if country exists in WooCommerce countries list.
		$countries    = WC()->countries->get_countries();
		$country_code = strpos( $value, ':' ) !== false ? explode( ':', $value )[0] : $value;

		if ( ! array_key_exists( $country_code, $countries ) ) {
			return new WP_Error(
				'invalid_country',
				__( 'Invalid country code provided.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// If state is provided, validate it exists for the country.
		if ( strpos( $value, ':' ) !== false ) {
			$state_code = explode( ':', $value )[1];
			$states     = WC()->countries->get_states( $country_code );

			if ( empty( $states ) || ! array_key_exists( $state_code, $states ) ) {
				return new WP_Error(
					'invalid_state',
					__( 'Invalid state code provided for the selected country.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Validate city setting.
	 *
	 * @param string $value City value.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_city_setting( $value ) {
		if ( empty( trim( $value ) ) ) {
			return new WP_Error(
				'invalid_city',
				__( 'City is required for store address.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Check city length (reasonable limits).
		if ( strlen( $value ) > 100 ) {
			return new WP_Error(
				'invalid_city',
				__( 'City name is too long. Maximum 100 characters allowed.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Validate postcode setting.
	 *
	 * @param string $value Postcode value.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_postcode_setting( $value ) {
		// Postcode is optional, but if provided, validate format.
		if ( empty( trim( $value ) ) ) {
			return true;
		}

		// Check postcode length (reasonable limits).
		if ( strlen( $value ) > 20 ) {
			return new WP_Error(
				'invalid_postcode',
				__( 'Postcode is too long. Maximum 20 characters allowed.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Validate address setting.
	 *
	 * @param string $value Address value.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_address_setting( $value ) {
		// Address is optional, but if provided, validate format.
		if ( empty( trim( $value ) ) ) {
			return true;
		}

		// Check address length (reasonable limits).
		if ( strlen( $value ) > 200 ) {
			return new WP_Error(
				'invalid_address',
				__( 'Address is too long. Maximum 200 characters allowed.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}
}
