<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Blocks\BlockPatterns;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Patterns\PTKClient;
use Automattic\WooCommerce\Blocks\Patterns\PTKPatternsStore;
use WP_Error;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Patterns controller
 *
 * @internal
 */
class Patterns {
	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			'wc-admin',
			'/patterns',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_pattern' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_patterns' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
				),
			)
		);
	}

	/**
	 * Fetch a single pattern from the PTK to ensure the API is available.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_pattern() {
		$ptk_client = Package::container()->get( PTKClient::class );

		$response = $ptk_client->fetch_patterns(
			array(
				'per_page' => 1,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return rest_ensure_response(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Fetch the patterns from the PTK and update the transient.
	 *
	 * @return WP_REST_Response
	 */
	public function update_patterns() {
		$ptk_patterns_store = Package::container()->get( PTKPatternsStore::class );

		$ptk_patterns_store->fetch_patterns();

		$block_patterns = Package::container()->get( BlockPatterns::class );

		$block_patterns->register_ptk_patterns();

		return rest_ensure_response(
			array(
				'success' => true,
			)
		);
	}
}
