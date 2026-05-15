<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperList;

/**
 * GET /shopper-lists — collection of the current user's shopper lists.
 */
class ShopperLists extends AbstractRoute {
	/**
	 * Route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'shopper-lists';

	/**
	 * Schema identifier this route uses.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'shopper-list';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path regex for this REST route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/shopper-lists';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Return the lists for the current user.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$response = array();

		foreach ( ShopperList::get_all_for_user() as $list ) {
			$response[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $list, $request ) );
		}

		return rest_ensure_response( $response );
	}
}
