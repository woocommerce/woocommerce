<?php
/**
 * POS Catalog API Controller.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

namespace Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog;

use Automattic\WooCommerce\Container;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * POS Catalog API Controller.
 */
class ApiController {
	const ROUTE_NAMESPACE = 'wc/pos/v1/product-catalog';

	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Dependency injector.
	 *
	 * @param Container $container The container instance. Everything else will be dynamic.
	 */
	public function init( $container ) {
		$this->container = $container;
	}

	/**
	 * Register the routes for the API controller.
	 */
	public function register_routes() {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/create',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'generate_feed' ],
				'permission_callback' => [ $this, 'is_authorized' ],
				'args'                => [
					'force'             => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => 'Force regeneration of the feed. NOOP if generation is in progress.',
					],
					'_product_fields'   => [
						'type'        => 'string',
						'description' => 'Comma-separated list of fields to include for non-variable products.',
						'required'    => false,
					],
					'_variation_fields' => [
						'type'        => 'string',
						'description' => 'Comma-separated list of fields to include for variations.',
						'required'    => false,
					],
				],
			]
		);
	}

	/**
	 * Checks if the current user has the necessary permissions to access the API.
	 *
	 * @return bool True if the user has the necessary permissions, false otherwise.
	 */
	public function is_authorized() {
		return is_user_logged_in() && (
			current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' )
		);
	}

	/**
	 * Starts generating a feed.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function generate_feed( WP_REST_Request $request ) {
		$generator = $this->container->get( AsyncGenerator::class );
		try {
			$params = [];
			if ( null !== $request['_product_fields'] ) {
				$params['_product_fields'] = $request['_product_fields'];
			}
			if ( null !== $request['_variation_fields'] ) {
				$params['_variation_fields'] = $request['_variation_fields'];
			}

			$response = $request->get_param( 'force' )
				? $generator->force_regeneration( $params )
				: $generator->get_status( $params );

			// Use the right datetime format.
			if ( isset( $response['scheduled_at'] ) ) {
				$response['scheduled_at'] = wc_rest_prepare_date_response( $response['scheduled_at'] );
			}
			if ( isset( $response['completed_at'] ) ) {
				$response['completed_at'] = wc_rest_prepare_date_response( $response['completed_at'] );
			}

			// Remove sensitive data from the response.
			if ( isset( $response['action_id'] ) ) {
				unset( $response['action_id'] );
			}
			if ( isset( $response['path'] ) ) {
				unset( $response['path'] );
			}
		} catch ( \Exception $e ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $e->getMessage(),
				],
				500
			);
		}
		return new WP_REST_Response( $response );
	}
}
