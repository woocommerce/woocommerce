<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\API\AI;

use Automattic\WooCommerce\Blocks\AI\Connection;
use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;
use Automattic\WooCommerce\Blocks\AIContent\UpdateProducts;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Store Info controller
 *
 * @internal
 */
class StoreInfo extends AIEndpoint {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	protected $endpoint = 'store-info';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		$this->register(
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_response' ),
					'permission_callback' => array( Middleware::class, 'is_authorized' ),
				),
				'schema' => array( $this, 'get_schema' ),
			)
		);
	}

	/**
	 * Update the store title powered by AI.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_response() {
		$product_updater = new UpdateProducts();
		$patterns        = PatternsHelper::get_patterns_ai_data_post();

		$products = $product_updater->fetch_product_ids( 'dummy' );

		if ( empty( $products ) && ! isset( $patterns ) ) {
			return rest_ensure_response(
				array(
					'is_ai_generated' => false,
				)
			);
		}

		return rest_ensure_response(
			array(
				'is_ai_generated' => true,
			)
		);
	}

	/**
	 * Get the Business Description response.
	 *
	 * @return array
	 */
	public function get_schema() {
		return array(
			'ai_content_generated' => true,
		);
	}
}
