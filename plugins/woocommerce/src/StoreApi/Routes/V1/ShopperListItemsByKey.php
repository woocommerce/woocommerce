<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperList;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * DELETE /shopper-lists/{slug}/items/{key}.
 */
class ShopperListItemsByKey extends AbstractRoute {
	// Stopgap CSRF guard, replaced once the upstream trait lands on trunk.
	use ShopperListsNonceCheck;

	/**
	 * Route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'shopper-list-items-by-key';

	/**
	 * Schema identifier this route uses.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'shopper-list-item';

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
		return '/shopper-lists/(?P<slug>[a-z0-9-]+)/items/(?P<key>[\w-]{32})';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array
	 */
	public function get_args() {
		return array(
			'args'   => array(
				'slug' => array(
					'description' => __( 'Stable slug for the list.', 'woocommerce' ),
					'type'        => 'string',
				),
				'key'  => array(
					'description' => __( 'Item key.', 'woocommerce' ),
					'type'        => 'string',
				),
			),
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Delete a single item from a list.
	 *
	 * @throws RouteException When the list or item doesn't exist.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_delete_response( \WP_REST_Request $request ) {
		$list = ShopperList::get_by_slug( (string) $request['slug'] );

		if ( ! $list ) {
			throw new RouteException( 'woocommerce_rest_shopper_list_not_found', esc_html__( 'Your saved list isn\'t available right now.', 'woocommerce' ), 404 );
		}

		if ( ! $list->remove_item( (string) $request['key'] ) ) {
			throw new RouteException( 'woocommerce_rest_shopper_list_item_not_found', esc_html__( 'That item isn\'t in your saved list anymore.', 'woocommerce' ), 404 );
		}

		$list->save();

		return new \WP_REST_Response( null, 204 );
	}
}
