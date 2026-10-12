<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute as StoreApiCartRoute;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\StoreApi\Utilities\UcpCartTransformer;
use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

/**
 * Shared behaviour of the UCP cart routes.
 *
 * Inherits session binding, cart loading and draft order sync from the Store API cart
 * route. Adds the UCP envelope, and keys the session off the path instead of a header.
 */
abstract class AbstractCartRoute extends StoreApiCartRoute {
	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'ucp-cart';

	/**
	 * Session key marking a cart that was cancelled through UCP.
	 *
	 * Without it a cancelled cart is indistinguishable from an empty one. Read by the UCP
	 * routes only, so a storefront session cloned from the cart keeps working.
	 */
	const CANCELLED_SESSION_KEY = 'ucp_cart_cancelled';

	/**
	 * Cart transformer.
	 *
	 * @var UcpCartTransformer|null
	 */
	private $transformer;

	/**
	 * The route's cart id once validated. Null until first resolved.
	 *
	 * @var string|null
	 */
	private $validated_cart_id;

	/**
	 * Build the `ucp` envelope for a response from this route.
	 *
	 * @param string|null $status Response status, omitted from the envelope when null.
	 * @return array
	 */
	protected function ucp_metadata( ?string $status = null ): array {
		return UcpUtils::response_metadata( UcpUtils::CAPABILITY_CART, $status );
	}

	/**
	 * Get the cart transformer.
	 *
	 * @return UcpCartTransformer
	 */
	protected function transformer(): UcpCartTransformer {
		if ( null === $this->transformer ) {
			$this->transformer = new UcpCartTransformer();
		}

		return $this->transformer;
	}

	/**
	 * Refuse an id this store never signed, then hand off with the token slot restored.
	 *
	 * The slot is per-process, not per-dispatch, so a second route dispatched in the same
	 * process — `rest_do_request()` from other code, or a test — would otherwise inherit
	 * this cart's token and bind to its session.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	public function get_response( \WP_REST_Request $request ) {
		if ( '' !== $this->route_cart_id( $request ) && '' === $this->validated_cart_id( $request ) ) {
			return $this->ucp_not_found();
		}

		$inherited_token = CartTokenUtils::get_request_cart_token();

		try {
			return parent::get_response( $request );
		} finally {
			$this->restore_request_cart_token( $inherited_token );
		}
	}

	/**
	 * Put the request's cart token slot back the way it was found.
	 *
	 * @param string $token The token the request arrived with, or '' for none.
	 */
	private function restore_request_cart_token( string $token ): void {
		if ( '' === $token ) {
			// phpcs:ignore WooCommerceStoreApi.StoreApi.CartTokenSource.ServerSuperglobal -- Clearing the slot, not reading the token from it.
			unset( $_SERVER['HTTP_CART_TOKEN'] );
			return;
		}

		// phpcs:ignore WooCommerceStoreApi.StoreApi.CartTokenSource.ServerSuperglobalWrite -- Restoring the value the request arrived with, already validated by whatever read it.
		$_SERVER['HTTP_CART_TOKEN'] = $token;
	}

	/**
	 * Bind the session to the cart id in the route path.
	 *
	 * The parent picks the Store API session handler from a `Cart-Token` header. UCP
	 * carries that token in the path, so it is promoted to the header first.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return void
	 */
	protected function load_cart_session( \WP_REST_Request $request ) {
		$cart_id = $this->validated_cart_id( $request );

		if ( '' !== $cart_id ) {
			$request->set_header( 'Cart-Token', $cart_id );
			// phpcs:ignore WooCommerceStoreApi.StoreApi.CartTokenSource.ServerSuperglobalWrite -- Signature checked in validated_cart_id(); this is the route's own path segment, promoted to the slot PHP would have filled from a Cart-Token header.
			$_SERVER['HTTP_CART_TOKEN'] = $cart_id;
		}

		parent::load_cart_session( $request );
	}

	/**
	 * The route's cart id once its signature checks out, or '' when it does not.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return string
	 */
	protected function validated_cart_id( \WP_REST_Request $request ): string {
		if ( null === $this->validated_cart_id ) {
			$cart_id                 = $this->route_cart_id( $request );
			$this->validated_cart_id = CartTokenUtils::validate_cart_token( $cart_id ) ? $cart_id : '';
		}

		return $this->validated_cart_id;
	}

	/**
	 * Dispatch the request, refusing a cart that was cancelled.
	 *
	 * Runs after the session is bound, so one check covers every id-bearing route.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function get_response_by_request_method( \WP_REST_Request $request ) {
		if ( '' !== $this->route_cart_id( $request ) && $this->cart_is_cancelled() ) {
			return $this->ucp_not_found();
		}

		return parent::get_response_by_request_method( $request );
	}

	/**
	 * UCP requests are not browser requests, so they carry no nonce.
	 *
	 * Cart creation has no token yet and is left open deliberately: it mints an empty
	 * guest cart, the same as an anonymous storefront add-to-cart.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return bool
	 */
	protected function requires_nonce( \WP_REST_Request $request ) {
		return false;
	}

	/**
	 * Read the cart id from the route path.
	 *
	 * `get_url_params()` only: `get_param()` merges body and query over it, which would
	 * let a caller name a session the route never handed them.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return string The path-supplied id, or '' when the route has no `id` segment.
	 */
	protected function route_cart_id( \WP_REST_Request $request ): string {
		$params = $request->get_url_params();

		return isset( $params['id'] ) && is_scalar( $params['id'] ) ? (string) $params['id'] : '';
	}

	/**
	 * Mint the cart id for the session the request is bound to.
	 *
	 * @return string
	 */
	protected function current_cart_id(): string {
		$session = WC()->session;

		return $session ? CartTokenUtils::get_cart_token( (string) $session->get_customer_id() ) : '';
	}

	/**
	 * Whether the bound session's cart was cancelled through UCP.
	 *
	 * @return bool
	 */
	protected function cart_is_cancelled(): bool {
		$session = WC()->session;

		return $session ? (bool) $session->get( self::CANCELLED_SESSION_KEY ) : false;
	}

	/**
	 * Release a session held by a cancelled cart, so a new cart can be created in it.
	 *
	 * The marker is sticky, so for a caller that keeps its session every later cart would
	 * answer `not_found`. A guest gets a new id, keeping the cancelled one dead; a
	 * logged-in session is keyed to the user, so there the cancelled id comes back to life.
	 */
	protected function release_cancelled_session(): void {
		$session = WC()->session;

		if ( ! $session || ! $this->cart_is_cancelled() ) {
			return;
		}

		// Only the cookie handler mints a replacement customer id here; the token handler
		// blanks it instead, which would leave the new cart with no id to be addressed by.
		if ( $session instanceof \WC_Session_Handler ) {
			$session->forget_session();
		}

		$session->set( self::CANCELLED_SESSION_KEY, null );
	}

	/**
	 * Build a successful cart response.
	 *
	 * @param string               $cart_id Cart identifier.
	 * @param array<string, mixed> $context Optional `messages` to merge into the payload.
	 * @return \WP_REST_Response
	 */
	protected function cart_response( string $cart_id, array $context = array() ): \WP_REST_Response {
		return new \WP_REST_Response(
			array_merge(
				array( 'ucp' => $this->ucp_metadata( 'success' ) ),
				$this->transformer()->build_cart( $this->cart_controller->get_cart_instance(), $cart_id, $context )
			),
			200
		);
	}

	/**
	 * Build a transport-level error response.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param string $severity Error severity.
	 * @param int    $status HTTP status.
	 * @param string $path JSONPath of the offending field.
	 * @return \WP_REST_Response
	 */
	protected function ucp_error( string $code, string $message, string $severity, int $status, string $path = '$' ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'ucp'      => $this->ucp_metadata( 'error' ),
				'messages' => array(
					array(
						'type'         => 'error',
						'code'         => $code,
						'content'      => $message,
						'severity'     => $severity,
						'path'         => $path,
						'content_type' => 'plain',
					),
				),
			),
			$status
		);
	}

	/**
	 * Build the response for a cart id that does not resolve.
	 *
	 * Never existed, expired and cancelled are reported alike, in the envelope rather
	 * than as a 404: UCP clients read `ucp.status`, not the HTTP status.
	 *
	 * @return \WP_REST_Response
	 */
	protected function ucp_not_found(): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'ucp'      => $this->ucp_metadata( 'error' ),
				'messages' => array(
					array(
						'type'         => 'error',
						'code'         => 'not_found',
						'content'      => __( 'Cart not found.', 'woocommerce' ),
						'severity'     => 'unrecoverable',
						'path'         => '$.id',
						'content_type' => 'plain',
					),
				),
			),
			200
		);
	}

	/**
	 * Parse the `line_items` of a request body into desired quantities.
	 *
	 * Two lines can hold the same product and differ only in cart item data, as an add-on
	 * does, so a payload naming a line is collected per cart item. A line id the cart no
	 * longer holds falls back to `item.id`: the buyer may have removed it meanwhile.
	 *
	 * @param mixed $line_items Raw `line_items` value.
	 * @return array{by_key: array<string, int>, by_product: array<int, int>}|\WP_REST_Response
	 *         Quantities per cart item key and per product id, or an error response.
	 */
	protected function parse_line_items( $line_items ) {
		if ( ! is_array( $line_items ) ) {
			return $this->ucp_error( 'VALIDATION_ERROR', __( 'line_items must be an array.', 'woocommerce' ), 'recoverable', 400, '$.line_items' );
		}

		$cart_items = $this->cart_controller->get_cart_items();
		$desired    = array(
			'by_key'     => array(),
			'by_product' => array(),
		);

		foreach ( $line_items as $index => $line_item ) {
			$path = sprintf( '$.line_items[%d]', (int) $index );

			if ( ! is_array( $line_item ) || ! isset( $line_item['item']['id'] ) || ! is_scalar( $line_item['item']['id'] ) ) {
				return $this->ucp_error( 'VALIDATION_ERROR', __( 'Each line item requires item.id.', 'woocommerce' ), 'recoverable', 400, $path . '.item.id' );
			}

			$quantity = isset( $line_item['quantity'] ) ? (int) $line_item['quantity'] : 1;

			if ( $quantity < 1 ) {
				return $this->ucp_error( 'VALIDATION_ERROR', __( 'Line item quantity must be at least 1.', 'woocommerce' ), 'recoverable', 400, $path . '.quantity' );
			}

			$item_key = isset( $line_item['id'] ) && is_scalar( $line_item['id'] ) ? (string) $line_item['id'] : '';

			// A named line is addressed directly, so the product behind it is not
			// re-resolved: one that has since gone out of stock must still be adjustable.
			if ( '' !== $item_key && isset( $cart_items[ $item_key ] ) ) {
				$desired['by_key'][ $item_key ] = ( $desired['by_key'][ $item_key ] ?? 0 ) + $quantity;
				continue;
			}

			$product_id = $this->resolve_item_id( (string) $line_item['item']['id'] );

			if ( 0 === $product_id ) {
				return $this->ucp_error( 'not_found', __( 'Product not found.', 'woocommerce' ), 'unrecoverable', 400, $path . '.item.id' );
			}

			// Repeating an id is additive, matching how a cart treats two adds of the
			// same product.
			$desired['by_product'][ $product_id ] = ( $desired['by_product'][ $product_id ] ?? 0 ) + $quantity;
		}

		return $desired;
	}

	/**
	 * Resolve a UCP item id to a purchasable product id.
	 *
	 * Accepts anything catalog hands out: product id, variation id or SKU.
	 *
	 * @param string $item_id Item identifier.
	 * @return int Product id, or 0 when nothing purchasable matched.
	 */
	protected function resolve_item_id( string $item_id ): int {
		$product_id = ctype_digit( $item_id ) ? (int) $item_id : (int) wc_get_product_id_by_sku( $item_id );
		$product    = $product_id > 0 ? wc_get_product( $product_id ) : null;

		return $product instanceof \WC_Product && $product->is_purchasable() ? (int) $product->get_id() : 0;
	}

	/**
	 * Bring the cart in line with a set of desired quantities.
	 *
	 * `line_items` is the complete desired state, so an unaccounted-for line is removed.
	 * An entry naming a cart item addresses that one; an entry naming only a product
	 * takes the first line holding it, and further lines of that product go.
	 *
	 * @param array{by_key: array<string, int>, by_product: array<int, int>} $desired Desired quantities.
	 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException When an item cannot be added.
	 */
	protected function apply_line_items( array $desired ): void {
		$by_key     = $desired['by_key'];
		$by_product = $desired['by_product'];

		foreach ( $this->cart_controller->get_cart_items() as $key => $cart_item ) {
			$key        = (string) $key;
			$product_id = (int) ( $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'] );
			$quantity   = (int) $cart_item['quantity'];

			if ( isset( $by_key[ $key ] ) ) {
				$target = $by_key[ $key ];
			} elseif ( isset( $by_product[ $product_id ] ) ) {
				$target = $by_product[ $product_id ];
				// Claimed, so a second line of the same product is not kept as a duplicate.
				unset( $by_product[ $product_id ] );
			} else {
				$this->cart_controller->get_cart_instance()->remove_cart_item( $key );
				continue;
			}

			if ( $target !== $quantity ) {
				$this->cart_controller->set_cart_item_quantity( $key, $target );
			}
		}

		foreach ( $by_product as $product_id => $quantity ) {
			$this->cart_controller->add_to_cart(
				array(
					'id'       => $product_id,
					'quantity' => $quantity,
				)
			);
		}

		$this->cart_controller->calculate_totals();
	}
}
