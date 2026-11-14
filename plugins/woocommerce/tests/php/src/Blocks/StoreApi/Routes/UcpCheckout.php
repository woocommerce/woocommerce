<?php
/**
 * UCP Checkout Route Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\StoreApi\Routes\V1\Ucp\AbstractCheckoutRoute;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils;

/**
 * Tests for the /wc/ucp/v1/checkout-sessions endpoints.
 */
class UcpCheckout extends ControllerTestCase {
	/**
	 * Published product IDs shared by the class.
	 *
	 * @var int[]
	 */
	private static $product_ids = array();

	/**
	 * Option behind the `ucp` feature.
	 */
	private const FEATURE_OPTION = 'woocommerce_feature_ucp_enabled';

	/**
	 * The WC() singletons the harness set up, restored after each test.
	 *
	 * @var array{session: \WC_Session|null, customer: \WC_Customer|null, cart: \WC_Cart|null}
	 */
	private $wc_globals_backup = array();

	/**
	 * Create immutable fixtures before route registration starts.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::$product_ids = array_map(
			static function ( $product ) {
				return $product->get_id();
			},
			self::create_class_fixture_products(
				array(
					array(
						'name'          => 'UCP Checkout Mug',
						'regular_price' => '19.99',
						'price'         => '19.99',
					),
					array(
						'name'          => 'UCP Checkout Poster',
						'regular_price' => '5.00',
						'price'         => '5.00',
					),
				)
			)
		);
	}

	/**
	 * Delete class fixtures.
	 */
	public static function wpTearDownAfterClass(): void {
		self::delete_class_fixture_products( self::$product_ids );
	}

	/**
	 * Enable the UCP routes and start from a fresh request.
	 */
	protected function setUp(): void {
		parent::setUp();

		update_option( self::FEATURE_OPTION, 'yes' );
		$this->initialize_store_api_server();

		$this->wc_globals_backup = array(
			'session'  => WC()->session,
			'customer' => WC()->customer,
			'cart'     => WC()->cart,
		);

		$this->simulate_new_request();
	}

	/**
	 * Restore the WC() singletons and the token slot.
	 */
	protected function tearDown(): void {
		unset( $_SERVER['HTTP_CART_TOKEN'] );

		if ( WC()->session ) {
			WC()->session->set( AbstractCheckoutRoute::CHECKOUT_SESSION_KEY, null );
			WC()->session->set( 'store_api_draft_order', null );
		}

		WC()->session  = $this->wc_globals_backup['session'];
		WC()->customer = $this->wc_globals_backup['customer'];
		WC()->cart     = $this->wc_globals_backup['cart'];
		unset( $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'], $GLOBALS['wp_actions']['woocommerce_after_calculate_totals'] );

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
		// A write needs a key of its own; the key itself is the UcpIdempotency suite's concern.
		$request->set_header( 'Idempotency-Key', wp_generate_uuid4() );
		$request->set_body( wp_json_encode( $body ) );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Persist the current session and drop the WC() singletons, as the end of a real request does.
	 *
	 * @param string $cart_token Cart token the next request carries, or '' for none.
	 */
	private function simulate_new_request( string $cart_token = '' ): void {
		if ( WC()->session instanceof \WC_Session_Handler ) {
			// save_data() is a no-op without a session cookie, which this suite never sends.
			$has_cookie = new \ReflectionProperty( WC()->session, '_has_cookie' );
			$has_cookie->setAccessible( true );
			$has_cookie->setValue( WC()->session, true );
		}

		if ( WC()->session ) {
			WC()->session->save_data();
		}

		if ( '' === $cart_token ) {
			unset( $_SERVER['HTTP_CART_TOKEN'] );
		} else {
			$_SERVER['HTTP_CART_TOKEN'] = $cart_token;
		}

		WC()->session  = null;
		WC()->customer = null;
		WC()->cart     = null;
		unset( $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'], $GLOBALS['wp_actions']['woocommerce_after_calculate_totals'] );
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
	 * Create a checkout holding one mug, or the given line item, with everything it needs to complete.
	 *
	 * @param array|null $line_item Line item to check out instead of the mug.
	 * @return array The response data.
	 */
	private function create_ready_checkout( ?array $line_item = null ): array {
		$response = $this->request(
			'POST',
			'/checkout-sessions',
			array(
				'line_items'       => array( $line_item ?? $this->line_item( 0, 1 ) ),
				'buyer'            => array(
					'email'        => 'buyer@example.com',
					'first_name'   => 'Ada',
					'last_name'    => 'Buyer',
					'phone_number' => '5550100',
				),
				'shipping_address' => array(
					'street_address'   => '1 Main St',
					'address_locality' => 'San Francisco',
					'address_region'   => 'CA',
					'postal_code'      => '94103',
					'address_country'  => 'US',
				),
			)
		);

		$this->assertSame( 201, $response->get_status() );

		return $response->get_data();
	}

	/**
	 * @testdox The checkout routes do not exist while the UCP feature is off.
	 */
	public function test_routes_are_not_registered_by_default(): void {
		delete_option( self::FEATURE_OPTION );
		$this->initialize_store_api_server();

		$this->assertSame( 404, $this->request( 'POST', '/checkout-sessions' )->get_status() );
		$this->assertSame( 'rest_no_route', $this->request( 'GET', '/checkout-sessions/abc' )->get_data()['code'] );
	}

	/**
	 * @testdox Creating from line items applies the lines and the buyer, and derives the status.
	 */
	public function test_create_from_line_items(): void {
		$data = $this->create_ready_checkout();

		$this->assertSame( array( 'dev.ucp.shopping.checkout' ), array_keys( $data['ucp']['capabilities'] ) );
		$this->assertInstanceOf( \stdClass::class, $data['ucp']['payment_handlers'], 'A checkout envelope must carry the payment_handlers registry.' );
		$this->assertSame( 'ready_for_complete', $data['status'] );
		$this->assertSame( 'buyer@example.com', $data['buyer']['email'] );
		$this->assertSame( 1999, $data['line_items'][0]['item']['price'] );
		$this->assertArrayNotHasKey( 'continue_url', $data );

		$missing = $this->request( 'POST', '/checkout-sessions', array() );
		$this->assertSame( 400, $missing->get_status(), 'line_items is required without a cart_id.' );
	}

	/**
	 * @testdox Creating from a cart keeps the cart id and contents; a checkout without a buyer email is incomplete.
	 */
	public function test_create_from_cart_id(): void {
		$cart = $this->request( 'POST', '/carts', array( 'line_items' => array( $this->line_item( 1, 3 ) ) ) )->get_data();
		$this->simulate_new_request();

		$response = $this->request( 'POST', '/checkout-sessions', array( 'cart_id' => $cart['id'] ) );
		$data     = $response->get_data();

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( $cart['id'], $data['id'] );
		$this->assertSame( 3, $data['line_items'][0]['quantity'] );
		$this->assertSame( 'incomplete', $data['status'] );
		$this->assertContains( '$.buyer.email', array_column( $data['messages'], 'path' ) );
	}

	/**
	 * @testdox An id that never named a checkout is a 404, from the path or from cart_id.
	 */
	public function test_unknown_id_is_not_found(): void {
		$get = $this->request( 'GET', '/checkout-sessions/not-a-token' );
		$this->assertSame( 404, $get->get_status() );
		$this->assertSame( 'not_found', $get->get_data()['messages'][0]['code'] );

		$this->assertSame( 404, $this->request( 'POST', '/checkout-sessions', array( 'cart_id' => 'not-a-token' ) )->get_status() );

		// A real cart that was never promoted is not a checkout.
		$cart = $this->request( 'POST', '/carts', array( 'line_items' => array( $this->line_item( 0, 1 ) ) ) )->get_data();
		$this->assertSame( 404, $this->request( 'GET', '/checkout-sessions/' . $cart['id'] )->get_status() );
	}

	/**
	 * @testdox An update replaces the lines and cancels an order already handed off for the old ones.
	 */
	public function test_update_replaces_line_items(): void {
		$created = $this->create_ready_checkout();
		$this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' );
		$handed_off = (int) WC()->session->get( 'store_api_draft_order' );

		$data = $this->request( 'PUT', '/checkout-sessions/' . $created['id'], array( 'line_items' => array( $this->line_item( 1, 2 ) ) ) )->get_data();

		$this->assertCount( 1, $data['line_items'] );
		$this->assertSame( (string) self::$product_ids[1], $data['line_items'][0]['item']['id'] );
		$this->assertSame( 2, $data['line_items'][0]['quantity'] );
		$this->assertSame( 'ready_for_complete', $data['status'] );
		$this->assertTrue( wc_get_order( $handed_off )->has_status( OrderStatus::CANCELLED ), 'An order for the old lines must no longer be payable.' );
		$this->assertNotSame( $handed_off, (int) WC()->session->get( 'store_api_draft_order' ) );

		// An order cancelled behind the checkout's back, as the hold-stock timer does, drops the hand-off.
		$this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' );
		wc_get_order( (int) WC()->session->get( 'store_api_draft_order' ) )->update_status( OrderStatus::CANCELLED );
		$this->assertSame( 'ready_for_complete', $this->request( 'GET', '/checkout-sessions/' . $created['id'] )->get_data()['status'] );
	}

	/**
	 * @testdox An update that changes nothing keeps the hand-off, and one refused after changing the lines drops it.
	 */
	public function test_update_drops_the_hand_off_only_for_a_change(): void {
		$created   = $this->create_ready_checkout();
		$path      = '/checkout-sessions/' . $created['id'];
		$completed = $this->request( 'POST', $path . '/complete' )->get_data();
		$order_id  = (int) WC()->session->get( 'store_api_draft_order' );

		foreach ( array( array(), array( 'line_items' => array( $this->line_item( 0, 1 ) ) ) ) as $body ) {
			$this->simulate_new_request( $created['id'] );
			$same = $this->request( 'PUT', $path, $body )->get_data();
			$this->assertSame( $completed['continue_url'], $same['continue_url'] ?? null );
			$this->assertTrue( wc_get_order( $order_id )->has_status( OrderStatus::PENDING ) );
		}

		$refused = $this->request(
			'PUT',
			$path,
			array(
				'line_items'       => array( $this->line_item( 1, 2 ) ),
				'shipping_address' => array( 'address_country' => 'ZZ' ),
			)
		);

		$this->assertSame( 400, $refused->get_status() );
		$this->assertTrue( wc_get_order( $order_id )->has_status( OrderStatus::CANCELLED ), 'The lines changed, so the order for the old ones must not stay payable.' );
		$this->assertArrayNotHasKey( 'continue_url', $this->request( 'GET', $path )->get_data() );

		// A cart changed through the Store API after a hand-off makes it stale before the update arrives.
		$this->request( 'POST', $path . '/complete' );
		$stale = (int) WC()->session->get( 'store_api_draft_order' );
		WC()->cart->set_quantity( (string) array_key_first( WC()->cart->get_cart() ), 3 );

		$this->assertArrayNotHasKey( 'continue_url', $this->request( 'PUT', $path, array() )->get_data() );
		$this->assertTrue( wc_get_order( $stale )->has_status( OrderStatus::CANCELLED ) );
	}

	/**
	 * @testdox An order paid, or put on hold, while an update is under way stays the checkout's order.
	 * @testWith ["processing", "completed"]
	 *           ["on-hold", "complete_in_progress"]
	 *
	 * @param string $order_status Status the order moves to mid-request.
	 * @param string $expected     Checkout status afterwards.
	 */
	public function test_update_keeps_an_order_paid_mid_request( string $order_status, string $expected ): void {
		$created = $this->create_ready_checkout();
		$path    = '/checkout-sessions/' . $created['id'];
		$this->request( 'POST', $path . '/complete' );
		$order_id = (int) WC()->session->get( 'store_api_draft_order' );

		$pay = static function () use ( $order_id, $order_status ) {
			wc_get_order( $order_id )->update_status( $order_status );
		};
		add_action( 'woocommerce_add_to_cart', $pay );
		$updated = $this->request( 'PUT', $path, array( 'line_items' => array( $this->line_item( 1, 1 ) ) ) )->get_data();
		remove_action( 'woocommerce_add_to_cart', $pay );
		$this->assertSame( $expected, $updated['status'] );
		$this->assertSame( (string) self::$product_ids[0], $updated['line_items'][0]['item']['id'], 'A paid checkout lists what was paid for, not the late change.' );

		$this->assertSame( $expected, $this->request( 'GET', $path )->get_data()['status'] );
		$this->assertSame( $order_id, (int) WC()->session->get( 'ucp_checkout' )['order_id'] );
	}

	/**
	 * @testdox An order that could not be cancelled stays the checkout's order, so no second one is handed off beside it.
	 */
	public function test_failed_cancellation_keeps_the_hand_off(): void {
		$created = $this->create_ready_checkout();
		$path    = '/checkout-sessions/' . $created['id'];
		$this->request( 'POST', $path . '/complete' );
		$order_id = (int) WC()->session->get( 'store_api_draft_order' );
		WC()->cart->set_quantity( (string) array_key_first( WC()->cart->get_cart() ), 2 );

		$fail = static function ( $order ) {
			if ( $order->has_status( OrderStatus::CANCELLED ) ) {
				throw new \Exception( 'Save failed.' );
			}
		};
		add_action( 'woocommerce_before_order_object_save', $fail );
		$statuses = array(
			$this->request( 'POST', $path . '/complete' )->get_status(),
			$this->request( 'POST', $path . '/cancel' )->get_status(),
		);
		remove_action( 'woocommerce_before_order_object_save', $fail );

		$this->assertSame( array( 500, 500 ), $statuses );
		$this->assertTrue( wc_get_order( $order_id )->has_status( OrderStatus::PENDING ) );
		$this->assertSame( $order_id, (int) WC()->session->get( 'ucp_checkout' )['order_id'] );
		$this->assertCount(
			1,
			wc_get_orders(
				array(
					'status' => 'any',
					'return' => 'ids',
					'limit'  => -1,
				)
			)
		);

		$this->assertArrayHasKey( 'continue_url', $this->request( 'POST', $path . '/complete' )->get_data() );
		$this->assertTrue( wc_get_order( $order_id )->has_status( OrderStatus::CANCELLED ), 'The retry cancels the order the failed attempt left payable.' );
	}

	/**
	 * @testdox Completing a changed cart makes no second order when the first is paid while the request is under way.
	 */
	public function test_complete_stops_for_an_order_paid_mid_request(): void {
		$created = $this->create_ready_checkout();
		$path    = '/checkout-sessions/' . $created['id'];
		$this->request( 'POST', $path . '/complete' );
		$order_id = (int) WC()->session->get( 'store_api_draft_order' );
		WC()->cart->set_quantity( (string) array_key_first( WC()->cart->get_cart() ), 2 );

		// Fires while the cart response is built, after the checkout was found open.
		$pay = static function ( $ids ) use ( $order_id ) {
			wc_get_order( $order_id )->update_status( OrderStatus::PROCESSING );

			return $ids;
		};
		add_filter( 'woocommerce_cart_crosssell_ids', $pay );
		$response = $this->request( 'POST', $path . '/complete' );
		remove_filter( 'woocommerce_cart_crosssell_ids', $pay );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( $order_id, (int) WC()->session->get( 'ucp_checkout' )['order_id'] );
		$this->assertSame( (string) $order_id, $this->request( 'GET', $path )->get_data()['order']['id'] );
	}

	/**
	 * @testdox A paid checkout reads from its order, whatever happens to the product afterwards.
	 */
	public function test_completed_checkout_reads_from_the_order(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 1,
				'regular_price'  => 10,
				'price'          => 10,
			)
		);
		$created = $this->create_ready_checkout(
			array(
				'item'     => array( 'id' => (string) $product->get_id() ),
				'quantity' => 1,
			)
		);
		$path    = '/checkout-sessions/' . $created['id'];
		$this->request( 'POST', $path . '/complete' );

		// Paying takes the last unit; the price is raised afterwards.
		wc_get_order( (int) WC()->session->get( 'store_api_draft_order' ) )->payment_complete();
		$product = wc_get_product( $product->get_id() );
		$product->set_regular_price( '25' );
		$product->save();
		$this->simulate_new_request( $created['id'] );

		$paid = $this->request( 'GET', $path )->get_data();

		$this->assertSame( 'completed', $paid['status'] );
		$this->assertSame( $created['totals'], $paid['totals'] );
		$this->assertSame( $created['line_items'][0]['item'], $paid['line_items'][0]['item'] );
		$this->assertSame( $created['line_items'][0]['totals'], $paid['line_items'][0]['totals'] );
		$this->assertArrayNotHasKey( 'messages', $paid, 'The buyer\'s own purchase must not read as a stock problem.' );
	}

	/**
	 * @testdox A promoted cart is read but not written through the cart routes while its checkout is live, and is no longer a cart afterwards.
	 */
	public function test_cart_writes_are_refused_once_promoted(): void {
		$created = $this->create_ready_checkout();
		$this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' );
		$order_id = (int) WC()->session->get( 'store_api_draft_order' );

		$read = $this->request( 'GET', '/carts/' . $created['id'] );
		$this->assertSame( 200, $read->get_status() );
		$this->assertSame( $created['line_items'], $read->get_data()['line_items'] );

		foreach ( array( array( 'PUT', '', array( 'line_items' => array( $this->line_item( 1, 2 ) ) ) ), array( 'POST', '/cancel', array() ) ) as list( $method, $path, $body ) ) {
			$response = $this->request( $method, '/carts/' . $created['id'] . $path, $body );
			$this->assertSame( 409, $response->get_status(), $method . $path );
			$this->assertContains( 'CONFLICT', array_column( $response->get_data()['messages'], 'code' ), $method . $path );
		}

		$this->assertTrue( wc_get_order( $order_id )->has_status( OrderStatus::PENDING ) );
		$this->assertSame( 'requires_escalation', $this->request( 'GET', '/checkout-sessions/' . $created['id'] )->get_data()['status'] );

		// Once the checkout ends the id is no longer a cart, while the checkout keeps reading back with its lines.
		$this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/cancel' );
		$gone = $this->request( 'GET', '/carts/' . $created['id'] );
		$this->assertSame( 200, $gone->get_status() );
		$this->assertSame( 'error', $gone->get_data()['ucp']['status'] );
		$this->assertSame( 'not_found', $gone->get_data()['messages'][0]['code'] );
		$canceled = $this->request( 'GET', '/checkout-sessions/' . $created['id'] )->get_data();
		$this->assertSame( 'canceled', $canceled['status'] );
		$this->assertSame( $created['line_items'], $canceled['line_items'] );
	}

	/**
	 * @testdox A payment awaiting the store's confirmation locks the checkout against completing again, changing and canceling.
	 */
	public function test_payment_awaiting_confirmation_locks_the_checkout(): void {
		$created = $this->create_ready_checkout();
		$this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' );
		$order = wc_get_order( (int) WC()->session->get( 'store_api_draft_order' ) );
		$order->update_status( OrderStatus::ON_HOLD );

		$writes = array(
			array( 'POST', '/complete', array() ),
			array( 'PUT', '', array( 'buyer' => array( 'email' => 'other@example.com' ) ) ),
			array( 'POST', '/cancel', array() ),
		);

		foreach ( $writes as list( $method, $path, $body ) ) {
			$response = $this->request( $method, '/checkout-sessions/' . $created['id'] . $path, $body );
			$this->assertSame( 409, $response->get_status(), $method . $path );
			$this->assertContains( 'CONFLICT', array_column( $response->get_data()['messages'], 'code' ), $method . $path );
		}

		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::ON_HOLD ) );
		$this->assertCount(
			1,
			wc_get_orders(
				array(
					'status' => 'any',
					'return' => 'ids',
					'limit'  => -1,
				)
			),
			'No second order may be made for a payment already on its way.'
		);
		$read = $this->request( 'GET', '/checkout-sessions/' . $created['id'] )->get_data();
		$this->assertSame( 'complete_in_progress', $read['status'], 'A payment already made must not read as one still to make.' );
		$this->assertArrayNotHasKey( 'continue_url', $read );
		$this->assertNotContains( 'error', array_column( $read['messages'] ?? array(), 'type' ) );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertSame( 'completed', $this->request( 'GET', '/checkout-sessions/' . $created['id'] )->get_data()['status'] );
	}

	/**
	 * @testdox A failed payment attempt the buyer could retry is cancelled when the checkout is canceled or changed.
	 * @dataProvider provider_requests_that_drop_the_hand_off
	 *
	 * @param string $method Request method.
	 * @param string $path   Route path after the checkout id.
	 * @param array  $body   Request body.
	 */
	public function test_failed_order_is_cancelled_with_the_hand_off( string $method, string $path, array $body ): void {
		$created = $this->create_ready_checkout();
		$this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' );
		$order = wc_get_order( (int) WC()->session->get( 'store_api_draft_order' ) );
		$order->update_status( OrderStatus::FAILED );
		$this->assertTrue( $order->needs_payment(), 'The payment page retries a failed order.' );

		$this->assertSame( 200, $this->request( $method, '/checkout-sessions/' . $created['id'] . $path, $body )->get_status() );
		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::CANCELLED ) );
	}

	/**
	 * Requests that end the hand-off of an order.
	 *
	 * @return array<string, array{string, string, array}>
	 */
	public function provider_requests_that_drop_the_hand_off(): array {
		return array(
			'cancel' => array( 'POST', '/cancel', array() ),
			'update' => array( 'PUT', '', array( 'buyer' => array( 'email' => 'other@example.com' ) ) ),
		);
	}

	/**
	 * @testdox Complete hands off a pending order's payment page, which a later request reads back, and paying it completes the checkout.
	 */
	public function test_complete_escalates_and_persists(): void {
		$created = $this->create_ready_checkout();

		$response = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete', array( 'payment' => array( 'instruments' => array() ) ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'requires_escalation', $data['status'] );
		$this->assertContains( 'ESCALATION_REQUIRED', array_column( $data['messages'], 'code' ) );

		$order = wc_get_order( (int) WC()->session->get( 'store_api_draft_order' ) );
		$this->assertInstanceOf( \WC_Order::class, $order );
		$this->assertTrue( $order->has_status( OrderStatus::PENDING ), 'The payment page only accepts an order awaiting payment.' );
		$this->assertSame( $order->get_checkout_payment_url(), $data['continue_url'] );

		$this->simulate_new_request( $created['id'] );
		$read_back = $this->request( 'GET', '/checkout-sessions/' . $created['id'] )->get_data();
		$this->assertSame( 'requires_escalation', $read_back['status'] );
		$this->assertSame( $data['continue_url'], $read_back['continue_url'] );

		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		$paid = $this->request( 'GET', '/checkout-sessions/' . $created['id'] )->get_data();
		$this->assertSame( 'completed', $paid['status'] );
		$this->assertSame( (string) $order->get_id(), $paid['order']['id'] );
		$this->assertSame( 409, $this->request( 'PUT', '/checkout-sessions/' . $created['id'], array() )->get_status() );
	}

	/**
	 * @testdox Complete refuses a checkout with nothing to pay, which the payment page would not take, and makes no order.
	 */
	public function test_complete_refuses_a_free_checkout(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => 0,
				'price'         => 0,
			)
		);
		$created = $this->create_ready_checkout(
			array(
				'item'     => array( 'id' => (string) $product->get_id() ),
				'quantity' => 1,
			)
		);

		$data = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' )->get_data();

		$this->assertSame( 'payment_not_required', $data['messages'][0]['code'] );
		$this->assertEmpty( WC()->session->get( 'store_api_draft_order' ) );
	}

	/**
	 * @testdox A store that requires an account to check out serves no checkout route and declares no checkout capability.
	 */
	public function test_checkout_is_unavailable_when_an_account_is_required(): void {
		add_filter( 'woocommerce_checkout_registration_required', '__return_true' );
		$this->initialize_store_api_server();
		$response     = $this->request( 'POST', '/checkout-sessions', array( 'line_items' => array( $this->line_item( 0, 1 ) ) ) );
		$capabilities = array_keys( Utils::capability_declarations() );
		remove_filter( 'woocommerce_checkout_registration_required', '__return_true' );

		$this->assertSame( 'rest_no_route', $response->get_data()['code'] );
		$this->assertNotContains( Utils::CAPABILITY_CHECKOUT, $capabilities );
		$this->assertContains( Utils::CAPABILITY_CART, $capabilities );
	}

	/**
	 * @testdox Completing again after the cart changed outside the checkout routes cancels the order first handed off.
	 */
	public function test_complete_cancels_the_order_a_changed_cart_left_behind(): void {
		$created = $this->create_ready_checkout();
		$first   = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' )->get_data();
		$stale   = (int) WC()->session->get( 'store_api_draft_order' );

		// As POST /wc/store/v1/cart/update-item does with the checkout id as its Cart-Token.
		$cart = WC()->cart;
		$cart->set_quantity( (string) array_key_first( $cart->get_cart() ), 2 );

		// Until then a read shows the changed cart without the payment URL of the order it no longer matches.
		$read = $this->request( 'GET', '/checkout-sessions/' . $created['id'] )->get_data();
		$this->assertArrayNotHasKey( 'continue_url', $read );
		$this->assertSame( 'ready_for_complete', $read['status'] );
		$this->assertSame( 2, $read['line_items'][0]['quantity'] );

		$second = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' )->get_data();

		$this->assertSame( 'requires_escalation', $second['status'] );
		$this->assertNotSame( $first['continue_url'], $second['continue_url'] );
		$this->assertTrue( wc_get_order( $stale )->has_status( OrderStatus::CANCELLED ), 'The order for the old cart must no longer be payable.' );
		$this->assertTrue( wc_get_order( (int) WC()->session->get( 'store_api_draft_order' ) )->has_status( OrderStatus::PENDING ) );
	}

	/**
	 * @testdox A shipping address no shipping method serves leaves the checkout incomplete, so no order is handed off.
	 */
	public function test_address_without_a_shipping_option_is_not_ready(): void {
		$zone = new \WC_Shipping_Zone();
		$zone->set_zone_name( 'UCP US only' );
		$zone->add_location( 'US', 'country' );
		$zone->save();
		$zone->add_shipping_method( 'flat_rate' );

		try {
			$created = $this->create_ready_checkout();
			$this->assertSame( 'ready_for_complete', $created['status'], 'A served address picks up the default rate.' );

			$unserved = array(
				'street_address'   => '1 Front St',
				'address_locality' => 'Toronto',
				'address_region'   => 'ON',
				'postal_code'      => 'M5V 2T6',
				'address_country'  => 'CA',
			);
			$updated  = $this->request( 'PUT', '/checkout-sessions/' . $created['id'], array( 'shipping_address' => $unserved ) )->get_data();

			$this->assertSame( 'incomplete', $updated['status'] );
			$this->assertContains( '$.shipping_address', array_column( $updated['messages'], 'path' ) );

			$completed = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' )->get_data();
			$this->assertSame( 'incomplete', $completed['status'] );
			$this->assertArrayNotHasKey( 'continue_url', $completed );
		} finally {
			$zone->delete();
		}
	}

	/**
	 * @testdox Billing follows the shipping address, and a checkout stays incomplete until the buyer's name and phone are known.
	 */
	public function test_billing_follows_shipping_and_is_required(): void {
		$body    = array(
			'line_items'       => array( $this->line_item( 0, 1 ) ),
			'buyer'            => array( 'email' => 'buyer@example.com' ),
			'shipping_address' => array(
				'street_address'   => '1 Main St',
				'address_locality' => 'San Francisco',
				'address_region'   => 'CA',
				'postal_code'      => '94103',
				'address_country'  => 'US',
			),
		);
		$created = $this->request( 'POST', '/checkout-sessions', $body )->get_data();

		$this->assertSame( 'incomplete', $created['status'] );
		$this->assertContains( '$.billing_address', array_column( $created['messages'], 'path' ) );

		$buyer   = array(
			'first_name'   => 'Ada',
			'last_name'    => 'Buyer',
			'phone_number' => '5550100',
		);
		$updated = $this->request( 'PUT', '/checkout-sessions/' . $created['id'], array( 'buyer' => $buyer ) )->get_data();
		$this->assertSame( 'ready_for_complete', $updated['status'] );

		$this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' );
		$order = wc_get_order( (int) WC()->session->get( 'store_api_draft_order' ) );
		$this->assertSame( '1 Main St', $order->get_billing_address_1() );
		$this->assertSame( 'Ada', $order->get_shipping_first_name() );
	}

	/**
	 * @testdox Complete refuses an order the Store API checkout validation refuses, and leaves it unpayable.
	 */
	public function test_complete_runs_checkout_validation(): void {
		$refuse = static function ( $order, $errors ) {
			$errors->add( 'refused', 'Refused by an extension.' );
		};
		add_action( 'woocommerce_checkout_validate_order_before_payment', $refuse, 10, 2 );

		$created  = $this->create_ready_checkout();
		$response = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' )->get_data();
		remove_action( 'woocommerce_checkout_validate_order_before_payment', $refuse, 10 );

		$this->assertSame( 'VALIDATION_ERROR', $response['messages'][0]['code'] );
		$this->assertSame( 'Refused by an extension.', $response['messages'][0]['content'] );
		$this->assertTrue( wc_get_order( (int) WC()->session->get( 'store_api_draft_order' ) )->has_status( OrderStatus::CHECKOUT_DRAFT ), 'The order must stay a draft, which the payment page refuses.' );
	}

	/**
	 * @testdox Complete fires the Store API checkout actions, and reports an order a callback paid as completed.
	 */
	public function test_complete_fires_the_store_api_checkout_actions(): void {
		$seen = array();
		$meta = static function ( $order ) use ( &$seen ) {
			$seen[] = 'meta:' . $order->get_status();
		};
		$pay  = static function ( $order ) use ( &$seen ) {
			$seen[] = 'processed:' . $order->get_status();
			$order->payment_complete();
		};
		add_action( 'woocommerce_store_api_checkout_update_order_meta', $meta );
		add_action( 'woocommerce_store_api_checkout_order_processed', $pay );

		$created = $this->create_ready_checkout();
		$data    = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' )->get_data();
		remove_action( 'woocommerce_store_api_checkout_update_order_meta', $meta );
		remove_action( 'woocommerce_store_api_checkout_order_processed', $pay );

		$this->assertSame( array( 'meta:checkout-draft', 'processed:pending' ), $seen );
		$this->assertSame( 'completed', $data['status'] );
		$this->assertArrayNotHasKey( 'continue_url', $data );
	}

	/**
	 * @testdox A cart changed by a checkout action no longer matches the order handed off, so the next update drops it.
	 */
	public function test_cart_changed_by_a_checkout_action_makes_the_hand_off_stale(): void {
		$change = static function () {
			WC()->cart->set_quantity( (string) array_key_first( WC()->cart->get_cart() ), 2 );
		};
		add_action( 'woocommerce_store_api_checkout_order_processed', $change );
		$created = $this->create_ready_checkout();
		$this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' );
		remove_action( 'woocommerce_store_api_checkout_order_processed', $change );
		$order_id = (int) WC()->session->get( 'ucp_checkout' )['order_id'];

		$this->assertArrayNotHasKey( 'continue_url', $this->request( 'PUT', '/checkout-sessions/' . $created['id'], array() )->get_data() );
		$this->assertTrue( wc_get_order( $order_id )->has_status( OrderStatus::CANCELLED ) );
	}

	/**
	 * @testdox Creating again from a cart that already is a checkout returns that checkout and changes nothing.
	 */
	public function test_create_from_an_existing_checkout_returns_it_unchanged(): void {
		$created   = $this->create_ready_checkout();
		$completed = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' )->get_data();

		$body     = array(
			'cart_id'          => $created['id'],
			'shipping_address' => array( 'address_locality' => 'Oakland' ),
		);
		$response = $this->request( 'POST', '/checkout-sessions', $body );

		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $completed['continue_url'], $data['continue_url'] );
		$this->assertSame( 'San Francisco', WC()->customer->get_shipping_city() );
		$this->assertContains( 'FIELDS_IGNORED', array_column( $data['messages'], 'code' ), 'The agent must learn its address was not applied.' );

		$plain = $this->request( 'POST', '/checkout-sessions', array( 'cart_id' => $created['id'] ) )->get_data();
		$this->assertEquals( $completed, $plain, 'A bare repeat has nothing ignored to warn about.' );
	}

	/**
	 * @testdox Complete holds the order's stock, as the payment page trusts a pending order to have.
	 */
	public function test_complete_holds_stock(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 3,
			)
		);
		$created = $this->create_ready_checkout(
			array(
				'item'     => array( 'id' => (string) $product->get_id() ),
				'quantity' => 2,
			)
		);

		$this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' );

		$this->assertSame( 2, wc_get_held_stock_quantity( $product ) );
	}

	/**
	 * @testdox Complete refuses a checkout whose stock another order took first and hands over no payable order.
	 */
	public function test_complete_refuses_stock_it_cannot_hold(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 1,
			)
		);
		$created = $this->create_ready_checkout(
			array(
				'item'     => array( 'id' => (string) $product->get_id() ),
				'quantity' => 1,
			)
		);

		// A rival checkout takes the last unit after this cart was checked and before its order is held.
		$rival = wc_create_order();
		$rival->add_product( $product, 1 );
		$rival->save();
		$hold_rival = static function () use ( $rival, &$hold_rival ) {
			remove_action( 'woocommerce_order_before_calculate_totals', $hold_rival );
			wc_reserve_stock_for_order( $rival );
		};
		add_action( 'woocommerce_order_before_calculate_totals', $hold_rival );
		$response = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'out_of_stock', $response->get_data()['messages'][0]['code'] );

		$order = wc_get_order( (int) WC()->session->get( 'store_api_draft_order' ) );
		$this->assertTrue( $order->has_status( OrderStatus::CHECKOUT_DRAFT ), 'A refused hold must not leave a payable order.' );
		$this->assertSame( 0, wc_get_held_stock_quantity( $product, $rival->get_id() ) );
	}

	/**
	 * @testdox Completing again cancels the order already handed off when its stock can no longer be held.
	 * @dataProvider provider_when_the_rival_takes_the_stock
	 *
	 * @param bool $mid_request Whether the rival takes the unit during the second complete rather than before it.
	 */
	public function test_complete_again_cancels_an_order_it_cannot_hold_stock_for( bool $mid_request ): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 1,
			)
		);
		$created = $this->create_ready_checkout(
			array(
				'item'     => array( 'id' => (string) $product->get_id() ),
				'quantity' => 1,
			)
		);
		$this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' );
		$order = wc_get_order( (int) WC()->session->get( 'store_api_draft_order' ) );

		// The hold lapses and a rival takes the unit.
		wc_release_stock_for_order( $order );
		$rival = wc_create_order();
		$rival->add_product( $product, 1 );
		$rival->save();
		$hold_rival = static function () use ( $rival, &$hold_rival ) {
			remove_action( 'woocommerce_order_before_calculate_totals', $hold_rival );
			wc_reserve_stock_for_order( $rival );
		};
		if ( $mid_request ) {
			add_action( 'woocommerce_order_before_calculate_totals', $hold_rival );
		} else {
			$hold_rival();
		}
		$response = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' );

		$this->assertContains( 'out_of_stock', array_column( $response->get_data()['messages'], 'code' ) );
		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::CANCELLED ) );
		$this->assertArrayNotHasKey( 'continue_url', $this->request( 'GET', '/checkout-sessions/' . $created['id'] )->get_data() );
	}

	/**
	 * When the rival order takes the stock, relative to the second complete.
	 *
	 * @return array<string, array{bool}>
	 */
	public function provider_when_the_rival_takes_the_stock(): array {
		return array(
			'before the request' => array( false ),
			'during the request' => array( true ),
		);
	}

	/**
	 * @testdox Stock that ran short after the cart was built is reported, not absorbed by lowering the quantity.
	 */
	public function test_stock_shortfall_is_reported_not_absorbed(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 3,
			)
		);
		$created = $this->create_ready_checkout(
			array(
				'item'     => array( 'id' => (string) $product->get_id() ),
				'quantity' => 2,
			)
		);

		wc_update_product_stock( $product, 1 );
		$this->simulate_new_request( $created['id'] );

		$read = $this->request( 'GET', '/checkout-sessions/' . $created['id'] )->get_data();
		$this->assertSame( 2, $read['line_items'][0]['quantity'], 'The agent asked for 2 and must still see 2.' );
		$this->assertSame( 'incomplete', $read['status'] );
		$this->assertContains( 'out_of_stock', array_column( $read['messages'], 'code' ) );

		$completed = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' )->get_data();
		$this->assertSame( 'incomplete', $completed['status'] );
		$this->assertArrayNotHasKey( 'continue_url', $completed );
		$this->assertEmpty( WC()->session->get( 'store_api_draft_order' ) );
	}

	/**
	 * @testdox Complete leaves an incomplete checkout as it is and creates no order.
	 */
	public function test_complete_refuses_an_incomplete_checkout(): void {
		$created = $this->request( 'POST', '/checkout-sessions', array( 'line_items' => array( $this->line_item( 0, 1 ) ) ) )->get_data();

		$data = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' )->get_data();

		$this->assertSame( 'incomplete', $data['status'] );
		$this->assertArrayNotHasKey( 'continue_url', $data );
		$this->assertEmpty( WC()->session->get( 'store_api_draft_order' ) );
	}

	/**
	 * @testdox A canceled checkout keeps answering as canceled and refuses further changes.
	 */
	public function test_cancel_then_conflict(): void {
		$created = $this->create_ready_checkout();
		$this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/complete' );
		$order_id = (int) WC()->session->get( 'store_api_draft_order' );

		$cancel = $this->request( 'POST', '/checkout-sessions/' . $created['id'] . '/cancel' );
		$this->assertSame( 200, $cancel->get_status() );
		$this->assertSame( 'canceled', $cancel->get_data()['status'] );
		$this->assertTrue( wc_get_order( $order_id )->has_status( OrderStatus::CANCELLED ), 'The handed-off order must no longer be payable.' );

		$this->assertSame( 'canceled', $this->request( 'GET', '/checkout-sessions/' . $created['id'] )->get_data()['status'] );

		foreach ( array( '/cancel', '/complete' ) as $action ) {
			$this->assertSame( 409, $this->request( 'POST', '/checkout-sessions/' . $created['id'] . $action )->get_status(), $action );
		}
		$this->assertSame( 409, $this->request( 'POST', '/checkout-sessions', array( 'cart_id' => $created['id'] ) )->get_status() );
	}
}
