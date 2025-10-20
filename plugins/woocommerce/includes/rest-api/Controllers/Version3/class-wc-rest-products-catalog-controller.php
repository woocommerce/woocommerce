<?php
/**
 * REST API Products Catalog controller
 *
 * Handles requests to the products/catalog endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   10.4.0
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * REST API Products Catalog controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Products_Catalog_Controller extends WC_REST_Controller {

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
	protected $rest_base = 'products/catalog';

	/**
	 * Register the routes for products catalog.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/generate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'generate_catalog' ),
					'permission_callback' => array( $this, 'generate_products_catalog_permissions_check' ),
					'args'                => array(
						'fields' => array(
							'description' => __( 'Product/variation fields to include in the catalog.', 'woocommerce' ),
							'type'        => 'array',
						),
					),
				),
				'schema' => array( $this, 'catalog_generation_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_catalog_generation_status' ),
					'permission_callback' => array( $this, 'generate_products_catalog_permissions_check' ),
					'args'                => array(
						'job_id' => array(
							'description' => __( 'Products catalog generation job ID.', 'woocommerce' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'catalog_generation_status_schema' ),
			)
		);
	}

	/**
	 * Generate products catalog.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function generate_catalog( $request ) {
		// Mock job ID.
		$response_data = array(
			'job_id' => '134aec',
		);
		return rest_ensure_response( $response_data );
	}

	/**
	 * Get products catalog generation job status.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function get_catalog_generation_status( $request ) {
		$job_id = $request->get_param( 'job_id' );
		if ( empty( $job_id ) || ! is_string( $job_id ) ) {
			return new WP_Error( 'invalid_job_id', __( 'Invalid products catalog generation job ID.', 'woocommerce' ), array( 'status' => 400 ) );
		}
		$job_id = sanitize_text_field( $job_id );
		// Mock response to make it look like the generation is complete.
		$response_data = array(
			'job_id'       => $job_id,
			'status'       => 'complete',
			'download_url' => rest_url( $this->namespace . '/' . $this->rest_base . '/download?filename=products_catalog.json' ),
		);
		return rest_ensure_response( $response_data );
	}

	/**
	 * Checks if a given request has permission to generate products catalog.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function generate_products_catalog_permissions_check( $request ) {
		if ( ! ( wc_rest_check_post_permissions( 'product', 'read' ) && wc_rest_check_post_permissions( 'product_variation', 'read' ) ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Products catalog generation schema.
	 *
	 * @return array Products catalog generation schema data.
	 */
	function catalog_generation_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'generate_products_catalog',
			'type'       => 'object',
			'properties' => array(
				'job_id' => array(
					'description' => __( 'Products catalog generation job ID.', 'woocommerce' ),
					'type'        => 'string',
				),
			),
			'required'   => array( 'job_id' ),
		);
	}

	/**
	 * Products catalog generation status schema.
	 *
	 * @return array Products catalog generation status schema data.
	 */
	function catalog_generation_status_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'products_catalog_generation_status',
			'type'       => 'object',
			'properties' => array(
				'job_id'       => array(
					'description' => __( 'Products catalog generation job ID.', 'woocommerce' ),
					'type'        => 'string',
				),
				'status'       => array(
					'description' => __( 'Products catalog generation status. Possible values: pending, processing, complete, failed.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'pending', 'processing', 'complete', 'failed' ),
				),
				'download_url' => array(
					'description' => __( 'Products catalog download URL when the generation is complete.', 'woocommerce' ),
					'type'        => 'string',
				),
			),
			'required'   => array( 'job_id', 'status' ),
		);
	}
}
