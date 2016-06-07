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
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<group>[\w-]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<group>[\w-]+)/batch', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'batch_items' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'get_public_batch_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<group>[\w-]+)/(?P<id>[\w-]+)', array(
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
		$setting = $this->get_setting( $request['group'], $request['id'] );

		if ( is_wp_error( $setting ) ) {
			return $setting;
		}

		$response = $this->prepare_item_for_response( $setting, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Return all settings in a group.
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$settings = $this->get_group_settings( $request['group'] );

		if ( is_wp_error( $settings ) ) {
			return $settings;
		}

		$data = array();

		foreach ( $settings as $setting_obj ) {
			$setting = $this->prepare_item_for_response( $setting_obj, $request );
			$setting = $this->prepare_response_for_collection( $setting );
			$data[]  = $setting;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get all settings in a group.
	 *
	 * @param string $group_id Group ID.
	 *
	 * @return array|WP_Error
	 */
	public function get_group_settings( $group_id ) {
		if ( empty( $group_id ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$settings = apply_filters( 'woocommerce_settings-' . $group_id, array() );

		if ( empty( $settings ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$filtered_settings = array();

		foreach ( $settings as $setting ) {
			$setting = $this->filter_setting( $setting );
			if ( $this->is_setting_type_valid( $setting['type'] ) ) {
				$setting['value']    = $this->get_value( $setting['id'] );
				$filtered_settings[] = $setting;
			}
		}

		return $filtered_settings;
	}

	/**
	 * Get setting data.
	 *
	 * @param string $group_id Group ID.
	 * @param string $setting_id Setting ID.
	 *
	 * @return stdClass|WP_Error
	 */
	public function get_setting( $group_id, $setting_id ) {
		if ( empty( $setting_id ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$settings = $this->get_group_settings( $group_id );

		if ( is_wp_error( $settings ) ) {
			return $settings;
		}

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
	 * Bulk create, update and delete items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Of WP_Error or WP_REST_Response.
	 */
	public function batch_items( $request ) {
		// Get the request params.
		$items = array_filter( $request->get_params() );

		/*
		 * Since our batch settings update is group-specific and matches based on the route,
		 * we inject the URL parameters (containing group) into the batch items
		 */
		if ( ! empty( $items['update'] ) ) {
			$to_update = array();
			foreach ( $items['update'] as $item ) {
				$to_update[] = array_merge( $request->get_url_params(), $item );
			}
			$request = new WP_REST_Request( $request->get_method() );
			$request->set_body_params( array( 'update' => $to_update ) );
		}

		return parent::batch_items( $request );
	}

	/**
	 * Update a single setting in a group.
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$setting = $this->get_setting( $request['group'], $request['id'] );

		if ( is_wp_error( $setting ) ) {
			return $setting;
		}

		$response = $this->prepare_item_for_response( $setting, $request );
		$value    = $this->sanitize_setting_value( $setting, $request['value'] );

		$response->set_data( array_merge( $response->get_data(), compact( 'value' ) ) );

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
		$data          = $this->filter_setting( $item );
		$data['value'] = $this->get_value( $data['id'] );

		$context = empty( $request['context'] ) ? 'view' : $request['context'];
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $data['id'], $request['group'] ) );

		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param string $setting_id Setting ID.
	 * @param string $group_id Group ID.
	 * @return array Links for the given setting.
	 */
	protected function prepare_links( $setting_id, $group_id ) {
		$base  = '/' . $this->namespace . '/' . $this->rest_base . '/' . $group_id;
		$links = array(
			'self' => array(
				'href' => rest_url( trailingslashit( $base ) . $setting_id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		return $links;
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
