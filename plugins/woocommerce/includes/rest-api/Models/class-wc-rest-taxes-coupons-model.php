<?php
/**
 * REST API Taxes and Coupons Settings Model
 *
 * Handles taxes and coupons settings group for REST API endpoints.
 *
 * @package WooCommerce\RestApi\Models
 * @since   4.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Taxes and Coupons Settings Model class.
 *
 * @package WooCommerce\RestApi\Models
 */
class WC_REST_Taxes_Coupons_Model extends WC_REST_Settings_Model {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'taxes_and_coupons_options',
			__( 'Taxes and coupons', 'woocommerce' ),
			__( 'Enable taxes and coupons and configure how they are calculated.', 'woocommerce' ),
			30
		);
	}

	/**
	 * Get the settings definitions for this group.
	 *
	 * @return array Array of setting definitions.
	 */
	public function get_settings_definitions() {
		$settings = $this->get_settings_by_group_id( 'WC_Settings_General', 'taxes_and_coupons_options' );

		$order_mapping = array(
			'woocommerce_calc_taxes'                  => 10,
			'woocommerce_enable_coupons'              => 20,
			'woocommerce_calc_discounts_sequentially' => 30,
		);

		foreach ( $settings as &$setting ) {
			if ( isset( $setting['id'] ) && isset( $order_mapping[ $setting['id'] ] ) ) {
				$setting['order'] = $order_mapping[ $setting['id'] ];
			}
		}

		return $settings;
	}

	/**
	 * Validate setting value based on business logic.
	 * Override parent method to add taxes and coupons specific validation.
	 *
	 * @param string $setting_id Setting ID.
	 * @param mixed  $value      Value to validate.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_setting_value( $setting_id, $value ) {
		switch ( $setting_id ) {
			case 'woocommerce_calc_taxes':
			case 'woocommerce_enable_coupons':
			case 'woocommerce_calc_discounts_sequentially':
				// Validate boolean/checkbox values.
				$valid_values = array( 'yes', 'no', true, false, '1', '0', 1, 0 );
				if ( ! in_array( $value, $valid_values, true ) ) {
					return new WP_Error(
						'rest_invalid_param',
						// translators: %s is the setting ID.
						sprintf( __( 'Invalid value for %s. Expected yes/no or true/false.', 'woocommerce' ), $setting_id ),
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
