<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Settings Controller.
 * Handles requests to the /settings/$group/$setting endpoints.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @version  2.7.0
 * @since    2.7.0
 */
class WC_Rest_Settings_Controller extends WP_Rest_Settings_Base {

	/**
	 * WP REST API namespace/version.
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Register routes.
	 * @since 2.7.0
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<group>[\w-]+)/(?P<setting>[\w-]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'edit_item' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'setting_schema' ),
		) );
	}

	/**
	 * Return a single setting.
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$setting = $this->_get_setting_from_request( $request );
		if ( is_wp_error( $setting ) ) {
			return $setting;
		}
		return rest_ensure_response( $setting );
	}

	/**
	 * Update a single setting.
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function edit_item( $request ) {
		$setting = $this->_get_setting_from_request( $request );
		if ( is_wp_error( $setting ) ) {
			return $setting;
		}

		$value = $setting['value'] = $this->sanitize_setting_value( $setting, $request['value'] );
		update_option( $setting['id'], $value );

		return rest_ensure_response( $setting );
	}

	/**
	 * Takes a valid request and returns back the corresponding setting array.
	 * @since 2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|array
	 */
	private function _get_setting_from_request( $request ) {
		if ( empty( $request['group'] ) || empty( $request['setting'] ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$settings  = apply_filters( 'woocommerce_settings-' . $request['group'], array() );
		$array_key = array_keys( wp_list_pluck( $settings, 'id' ), $request['setting'] );

		if ( empty( $array_key ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$setting          = $this->filter_setting( $settings[ $array_key[0] ] );
		$setting['value'] = $this->get_value( $setting['id'] );

		if ( ! $this->is_setting_type_valid( $setting['type'] ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $setting;
	}

	/**
	 * Get the settings schema, conforming to JSON Schema.
	 * @since  2.7.0
	 * @return array
	 */
	public function setting_schema() {
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'settings',
			'type'                 => 'object',
			'properties'           => array(
				'id'               => array(
					'description'  => __( 'A unique identifier for the setting.', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'label'            => array(
					'description'  => __( 'A human readable label. This is a translated string that can be used in interfaces.', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'description'      => array(
					'description'  => __( 'A human readable description. This is a translated string that can be used in interfaces.', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'default'          => array(
					'description'  => __( 'Default value for the setting.', 'woocommerce' ),
					'type'         => 'mixed',
				),
				'tip'              => array(
					'description'  => __( 'Extra help text explaining the setting.', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'placeholder'      => array(
					'description'  => __( 'Placeholder text to be displayed in text inputs.', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'type'             => array(
					'description'  => __( 'Type of setting. Allowed values: text, email, number, color, password, textarea, select, multiselect, radio, image_width, checkbox.', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'options'          => array(
					'description'  => __( 'Array of options (key value pairs) for inputs such as select, multiselect, and radio buttons.', 'woocommerce' ),
					'type'         => 'array',
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

}
