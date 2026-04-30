<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperList;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListItem;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ShopperListSchema;

/**
 * GET / POST on /shopper-lists/{slug}/items.
 *
 * GET returns the items in a list.
 * POST saves an item to the list either from an existing cart line or from direct item payload fields.
 */
class ShopperListItems extends AbstractRoute {
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
						'description' => __( 'Product ID to save directly when cart_item_key is not supplied.', 'woocommerce' ),
						'type'        => 'integer',
					),
					'variation_id'  => array(
						'description' => __( 'Variation ID, when saving a variation product directly.', 'woocommerce' ),
						'type'        => 'integer',
					),
					'variation'     => array(
						'description' => __( 'Variation attributes keyed by attribute name.', 'woocommerce' ),
						'type'        => 'array',
					),
					'quantity'      => array(
						'description' => __( 'Quantity for the saved item.', 'woocommerce' ),
						'type'        => 'integer',
					),
					'item_data'     => array(
						'description' => __( 'Custom item data captured with the saved item.', 'woocommerce' ),
						'type'        => 'array',
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
			throw new RouteException( 'woocommerce_rest_shopper_list_not_found', esc_html__( 'Shopper list not found.', 'woocommerce' ), 404 );
		}

		$items = array_values( $list->get_items() );
		$this->prime_product_caches_for_items( $items );

		$response = array();
		foreach ( $items as $item ) {
			$response[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $item->to_array(), $request ) );
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
			throw new RouteException( 'woocommerce_rest_shopper_list_not_found', esc_html__( 'Shopper list not found.', 'woocommerce' ), 404 );
		}

		[ $lookup_id, $variation, $item_data, $quantity ] = $this->resolve_item_payload( $request );

		$item = ShopperListItem::from_product( $lookup_id, $variation, $item_data, $quantity );
		if ( ! $item ) {
			throw new RouteException( 'woocommerce_rest_shopper_list_unknown_product', esc_html__( 'No product exists for the supplied item.', 'woocommerce' ), 404 );
		}

		$list->add_item( $item );
		$list->save();

		return $this->prepare_list_response( $list, 201 );
	}

	/**
	 * Render a full ShopperList response (metadata + items) for write endpoints.
	 *
	 * @param ShopperList $shopper_list Shopper list to render.
	 * @param int         $status       HTTP status to set on the response.
	 *
	 * @return \WP_REST_Response
	 */
	private function prepare_list_response( ShopperList $shopper_list, int $status = 200 ): \WP_REST_Response {
		$items = array_values( $shopper_list->get_items() );
		$this->prime_product_caches_for_items( $items );

		$list_schema       = $this->schema_controller->get( ShopperListSchema::IDENTIFIER );
		$response          = (array) $list_schema->get_item_response( $shopper_list->to_array() );
		$response['items'] = array_map(
			fn( ShopperListItem $item ) => $this->schema->get_item_response( $item->to_array() ),
			$items
		);

		return new \WP_REST_Response( $response, $status );
	}

	/**
	 * Resolve the POST input into a uniform payload (product lookup id, variation, item_data).
	 *
	 * Accepts either an existing cart_item_key, or direct product_id/variation_id/variation/item_data.
	 *
	 * @throws RouteException When neither a cart_item_key nor a product_id is supplied, or the cart_item_key is unknown.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return array{0:int,1:array,2:array,3:int} `[ lookup_id, variation, item_data, quantity ]`.
	 */
	private function resolve_item_payload( \WP_REST_Request $request ): array {
		$cart_item_key = (string) $request->get_param( 'cart_item_key' );

		if ( $cart_item_key ) {
			$cart = WC()->cart;
			if ( ! $cart instanceof \WC_Cart || empty( $cart->cart_contents[ $cart_item_key ] ) ) {
				throw new RouteException( 'woocommerce_rest_shopper_list_invalid_cart_item_key', esc_html__( 'No cart item exists for the supplied key.', 'woocommerce' ), 404 );
			}

			$line            = $cart->cart_contents[ $cart_item_key ];
			$product_id      = absint( $line['product_id'] ?? 0 );
			$variation_id    = absint( $line['variation_id'] ?? 0 );
			$variation_attrs = isset( $line['variation'] ) && is_array( $line['variation'] ) ? $line['variation'] : array();

			return array(
				$variation_id ? $variation_id : $product_id,
				$variation_attrs,
				$this->extract_custom_cart_item_data( $line ),
				absint( $line['quantity'] ?? 1 ),
			);
		}

		$product_id = absint( $request->get_param( 'product_id' ) );
		if ( ! $product_id ) {
			throw new RouteException( 'woocommerce_rest_shopper_list_missing_item_input', esc_html__( 'Provide cart_item_key or product_id.', 'woocommerce' ), 400 );
		}

		$variation_id = absint( $request->get_param( 'variation_id' ) );

		return array(
			$variation_id ? $variation_id : $product_id,
			(array) $request->get_param( 'variation' ),
			(array) $request->get_param( 'item_data' ),
			absint( $request->get_param( 'quantity' ) ),
		);
	}

	/**
	 * Strip the WC_Product and line totals from a cart line, leaving only serializable custom fields.
	 *
	 * @param array $cart_item Cart item array.
	 */
	private function extract_custom_cart_item_data( array $cart_item ): array {
		$skip = array( 'data', 'data_hash', 'product_id', 'variation_id', 'variation', 'quantity', 'key', 'line_subtotal', 'line_subtotal_tax', 'line_total', 'line_tax', 'line_tax_data' );
		$data = array();
		foreach ( $cart_item as $k => $v ) {
			if ( in_array( $k, $skip, true ) ) {
				continue;
			}
			$data[ $k ] = $v;
		}
		return $data;
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
