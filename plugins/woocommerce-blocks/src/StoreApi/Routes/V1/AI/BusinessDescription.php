<?php

namespace Automattic\WooCommerce\StoreApi\Routes\V1\AI;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractRoute;

/**
 * BusinessDescription class.
 *
 * @internal
 */
class BusinessDescription extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ai/business-description';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'ai/business-description';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/ai/business-description';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ Middleware::class, 'is_authorized' ],
				'args'                => [
					'business_description' => [
						'description' => __( 'The business description for a given store.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
					],
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Update the last business description.
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {

		$business_description = $request->get_param( 'business_description' );

		if ( ! $business_description ) {
			return $this->error_to_response(
				new \WP_Error(
					'invalid_business_description',
					__( 'Invalid business description.', 'woo-gutenberg-products-block' )
				)
			);
		}

		update_option( 'last_business_description_with_ai_content_generated', $business_description );

		return rest_ensure_response(
			array(
				'ai_content_generated' => true,
			)
		);
	}

}
