<?php
/**
 * UCP Cart Route Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\Ucp\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\SessionHandler;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;

/**
 * Tests for the /wc/ucp/v1/carts endpoints.
 */
class UcpCart extends ControllerTestCase {
	/**
	 * Published product IDs shared by the class, oldest first.
	 *
	 * @var int[]
	 */
	private static $product_ids = array();

	/**
	 * A product with exactly one unit in stock.
	 *
	 * @var int
	 */
	private static $limited_stock_id = 0;

	/**
	 * Variable parent id and one of its variation ids.
	 *
	 * @var array{parent: int, variation: int}
	 */
	private static $variable = array(
		'parent'    => 0,
		'variation' => 0,
	);

	/**
	 * Option behind the `ucp` feature.
	 */
	private const FEATURE_OPTION = 'woocommerce_feature_ucp_enabled';

	/**
	 * Create immutable fixtures before route registration starts.
	 */
	public static function wpSetUpBeforeClass(): void {
		$products = self::create_class_fixture_products(
			array(
				array(
					'name'          => 'UCP Cart Mug',
					'regular_price' => '19.99',
					'price'         => '19.99',
					'sku'           => 'ucp-cart-mug',
				),
				array(
					'name'          => 'UCP Cart Poster',
					'regular_price' => '5.00',
					'price'         => '5.00',
					'sku'           => 'ucp-cart-poster',
				),
			)
		);

		self::$product_ids = array_map(
			static function ( $product ) {
				return $product->get_id();
			},
			$products
		);

		$limited = self::create_class_fixture_products(
			array(
				array(
					'name'           => 'UCP Cart Last One',
					'regular_price'  => '9.00',
					'price'          => '9.00',
					'manage_stock'   => true,
					'stock_quantity' => 1,
				),
			)
		);

		self::$limited_stock_id = $limited[0]->get_id();

		$fixtures  = new \Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData();
		$parent    = $fixtures->get_variable_product( array( 'name' => 'UCP Cart Tee' ), array( $fixtures->get_product_attribute( 'size', array( 'small', 'large' ) ) ) );
		$variation = $fixtures->get_variation_product(
			$parent->get_id(),
			array( 'pa_size' => 'small' ),
			array( 'regular_price' => '12.00' )
		);

		// Sync so the parent reports a price and therefore passes is_purchasable(), as a
		// real variable product does — that is what makes the parent reach add_to_cart
		// unless the route refuses it first.
		\WC_Product_Variable::sync( $parent->get_id() );

		self::$variable = array(
			'parent'    => $parent->get_id(),
			'variation' => $variation->get_id(),
		);
	}

	/**
	 * Delete class fixtures.
	 */
	public static function wpTearDownAfterClass(): void {
		self::delete_class_fixture_products(
			array_merge(
				self::$product_ids,
				array( self::$limited_stock_id, self::$variable['variation'], self::$variable['parent'] )
			)
		);
	}

	/**
	 * Enable the UCP routes before the REST server is built.
	 */
	protected function setUp(): void {
		update_option( self::FEATURE_OPTION, 'yes' );
		parent::setUp();
	}

	/**
	 * Restore the default disabled state and clear the cancellation marker.
	 */
	protected function tearDown(): void {
		delete_option( self::FEATURE_OPTION );
		unset( $_SERVER['HTTP_CART_TOKEN'] );

		if ( WC()->session ) {
			WC()->session->set( AbstractCartRoute::CANCELLED_SESSION_KEY, null );
		}

		if ( WC()->cart instanceof \WC_Cart ) {
			\WC_Cart_Session::set_updates_enabled_for_cart( WC()->cart, true );
		}

		parent::tearDown();
	}

	/**
	 * Dispatch a request with a JSON body.
	 *
	 * @param string $method HTTP method.
	 * @param string $path Route path under /wc/ucp/v1.
	 * @param array  $body Request body.
	 * @return \WP_REST_Response
	 */
	private function request( string $method, string $path, array $body = array() ) {
		$request = new \WP_REST_Request( $method, '/wc/ucp/v1' . $path );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Create a cart holding the given line items.
	 *
	 * @param array $line_items UCP line items.
	 * @return array The response data.
	 */
	private function create_cart( array $line_items ): array {
		$response = $this->request( 'POST', '/carts', array( 'line_items' => $line_items ) );

		$this->assertSame( 200, $response->get_status() );

		return $response->get_data();
	}

	/**
	 * Build a UCP line item for one of the fixture products.
	 *
	 * @param int $index Fixture index.
	 * @param int $quantity Quantity.
	 * @return array
	 */
	private function line_item( int $index, int $quantity ): array {
		return array(
			'item'     => array( 'id' => (string) self::$product_ids[ $index ] ),
			'quantity' => $quantity,
		);
	}

	/**
	 * Start a session the way a fresh HTTP request would.
	 *
	 * The session and cart are loaded once per process, so a test that needs a second
	 * request has to rebuild them: persist what the current request would save on
	 * shutdown, then construct the handler the next request would pick.
	 *
	 * @param string $cart_token Cart token the next request carries, or '' for none.
	 */
	private function simulate_new_request( string $cart_token = '' ): void {
		if ( WC()->session ) {
			// WC_Session_Handler::save_data() is a no-op without a session cookie, and no
			// cookie is ever sent in this suite. A real cart request sets one through
			// woocommerce_set_cart_cookies, so flip the same flag before saving.
			if ( WC()->session instanceof \WC_Session_Handler ) {
				$has_cookie = new \ReflectionProperty( WC()->session, '_has_cookie' );
				$has_cookie->setAccessible( true );
				$has_cookie->setValue( WC()->session, true );
			}

			WC()->session->save_data();
		}

		if ( '' === $cart_token ) {
			unset( $_SERVER['HTTP_CART_TOKEN'] );
		} else {
			$_SERVER['HTTP_CART_TOKEN'] = $cart_token;
		}

		$session = '' === $cart_token ? new \WC_Session_Handler() : new SessionHandler();
		$session->init();

		WC()->session  = $session;
		WC()->customer = new \WC_Customer( get_current_user_id(), true );
		WC()->cart     = new \WC_Cart();
	}

	/**
	 * Read one entry out of a UCP totals array.
	 *
	 * @param array  $totals Totals array.
	 * @param string $type Total type.
	 * @return int|null
	 */
	private function total( array $totals, string $type ) {
		foreach ( $totals as $entry ) {
			if ( $type === $entry['type'] ) {
				return $entry['amount'];
			}
		}

		return null;
	}

	/**
	 * The routes must not exist unless a site opts in.
	 */
	public function test_routes_are_not_registered_by_default(): void {
		delete_option( self::FEATURE_OPTION );
		$this->initialize_store_api_server();

		$this->assertSame( 404, $this->request( 'POST', '/carts' )->get_status() );
		$this->assertSame( 404, $this->request( 'GET', '/carts/abc' )->get_status() );
		$this->assertSame( 404, $this->request( 'POST', '/carts/abc/cancel' )->get_status() );
	}

	/**
	 * A UCP cart route never reads the browser's session cookie.
	 *
	 * Cart creation carries no token, so the parent would leave the cookie handler in
	 * place and bind the route to whatever cart the caller's cookie names. With
	 * `line_items` read as the complete desired state, a cross-site POST would then
	 * rewrite that shopper's cart, and `requires_nonce()` returning false leaves nothing
	 * else in the way. The handler is fixed for every route here instead.
	 */
	public function test_cart_routes_never_bind_to_the_session_cookie(): void {
		unset( $_SERVER['HTTP_CART_TOKEN'] );

		$routes = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$route  = $routes->get( 'ucp-carts', 'ucp' );

		$bind = new \ReflectionMethod( $route, 'load_cart_session' );
		$bind->setAccessible( true );
		$bind->invoke( $route, new \WP_REST_Request( 'POST', '/wc/ucp/v1/carts' ) );

		try {
			$this->assertSame(
				SessionHandler::class,
				apply_filters( 'woocommerce_session_handler', \WC_Session_Handler::class )
			);
		} finally {
			remove_filter( 'woocommerce_session_handler', array( AbstractCartRoute::class, 'session_handler_class' ) );
		}
	}

	/**
	 * A request naming more lines than the ceiling is refused before any product lookup.
	 */
	public function test_create_cart_refuses_too_many_line_items(): void {
		$line_items = array_fill( 0, AbstractCartRoute::LINE_ITEMS_MAX + 1, $this->line_item( 0, 1 ) );

		$response = $this->request( 'POST', '/carts', array( 'line_items' => $line_items ) );
		$data     = $response->get_data();

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'error', $data['ucp']['status'] );
		$this->assertSame( 'VALIDATION_ERROR', $data['messages'][0]['code'] );
		$this->assertSame( '$.line_items', $data['messages'][0]['path'] );

		// Exactly the ceiling is still accepted.
		$this->create_cart( array_slice( $line_items, 0, AbstractCartRoute::LINE_ITEMS_MAX ) );
	}

	/**
	 * A quantity-limit refusal is reported as `invalid_quantity`, distinct from a gone item.
	 *
	 * An agent can act on the two differently — fix the quantity vs. drop the line — so the
	 * mapping keeps them apart instead of collapsing both to `item_unavailable`.
	 */
	public function test_update_cart_reports_a_quantity_limit_as_invalid_quantity(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );

		// Cap the product at one per cart, then ask for two.
		$cap = static function () {
			return 1;
		};
		add_filter( 'woocommerce_store_api_product_quantity_maximum', $cap );

		$response = $this->request( 'PUT', '/carts/' . $created['id'], array( 'line_items' => array( $this->line_item( 0, 2 ) ) ) );
		$data     = $response->get_data();

		remove_filter( 'woocommerce_store_api_product_quantity_maximum', $cap );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'error', $data['ucp']['status'] );
		$this->assertSame( 'invalid_quantity', $data['messages'][0]['code'] );

		// A refused update leaves the cart alone.
		$this->assertCount( 1, $this->request( 'GET', '/carts/' . $created['id'] )->get_data()['line_items'] );
	}

	/**
	 * A failure the Store API base class reports in its own shape is re-shaped for UCP.
	 *
	 * The parent refuses to load a cart whose session failed earlier in the process; that
	 * is the same exit a throwing plugin or a session error reaches.
	 */
	public function test_errors_from_the_store_api_base_class_wear_the_ucp_envelope(): void {
		$cart_id = $this->create_cart( array( $this->line_item( 0, 1 ) ) )['id'];

		\WC_Cart_Session::set_updates_enabled_for_cart( WC()->cart, false );

		$response = $this->request( 'GET', '/carts/' . $cart_id );
		$data     = $response->get_data();

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'error', $data['ucp']['status'] );
		$this->assertSame( 'TRANSPORT_ERROR', $data['messages'][0]['code'] );
		$this->assertSame( 'unrecoverable', $data['messages'][0]['severity'] );
		$this->assertArrayNotHasKey( 'code', $data, 'The Store API error shape must not leak through.' );
	}

	/**
	 * Creating a cart answers the UCP cart shape, with amounts in minor units.
	 */
	public function test_create_cart_returns_ucp_cart(): void {
		$data = $this->create_cart( array( $this->line_item( 0, 2 ) ) );

		$this->assertSame( '2026-04-08', $data['ucp']['version'] );
		$this->assertSame( 'success', $data['ucp']['status'] );
		// The envelope names only the capability relevant to the operation answered.
		$this->assertSame( array( 'dev.ucp.shopping.cart' ), array_keys( $data['ucp']['capabilities'] ) );

		$this->assertNotEmpty( $data['id'] );
		$this->assertSame( get_woocommerce_currency(), $data['currency'] );

		$this->assertCount( 1, $data['line_items'] );
		$line_item = $data['line_items'][0];
		$this->assertSame( (string) self::$product_ids[0], $line_item['item']['id'] );
		$this->assertSame( 'UCP Cart Mug', $line_item['item']['title'] );
		$this->assertSame( 1999, $line_item['item']['price'] );
		$this->assertSame( 2, $line_item['quantity'] );
		$this->assertSame( 3998, $this->total( $line_item['totals'], 'subtotal' ) );

		$this->assertSame( 3998, $this->total( $data['totals'], 'subtotal' ) );
		$this->assertSame( 3998, $this->total( $data['totals'], 'total' ) );
	}

	/**
	 * The cart hands the buyer a URL that restores the same cart in a browser.
	 */
	public function test_continue_url_carries_the_cart_token(): void {
		$data = $this->create_cart( array( $this->line_item( 0, 1 ) ) );

		$query = array();
		wp_parse_str( (string) wp_parse_url( $data['continue_url'], PHP_URL_QUERY ), $query );

		// WC_Session_Handler restores or clones the guest session named by this token.
		$this->assertSame( $data['id'], $query['session'] );
		$this->assertStringStartsWith( wc_get_checkout_url(), $data['continue_url'] );
	}

	/**
	 * A cart reads back with the contents it was created with.
	 */
	public function test_get_cart_returns_the_same_contents(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );

		$response = $this->request( 'GET', '/carts/' . $created['id'] );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $created['id'], $data['id'] );
		$this->assertSame( $created['line_items'], $data['line_items'] );
	}

	/**
	 * An update is a full replacement: a product left out of `line_items` is removed.
	 */
	public function test_update_cart_replaces_line_items(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );

		$both = $this->request(
			'PUT',
			'/carts/' . $created['id'],
			array(
				'line_items' => array(
					$this->line_item( 0, 3 ),
					$this->line_item( 1, 1 ),
				),
			)
		)->get_data();

		$this->assertCount( 2, $both['line_items'] );
		$this->assertSame( array( 3, 1 ), wp_list_pluck( $both['line_items'], 'quantity' ) );

		// Dropping a line must remove it, not merge the two payloads.
		$dropped = $this->request(
			'PUT',
			'/carts/' . $created['id'],
			array( 'line_items' => array( $this->line_item( 1, 1 ) ) )
		)->get_data();

		$this->assertCount( 1, $dropped['line_items'] );
		$this->assertSame( (string) self::$product_ids[1], $dropped['line_items'][0]['item']['id'] );
		$this->assertSame( 500, $this->total( $dropped['totals'], 'total' ) );
	}

	/**
	 * Add a second line of a fixture product, distinguished only by cart item data.
	 *
	 * This is the shape a product add-on or personalization field produces: the same
	 * product on two lines, because the cart item key hashes the extra data.
	 *
	 * @param int $index Fixture index.
	 * @param int $quantity Quantity.
	 */
	private function add_personalized_line( int $index, int $quantity ): void {
		WC()->cart->add_to_cart( self::$product_ids[ $index ], $quantity, 0, array(), array( 'engraving' => 'For Ada' ) );
		WC()->cart->calculate_totals();
	}

	/**
	 * Echoing a cart back unchanged must leave it unchanged.
	 *
	 * Two lines of one product are told apart by `line_items[].id`, the cart item key.
	 * Reconciling on the product alone would write the summed quantity onto one line and
	 * leave the other standing, so a no-op round trip would grow the cart every time.
	 */
	public function test_update_cart_round_trip_does_not_inflate_duplicate_lines(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );
		$this->add_personalized_line( 0, 2 );

		$read = $this->request( 'GET', '/carts/' . $created['id'] )->get_data();

		$this->assertCount( 2, $read['line_items'] );
		$this->assertSame( 5997, $this->total( $read['totals'], 'total' ) );
		$this->assertNotSame( $read['line_items'][0]['id'], $read['line_items'][1]['id'] );

		$echoed = $this->request( 'PUT', '/carts/' . $created['id'], array( 'line_items' => $read['line_items'] ) )->get_data();

		$this->assertSame( array( 1, 2 ), wp_list_pluck( $echoed['line_items'], 'quantity' ) );
		$this->assertSame( 5997, $this->total( $echoed['totals'], 'total' ) );
	}

	/**
	 * Clearing the cart removes every line, including duplicates of one product.
	 */
	public function test_update_cart_clears_duplicate_lines_of_one_product(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );
		$this->add_personalized_line( 0, 2 );

		$cleared = $this->request( 'PUT', '/carts/' . $created['id'], array( 'line_items' => array() ) )->get_data();

		$this->assertSame( array(), $cleared['line_items'] );
	}

	/**
	 * A payload naming no line ids describes the cart by product, so duplicates collapse.
	 */
	public function test_update_cart_collapses_duplicate_lines_when_no_id_is_given(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );
		$this->add_personalized_line( 0, 2 );

		$collapsed = $this->request(
			'PUT',
			'/carts/' . $created['id'],
			array( 'line_items' => array( $this->line_item( 0, 4 ) ) )
		)->get_data();

		$this->assertCount( 1, $collapsed['line_items'] );
		$this->assertSame( 4, $collapsed['line_items'][0]['quantity'] );
		$this->assertSame( 7996, $this->total( $collapsed['totals'], 'total' ) );
	}

	/**
	 * A line id the cart no longer holds falls back to the product it names.
	 */
	public function test_update_cart_ignores_a_stale_line_id(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );

		$stale = array_merge( $this->line_item( 0, 2 ), array( 'id' => 'a-line-that-is-gone' ) );

		$updated = $this->request( 'PUT', '/carts/' . $created['id'], array( 'line_items' => array( $stale ) ) )->get_data();

		$this->assertCount( 1, $updated['line_items'] );
		$this->assertSame( 2, $updated['line_items'][0]['quantity'] );
	}

	/**
	 * Omitting `line_items` leaves the cart alone; an empty list clears it.
	 */
	public function test_update_cart_distinguishes_omitted_from_empty_line_items(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );

		$untouched = $this->request( 'PUT', '/carts/' . $created['id'] )->get_data();
		$this->assertCount( 1, $untouched['line_items'] );

		$cleared = $this->request( 'PUT', '/carts/' . $created['id'], array( 'line_items' => array() ) )->get_data();
		$this->assertSame( array(), $cleared['line_items'] );
	}

	/**
	 * Only PUT updates a cart, as the cart REST binding specifies.
	 *
	 * PATCH matters most: `line_items` is the complete desired state, so a partial
	 * payload taken as an update would delete every line it left out.
	 */
	public function test_only_put_updates_a_cart(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );

		foreach ( array( 'POST', 'PATCH', 'DELETE' ) as $method ) {
			$response = $this->request( $method, '/carts/' . $created['id'], array( 'line_items' => array() ) );

			$this->assertSame( 404, $response->get_status(), $method . ' on a cart' );
			$this->assertSame( 'rest_no_route', $response->get_data()['code'], $method . ' on a cart' );
		}

		// None of them emptied the cart.
		$this->assertCount( 1, $this->request( 'GET', '/carts/' . $created['id'] )->get_data()['line_items'] );
	}

	/**
	 * Malformed line items are refused in the envelope, with the offending path.
	 */
	public function test_update_cart_reports_invalid_line_items(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );

		$missing_id = $this->request(
			'PUT',
			'/carts/' . $created['id'],
			array( 'line_items' => array( array( 'quantity' => 1 ) ) )
		);

		$this->assertSame( 400, $missing_id->get_status() );
		$this->assertSame( 'error', $missing_id->get_data()['ucp']['status'] );
		$this->assertSame( 'VALIDATION_ERROR', $missing_id->get_data()['messages'][0]['code'] );
		$this->assertSame( '$.line_items[0].item.id', $missing_id->get_data()['messages'][0]['path'] );

		$zero_quantity = $this->request(
			'PUT',
			'/carts/' . $created['id'],
			array( 'line_items' => array( array_merge( $this->line_item( 0, 1 ), array( 'quantity' => 0 ) ) ) )
		);

		$this->assertSame( 400, $zero_quantity->get_status() );
		$this->assertSame( '$.line_items[0].quantity', $zero_quantity->get_data()['messages'][0]['path'] );

		// A well-formed payload naming an id the store cannot sell is a business outcome,
		// reported at 200 like every other `not_found`, not a malformed request.
		$unknown_product = $this->request(
			'PUT',
			'/carts/' . $created['id'],
			array(
				'line_items' => array(
					array(
						'item'     => array( 'id' => '99999999' ),
						'quantity' => 1,
					),
				),
			)
		);

		$this->assertSame( 200, $unknown_product->get_status() );
		$this->assertSame( 'error', $unknown_product->get_data()['ucp']['status'] );
		$this->assertSame( 'not_found', $unknown_product->get_data()['messages'][0]['code'] );
		$this->assertSame( '$.line_items[0].item.id', $unknown_product->get_data()['messages'][0]['path'] );

		// The cart itself is untouched by a refused update.
		$this->assertCount( 1, $this->request( 'GET', '/carts/' . $created['id'] )->get_data()['line_items'] );
	}

	/**
	 * A variable product is bought by naming one of its variants.
	 *
	 * The variant id is what catalog hands out, and what the cart echoes back, so an id
	 * from search round-trips into a cart without translation.
	 */
	public function test_create_cart_accepts_a_variation_id(): void {
		$data = $this->create_cart(
			array(
				array(
					'item'     => array( 'id' => (string) self::$variable['variation'] ),
					'quantity' => 2,
				),
			)
		);

		$this->assertCount( 1, $data['line_items'] );
		$this->assertSame( (string) self::$variable['variation'], $data['line_items'][0]['item']['id'] );
		$this->assertSame( 1200, $data['line_items'][0]['item']['price'] );
		$this->assertSame( 2400, $this->total( $data['totals'], 'total' ) );
	}

	/**
	 * A variable parent is refused, in the envelope, telling the agent to name a variant.
	 *
	 * The parent reports a price so it passes `is_purchasable()`, and letting it through
	 * would reach `CartController::add_to_cart()` and surface a Store API error shape with
	 * no `ucp` object for a client to read.
	 */
	public function test_create_cart_refuses_a_variable_parent(): void {
		$response = $this->request(
			'POST',
			'/carts',
			array(
				'line_items' => array(
					array(
						'item'     => array( 'id' => (string) self::$variable['parent'] ),
						'quantity' => 1,
					),
				),
			)
		);
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'error', $data['ucp']['status'] );
		$this->assertSame( 'item_unavailable', $data['messages'][0]['code'] );
		$this->assertSame( '$.line_items[0].item.id', $data['messages'][0]['path'] );
	}

	/**
	 * A stock ceiling is reported as `out_of_stock`, in the envelope, with the cart's message.
	 *
	 * The cart distinguishes more refusals than UCP names codes for, so the ones an agent
	 * can act on differently are kept apart: a stock ceiling invites a smaller quantity,
	 * where `item_unavailable` would only say "no".
	 */
	public function test_update_cart_reports_a_stock_ceiling_as_out_of_stock(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );

		$response = $this->request(
			'PUT',
			'/carts/' . $created['id'],
			array(
				'line_items' => array(
					array(
						'item'     => array( 'id' => (string) self::$limited_stock_id ),
						'quantity' => 5,
					),
				),
			)
		);
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'error', $data['ucp']['status'] );
		$this->assertSame( 'out_of_stock', $data['messages'][0]['code'] );
		// The cart's own wording names the product and what is left, which is actionable.
		$this->assertStringContainsString( 'not enough stock', $data['messages'][0]['content'] );

		// A refused update leaves the cart alone.
		$this->assertCount( 1, $this->request( 'GET', '/carts/' . $created['id'] )->get_data()['line_items'] );
	}

	/**
	 * An id this store never signed is reported as not found, in the envelope.
	 */
	public function test_unknown_cart_id_is_not_found(): void {
		$response = $this->request( 'GET', '/carts/not-a-real-token' );
		$data     = $response->get_data();

		// UCP clients read ucp.status and messages, not the HTTP status.
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'error', $data['ucp']['status'] );
		$this->assertSame( 'not_found', $data['messages'][0]['code'] );
		$this->assertArrayNotHasKey( 'line_items', $data );
	}

	/**
	 * Cancel answers with the cart as it stood, then the id stops resolving.
	 */
	public function test_cancel_cart_returns_prior_state_then_not_found(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 2 ) ) );

		$cancelled = $this->request( 'POST', '/carts/' . $created['id'] . '/cancel' );
		$data      = $cancelled->get_data();

		$this->assertSame( 200, $cancelled->get_status() );
		$this->assertSame( 'success', $data['ucp']['status'] );
		// "Business MUST return the cart state before deletion."
		$this->assertCount( 1, $data['line_items'] );
		$this->assertSame( 2, $data['line_items'][0]['quantity'] );
		$this->assertSame( 'CANCELED', $data['messages'][0]['code'] );

		foreach ( array( 'GET', 'PUT' ) as $method ) {
			$after = $this->request( $method, '/carts/' . $created['id'] )->get_data();
			$this->assertSame( 'not_found', $after['messages'][0]['code'], $method . ' on a cancelled cart' );
		}

		$again = $this->request( 'POST', '/carts/' . $created['id'] . '/cancel' )->get_data();
		$this->assertSame( 'not_found', $again['messages'][0]['code'] );
	}

	/**
	 * A caller that keeps its session can still create carts after cancelling one.
	 *
	 * The cancellation marker is sticky, so without releasing the session every later
	 * cart in it would answer not_found.
	 */
	public function test_a_cart_can_be_created_after_cancelling_one(): void {
		$cancelled = $this->create_cart( array( $this->line_item( 0, 1 ) ) );
		$this->request( 'POST', '/carts/' . $cancelled['id'] . '/cancel' );

		$fresh = $this->create_cart( array( $this->line_item( 1, 2 ) ) );

		$this->assertNotSame( $cancelled['id'], $fresh['id'] );
		$this->assertCount( 1, $fresh['line_items'] );
		$this->assertSame( 2, $fresh['line_items'][0]['quantity'] );

		$read_back = $this->request( 'GET', '/carts/' . $fresh['id'] )->get_data();
		$this->assertSame( $fresh['id'], $read_back['id'] );
		$this->assertSame( $fresh['line_items'], $read_back['line_items'] );

		// That the cancelled id stays dead is not assertable here: the session is loaded
		// once per process, so a second id in the same test cannot swap back to it. Over
		// HTTP the cancelled session row survives with its marker, because shutdown saves
		// the new session without deleting the old one.
		$this->assertFalse( (bool) WC()->session->get( AbstractCartRoute::CANCELLED_SESSION_KEY ) );
	}

	/**
	 * A cancelled cart id stays dead on a later request.
	 *
	 * Covers what the single-process checks cannot: the marker is written to the
	 * cancelled cart's own session row, and a request carrying that id loads the row
	 * back and is refused.
	 */
	public function test_a_cancelled_cart_id_is_still_not_found_on_a_later_request(): void {
		$cancelled = $this->create_cart( array( $this->line_item( 0, 1 ) ) );
		$this->request( 'POST', '/carts/' . $cancelled['id'] . '/cancel' );

		// A later request with no token: a different caller, or the same one without cookies.
		$this->simulate_new_request();
		$fresh = $this->create_cart( array( $this->line_item( 1, 1 ) ) );
		$this->assertNotSame( $cancelled['id'], $fresh['id'] );

		// A request that does carry the cancelled id.
		$this->simulate_new_request( $cancelled['id'] );

		foreach ( array( 'GET', 'PUT' ) as $method ) {
			$response = $this->request( $method, '/carts/' . $cancelled['id'] );

			$this->assertSame( 'not_found', $response->get_data()['messages'][0]['code'], $method . ' on a cancelled cart' );
			$this->assertArrayNotHasKey( 'line_items', $response->get_data() );
		}

		$this->assertSame(
			'not_found',
			$this->request( 'POST', '/carts/' . $cancelled['id'] . '/cancel' )->get_data()['messages'][0]['code']
		);
	}

	/**
	 * Cancelling a cart must not break a storefront session cloned from it.
	 *
	 * The marker lives in the WooCommerce session, which the buyer's browser can
	 * reach through `continue_url`, so only the UCP routes may act on it.
	 */
	public function test_cancelling_a_cart_leaves_the_storefront_session_usable(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );
		$this->request( 'POST', '/carts/' . $created['id'] . '/cancel' );

		$this->assertTrue( (bool) WC()->session->get( AbstractCartRoute::CANCELLED_SESSION_KEY ) );

		$store_api = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );

		$this->assertSame( 200, $store_api->get_status() );
		$this->assertSame( array(), $store_api->get_data()['items'] );
	}

	/**
	 * A cart request leaves the token slot as it found it.
	 *
	 * The slot is per-process, so a route that kept its token there would bind the next
	 * dispatch in the same process — `rest_do_request()` from other code, or a test — to
	 * this cart's session.
	 */
	public function test_a_cart_request_does_not_leak_its_token_to_the_next_dispatch(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );

		$this->assertSame( '', CartTokenUtils::get_request_cart_token(), 'after create' );

		$this->request( 'GET', '/carts/' . $created['id'] );
		$this->assertSame( '', CartTokenUtils::get_request_cart_token(), 'after read' );

		$this->request( 'PUT', '/carts/' . $created['id'], array( 'line_items' => array( $this->line_item( 0, 2 ) ) ) );
		$this->assertSame( '', CartTokenUtils::get_request_cart_token(), 'after update' );

		// A request that arrived with its own token keeps it, rather than being cleared.
		$_SERVER['HTTP_CART_TOKEN'] = $created['id'];
		$this->request( 'GET', '/carts/' . $created['id'] );
		$this->assertSame( $created['id'], CartTokenUtils::get_request_cart_token(), 'inherited token preserved' );
	}

	/**
	 * The cart id comes from the path only, never from the body or query string.
	 */
	public function test_cart_id_is_read_from_the_path_only(): void {
		$created = $this->create_cart( array( $this->line_item( 0, 1 ) ) );

		$request = new \WP_REST_Request( 'GET', '/wc/ucp/v1/carts/' . $created['id'] );
		$request->set_query_params( array( 'id' => 'someone-elses-token' ) );

		$this->assertSame( $created['id'], rest_get_server()->dispatch( $request )->get_data()['id'] );
	}
}
