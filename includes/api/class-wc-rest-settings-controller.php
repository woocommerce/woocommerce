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
class WC_Rest_Settings_Controller extends WC_REST_Settings_API_Controller {

	/**
	 * WP REST API namespace/version.
	 */
	protected $namespace = 'wc/v1';

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
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Return a single setting.
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$setting = $this->get_setting( $request['group'], $request['setting'] );

		if ( is_wp_error( $setting ) ) {
			return $setting;
		}

		$response = $this->prepare_item_for_response( $setting, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Get setting data.
	 *
	 * @param string $group Group ID.
	 * @param string $setting_id Setting ID.
	 *
	 * @return stdClass|WP_Error
	 */
	public function get_setting( $group, $setting_id ) {
		if ( empty( $group ) || empty( $setting_id ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$settings  = apply_filters( 'woocommerce_settings-' . $group, array() );
		$array_key = array_keys( wp_list_pluck( $settings, 'id' ), $setting_id );

		if ( empty( $array_key ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$setting = $settings[ $array_key[0] ];

		if ( ! $this->is_setting_type_valid( $setting['type'] ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $setting;
	}

	/**
	 * Update a single setting in a group.
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$setting = $this->get_setting( $request['group'], $request['setting'] );

		if ( is_wp_error( $setting ) ) {
			return $setting;
		}

		$response          = $this->prepare_item_for_response( $setting, $request );
		$value             = $this->sanitize_setting_value( $setting, $request['value'] );
		$response['value'] = $value;

		update_option( $setting['id'], $value );

		return rest_ensure_response( $response );
	}

	/**
	 * Prepare a single setting object for response.
	 *
	 * @param object $item Setting object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$setting          = $this->filter_setting( $item );
		$setting['value'] = $this->get_value( $setting['id'] );

		return $setting;
	}

	/**
	 * Get the settings schema, conforming to JSON Schema.
	 *
	 * @since 2.7.0
	 * @return array
	 */
	public function get_item_schema() {
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
