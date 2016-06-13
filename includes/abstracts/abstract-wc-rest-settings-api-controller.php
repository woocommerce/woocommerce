<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for settings endpoints so common code can be shared. Handles permissions
 * and helper functions used by both settings and groups endpoints.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/Abstracts
 * @extends  WP_REST_Controller
 * @version  2.7.0
 */
class WC_REST_Settings_API_Controller extends WC_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Makes sure the current user has access to the settings APIs.
	 *
	 * @since  2.7.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_view',
				__( 'Sorry, you cannot access settings.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Filters out bad values from the settings array/filter so we
	 * only return known values via the API.
	 *
	 * @since 2.7.0
	 * @param  array $setting
	 * @return array
	 */
	public function filter_setting( $setting ) {
		$setting = array_intersect_key(
			$setting,
			array_flip( array_filter( array_keys( $setting ), array( $this, 'allowed_setting_keys' ) ) )
		);

		if ( empty( $setting['options'] ) ) {
			unset( $setting['options'] );
		}

		return $setting;
	}

	/**
	 * Callback for allowed keys for each setting response.
	 *
	 * @since  2.7.0
	 * @param  string $key Key to check
	 * @return boolean
	 */
	public function allowed_setting_keys( $key ) {
		return in_array( $key, array(
			'id', 'label', 'description', 'default', 'tip',
			'placeholder', 'type', 'options', 'value',
		) );
	}

	/**
	 * Boolean for if a setting type is a valid supported setting type.
	 *
	 * @since  2.7.0
	 * @param  string  $type
	 * @return boolean
	 */
	public function is_setting_type_valid( $type ) {
		return in_array( $type, array(
			'text', 'email', 'number', 'color', 'password',
			'textarea', 'select', 'multiselect', 'radio', 'checkbox',
			'multi_select_countries', 'image_width',
		) );
	}
}
