<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Settings Groups Controller.
 * Handles requests to the /settings and /settings/<group> endpoints.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @version  2.7.0
 * @since    2.7.0
 */
class WC_Rest_Settings_Groups_Controller extends WC_REST_Settings_API_Controller {

	/**
	 * WP REST API namespace/version.
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Register routes.
	 * @since 2.7.0
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Get all settings groups items.
	 *
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$groups = apply_filters( 'woocommerce_settings_groups', array() );
		if ( empty( $groups ) ) {
			return new WP_Error( 'rest_setting_groups_empty', __( 'No setting groups have been registered.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		$defaults        = $this->group_defaults();
		$filtered_groups = array();
		foreach ( $groups as $group ) {
			$sub_groups = array();
			foreach ( $groups as $_group ) {
				if ( ! empty( $_group['parent_id'] ) && $group['id'] === $_group['parent_id'] ) {
					$sub_groups[] = $_group['id'];
				}
			}
			$group['sub_groups'] = $sub_groups;

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
	 *
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$group = $this->prepare_item_for_response( $request );
		if ( is_wp_error( $group ) ) {
			return $group;
		}
		return rest_ensure_response( $group );
	}

	/**
	 * Prepare a report sales object for serialization.
	 *
	 * @param null $_
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $_, $request ) {
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

		// Find sub groups.
		$sub_groups = array();
		foreach ( $groups as $_group ) {
			if ( ! empty( $_group['parent_id'] ) && $group['id'] === $_group['parent_id'] ) {
				$sub_groups[] = $_group['id'];
			}
		}

		$filtered_group             = $this->filter_group( $group );
		$filtered_group['settings'] = array();
		$settings                   = apply_filters( 'woocommerce_settings-' . $group['id'], array() );

		if ( ! empty( $settings ) ) {
			foreach ( $settings as $setting ) {
				$setting           = $this->filter_setting( $setting );
				$setting['value']  = $this->get_value( $setting['id'] );
				if ( $this->is_setting_type_valid( $setting['type'] ) ) {
					$filtered_group['settings'][] = $setting;
				}
			}
		}

		$filtered_group['sub_groups'] = $sub_groups;

		return $filtered_group;
	}

	/**
	 * Filters out bad values from the groups array/filter so we
	 * only return known values via the API.
	 *
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
	 * Callback for allowed keys for each group response.
	 *
	 * @since  2.7.0
	 * @param  string $key Key to check
	 * @return boolean
	 */
	public function allowed_group_keys( $key ) {
		return in_array( $key, array( 'id', 'label', 'description', 'parent_id', 'sub_groups' ) );
	}

	/**
	 * Returns default settings for groups. null means the field is required.
	 *
	 * @since  2.7.0
	 * @return array
	 */
	protected function group_defaults() {
		return array(
			'id'          => null,
			'label'       => null,
			'description' => '',
			'parent_id'   => '',
			'sub_groups'  => array(),
		);
	}

	/**
	 * Get the groups schema, conforming to JSON Schema.
	 *
	 * @since  2.7.0
	 * @return array
	 */
	public function get_item_schema() {
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
				'sub_groups'        => array(
					'description'  => __( 'IDs for settings sub groups.', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
