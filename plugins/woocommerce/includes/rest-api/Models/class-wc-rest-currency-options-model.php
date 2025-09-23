<?php
/**
 * REST API Currency Options Settings Model
 *
 * Handles currency options settings group for REST API endpoints.
 *
 * @package WooCommerce\RestApi\Models
 * @since   4.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Currency Options Settings Model class.
 *
 * @package WooCommerce\RestApi\Models
 */
class WC_REST_Currency_Options_Model extends WC_REST_Settings_Model {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'pricing_options',
			__( 'Currency options', 'woocommerce' ),
			__( 'The following options affect how prices are displayed on the frontend.', 'woocommerce' ),
			40
		);
	}

	/**
	 * Get the settings definitions for this group.
	 *
	 * @return array Array of setting definitions.
	 */
	public function get_settings_definitions() {
		$settings = $this->get_settings_by_group_id( 'WC_Settings_General', 'pricing_options' );

		$order_mapping = array(
			'woocommerce_currency'           => 10,
			'woocommerce_price_thousand_sep' => 20,
			'woocommerce_price_decimal_sep'  => 30,
			'woocommerce_currency_pos'       => 40,
			'woocommerce_price_num_decimals' => 50,
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
		if ( 'select' === $field_type && 'woocommerce_currency' === $field_id ) {
			return $this->get_currency_options();
		}

		return parent::get_field_options( $field_type, $field_id );
	}

	/**
	 * Get currency options.
	 *
	 * @return array Currency options.
	 */
	private function get_currency_options() {
		$currency_options = array();
		$currencies       = get_woocommerce_currencies();

		foreach ( $currencies as $code => $name ) {
			$label                     = function_exists( 'wp_specialchars_decode' ) ? wp_specialchars_decode( (string) $name ) : (string) $name;
			$symbol                    = function_exists( 'wp_specialchars_decode' ) ? wp_specialchars_decode( (string) get_woocommerce_currency_symbol( $code ) ) : (string) get_woocommerce_currency_symbol( $code );
			$currency_options[ $code ] = $label . ' (' . $symbol . ') — ' . $code;
		}

		return $currency_options;
	}

	/**
	 * Validate setting value based on business logic.
	 * Override parent method to add currency options specific validation.
	 *
	 * @param string $setting_id Setting ID.
	 * @param mixed  $value      Value to validate.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_setting_value( $setting_id, $value ) {
		switch ( $setting_id ) {
			case 'woocommerce_price_num_decimals':
				if ( ! is_numeric( $value ) || $value < 0 || $value > 10 ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Number of decimals must be between 0 and 10.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_currency':
				// Validate currency code exists in WooCommerce currencies.
				$currencies = get_woocommerce_currencies();
				if ( ! array_key_exists( $value, $currencies ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Invalid currency code provided.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_currency_pos':
				$valid_positions = array( 'left', 'right', 'left_space', 'right_space' );
				if ( ! in_array( $value, $valid_positions, true ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Invalid currency position provided.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_price_thousand_sep':
			case 'woocommerce_price_decimal_sep':
				// Validate separator is a single character.
				if ( strlen( $value ) > 1 ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Price separator must be a single character.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			default:
				return parent::validate_setting_value( $setting_id, $value );
		}

		return true;
	}
}
