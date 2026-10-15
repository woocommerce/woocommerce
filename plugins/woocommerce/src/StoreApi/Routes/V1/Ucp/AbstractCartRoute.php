<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute as StoreApiCartRoute;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\SessionHandler;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\StoreApi\Utilities\UcpCartMapper;
use Automattic\WooCommerce\StoreApi\Utilities\UcpIdempotencyStore;
use Automattic\WooCommerce\StoreApi\Utilities\UcpRequestFingerprint;
use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

/**
 * Shared behaviour of the UCP cart routes.
 *
 * Inherits session binding, cart loading and draft order sync from the Store API cart
 * route. Adds the UCP envelope, and keys the session off the path instead of a header.
 *
 * Writes are last-wins, as for every Store API cart: the session row is read when the
 * request starts and written whole at shutdown, with no lock or version check. Two
 * callers updating the same cart id at once can overwrite each other's lines.
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
	 * Most lines one request may name.
	 *
	 * Each line can cost a product lookup, so an open route needs a ceiling. Matches the
	 * catalog search page size, so one page of results always fits in one cart request.
	 */
	const LINE_ITEMS_MAX = 100;

	/**
	 * Build the `ucp` envelope for a response from this route.
	 *
	 * @param string $status Response status, `success` or `error`.
	 * @return array
	 */
	protected function ucp_metadata( string $status ): array {
		return UcpUtils::response_metadata( UcpUtils::CAPABILITY_CART, $status );
	}

	/**
	 * Answer the request, under the `Idempotency-Key` it carries.
	 *
	 * The key is settled before anything else runs: a replay must not load a session or resolve
	 * a cart id, since a retried `cancel` has to return the stored response rather than the
	 * `not_found` its own first run left behind.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	public function get_response( \WP_REST_Request $request ) {
		$claimed = $this->claim_idempotency_key( $request );

		if ( $claimed instanceof \WP_REST_Response ) {
			return $claimed;
		}

		$response = $this->dispatch_ucp_request( $request );

		// The key stays local to this request: one route instance serves every dispatch in
		// the process, so a key held on the instance would outlive the request that claimed it.
		if ( '' !== $claimed ) {
			$this->store_idempotent_response( $claimed, $response );
		}

		return $response;
	}

	/**
	 * Record the outcome of a request that owns an idempotency key.
	 *
	 * @param string            $key Idempotency key this request owns.
	 * @param \WP_REST_Response $response Response the request produced.
	 * @return void
	 */
	private function store_idempotent_response( string $key, \WP_REST_Response $response ): void {
		if ( $this->is_replayable( $response ) ) {
			UcpIdempotencyStore::finalize( $key, $response->get_status(), $response->get_data() );

			return;
		}

		// Not replayable, so the key is released: the client's retry has to reprocess
		// rather than be answered from storage.
		UcpIdempotencyStore::delete( $key );
	}

	/**
	 * Refuse an id this store never signed, and restore the token slot afterwards.
	 *
	 * The slot is per-process, so leaving it set would bind the next dispatch in the same
	 * process — an internal `rest_do_request()`, or a test — to this cart's session.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	private function dispatch_ucp_request( \WP_REST_Request $request ): \WP_REST_Response {
		if ( '' !== $this->route_cart_id( $request ) && '' === $this->validated_cart_id( $request ) ) {
			return $this->ucp_not_found();
		}

		$inherited_token = CartTokenUtils::get_request_cart_token();

		try {
			return parent::get_response( $request );
		} finally {
			$this->restore_request_cart_token( $inherited_token );
			remove_filter( 'woocommerce_session_handler', array( self::class, 'session_handler_class' ) );
		}
	}

	/**
	 * Settle the `Idempotency-Key` a write carries, before the write runs.
	 *
	 * Reads only: a `GET` changes nothing, so a retry needs no key and is never stored.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response|string A response settling the request on its own, or the
	 *         claimed key — '' when the request proceeds owning no key.
	 */
	private function claim_idempotency_key( \WP_REST_Request $request ) {
		if ( in_array( strtoupper( (string) $request->get_method() ), array( 'GET', 'HEAD' ), true ) ) {
			return '';
		}

		/**
		 * Filters whether UCP idempotency handling runs at all.
		 *
		 * @since 11.3.0
		 *
		 * @param bool $enabled Whether the `Idempotency-Key` header is honoured. Default true.
		 */
		if ( ! apply_filters( 'woocommerce_ucp_idempotency_enabled', true ) ) {
			return '';
		}

		/**
		 * Filters whether a UCP write must carry an `Idempotency-Key`.
		 *
		 * True, the default, matches the UCP OpenAPI document, which declares the header
		 * required on every mutating cart operation: a missing or malformed key is refused
		 * at 400. False honours a key when one is sent and lets a keyless write through.
		 *
		 * @since 11.3.0
		 *
		 * @param bool $strict Whether a key is required. Default true.
		 */
		$strict = (bool) apply_filters( 'woocommerce_ucp_idempotency_strict', true );
		$key    = (string) $request->get_header( 'Idempotency-Key' );

		if ( '' === $key || ! wp_is_uuid( $key ) ) {
			if ( ! $strict ) {
				// Not stored when it is not a UUID: the column is bounded, so a longer key
				// would be truncated and could then collide with an unrelated request's.
				return '';
			}

			return $this->ucp_error(
				'' === $key ? 'IDEMPOTENCY_KEY_REQUIRED' : 'IDEMPOTENCY_KEY_INVALID',
				'' === $key
					? __( 'The Idempotency-Key header is required.', 'woocommerce' )
					: __( 'The Idempotency-Key header must be a UUID.', 'woocommerce' ),
				'recoverable',
				400
			);
		}

		$fingerprint = UcpRequestFingerprint::compute( $request );

		if ( UcpIdempotencyStore::try_begin( $key, $fingerprint ) ) {
			return $key;
		}

		$existing = UcpIdempotencyStore::find( $key );

		// A row that is gone was taken by someone, then purged: with a window measured in
		// hours, that cannot be a key claimed moments ago, so it is reported as held.
		if ( null === $existing ) {
			return $this->idempotency_in_flight();
		}

		if ( (string) $existing['request_fingerprint'] !== $fingerprint ) {
			return $this->ucp_error(
				'IDEMPOTENCY_KEY_REUSED',
				__( 'The Idempotency-Key was already used for a different request.', 'woocommerce' ),
				'unrecoverable',
				409
			);
		}

		if ( UcpIdempotencyStore::STATE_COMPLETED !== (string) $existing['state'] ) {
			return $this->idempotency_in_flight();
		}

		return $this->replay( $existing );
	}

	/**
	 * Whether a response may be stored for replay.
	 *
	 * A refusal is not: UCP reports one at 200 with `ucp.status` set to `error`, and the cart is
	 * rolled back. Nothing happened, so a retry must run again — once the stock is back or the
	 * quantity corrected, a stored refusal would stand in the corrected request's way.
	 *
	 * @param \WP_REST_Response $response Response to inspect.
	 * @return bool
	 */
	private function is_replayable( \WP_REST_Response $response ): bool {
		$status = $response->get_status();

		if ( $status < 200 || $status >= 300 ) {
			return false;
		}

		$data = $response->get_data();

		return ! is_array( $data ) || ! isset( $data['ucp']['status'] ) || 'error' !== $data['ucp']['status'];
	}

	/**
	 * Replay a stored response.
	 *
	 * The original status is repeated, since that is the contract the first call published;
	 * the `Idempotency-Replayed` header is what sets a replay apart from a fresh answer.
	 *
	 * @param array<string, mixed> $existing Stored record.
	 * @return \WP_REST_Response
	 */
	private function replay( array $existing ): \WP_REST_Response {
		$response = new \WP_REST_Response(
			json_decode( (string) $existing['response_body'], true ),
			isset( $existing['response_status'] ) ? (int) $existing['response_status'] : 200
		);

		$response->header( 'Idempotency-Replayed', 'true' );

		return $response;
	}

	/**
	 * Refuse a key another request is still working on.
	 *
	 * @return \WP_REST_Response
	 */
	private function idempotency_in_flight(): \WP_REST_Response {
		return $this->ucp_error(
			'IDEMPOTENCY_CONFLICT',
			__( 'A request with this Idempotency-Key is still being processed.', 'woocommerce' ),
			'recoverable',
			409
		);
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

		if ( '' === $cart_id ) {
			// Creation starts from a fresh guest session: the id is minted here, never
			// inherited from a Cart-Token header or from the logged-in user. Without this the
			// token handler would key an empty session on the user id and overwrite the
			// shopper's own session row on save.
			$cart_id = CartTokenUtils::get_cart_token( wc_rand_hash( 't_', 30 ) );
		}

		$request->set_header( 'Cart-Token', $cart_id );
		// phpcs:ignore WooCommerceStoreApi.StoreApi.CartTokenSource.ServerSuperglobalWrite -- Either the route's own path segment, signature checked in validated_cart_id(), or a token minted just above; promoted to the slot PHP would have filled from a Cart-Token header.
		$_SERVER['HTTP_CART_TOKEN'] = $cart_id;

		// The parent promotes the token handler only for a request that carries a token,
		// which would leave cart creation on the cookie handler. UCP has no cookie to
		// trust: the id is the only credential, so the handler is fixed for every route.
		add_filter( 'woocommerce_session_handler', array( self::class, 'session_handler_class' ) );

		parent::load_cart_session( $request );
	}

	/**
	 * The session handler a UCP cart route binds to.
	 *
	 * Token-keyed, so an id this store signed is the only way to reach a cart. Without a
	 * valid token it mints an empty session, which is what cart creation needs.
	 *
	 * @return string
	 */
	public static function session_handler_class(): string {
		return SessionHandler::class;
	}

	/**
	 * The request's cart id when its signature checks out, or '' when it does not.
	 *
	 * Read from the request every time: the route instance outlives a request, so a
	 * cached id would bind the next dispatch in the same process to this cart.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return string
	 */
	protected function validated_cart_id( \WP_REST_Request $request ): string {
		$cart_id = $this->route_cart_id( $request );

		return CartTokenUtils::validate_cart_token( $cart_id ) ? $cart_id : '';
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
	 * Safe because `load_cart_session()` binds every route to the token-keyed session
	 * handler, so no request here reads the browser's session cookie: there is no ambient
	 * credential for a cross-site request to ride on. Cart creation is left open
	 * deliberately — it mints a guest session of its own, whoever is logged in.
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
				( new UcpCartMapper() )->build_cart( $this->cart_item_response(), $cart_id, $context )
			),
			200
		);
	}

	/**
	 * Build the Store API cart response for the current cart, as nested arrays.
	 *
	 * @return array
	 */
	private function cart_item_response(): array {
		// Nested values are stdClass; decode through JSON for uniform array access.
		return (array) json_decode( (string) wp_json_encode( $this->schema->get_item_response( $this->cart_controller->get_cart_for_response() ) ), true );
	}

	/**
	 * Build an error response.
	 *
	 * `$status` follows the split UCP draws: a malformed request is a transport error and
	 * keeps a non-2xx status, while a valid request that ends in a refusal is a business
	 * outcome and is reported at 200 through `ucp.status` and `messages`.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param string $severity Error severity.
	 * @param int    $status HTTP status.
	 * @param string $path JSONPath of the offending field.
	 * @return \WP_REST_Response
	 */
	protected function ucp_error( string $code, string $message, string $severity, int $status, string $path = '$' ): \WP_REST_Response {
		return UcpUtils::error_response( UcpUtils::CAPABILITY_CART, $code, $message, $severity, $status, $path );
	}

	/**
	 * Report an error the parent raised in the UCP envelope.
	 *
	 * Every failure the Store API base class produces — a session that fails to load,
	 * a `RouteException` a handler let through, an unexpected exception — funnels through
	 * here. The cart's own code is kept where UCP has a name for it, and the HTTP status
	 * is the parent's. The `cart` payload the parent attaches to a 409 conflict is dropped:
	 * the UCP envelope has no slot for it, and the client re-reads the cart instead.
	 *
	 * @param \WP_Error $error WP Error object.
	 * @return \WP_REST_Response
	 */
	protected function error_to_response( $error ) {
		$data    = $error->get_error_data();
		$status  = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 500;
		$message = (string) $error->get_error_message();

		return $this->ucp_error(
			UcpUtils::cart_error_code( (string) $error->get_error_code(), 'TRANSPORT_ERROR' ),
			'' !== $message ? $message : __( 'The cart could not be loaded. Please try again.', 'woocommerce' ),
			$status >= 500 ? 'unrecoverable' : 'recoverable',
			$status
		);
	}

	/**
	 * Build the response for a cart id that does not resolve.
	 *
	 * Never existed, expired and cancelled are reported alike, and at 200: the request was
	 * valid, so the refusal is a business outcome. See `ucp_error()`.
	 *
	 * @return \WP_REST_Response
	 */
	protected function ucp_not_found(): \WP_REST_Response {
		return UcpUtils::error_response( UcpUtils::CAPABILITY_CART, 'not_found', __( 'Cart not found.', 'woocommerce' ), 'unrecoverable', 200, '$.id' );
	}

	/**
	 * Parse the `line_items` of a request body into desired quantities.
	 *
	 * Two lines can hold the same product and differ only in cart item data, as an add-on
	 * does, so a payload naming a line is collected per cart item. A line id the cart no
	 * longer holds falls back to `item.id`: the buyer may have removed it meanwhile.
	 *
	 * A malformed payload is refused at 400, an unsellable id at 200 — see `ucp_error()`.
	 *
	 * @param mixed $line_items Raw `line_items` value.
	 * @return array{by_key: array<string, int>, by_product: array<int, int>}|\WP_REST_Response
	 *         Quantities per cart item key and per product id, or an error response.
	 */
	protected function parse_line_items( $line_items ) {
		if ( ! is_array( $line_items ) ) {
			return $this->ucp_error( 'VALIDATION_ERROR', __( 'line_items must be an array.', 'woocommerce' ), 'recoverable', 400, '$.line_items' );
		}

		if ( count( $line_items ) > self::LINE_ITEMS_MAX ) {
			return $this->ucp_error(
				'VALIDATION_ERROR',
				/* translators: %d: maximum number of line items accepted in one request. */
				sprintf( __( 'line_items may hold at most %d entries.', 'woocommerce' ), self::LINE_ITEMS_MAX ),
				'recoverable',
				400,
				'$.line_items'
			);
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

			$item_id = (string) $line_item['item']['id'];
			$product = $this->get_item_product( $item_id );

			// The payload is well formed; the store simply has nothing orderable under that
			// id. A business outcome, so 200 like every other refusal. An id matching no
			// product is `not_found`; one matching a product a cart line cannot hold — a
			// variable parent, grouped, external or otherwise unpurchasable — exists but is
			// unavailable.
			if ( ! $product instanceof \WC_Product ) {
				return $this->ucp_error( 'not_found', __( 'Product not found.', 'woocommerce' ), 'unrecoverable', 200, $path . '.item.id' );
			}

			if ( $product instanceof \WC_Product_Variable || ! $product->is_purchasable() ) {
				$message = $product instanceof \WC_Product_Variable
					? __( 'This product is only orderable as one of its variants. Use a variant id from the catalog.', 'woocommerce' )
					: __( 'This product cannot be added to a cart.', 'woocommerce' );

				return $this->ucp_error( 'item_unavailable', $message, 'recoverable', 200, $path . '.item.id' );
			}

			// Repeating an id is additive, matching how a cart treats two adds of the
			// same product.
			$product_id                           = (int) $product->get_id();
			$desired['by_product'][ $product_id ] = ( $desired['by_product'][ $product_id ] ?? 0 ) + $quantity;
		}

		return $desired;
	}

	/**
	 * Load the product an item id names.
	 *
	 * Ids only, as the catalog routes resolve them: the catalog hands out no SKU
	 * identifier, and a SKU fallback would let a numeric SKU resolve as a post id.
	 *
	 * @param string $item_id Item identifier.
	 * @return \WC_Product|null
	 */
	private function get_item_product( string $item_id ) {
		$product_id = ctype_digit( $item_id ) ? (int) $item_id : 0;
		$product    = $product_id > 0 ? wc_get_product( $product_id ) : null;

		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * Bring the cart in line with a set of desired quantities.
	 *
	 * `line_items` is the complete desired state, so an unaccounted-for line is removed.
	 * An entry naming a cart item addresses that one; an entry naming only a product takes
	 * the first line holding it, and further lines of that product go.
	 *
	 * A refusal from the cart is returned in the UCP envelope, carrying the cart's own
	 * message, since the Store API error shape has no `ucp` object to read. Removals run
	 * before adds, so the contents are restored first: otherwise replacing a cart with
	 * something out of stock would empty it.
	 *
	 * @param array{by_key: array<string, int>, by_product: array<int, int>} $desired Desired quantities.
	 * @return \WP_REST_Response|null An error response, or null when the cart was updated.
	 * @throws \Throwable Anything other than a cart refusal, after the cart is restored.
	 */
	protected function apply_line_items( array $desired ) {
		$cart     = $this->cart_controller->get_cart_instance();
		$snapshot = $cart->get_cart_contents();

		try {
			$this->reconcile_line_items( $desired );
		} catch ( \Throwable $error ) {
			// Whatever threw, including a hook inside the cart, the removals already
			// applied must not stand.
			$cart->set_cart_contents( $snapshot );
			$this->cart_controller->calculate_totals();

			if ( ! $error instanceof RouteException ) {
				throw $error;
			}

			return $this->ucp_error(
				UcpUtils::cart_error_code( $error->getErrorCode() ),
				$error->getMessage(),
				'recoverable',
				200,
				'$.line_items'
			);
		}

		return null;
	}

	/**
	 * Reconcile the cart to the desired quantities.
	 *
	 * @param array{by_key: array<string, int>, by_product: array<int, int>} $desired Desired quantities.
	 * @throws RouteException When an item cannot be added or adjusted.
	 */
	private function reconcile_line_items( array $desired ): void {
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
				// Totals are recalculated once below, not per changed line.
				$this->cart_controller->set_cart_item_quantity( $key, $target, false );
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
