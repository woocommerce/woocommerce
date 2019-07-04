<?php
/**
 * REST API Onboarding Profile Controller
 *
 * Handles requests to /onboarding/profile
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * Onboarding Profile controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Data_Controller
 */
class WC_Admin_REST_Onboarding_Profile_Controller extends WC_REST_Data_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'onboarding/profile';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_items' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check whether a given request has permission to read onboarding profile data.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce-admin' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check whether a given request has permission to edit onboarding profile data.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot edit this resource.', 'woocommerce-admin' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Return all onboarding profile data.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$onboarding_data = get_option( 'wc_onboarding_profile', array() );
		$item_schema     = $this->get_item_schema();

		$items = array();
		foreach ( $item_schema['properties'] as $key => $property_schema ) {
			$items[ $key ] = isset( $onboarding_data[ $key ] ) ? $onboarding_data[ $key ] : null;
		}

		$item = $this->prepare_item_for_response( $items, $request );
		$data = $this->prepare_response_for_collection( $item );

		return rest_ensure_response( $data );
	}

	/**
	 * Update onboarding profile data.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_items( $request ) {
		$params          = $request->get_json_params();
		$query_args      = $this->prepare_objects_query( $params );
		$onboarding_data = get_option( 'wc_onboarding_profile', array() );
		update_option( 'wc_onboarding_profile', array_merge( $onboarding_data, $query_args ) );

		$result = array(
			'status'  => 'success',
			'message' => __( 'Onboarding profile data has been updated.', 'woocommerce-admin' ),
		);

		$response = $this->prepare_item_for_response( $result, $request );
		$data     = $this->prepare_response_for_collection( $response );

		return rest_ensure_response( $data );
	}

	/**
	 * Prepare objects query.
	 *
	 * @param  array $params The params sent in the request.
	 * @return array
	 */
	protected function prepare_objects_query( $params ) {
		$args       = array();
		$properties = self::get_profile_properties();

		foreach ( $properties as $key => $property ) {
			if ( isset( $params[ $key ] ) ) {
				$args[ $key ] = $params[ $key ];
			}
		}

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post
		 * collection request.
		 *
		 * @param array $args    Key value array of query var to query value.
		 * @param array $params The params sent in the request.
		 */
		$args = apply_filters( 'woocommerce_rest_onboarding_profile_object_query', $args, $params );

		return $args;
	}


	/**
	 * Prepare the data object for response.
	 *
	 * @param object          $item Data object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data     = $this->add_additional_fields_to_object( $item, $request );
		$data     = $this->filter_response_by_context( $data, 'view' );
		$response = rest_ensure_response( $data );

		/**
		 * Filter the list returned from the API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array            $item     The original item.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_onboarding_profile', $response, $item, $request );
	}

	/**
	 * Get onboarding profile properties.
	 *
	 * @return array
	 */
	public static function get_profile_properties() {
		$properties = array(
			'completed'           => array(
				'type'              => 'boolean',
				'description'       => __( 'Whether or not the profile was completed.', 'woocommerce-admin' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			),
			'skipped'             => array(
				'type'              => 'boolean',
				'description'       => __( 'Whether or not the profile was skipped.', 'woocommerce-admin' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			),
			'account_type'        => array(
				'type'              => 'string',
				'description'       => __( 'Account type used for Jetpack.', 'woocommerce-admin' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => array(
					'new',
					'existing',
					'google',
				),
			),
			'industry'            => array(
				'type'              => 'array',
				'description'       => __( 'Industry.', 'woocommerce-admin' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'sanitize_callback' => 'wp_parse_slug_list',
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => array(
					'enum' => array_keys( WC_Admin_Onboarding::get_allowed_industries() ),
					'type' => 'string',
				),
			),
			'product_types'       => array(
				'type'              => 'array',
				'description'       => __( 'Types of products sold.', 'woocommerce-admin' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'sanitize_callback' => 'wp_parse_slug_list',
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => array(
					'enum' => array_keys( WC_Admin_Onboarding::get_allowed_product_types() ),
					'type' => 'string',
				),
			),
			'product_count'       => array(
				'type'              => 'string',
				'description'       => __( 'Number of products to be added.', 'woocommerce-admin' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => array(
					'1-10',
					'11-100',
					'101-1000',
					'1000+',
				),
			),
			'selling_venues'      => array(
				'type'              => 'string',
				'description'       => __( 'Other places the store is selling products.', 'woocommerce-admin' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => array(
					'no',
					'other',
					'brick-mortar',
					'brick-mortar-other',
				),
			),
			'other_platform'      => array(
				'type'              => 'string',
				'description'       => __( 'Name of other platform used to sell.', 'woocommerce-admin' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => array(
					'shopify',
					'bigcommerce',
					'magento',
					'wix',
					'other',
				),
			),
			'business_extensions' => array(
				'type'              => 'array',
				'description'       => __( 'Extra business extensions to install.', 'woocommerce-admin' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'sanitize_callback' => 'wp_parse_slug_list',
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => array(
					'enum' => array( 'mailchimp', 'facebook' ),
					'type' => 'string',
				),
			),
			'theme'               => array(
				'type'              => 'string',
				'description'       => __( 'Selected store theme.', 'woocommerce-admin' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'sanitize_callback' => 'sanitize_title_with_dashes',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'items_purchased'     => array(
				'type'              => 'boolean',
				'description'       => __( 'Whether or not the user opted to purchase items now or later.', 'woocommerce-admin' ),
				'context'           => array( 'view' ),
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		return apply_filters( 'woocommerce_onboarding_profile_properties', $properties );
	}

	/**
	 * Get the schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		// Unset properties used for collection params.
		$properties = self::get_profile_properties();
		foreach ( $properties as $key => $property ) {
			unset( $properties[ $key ]['default'] );
			unset( $properties[ $key ]['items'] );
			unset( $properties[ $key ]['validate_callback'] );
			unset( $properties[ $key ]['sanitize_callback'] );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'onboarding_profile',
			'type'       => 'object',
			'properties' => $properties,
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		// Unset properties used for item schema.
		$params = self::get_profile_properties();
		foreach ( $params as $key => $param ) {
			unset( $params[ $key ]['context'] );
			unset( $params[ $key ]['readonly'] );
		}

		$params['context'] = $this->get_context_param( array( 'default' => 'view' ) );

		return apply_filters( 'rest_onboarding_profile_collection_params', $params );
	}
}
