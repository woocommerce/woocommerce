<?php
/**
 * REST API General Options Settings Model
 *
 * Handles general options settings group for REST API endpoints.
 *
 * @package WooCommerce\RestApi\Models
 * @since   4.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API General Options Settings Model class.
 *
 * @package WooCommerce\RestApi\Models
 */
class WC_REST_General_Options_Model extends WC_REST_Settings_Model {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'general_options',
			__( 'General options', 'woocommerce' ),
			'',
			20
		);
	}

	/**
	 * Get the settings definitions for this group.
	 *
	 * @return array Array of setting definitions.
	 */
	public function get_settings_definitions() {
		$settings = $this->get_settings_by_group_id( 'WC_Settings_General', 'general_options' );

		$order_mapping = array(
			'woocommerce_allowed_countries'          => 10,
			'woocommerce_all_except_countries'       => 20,
			'woocommerce_specific_allowed_countries' => 30,
			'woocommerce_ship_to_countries'          => 40,
			'woocommerce_specific_ship_to_countries' => 50,
			'woocommerce_default_customer_address'   => 60,
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
		if ( 'multi_select_countries' === $field_type ) {
			return WC()->countries->get_countries();
		}

		return parent::get_field_options( $field_type, $field_id );
	}

	/**
	 * Validate setting value based on business logic.
	 * Override parent method to add general options specific validation.
	 *
	 * @param string $setting_id Setting ID.
	 * @param mixed  $value      Value to validate.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_setting_value( $setting_id, $value ) {
		switch ( $setting_id ) {
			case 'woocommerce_default_country':
				return $this->validate_country_or_state_code( $value );

			case 'woocommerce_allowed_countries':
				$valid_options = array( 'all', 'all_except', 'specific' );
				if ( ! in_array( $value, $valid_options, true ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Invalid selling location option.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_ship_to_countries':
				$valid_options = array( '', 'all', 'specific', 'disabled' );
				if ( ! in_array( $value, $valid_options, true ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Invalid shipping location option.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_specific_allowed_countries':
			case 'woocommerce_specific_ship_to_countries':
				if ( ! is_array( $value ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Expected an array of country codes.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}

				foreach ( $value as $code ) {
					if ( ! is_string( $code ) || ! $this->validate_country_or_state_code( $code ) ) {
						return new WP_Error(
							'rest_invalid_param',
							__( 'Invalid country code in list.', 'woocommerce' ),
							array( 'status' => 400 )
						);
					}
				}
				break;

			default:
				return parent::validate_setting_value( $setting_id, $value );
		}

		return true;
	}

	/**
	 * Validate country or state code.
	 *
	 * @param string $country_or_state Country or state code.
	 * @return boolean Valid or not valid.
	 */
	private function validate_country_or_state_code( $country_or_state ) {
		list( $country, $state ) = array_pad( explode( ':', (string) $country_or_state, 2 ), 2, '' );
		if ( '' === $country ) {
			return false;
		}
		$country_codes = array_keys( WC()->countries->get_countries() );
		if ( ! in_array( $country, $country_codes, true ) ) {
			return false;
		}
		if ( '' === $state ) {
			return true;
		}
		$states_for_country = WC()->countries->get_states( $country );
		if ( empty( $states_for_country ) ) {
			return false;
		}
		return isset( $states_for_country[ $state ] );
	}
}
