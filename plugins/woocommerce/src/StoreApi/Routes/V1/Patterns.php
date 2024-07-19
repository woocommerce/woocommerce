<?php

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Patterns\PTKClient;
use Automattic\WooCommerce\Blocks\Patterns\PTKPatternsStore;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Patterns class.
 */
class Patterns extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'patterns';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'patterns';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/patterns';
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
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Fetch a single pattern from the PTK to ensure the API is available.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|\WP_HTTP_Response|WP_REST_Response
	 * @throws RouteException If the patterns cannot be fetched.
	 */
	protected function get_route_response( WP_REST_Request $request ) {
		$ptk_client = Package::container()->get( PTKClient::class );

		$response = $ptk_client->fetch_patterns(
			array(
				'per_page' => 1,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RouteException(
				wp_kses( $response->get_error_message(), array() ),
				wp_kses( $response->get_error_code(), array() )
			);
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
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 * @throws Exception If the patterns cannot be fetched.
	 */
	protected function get_route_post_response( WP_REST_Request $request ) {
		$ptk_patterns_store = Package::container()->get( PTKPatternsStore::class );

		$ptk_patterns_store->fetch_patterns();

		return rest_ensure_response(
			array(
				'success' => true,
			)
		);
	}
}
