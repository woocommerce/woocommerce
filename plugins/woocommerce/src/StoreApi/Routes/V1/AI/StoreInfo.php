<?php

namespace Automattic\WooCommerce\StoreApi\Routes\V1\AI;

use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;
use Automattic\WooCommerce\Blocks\AIContent\UpdateProducts;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractRoute;

/**
 * StoreInfo class.
 *
 * @internal
 */
class StoreInfo extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ai/store-info';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'ai/store-info';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/ai/store-info';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ Middleware::class, 'is_authorized' ],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Update the store title powered by AI.
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
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


}
