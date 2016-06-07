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
	 * Cleans a value before setting it.
	 *
	 * @since  2.7.0
	 *
	 * @param  array $setting   WC Setting Array
	 * @param  mixed $raw_value Raw value from PUT request
	 * @return mixed Sanitized value
		 */
	public function sanitize_setting_value( $setting, $raw_value ) {
		switch ( $setting['type'] ) {
			case 'checkbox' :
				$default = ( ! empty( $setting['default'] ) ? $setting['default'] : 'no' );
				$value   = ( in_array( $raw_value, array( 'yes', 'no' ) ) ? $raw_value : $default );
				break;
			case 'email' :
				$value   = sanitize_email( $raw_value );
				$default = ( ! empty( $setting['default'] ) ? $setting['default'] : '' );
				$value   = ( ! empty( $value ) ? $value : $default );
				break;
			case 'textarea' :
				$value = wp_kses( trim( $raw_value ),
					array_merge(
						array(
							'iframe' => array( 'src' => true, 'style' => true, 'id' => true, 'class' => true )
						),
						wp_kses_allowed_html( 'post' )
					)
				);
				break;
			case 'multiselect' :
			case 'multi_select_countries' :
				$value = array_filter( array_map( 'wc_clean', (array) $raw_value ) );
				break;
			case 'image_width' :
				$value = array();
				if ( isset( $raw_value['width'] ) ) {
					$value['width']  = wc_clean( $raw_value['width'] );
					$value['height'] = wc_clean( $raw_value['height'] );
					$value['crop']   = isset( $raw_value['crop'] ) ? 1 : 0;
				} else {
					$value['width']  = $setting['default']['width'];
					$value['height'] = $setting['default']['height'];
					$value['crop']   = $setting['default']['crop'];
				}
				break;
			case 'select':
				$options = array_keys( $setting['options'] );
				$default = ( empty( $setting['default'] ) ? $options[0] : $setting['default'] );
				$value   = in_array( $raw_value, $options ) ? $raw_value : $default;
				break;
			default :
				$value = wc_clean( $raw_value );
				break;
		}

		// A couple fields changed in the REST API -- we can just pass these too so old filters still work
		$setting['desc']  = ( ! empty( $setting['description'] ) ? $setting['description'] : '' );
		$setting['title'] = ( ! empty( $setting['label'] ) ? $setting['label'] : '' );

		$value = apply_filters( 'woocommerce_admin_settings_sanitize_option', $value, $setting, $raw_value );
		$value = apply_filters( "woocommerce_admin_settings_sanitize_option_" . $setting['id'], $value, $setting, $raw_value );
		do_action( 'woocommerce_update_option', $setting );

		return $value;
	}

	/**
	 * Get a value from WP's settings API.
	 *
	 * @since  2.7.0
	 * @param  string $setting
	 * @param  string $default
	 * @return mixed
	 */
	public function get_value( $setting, $default = '' ) {
		if ( strstr( $setting, '[' ) ) { // Array value.
			parse_str( $setting, $setting_array );
			$setting = current( array_keys( $setting ) );
			$values  = get_option( $setting, '' );
			$key     = key( $setting_array[ $setting ] );
			$value   = isset( $values[ $key ] ) ? $values[ $key ] : null;
		} else { // Single value.
			$value = get_option( $setting, null );
		}

		if ( is_array( $value ) ) {
			$value = array_map( 'stripslashes', $value );
		} elseif ( ! is_null( $value ) ) {
			$value = stripslashes( $value );
		}

		return $value === null ? $default : $value;
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
