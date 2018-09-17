<?php
/**
 * REST API Reports controller extended by WC Admin plugin.
 *
 * Handles requests to the reports endpoint.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports controller class.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Reports_Controller
 */
class WC_Admin_REST_Reports_Controller extends WC_REST_Reports_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports';

	/**
	 * Register the routes for reports.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check whether a given request has permission to read reports.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'reports', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get all reports.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$data    = array();
		$reports = array(
			array(
				'slug'        => 'revenue/stats',
				'description' => __( 'Stats about revenue.', 'woocommerce' ),
			),
			array(
				'slug'        => 'orders/stats',
				'description' => __( 'Stats about orders.', 'woocommerce' ),
			),
			array(
				'slug'        => 'products',
				'description' => __( 'Products detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'products/stats',
				'description' => __( 'Stats about products.', 'woocommerce' ),
			),
			array(
				'slug'        => 'categories',
				'description' => __( 'Product categories detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'categories/stats',
				'description' => __( 'Stats about product categories.', 'woocommerce' ),
			),
			array(
				'slug'        => 'coupons',
				'description' => __( 'Coupons detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'coupons/stats',
				'description' => __( 'Stats about coupons.', 'woocommerce' ),
			),
			array(
				'slug'        => 'taxes',
				'description' => __( 'Taxes detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'taxes/stats',
				'description' => __( 'Stats about taxes.', 'woocommerce' ),
			),
			array(
				'slug'        => 'downloads',
				'description' => __( 'Product downloads detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'downloads/files',
				'description' => __( 'Product download files detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'downloads/stats',
				'description' => __( 'Stats about product downloads.', 'woocommerce' ),
			),
			array(
				'slug'        => 'customers',
				'description' => __( 'Customers detailed reports.', 'woocommerce' ),
			),
		);

		foreach ( $reports as $report ) {
			$item   = $this->prepare_item_for_response( (object) $report, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Prepare a report object for serialization.
	 *
	 * @param stdClass        $report  Report data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $report, $request ) {
		$data = array(
			'slug'        => $report->slug,
			'description' => $report->description,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links( array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $report->slug ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		) );

		/**
		 * Filter a report returned from the API.
		 *
		 * Allows modification of the report data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $report   The original report object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report', $response, $report, $request );
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report',
			'type'       => 'object',
			'properties' => array(
				'slug'        => array(
					'description' => __( 'An alphanumeric identifier for the resource.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'A human-readable description of the resource.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
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
