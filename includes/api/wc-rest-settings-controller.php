<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Settings Controller.
 * Handles requests to the /settings endpoints.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @version  2.7.0
 * @since    2.7.0
 */
class WC_Rest_Settings_Controller extends WP_Rest_Controller {

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
		register_rest_route( WC_API::REST_API_NAMESPACE, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_groups' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
			'schema' => array( $this, 'group_schema' ),
		) );
		register_rest_route( WC_API::REST_API_NAMESPACE, '/' . $this->rest_base . '/(?P<group>[\w-]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_group' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_settings' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'group_schema' ),
		) );
		register_rest_route( WC_API::REST_API_NAMESPACE, '/' . $this->rest_base . '/(?P<group>[\w-]+)/(?P<setting>[\w-]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_setting' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_setting' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'setting_schema' ),
		) );
	}

	/**
	 * Makes sure the current user has access to the settings APIs.
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
	 * Get all settings groups.
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_groups( $request ) {
		$groups = apply_filters( 'woocommerce_settings_groups', array() );
		if ( empty( $groups ) ) {
			return new WP_Error( 'rest_setting_groups_empty', __( 'No setting groups have been registered.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		$defaults        = $this->group_defaults();
		$filtered_groups = array();
		foreach ( $groups as $group ) {
			$group = wp_parse_args( $group, $defaults );
			if ( ! is_null( $group['id'] ) && ! is_null( $group['label'] ) ) {
				$filtered_groups[] = $this->filter_group( $group );
			}
		}

		$response = rest_ensure_response( $filtered_groups );
		return $response;
	}

	/**
	 * Return a single setting group and its settings.
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_group( $request ) {
		$group = $this->_get_group_from_request( $request );
		if ( is_wp_error( $group ) ) {
			return $group;
		}
		return rest_ensure_response( $group );
	}

	/**
	 * Return a single setting.
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_setting( $request ) {
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
	public function update_setting( $request ) {
		$setting = $this->_get_setting_from_request( $request );
		if ( is_wp_error( $setting ) ) {
			return $setting;
		}

		$value = $setting['value'] = $this->sanitize_setting_value( $setting, $request['value'] );
		update_option( $setting['id'], $value );

		return rest_ensure_response( $setting );
	}

	/**
	 * Update a multiple settings at once.
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_settings( $request ) {
		$group = $this->_get_group_from_request( $request );
		if ( is_wp_error( $group ) ) {
			return $group;
		}

		foreach ( $group['settings'] as $array_key => $setting ) {
			if ( isset( $request['values'][ $setting['id'] ] ) ) {
				$value = $this->sanitize_setting_value( $setting, $request['values'][ $setting['id'] ] );
				$group['settings'][ $array_key ]['value'] = $value;
				update_option( $setting['id'], $value );
			}
		}

		return rest_ensure_response( $group );
	}

	/**
	 * Cleans a value before setting it.
	 * @since  2.7.0
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
				$value = wp_kses_post( trim( $raw_value ) );
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
	 * Takes a valid request and returns back the corresponding setting array.
	 * @since 2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|array
	 */
	private function _get_setting_from_request( $request ) {
		if ( empty( $request['group'] ) || empty( $request['setting'] ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$settings  = apply_filters( 'woocommerce_settings_' . $request['group'], array() );
		$array_key = array_keys( wp_list_pluck( $settings, 'id' ), $request['setting'] );

		if ( empty( $array_key ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$setting          = $this->filter_setting( $settings[ $array_key[0] ] );
		$setting['value'] = $this->get_value( $setting['id'] );

		if ( ! $this->is_valid_type( $setting['type'] ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $setting;
	}

	/**
	 * Takes a valid request and returns back the corresponding group array.
	 * @since 2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|array
	 */
	private function _get_group_from_request( $request ) {
		$groups = apply_filters( 'woocommerce_settings_groups', array() );
		if ( empty( $groups ) ) {
			return new WP_Error( 'rest_setting_group_invalid', __( 'Invalid setting group.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$index_key = array_keys( wp_list_pluck( $groups, 'id' ), $request['group'] );
		if ( empty( $index_key ) || empty( $groups[ $index_key[0] ] ) ) {
			return new WP_Error( 'rest_setting_group_invalid', __( 'Invalid setting group.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$group = wp_parse_args( $groups[ $index_key[0] ], $this->group_defaults() );
		if ( is_null( $group['id'] ) || is_null( $group['label'] ) ) {
			return new WP_Error( 'rest_setting_group_invalid', __( 'Invalid setting group.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$filtered_group             = $this->filter_group( $group );
		$filtered_group['settings'] = array();
		$settings                   = apply_filters( 'woocommerce_settings_' . $group['id'], array() );
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $setting ) {
				$setting           = $this->filter_setting( $setting );
				$setting['value']  = $this->get_value( $setting['id'] );
				if ( $this->is_valid_type( $setting['type'] ) ) {
					$filtered_group['settings'][] = $setting;
				}
			}
		}

		return $filtered_group;
	}

	/**
	 * Get the groups schema, conforming to JSON Schema.
	 * @since  2.7.0
	 * @return array
	 */
	public function group_schema() {
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'settings-group',
			'type'                 => 'object',
			'properties'           => array(
				'id'               => array(
					'description'  => __( 'A unique identifier that can be used to link settings together.', 'woocommerce' ),
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
				'parent_id'        => array(
					'description'  => __( 'ID of parent grouping.', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
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

	/**
	 * Get a value from WP's settings API.
	 * @since  2.7.0
	 * @param  string $setting
	 * @param  string $default
	 * @return mixed
	 */
	public function get_value( $setting, $default = '' ) {
		if ( strstr( $setting, '[' ) ) { // Array value
			parse_str( $setting, $setting_array );
			$setting = current( array_keys( $setting ) );
			$values  = get_option( $setting, '' );
			$key = key( $setting_array[ $setting ] );
			if ( isset( $values[ $key ] ) ) {
				$value = $values[ $key ];
			} else {
				$value = null;
			}
		} else { // Single value
			$value = get_option( $setting, null );
		}

		if ( is_array( $setting ) ) {
			$value = array_map( 'stripslashes', $value );
		} elseif ( ! is_null( $value ) ) {
			$value = stripslashes( $value );
		}

		return $value === null ? $default : $value;
	}

	/**
	 * Filters out bad values from the groups array/filter so we
	 * only return known values via the API.
	 * @since 2.7.0
	 * @param  array $group
	 * @return array
	 */
	public function filter_group( $group ) {
		return array_intersect_key(
			$group,
			array_flip( array_filter( array_keys( $group ), array( $this, 'allowed_group_keys' ) ) )
		);
	}

	/**
	 * Filters out bad values from the settings array/filter so we
	 * only return known values via the API.
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
	 * Callback for allowed keys for each group response.
	 * @since  2.7.0
	 * @param  string $key Key to check
	 * @return boolean
	 */
	public function allowed_group_keys( $key ) {
		return in_array( $key, array( 'id', 'label', 'description', 'parent_id' ) );
	}

	/**
	 * Callback for allowed keys for each setting response.
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
	 * @since  2.7.0
	 * @param  string  $type
	 * @return boolean
	 */
	public function is_valid_type( $type ) {
		return in_array( $type, array(
			'text', 'email', 'number', 'color', 'password',
			'textarea', 'select', 'multiselect', 'radio', 'checkbox',
		) );
	}

	/**
	 * Returns default settings for groups. null means the field is required.
	 * @since  2.7.0
	 * @return array
	 */
	protected function group_defaults() {
		return array(
			'id'            => null,
			'label'         => null,
			'description'   => '',
			'parent_id'     => '',
		);
	}

	/**
	 * Returns default settings for settings. null means the field is required.
	 * @since  2.7.0
	 * @return array
	 */
	protected function setting_defaults() {
		return array(
			'id'            => null,
			'label'         => null,
			'type'          => null,
			'description'   => '',
			'tip'           => '',
			'placeholder'   => '',
			'default'       => '',
			'options'       => array(),
			'value'         => '',
		);
	}

}
