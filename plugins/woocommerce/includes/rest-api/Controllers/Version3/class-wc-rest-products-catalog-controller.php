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

use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;
use Automattic\WooCommerce\Internal\ProductCatalog\AsyncGenerator;

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
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'request_catalog' ),
					'permission_callback' => array( $this, 'request_catalog_permissions_check' ),
					'args'                => array(
						'fields'         => array(
							'description'       => __( 'Product/variation fields to include in the catalog. Can be an array or comma-separated string.', 'woocommerce' ),
							'type'              => array( 'array', 'string' ),
							'items'             => array( 'type' => 'string' ),
							'required'          => true,
							'validate_callback' => array( $this, 'validate_fields_arg' ),
							'sanitize_callback' => array( $this, 'sanitize_fields_arg' ),
						),
						'force_generate' => array(
							'description'       => __( 'Whether to generate a new catalog file regardless of whether a catalog file already exists.', 'woocommerce' ),
							'type'              => 'boolean',
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
				'schema' => array( $this, 'catalog_schema' ),
			)
		);
	}

	/**
	 * Request products catalog.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function request_catalog( $request ) {
		$fields         = $this->sanitize_fields_arg( $request->get_param( 'fields' ) ?? array() );
		$force_generate = $request->get_param( 'force_generate' ) ?? false;

		try {
			$generator = wc_get_container()->get( AsyncGenerator::class );

			$status = $force_generate ? $generator->force_regeneration() : $generator->get_status();

			// Map internal status to API response.
			return $this->map_status_to_response( $status );

		} catch ( \Exception $e ) {
			return new WP_Error(
				'catalog_generation_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Map internal status to API response format.
	 *
	 * @param array $status Internal status array.
	 * @return WP_REST_Response|WP_Error
	 */
	private function map_status_to_response( array $status ) {
		// Validate state exists.
		if ( ! isset( $status['state'] ) ) {
			return new WP_Error(
				'catalog_invalid_state',
				__( 'Invalid catalog generation state.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		switch ( $status['state'] ) {
			case AsyncGenerator::STATE_COMPLETED:
				if ( ! isset( $status['url'] ) ) {
					return new WP_Error(
						'catalog_missing_url',
						__( 'Completed catalog missing download URL.', 'woocommerce' ),
						array( 'status' => 500 )
					);
				}
				return rest_ensure_response(
					array(
						'status'       => 'complete',
						'download_url' => $status['url'],
					)
				);

			case AsyncGenerator::STATE_SCHEDULED:
				return rest_ensure_response(
					array(
						'status'       => 'pending',
						'download_url' => null,
					)
				);

			case AsyncGenerator::STATE_IN_PROGRESS:
				return rest_ensure_response(
					array(
						'status'       => 'processing',
						'download_url' => null,
					)
				);

			case AsyncGenerator::STATE_FAILED:
				return new WP_Error(
					'catalog_generation_failed',
					$status['error'] ?? __( 'Catalog generation failed.', 'woocommerce' ),
					array( 'status' => 500 )
				);

			default:
				return new WP_Error(
					'catalog_unknown_state',
					/* translators: %s: Catalog generation state */
					sprintf( __( 'Unknown state: %s', 'woocommerce' ), $status['state'] ),
					array( 'status' => 500 )
				);
		}
	}

	/**
	 * Checks if a given request has permission to request products catalog.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function request_catalog_permissions_check( $request ) {
		if ( ! ( wc_rest_check_post_permissions( 'product', 'read' ) && wc_rest_check_post_permissions( 'product_variation', 'read' ) ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Validate fields argument.
	 *
	 * @param mixed $value The value to validate.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function validate_fields_arg( $value ) {
		if ( ! is_array( $value ) && ! is_string( $value ) ) {
			return new WP_Error( 'invalid_fields', __( 'fields must be an array of strings or a comma-separated string.', 'woocommerce' ) );
		}

		if ( ( is_array( $value ) && empty( $value ) ) || ( is_string( $value ) && '' === trim( $value ) ) ) {
			return new WP_Error( 'invalid_fields', __( 'fields cannot be empty.', 'woocommerce' ) );
		}

		return true;
	}

	/**
	 * Sanitize fields argument.
	 *
	 * @param mixed $value The value to sanitize. Can be an array or comma-separated string.
	 * @return array Sanitized and canonicalized fields array.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function sanitize_fields_arg( $value ) {
		if ( is_string( $value ) ) {
			$value = array_map( 'trim', explode( ',', $value ) );
		}
		return $this->canonicalize_fields( is_array( $value ) ? $value : array() );
	}

	/**
	 * Products catalog schema.
	 *
	 * @return array Products catalog schema data.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function catalog_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'products_catalog',
			'type'       => 'object',
			'properties' => array(
				'status'       => array(
					'description' => __( 'Products catalog generation status.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'pending', 'processing', 'complete' ),
				),
				'download_url' => array(
					'description' => __( 'Products catalog file URL. Null when catalog is not ready.', 'woocommerce' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'uri',
				),
			),
			'required'   => array( 'status', 'download_url' ),
		);
	}

	/**
	 * Get catalog file information based on fields.
	 *
	 * @param array $fields Product/variation fields to include in the catalog.
	 * @return array|WP_Error Array with 'filepath', 'url', and 'directory' keys, or WP_Error on failure.
	 */
	private function get_catalog_file_info( $fields ) {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error( 'upload_dir_error', $upload_dir['error'], array( 'status' => 500 ) );
		}

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

	/**
	 * Canonicalize fields array for stable hashing.
	 *
	 * @param array $fields Product/variation fields.
	 * @return array Canonicalized fields array.
	 */
	private function canonicalize_fields( array $fields ) {
		$fields = array_values( array_unique( array_map( 'strval', $fields ) ) );
		sort( $fields, SORT_STRING );
		return $fields;
	}
}
