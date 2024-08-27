<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\API\AI;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Store Title controller
 *
 * @internal
 */
class BusinessDescription extends AIEndpoint {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	protected $endpoint = 'business-description';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		$this->register(
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_business_description' ),
					'permission_callback' => array( Middleware::class, 'is_authorized' ),
					'args'                => array(
						'business_description' => array(
							'description' => __( 'The business description for a given store.', 'woocommerce' ),
							'type'        => 'string',
						),
					),
				),
				'schema' => array( $this, 'get_schema' ),
			)
		);
	}

	/**
	 * Update the business description.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function update_business_description( $request ) {

		$business_description = $request->get_param( 'business_description' );

		if ( ! $business_description ) {
			return new WP_Error(
				'invalid_business_description',
				__( 'Invalid business description.', 'woocommerce' )
			);
		}

		update_option( 'last_business_description_with_ai_content_generated', $business_description );

		return rest_ensure_response(
			array(
				'ai_content_generated' => true,
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
