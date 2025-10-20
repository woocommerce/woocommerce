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
						'fields'         => array(
							'description' => __( 'Product/variation fields to include in the catalog.', 'woocommerce' ),
							'type'        => 'array',
						),
						'force_generate' => array(
							'description' => __( 'Force generation of a new catalog file.', 'woocommerce' ),
							'type'        => 'boolean',
							'default'     => false,
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
		$fields         = $request->get_param( 'fields' ) ?? array();
		$force_generate = $request->get_param( 'force_generate' ) ?? false;
		$file_info      = $this->get_catalog_file_info( $fields );

		// Check if file exists and force_generate is false.
		if ( ! $force_generate && file_exists( $file_info['filepath'] ) ) {
			$response_data = array(
				'download_url' => $file_info['url'],
			);
		} else {
			// Temporarily return job_id with base64-encoded fields for use in status endpoint.
			// TODO: WOOMOB-1455 - Replace with proper async job tracking once we have a job tracking system.
			$job_id = base64_encode( wp_json_encode( $fields ) );

			$response_data = array(
				'job_id' => $job_id,
			);
		}

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

		// Decode fields from job_id (base64-encoded JSON).
		$fields_json = base64_decode( $job_id, true );
		if ( false === $fields_json ) {
			return new WP_Error( 'invalid_job_id', __( 'Invalid products catalog generation job ID.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$fields = json_decode( $fields_json, true );
		if ( null === $fields ) {
			$fields = array();
		}

		// Get file info based on decoded fields.
		$file_info = $this->get_catalog_file_info( $fields );

		// Create directory if it doesn't exist.
		if ( ! file_exists( $file_info['directory'] ) ) {
			wp_mkdir_p( $file_info['directory'] );
		}

		// Generate empty catalog file.
		$catalog_data = array(
			'products'   => array(),
			'variations' => array(),
		);

		// Write to file.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$result = file_put_contents( $file_info['filepath'], wp_json_encode( $catalog_data ) );

		if ( false === $result ) {
			return new WP_Error( 'catalog_generation_failed', __( 'Failed to generate catalog file.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		$response_data = array(
			'job_id'       => $job_id,
			'status'       => 'complete',
			'download_url' => $file_info['url'],
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
				'job_id'       => array(
					'description' => __( 'Products catalog generation job ID. Returned when catalog needs to be generated.', 'woocommerce' ),
					'type'        => 'string',
				),
				'download_url' => array(
					'description' => __( 'Products catalog download URL. Returned when catalog file already exists.', 'woocommerce' ),
					'type'        => 'string',
				),
			),
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

	/**
	 * Get catalog file information based on fields.
	 *
	 * @param array $fields Product/variation fields to include in the catalog.
	 * @return array Array with 'filepath', 'url', and 'directory' keys.
	 */
	private function get_catalog_file_info( $fields ) {
		$upload_dir  = wp_upload_dir();
		$catalog_dir = trailingslashit( $upload_dir['basedir'] ) . 'wc-catalog/';
		$catalog_url = trailingslashit( $upload_dir['baseurl'] ) . 'wc-catalog/';

		$today        = gmdate( 'Y-m-d' );
		$catalog_hash = wp_hash( $today . wp_json_encode( $fields ) );
		$filename     = "products-{$today}-{$catalog_hash}.json";

		return array(
			'filepath'  => $catalog_dir . $filename,
			'url'       => $catalog_url . $filename,
			'directory' => $catalog_dir,
		);
	}
}
