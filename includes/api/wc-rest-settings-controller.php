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
			'schema' => array( $this, 'group_schema' ),
		) );
		// @todo change this to support settings with array keys / multiple values?
		register_rest_route( WC_API::REST_API_NAMESPACE, '/' . $this->rest_base . '/(?P<group>[\w-]+)/(?P<setting>[\w-]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_setting' ),
				'permission_callback' => array( $this, 'permissions_check' ),
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
			//return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot access settings.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/*
	|--------------------------------------------------------------------------
	| /settings
	|--------------------------------------------------------------------------
	| Returns a list of "settings" groups so all settings for a particular page
	| or section can be properly loaded.
	*/

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
		$groups = apply_filters( 'woocommerce_settings_groups', array() );
		if ( empty( $groups ) ) {
			return new WP_Error( 'rest_setting_group_invalid', __( 'Invalid setting group.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$index_key = array_keys( wp_list_pluck( $groups, 'id' ), $request['group'] );
		if ( empty( $index_key ) || empty( $groups[ $index_key[0] ] ) ) {
			return new WP_Error( 'rest_setting_group_invalid', __( 'Invalid setting group.' ), array( 'status' => 404 ) );
		}

		$group = wp_parse_args( $groups[ $index_key[0] ], $this->group_defaults() );
		if ( is_null( $group['id'] ) || is_null( $group['label'] ) ) {
			return new WP_Error( 'rest_setting_group_invalid', __( 'Invalid setting group.' ), array( 'status' => 404 ) );
		}

		$filtered_group             = $this->filter_group( $group );
		$filtered_group['settings'] = array();
		$settings                   = apply_filters( 'woocommerce_settings_' . $group['id'], array() );
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $setting ) {
				$setting                                    = $this->filter_setting( $setting );
				$setting['value']                           = $this->get_value( $setting['id'] );
				if ( $this->is_valid_type( $setting['type'] ) ) {
					$filtered_group['settings'][] = $setting;
				}
			}
		}

		return rest_ensure_response( $filtered_group );
	}

	/**
	 * Return a single setting.
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_setting( $request ) {
		if ( empty( $request['group'] ) || empty( $request['setting'] ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.' ), array( 'status' => 404 ) );
		}

		$settings  = apply_filters( 'woocommerce_settings_' . $request['group'], array() );
		$array_key = array_keys( wp_list_pluck( $settings, 'id' ), $request['setting'] );

		if ( empty( $array_key ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.' ), array( 'status' => 404 ) );
		}

		$setting          = $this->filter_setting( $settings[ $array_key[0] ] );
		$setting['value'] = $this->get_value( $setting['id'] );

		if ( ! $this->is_valid_type( $setting['type'] ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $setting );
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
					'description'  => __( 'A unique identifier that can be used to link settings together.' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'label'               => array(
					'description'  => __( 'A human readable label. This is a translated string that can be used in interfaces.' ),
					'type'         => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'description'        => array(
					'description'  => __( 'A human readable description. This is a translated string that can be used in interfaces.' ),
					'type'         => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'parent_id'        => array(
					'description'  => __( 'ID of parent grouping.' ),
					'type'         => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
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
