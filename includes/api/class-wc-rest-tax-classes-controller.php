<?php
/**
 * REST API Tax Classes controller
 *
 * Handles requests to the /taxes/classes endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Tax Classes controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Tax_Classes_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'taxes/classes';

	/**
	 * Register the routes for tax classes.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<slug>\w[\w\s\-]*)', array(
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default'     => false,
						'description' => __( 'Required to be true, as resource does not support trashing.', 'woocommerce' ),
					),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check whether a given request has permission to read tax classes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access create tax classes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access delete a tax.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'delete' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get all tax classes.
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function get_items( $request ) {
		$tax_classes = array();

		// Add standard class.
		$tax_classes[] = array(
			'slug' => 'standard',
			'name' => __( 'Standard Rate', 'woocommerce' ),
		);

		$classes = WC_Tax::get_tax_classes();

		foreach ( $classes as $class ) {
			$tax_classes[] = array(
				'slug' => sanitize_title( $class ),
				'name' => $class,
			);
		}

		$data = array();
		foreach ( $tax_classes as $tax_class ) {
			$class  = $this->prepare_item_for_response( $tax_class, $request );
			$class  = $this->prepare_response_for_collection( $class );
			$data[] = $class;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Create a single tax.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$exists    = false;
		$classes   = WC_Tax::get_tax_classes();
		$tax_class = array(
			'slug' => sanitize_title( $request['name'] ),
			'name' => $request['name'],
		);

		// Check if class exists.
		foreach ( $classes as $key => $class ) {
			if ( sanitize_title( $class ) === $tax_class['slug'] ) {
				$exists = true;
				break;
			}
		}

		// Return error if tax class already exists.
		if ( $exists ) {
			return new WP_Error( 'woocommerce_rest_tax_class_exists', __( 'Cannot create existing resource.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		// Add the new class.
		$classes[] = $tax_class['name'];

		update_option( 'woocommerce_tax_classes', implode( "\n", $classes ) );

		$this->update_additional_fields_for_object( $tax_class, $request );

		/**
		 * Fires after a tax class is created or updated via the REST API.
		 *
		 * @param stdClass        $tax_class Data used to create the tax class.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating tax class, false when updating tax class.
		 */
		do_action( 'woocommerce_rest_insert_tax_class', (object) $tax_class, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $tax_class, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $tax_class['slug'] ) ) );

		return $response;
	}

	/**
	 * Delete a single tax class.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		global $wpdb;

		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for this type, error out.
		if ( ! $force ) {
			return new WP_Error( 'woocommerce_rest_trash_not_supported', __( 'Taxes do not support trashing.', 'woocommerce' ), array( 'status' => 501 ) );
		}

		$tax_class = array(
			'slug' => sanitize_title( $request['slug'] ),
			'name' => '',
		);
		$classes = WC_Tax::get_tax_classes();
		$deleted = false;

		foreach ( $classes as $key => $class ) {
			if ( sanitize_title( $class ) === $tax_class['slug'] ) {
				$tax_class['name'] = $class;
				unset( $classes[ $key ] );
				$deleted = true;
				break;
			}
		}

		if ( ! $deleted ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource id.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		update_option( 'woocommerce_tax_classes', implode( "\n", $classes ) );

		// Delete tax rate locations locations from the selected class.
		$wpdb->query( $wpdb->prepare( "
			DELETE locations.*
			FROM {$wpdb->prefix}woocommerce_tax_rate_locations AS locations
			INNER JOIN
				{$wpdb->prefix}woocommerce_tax_rates AS rates
				ON rates.tax_rate_id = locations.tax_rate_id
			WHERE rates.tax_rate_class = '%s'
		", $tax_class['slug'] ) );

		// Delete tax rates in the selected class.
		$wpdb->delete( $wpdb->prefix . 'woocommerce_tax_rates', array( 'tax_rate_class' => $tax_class['slug'] ), array( '%s' ) );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $tax_class, $request );

		/**
		 * Fires after a tax class is deleted via the REST API.
		 *
		 * @param stdClass         $tax_class The tax data.
		 * @param WP_REST_Response $response  The response returned from the API.
		 * @param WP_REST_Request  $request   The request sent to the API.
		 */
		do_action( 'woocommerce_rest_delete_tax', (object) $tax_class, $response, $request );

		return $response;
	}

	/**
	 * Prepare a single tax class output for response.
	 *
	 * @param array $tax_class Tax class data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $tax_class, $request ) {
		$data = $tax_class;

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links() );

		/**
		 * Filter tax object returned from the REST API.
		 *
		 * @param WP_REST_Response $response  The response object.
		 * @param stdClass         $tax_class Tax object used to create response.
		 * @param WP_REST_Request  $request   Request object.
		 */
		return apply_filters( 'woocommerce_rest_prepare_tax', $response, (object) $tax_class, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @return array Links for the given tax class.
	 */
	protected function prepare_links() {
		$links = array(
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the Tax Classes schema, conforming to JSON Schema
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'tax_class',
			'type'       => 'object',
			'properties' => array(
				'slug' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name' => array(
					'description' => __( 'Tax class name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}
}
