<?php
/**
 * ProductMapper class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog;

use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductMapperInterface;
use WC_Product;
use WC_REST_Products_Controller;
use WC_REST_Product_Variations_Controller;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Mapper for the POS catalog.
 *
 * Uses WooCommerce REST API controllers to map product data.
 *
 * @since 10.5.0
 */
class ProductMapper implements ProductMapperInterface {
	/**
	 * Fields to include in the product mapping.
	 *
	 * @var string|null Fields to include in the product mapping.
	 */
	private ?string $fields = null;

	/**
	 * Fields to include in the variation mapping.
	 *
	 * @var string|null Fields to include in the variation mapping.
	 */
	private ?string $variation_fields = null;

	/**
	 * REST controller instance for products.
	 *
	 * @var WC_REST_Products_Controller|null
	 */
	private ?WC_REST_Products_Controller $products_controller = null;

	/**
	 * REST controller instance for variations.
	 *
	 * @var WC_REST_Product_Variations_Controller|null
	 */
	private ?WC_REST_Product_Variations_Controller $variations_controller = null;

	/**
	 * Cached REST request instance for products.
	 *
	 * @var WP_REST_Request<array<string, mixed>>|null
	 */
	private ?WP_REST_Request $products_request = null;

	/**
	 * Cached REST request instance for variations.
	 *
	 * @var WP_REST_Request<array<string, mixed>>|null
	 */
	private ?WP_REST_Request $variations_request = null;

	/**
	 * Initialize the mapper.
	 *
	 * @internal
	 * @return void
	 */
	final public function init(): void {
		$this->products_controller   = new WC_REST_Products_Controller();
		$this->variations_controller = new WC_REST_Product_Variations_Controller();
	}

	/**
	 * Set fields to include in the product mapping.
	 *
	 * @since 10.5.0
	 *
	 * @param string|null $fields Fields to include in the product mapping.
	 * @return void
	 */
	public function set_fields( ?string $fields = null ): void {
		$this->fields           = $fields;
		$this->products_request = null; // Invalidate the cached request.
	}

	/**
	 * Set fields to include in the variation mapping.
	 *
	 * @since 10.5.0
	 *
	 * @param string|null $fields Fields to include in the variation mapping.
	 * @return void
	 */
	public function set_variation_fields( ?string $fields = null ): void {
		$this->variation_fields   = $fields;
		$this->variations_request = null; // Invalidate the cached request.
	}

	/**
	 * Map WooCommerce product to catalog row
	 *
	 * @since 10.5.0
	 *
	 * @param WC_Product $product Product to map.
	 * @return array Mapped product data array.
	 * @throws \RuntimeException If the controller is not initialized.
	 */
	public function map_product( WC_Product $product ): array {
		$is_variation = $product->is_type( 'variation' );
		$controller   = $is_variation
			? $this->variations_controller
			: $this->products_controller;

		// This should never be the case, as the class should be loaded through DI.
		if ( null === $controller ) {
			throw new \RuntimeException( 'ProductMapper::init() must be called before map_product().' );
		}

		$request  = $is_variation ? $this->get_variations_request() : $this->get_products_request();
		$response = $controller->prepare_object_for_response( $product, $request );

		// Apply _fields filtering (normally done by REST server dispatch).
		$fields = $is_variation ? $this->variation_fields : $this->fields;
		if ( null !== $fields ) {
			$response = rest_filter_response_fields( $response, rest_get_server(), $request );
		}

		$row = array(
			'type' => $product->get_type(),
			'data' => $response->get_data(),
		);

		/**
		 * Filter mapped catalog product data.
		 *
		 * @since 10.5.0
		 * @param array      $row     Mapped product data.
		 * @param WC_Product $product Product object.
		 */
		return apply_filters( 'woocommerce_pos_catalog_map_product', $row, $product );
	}

	/**
	 * Get the REST request instance for products.
	 *
	 * @return WP_REST_Request<array<string, mixed>>
	 */
	protected function get_products_request(): WP_REST_Request {
		if ( null === $this->products_request ) {
			/**
			 * Type hint for PHPStan generics.
			 *
			 * @var WP_REST_Request<array<string, mixed>> $request
			 * */
			$request                = new WP_REST_Request( 'GET' );
			$this->products_request = $request;
			$this->products_request->set_param( 'context', 'view' );

			if ( null !== $this->fields ) {
				$this->products_request->set_param( '_fields', $this->fields );
			}
		}

		return $this->products_request;
	}

	/**
	 * Get the REST request instance for variations.
	 *
	 * @return WP_REST_Request<array<string, mixed>>
	 */
	protected function get_variations_request(): WP_REST_Request {
		if ( null === $this->variations_request ) {
			/**
			 * Type hint for PHPStan generics.
			 *
			 * @var WP_REST_Request<array<string, mixed>> $request
			 */
			$request                  = new WP_REST_Request( 'GET' );
			$this->variations_request = $request;
			$this->variations_request->set_param( 'context', 'view' );

			if ( null !== $this->variation_fields ) {
				$this->variations_request->set_param( '_fields', $this->variation_fields );
			}
		}

		return $this->variations_request;
	}
}
