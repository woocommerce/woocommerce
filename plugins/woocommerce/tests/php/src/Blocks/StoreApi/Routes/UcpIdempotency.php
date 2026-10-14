<?php
/**
 * UCP Idempotency-Key Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Utilities\UcpIdempotencyStore;
use Automattic\WooCommerce\StoreApi\Utilities\UcpRequestFingerprint;

/**
 * Tests for `Idempotency-Key` handling on the /wc/ucp/v1/carts writes.
 */
class UcpIdempotency extends ControllerTestCase {
	/**
	 * Published product IDs shared by the class, oldest first.
	 *
	 * @var int[]
	 */
	private static $product_ids = array();

	/**
	 * Option behind the `ucp` feature.
	 */
	private const FEATURE_OPTION = 'woocommerce_feature_ucp_enabled';

	/**
	 * Create the fixtures the whole class shares.
	 */
	public static function wpSetUpBeforeClass(): void {
		$products = self::create_class_fixture_products(
			array(
				array(
					'name'          => 'UCP Idempotency Mug',
					'regular_price' => '19.99',
					'price'         => '19.99',
					'sku'           => 'ucp-idempotency-mug',
				),
				array(
					'name'          => 'UCP Idempotency Poster',
					'regular_price' => '9.99',
					'price'         => '9.99',
					'sku'           => 'ucp-idempotency-poster',
				),
			)
		);

		self::$product_ids = array_map(
			static function ( $product ) {
				return $product->get_id();
			},
			$products
		);
	}

	/**
	 * Remove the class fixtures.
	 */
	public static function wpTearDownAfterClass(): void {
		self::delete_class_fixture_products( self::$product_ids );
		self::$product_ids = array();
	}

	/**
	 * Enable the UCP routes before the REST server is built.
	 */
	protected function setUp(): void {
		update_option( self::FEATURE_OPTION, 'yes' );
		parent::setUp();
	}

	/**
	 * Restore the default disabled state.
	 */
	protected function tearDown(): void {
		delete_option( self::FEATURE_OPTION );
		unset( $_SERVER['HTTP_CART_TOKEN'] );

		parent::tearDown();
	}

	/**
	 * Dispatch a request with a JSON body and, optionally, an idempotency key.
	 *
	 * @param string $method HTTP method.
	 * @param string $path Route path under /wc/ucp/v1.
	 * @param array  $body Request body.
	 * @param string $key Idempotency key, or '' to send none.
	 * @return \WP_REST_Response
	 */
	private function request( string $method, string $path, array $body = array(), string $key = '' ) {
		$request = new \WP_REST_Request( $method, '/wc/ucp/v1' . $path );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		if ( '' !== $key ) {
			$request->set_header( 'Idempotency-Key', $key );
		}

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * A body creating a cart holding one of the fixture products.
	 *
	 * @param int $index Fixture index.
	 * @param int $quantity Quantity.
	 * @return array
	 */
	private function cart_body( int $index, int $quantity = 1 ): array {
		return array(
			'line_items' => array(
				array(
					'item'     => array( 'id' => (string) self::$product_ids[ $index ] ),
					'quantity' => $quantity,
				),
			),
		);
	}

	/**
	 * Read the message codes out of a UCP response.
	 *
	 * @param \WP_REST_Response $response Response to read.
	 * @return string[]
	 */
	private function message_codes( \WP_REST_Response $response ): array {
		$data = $response->get_data();

		return array_map(
			static function ( array $message ) {
				return $message['code'];
			},
			$data['messages'] ?? array()
		);
	}

	/**
	 * How many records the store holds.
	 *
	 * @return int
	 */
	private function stored_record_count(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Asserting on the table under test.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_ucp_idempotency_keys" );
	}

	/**
	 * A write carrying no key is refused, and nothing is created.
	 */
	public function test_a_write_without_a_key_is_refused(): void {
		$response = $this->request( 'POST', '/carts', $this->cart_body( 0 ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( array( 'IDEMPOTENCY_KEY_REQUIRED' ), $this->message_codes( $response ) );
		$this->assertSame( 0, $this->stored_record_count() );
	}

	/**
	 * A key that is not a UUID is refused.
	 */
	public function test_a_key_that_is_not_a_uuid_is_refused(): void {
		$response = $this->request( 'POST', '/carts', $this->cart_body( 0 ), 'not-a-uuid' );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( array( 'IDEMPOTENCY_KEY_INVALID' ), $this->message_codes( $response ) );
		$this->assertSame( 0, $this->stored_record_count() );
	}

	/**
	 * A keyless write goes through once a site turns strict mode off.
	 */
	public function test_a_keyless_write_passes_when_strict_mode_is_off(): void {
		add_filter( 'woocommerce_ucp_idempotency_strict', '__return_false' );

		$response = $this->request( 'POST', '/carts', $this->cart_body( 0 ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 0, $this->stored_record_count() );
	}

	/**
	 * Retrying a create replays the first response instead of making a second cart.
	 */
	public function test_a_retry_replays_the_stored_response(): void {
		$key   = wp_generate_uuid4();
		$body  = $this->cart_body( 0, 2 );
		$first = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( 200, $first->get_status() );

		$second = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( $first->get_status(), $second->get_status() );
		$this->assertSame( 'true', $second->get_headers()['Idempotency-Replayed'] ?? '' );
		$this->assertSame( $first->get_data(), $second->get_data() );
		$this->assertSame( 1, $this->stored_record_count() );
	}

	/**
	 * Reusing a key for a different payload is a conflict.
	 */
	public function test_reusing_a_key_for_another_payload_is_a_conflict(): void {
		$key = wp_generate_uuid4();

		$this->assertSame( 200, $this->request( 'POST', '/carts', $this->cart_body( 0 ), $key )->get_status() );

		$response = $this->request( 'POST', '/carts', $this->cart_body( 1 ), $key );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( array( 'IDEMPOTENCY_KEY_REUSED' ), $this->message_codes( $response ) );
	}

	/**
	 * A key another request still holds is a conflict, not a replay.
	 */
	public function test_a_key_still_in_flight_is_a_conflict(): void {
		$key     = wp_generate_uuid4();
		$body    = $this->cart_body( 0 );
		$request = new \WP_REST_Request( 'POST', '/wc/ucp/v1/carts' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		// Stand in for a request that claimed the key and has not answered yet.
		$this->assertTrue( UcpIdempotencyStore::try_begin( $key, UcpRequestFingerprint::compute( $request ) ) );

		$response = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( array( 'IDEMPOTENCY_CONFLICT' ), $this->message_codes( $response ) );
	}

	/**
	 * A malformed write is not stored, so the same key can carry the corrected one.
	 */
	public function test_a_non_2xx_response_is_not_stored(): void {
		$key      = wp_generate_uuid4();
		$response = $this->request( 'POST', '/carts', array( 'line_items' => 'not-an-array' ), $key );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 0, $this->stored_record_count() );
		$this->assertNull( UcpIdempotencyStore::find( $key ) );
	}

	/**
	 * A refusal reported at 200 is not stored either: nothing happened, so a retry has to
	 * be processed again rather than answered with the refusal.
	 */
	public function test_a_refusal_reported_at_200_is_not_stored(): void {
		$key      = wp_generate_uuid4();
		$body     = array(
			'line_items' => array(
				array(
					'item'     => array( 'id' => '99999999' ),
					'quantity' => 1,
				),
			),
		);
		$response = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'error', $response->get_data()['ucp']['status'] );
		$this->assertSame( array( 'not_found' ), $this->message_codes( $response ) );
		$this->assertSame( 0, $this->stored_record_count() );
	}

	/**
	 * Reading a cart needs no key and stores nothing.
	 */
	public function test_a_read_needs_no_key(): void {
		$created = $this->request( 'POST', '/carts', $this->cart_body( 0 ), wp_generate_uuid4() );
		$cart_id = $created->get_data()['id'];

		$response = $this->request( 'GET', '/carts/' . $cart_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $cart_id, $response->get_data()['id'] );
		$this->assertSame( 1, $this->stored_record_count() );
	}

	/**
	 * A retried cancel replays its own response, rather than the `not_found` the first
	 * call left behind.
	 */
	public function test_a_retried_cancel_replays_the_prior_state(): void {
		$created = $this->request( 'POST', '/carts', $this->cart_body( 0 ), wp_generate_uuid4() );
		$cart_id = $created->get_data()['id'];
		$key     = wp_generate_uuid4();

		$first = $this->request( 'POST', '/carts/' . $cart_id . '/cancel', array(), $key );

		$this->assertSame( 200, $first->get_status() );
		$this->assertSame( array( 'CANCELED' ), $this->message_codes( $first ) );

		$second = $this->request( 'POST', '/carts/' . $cart_id . '/cancel', array(), $key );

		$this->assertSame( 'true', $second->get_headers()['Idempotency-Replayed'] ?? '' );
		$this->assertSame( $first->get_data(), $second->get_data() );
	}

	/**
	 * The kill switch takes the whole mechanism out of the way.
	 */
	public function test_the_kill_switch_stores_nothing(): void {
		add_filter( 'woocommerce_ucp_idempotency_enabled', '__return_false' );

		$key = wp_generate_uuid4();

		$this->assertSame( 200, $this->request( 'POST', '/carts', $this->cart_body( 0 ), $key )->get_status() );
		$this->assertSame( 0, $this->stored_record_count() );
	}

	/**
	 * Cleanup removes expired records, both completed and orphaned, and keeps fresh ones.
	 */
	public function test_cleanup_removes_expired_records_of_both_kinds(): void {
		global $wpdb;

		$fresh    = wp_generate_uuid4();
		$orphaned = wp_generate_uuid4();
		$finished = wp_generate_uuid4();

		UcpIdempotencyStore::try_begin( $fresh, str_repeat( 'a', 64 ) );
		UcpIdempotencyStore::try_begin( $orphaned, str_repeat( 'b', 64 ) );
		UcpIdempotencyStore::try_begin( $finished, str_repeat( 'c', 64 ) );
		UcpIdempotencyStore::finalize( $finished, 200, array( 'ok' => true ) );

		$stale = gmdate( 'Y-m-d H:i:s', time() - ( 2 * DAY_IN_SECONDS ) );

		foreach ( array( $orphaned, $finished ) as $key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ageing a row the test just created.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}wc_ucp_idempotency_keys SET created_at = %s WHERE idempotency_key = %s",
					$stale,
					$key
				)
			);
		}

		UcpIdempotencyStore::cleanup();

		$this->assertNotNull( UcpIdempotencyStore::find( $fresh ) );
		$this->assertNull( UcpIdempotencyStore::find( $orphaned ) );
		$this->assertNull( UcpIdempotencyStore::find( $finished ) );
	}

	/**
	 * Only one of several concurrent claims on a key wins.
	 */
	public function test_only_one_claim_on_a_key_wins(): void {
		$key         = wp_generate_uuid4();
		$fingerprint = str_repeat( 'd', 64 );

		$this->assertTrue( UcpIdempotencyStore::try_begin( $key, $fingerprint ) );
		$this->assertFalse( UcpIdempotencyStore::try_begin( $key, $fingerprint ) );
		$this->assertSame( UcpIdempotencyStore::STATE_IN_PROGRESS, UcpIdempotencyStore::find( $key )['state'] );
	}

	/**
	 * A stored response keeps the status the first call answered with.
	 */
	public function test_a_stored_response_keeps_its_status(): void {
		$key = wp_generate_uuid4();

		UcpIdempotencyStore::try_begin( $key, str_repeat( 'e', 64 ) );
		UcpIdempotencyStore::finalize( $key, 201, array( 'id' => 'cart-1' ) );

		$record = UcpIdempotencyStore::find( $key );

		$this->assertSame( UcpIdempotencyStore::STATE_COMPLETED, $record['state'] );
		$this->assertSame( 201, (int) $record['response_status'] );
		$this->assertSame( array( 'id' => 'cart-1' ), json_decode( $record['response_body'], true ) );
	}

	/**
	 * Key order and whitespace do not change a fingerprint: a proxy re-serializing a
	 * payload must not turn a genuine retry into a conflict.
	 */
	public function test_a_fingerprint_ignores_key_order_and_whitespace(): void {
		$one = new \WP_REST_Request( 'PUT', '/wc/ucp/v1/carts/abc' );
		$one->set_header( 'content-type', 'application/json' );
		$one->set_body( '{"currency":"USD","line_items":[{"item":{"id":"1"},"quantity":2}]}' );

		$two = new \WP_REST_Request( 'PUT', '/wc/ucp/v1/carts/abc' );
		$two->set_header( 'content-type', 'application/json' );
		$two->set_body( '{ "line_items" : [ { "quantity" : 2, "item" : { "id" : "1" } } ] , "currency" : "USD" }' );

		$this->assertSame( UcpRequestFingerprint::compute( $one ), UcpRequestFingerprint::compute( $two ) );
	}

	/**
	 * A different payload, route or method is a different fingerprint.
	 */
	public function test_a_fingerprint_follows_payload_route_and_method(): void {
		$base = new \WP_REST_Request( 'PUT', '/wc/ucp/v1/carts/abc' );
		$base->set_header( 'content-type', 'application/json' );
		$base->set_body( '{"quantity":1}' );

		$other_payload = new \WP_REST_Request( 'PUT', '/wc/ucp/v1/carts/abc' );
		$other_payload->set_header( 'content-type', 'application/json' );
		$other_payload->set_body( '{"quantity":2}' );

		$other_route = new \WP_REST_Request( 'PUT', '/wc/ucp/v1/carts/def' );
		$other_route->set_header( 'content-type', 'application/json' );
		$other_route->set_body( '{"quantity":1}' );

		$other_method = new \WP_REST_Request( 'POST', '/wc/ucp/v1/carts/abc' );
		$other_method->set_header( 'content-type', 'application/json' );
		$other_method->set_body( '{"quantity":1}' );

		$fingerprint = UcpRequestFingerprint::compute( $base );

		$this->assertNotSame( $fingerprint, UcpRequestFingerprint::compute( $other_payload ) );
		$this->assertNotSame( $fingerprint, UcpRequestFingerprint::compute( $other_route ) );
		$this->assertNotSame( $fingerprint, UcpRequestFingerprint::compute( $other_method ) );
	}

	/**
	 * A list keeps its order, since position carries meaning there.
	 */
	public function test_a_fingerprint_keeps_list_order(): void {
		$one = new \WP_REST_Request( 'PUT', '/wc/ucp/v1/carts/abc' );
		$one->set_header( 'content-type', 'application/json' );
		$one->set_body( '{"line_items":[{"item":{"id":"1"}},{"item":{"id":"2"}}]}' );

		$two = new \WP_REST_Request( 'PUT', '/wc/ucp/v1/carts/abc' );
		$two->set_header( 'content-type', 'application/json' );
		$two->set_body( '{"line_items":[{"item":{"id":"2"}},{"item":{"id":"1"}}]}' );

		$this->assertNotSame( UcpRequestFingerprint::compute( $one ), UcpRequestFingerprint::compute( $two ) );
	}
}
