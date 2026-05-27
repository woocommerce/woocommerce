<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperList;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListItem;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * GET / POST on /shopper-lists/{slug}/items.
 *
 * GET returns the items in a list.
 * POST saves an item to the list either from an existing cart line or from direct item payload fields.
 */
class ShopperListItems extends AbstractRoute {
	// Stopgap CSRF guard, replaced once the upstream trait lands on trunk.
	use ShopperListsNonceCheck;

	/**
	 * Route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'shopper-list-items';

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
		return '/shopper-lists/(?P<slug>[a-z0-9-]+)/items';
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
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => array(
					'cart_item_key' => array(
						'description' => __( 'Existing cart item key to copy into the list.', 'woocommerce' ),
						'type'        => 'string',
					),
					'product_id'    => array(
						'description' => __( 'Product or variation ID to save. Required when cart_item_key is not supplied.', 'woocommerce' ),
						'type'        => 'integer',
					),
					'variation'     => array(
						'description' => __( 'Chosen attributes (for variations).', 'woocommerce' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'attribute' => array(
									'description' => __( 'Variation attribute name.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
								'value'     => array(
									'description' => __( 'Variation attribute value.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
					'quantity'      => array(
						'description' => __( 'Quantity for the saved item.', 'woocommerce' ),
						'type'        => 'integer',
						'default'     => 1,
					),
				),
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Return the items in the requested list.
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

		$items = array_values( $list->get_items() );
		$this->prime_product_caches_for_items( $items );

		$response = array();
		foreach ( $items as $item ) {
			$response[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $item, $request ) );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Add an item to the requested list from cart_item_key or direct product payload fields.
	 *
	 * @throws RouteException On validation failure.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$list = ShopperList::get_by_slug( (string) $request['slug'] );

		if ( ! $list ) {
			throw new RouteException( 'woocommerce_rest_shopper_list_not_found', esc_html__( 'Your saved list isn\'t available right now.', 'woocommerce' ), 404 );
		}

		[ $lookup_id, $variation, $quantity ] = $this->resolve_item_payload( $request );

		try {
			$item = ShopperListItem::from_product( $lookup_id, $variation, $quantity );
		} catch ( \InvalidArgumentException $e ) {
			throw new RouteException( 'woocommerce_rest_shopper_list_invalid_variation', esc_html( $e->getMessage() ), 400 );
		}
		if ( ! $item ) {
			throw new RouteException( 'woocommerce_rest_shopper_list_unknown_product', esc_html__( 'No product exists for the supplied item.', 'woocommerce' ), 404 );
		}

		$list->add_item( $item );
		$list->save();

		$saved = $list->find_item( $item->get_key() ) ?? $item;
		return new \WP_REST_Response( $this->schema->get_item_response( $saved ), 201 );
	}

	/**
	 * Resolve the POST input into a uniform payload (product lookup id, variation, quantity).
	 *
	 * Accepts either an existing cart_item_key, or direct product_id/variation_id/variation.
	 *
	 * @throws RouteException When neither a cart_item_key nor a product_id is supplied, or the cart_item_key is unknown.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return array{0:int,1:array,2:int} `[ lookup_id, variation, quantity ]`.
	 */
	private function resolve_item_payload( \WP_REST_Request $request ): array {
		$cart_item_key = (string) $request->get_param( 'cart_item_key' );

		if ( $cart_item_key ) {
			if ( ! did_action( 'woocommerce_load_cart_from_session' ) || ! wc()->cart ) {
				wc_load_cart();
			}

			$cart_contents = wc()->cart->get_cart();
			if ( empty( $cart_contents[ $cart_item_key ] ) ) {
				throw new RouteException( 'woocommerce_rest_shopper_list_invalid_cart_item_key', esc_html__( 'No cart item exists for the supplied key.', 'woocommerce' ), 404 );
			}

			$line            = $cart_contents[ $cart_item_key ];
			$product_id      = absint( $line['product_id'] ?? 0 );
			$variation_id    = absint( $line['variation_id'] ?? 0 );
			$variation_attrs = isset( $line['variation'] ) && is_array( $line['variation'] ) ? $line['variation'] : array();

			return array(
				$variation_id ? $variation_id : $product_id,
				$variation_attrs,
				absint( $line['quantity'] ?? 1 ),
			);
		}//end if

		$product_id = absint( $request->get_param( 'product_id' ) );
		if ( ! $product_id ) {
			throw new RouteException( 'woocommerce_rest_shopper_list_missing_item_input', esc_html__( 'Provide cart_item_key or product_id.', 'woocommerce' ), 400 );
		}

		$variation = wp_list_pluck( (array) $request->get_param( 'variation' ), 'value', 'attribute' );

		return array(
			$product_id,
			(array) array_combine(
				array_map( 'wc_variation_attribute_name', array_keys( $variation ) ),
				array_values( $variation )
			),
			absint( $request->get_param( 'quantity' ) ),
		);
	}

	/**
	 * Prime post caches before the per-item product lookup loop in the schema.
	 *
	 * @param ShopperListItem[] $items Items.
	 */
	private function prime_product_caches_for_items( array $items ): void {
		$ids = array_map(
			fn( $item ) => $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(),
			$items
		);

		_prime_post_caches( array_unique( $ids ) );
	}
}
