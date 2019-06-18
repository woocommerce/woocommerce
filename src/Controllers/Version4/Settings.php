<?php
/**
 * REST API Settings controller
 *
 * Handles requests to the /settings endpoints.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Controllers\Version4;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Settings controller class.
 */
class Settings extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Permission to check.
	 *
	 * @var string
	 */
	protected $resource_type = 'settings';

	/**
	 * Singular name for resource type.
	 *
	 * Used in filter/action names for single resources.
	 *
	 * @var string
	 */
	protected $singular = 'setting';

	/**
	 * Register routes.
	 *
	 * @since 3.0.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),
			true
		);
		$this->register_batch_route();
	}

	/**
	 * Update a setting.
	 *
	 * @param  \WP_REST_Request $request Request data.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ) {
		$options_controller = new \WC_REST_Setting_Options_Controller();
		$response           = $options_controller->update_item( $request );

		return $response;
	}

	/**
	 * Get all settings groups items.
	 *
	 * @since  3.0.0
	 * @param  \WP_REST_Request $request Request data.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$groups = apply_filters( 'woocommerce_settings_groups', array() );
		if ( empty( $groups ) ) {
			return new \WP_Error( 'rest_setting_groups_empty', __( 'No setting groups have been registered.', 'woocommerce' ), array( 'status' => 500 ) );
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
				$group_obj  = $this->filter_group( $group );
				$group_data = $this->prepare_item_for_response( $group_obj, $request );
				$group_data = $this->prepare_response_for_collection( $group_data );

				$filtered_groups[] = $group_data;
			}
		}

		$response = rest_ensure_response( $filtered_groups );
		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param mixed            $item Object to prepare.
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function prepare_links( $item, $request ) {
		$base  = '/' . $this->namespace . '/' . $this->rest_base;
		$links = array(
			'options' => array(
				'href' => rest_url( trailingslashit( $base ) . $item['id'] ),
			),
		);

		return $links;
	}

	/**
	 * Filters out bad values from the groups array/filter so we
	 * only return known values via the API.
	 *
	 * @since 3.0.0
	 * @param  array $group Group.
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
	 * @since  3.0.0
	 * @param  string $key Key to check.
	 * @return boolean
	 */
	public function allowed_group_keys( $key ) {
		return in_array( $key, array( 'id', 'label', 'description', 'parent_id', 'sub_groups' ) );
	}

	/**
	 * Returns default settings for groups. null means the field is required.
	 *
	 * @since  3.0.0
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
	 * @since  3.0.0
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'setting_group',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'A unique identifier that can be used to link settings together.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'label'       => array(
					'description' => __( 'A human readable label for the setting used in interfaces.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'description' => array(
					'description' => __( 'A human readable description for the setting used in interfaces.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'parent_id'   => array(
					'description' => __( 'ID of parent grouping.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'sub_groups'  => array(
					'description' => __( 'IDs for settings sub groups.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
