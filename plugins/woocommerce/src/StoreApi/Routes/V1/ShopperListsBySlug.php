<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperList;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * GET /shopper-lists/{slug} — metadata for a single list.
 */
class ShopperListsBySlug extends AbstractRoute {
	/**
	 * Route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'shopper-lists-by-slug';

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
		return '/shopper-lists/(?P<slug>[a-z0-9-]+)';
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
			),
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
	 * Return the list metadata for the requested slug.
	 *
	 * @throws RouteException When the list doesn't exist.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$list = ShopperList::get_by_slug( (string) $request['slug'] );

		if ( ! $list ) {
			throw new RouteException( 'woocommerce_rest_shopper_list_not_found', esc_html__( 'Your saved list isn\'t available right now.', 'woocommerce' ), 404 );
		}

		return rest_ensure_response( $this->prepare_item_for_response( $list, $request ) );
	}
}
