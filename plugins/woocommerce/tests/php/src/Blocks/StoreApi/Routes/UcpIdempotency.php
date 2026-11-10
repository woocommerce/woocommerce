<?php
/**
 * UCP Idempotency-Key Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Utilities\Ucp\IdempotencyClaim;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\IdempotencyStore;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\RequestFingerprint;

/**
 * Tests for `Idempotency-Key` handling on the /wc/ucp/v1/carts writes.
 */
class UcpIdempotency extends UcpCartTestCase {
	/**
	 * Published product IDs shared by the class, oldest first.
	 *
	 * @var int[]
	 */
	private static $product_ids = array();

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
		$this->assertSame( array( 'dev.ucp.shopping.cart' ), array_keys( $response->get_data()['ucp']['capabilities'] ) );

		$checkout = $this->request( 'POST', '/checkout-sessions', $this->cart_body( 0 ) );
		$this->assertSame( array( 'dev.ucp.shopping.checkout' ), array_keys( $checkout->get_data()['ucp']['capabilities'] ), 'The refusal names the capability of the route it came from.' );
	}

	/**
	 * A malformed key is refused.
	 *
	 * @dataProvider provider_malformed_keys
	 *
	 * @param string $key Key to send.
	 */
	public function test_a_malformed_key_is_refused( string $key ): void {
		$response = $this->request( 'POST', '/carts', $this->cart_body( 0 ), $key );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( array( 'IDEMPOTENCY_KEY_INVALID' ), $this->message_codes( $response ) );
		$this->assertSame( 0, $this->stored_record_count() );
	}

	/**
	 * Keys outside the accepted shape.
	 *
	 * @return array<string, array{string}>
	 */
	public function provider_malformed_keys(): array {
		return array(
			'too short'          => array( str_repeat( 'a', 21 ) ),
			'outside alphabet'   => array( str_repeat( 'a', 21 ) . '.' ),
			'longer than column' => array( str_repeat( 'a', 192 ) ),
		);
	}

	/**
	 * A key need not be a UUID: any 22 characters of the alphabet are accepted and replay.
	 */
	public function test_a_non_uuid_key_is_accepted_and_replays(): void {
		$key  = 'Ab3_dE-7Gh9Jk1Lm3Np5Qr';
		$body = $this->cart_body( 0 );

		$first = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( 200, $first->get_status() );

		$second = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( 'true', $second->get_headers()['Idempotency-Replayed'] ?? '' );
		$this->assertSame( 1, $this->stored_record_count() );
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
	 * A completed record past retention is dropped and the write runs again.
	 */
	public function test_a_record_past_retention_is_not_replayed(): void {
		global $wpdb;

		$key   = wp_generate_uuid4();
		$body  = $this->cart_body( 0 );
		$first = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( 'success', $first->get_data()['ucp']['status'] );

		// Age the record past retention, as a retry arriving before the daily sweep finds it.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ageing a row the test just created.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_ucp_idempotency_keys SET completed_at = %s WHERE idempotency_key = %s",
				gmdate( 'Y-m-d H:i:s', time() - ( IdempotencyStore::retention_seconds() + 60 ) ),
				$key
			)
		);

		$second = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( 200, $second->get_status() );
		$this->assertArrayNotHasKey( 'Idempotency-Replayed', $second->get_headers() );
		$this->assertNotSame( $first->get_data()['id'], $second->get_data()['id'], 'An expired key runs the write again.' );
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
		$this->assertTrue( IdempotencyStore::try_begin( $key, RequestFingerprint::compute( $request ) ) );

		$response = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( array( 'IDEMPOTENCY_CONFLICT' ), $this->message_codes( $response ) );
	}

	/**
	 * A key the store cannot record fails closed, so the write never runs undeduplicated.
	 */
	public function test_an_unavailable_store_refuses_the_write(): void {
		global $wpdb;

		$table        = $wpdb->prefix . 'wc_ucp_idempotency_keys';
		$query_filter = static function ( $query ) use ( $table ) {
			return false !== strpos( $query, $table ) ? 'INVALID SQL' : $query;
		};

		$logger = $this->createMock( \WC_Logger_Interface::class );
		$logger->expects( $this->once() )
			->method( 'error' )
			->with( $this->stringContains( 'UCP idempotency record could not be written' ) );
		$logger_filter = static function () use ( $logger ) {
			return $logger;
		};

		$previous_suppress_errors = $wpdb->suppress_errors( true );
		add_filter( 'query', $query_filter );
		add_filter( 'woocommerce_logging_class', $logger_filter );

		try {
			$response = $this->request( 'POST', '/carts', $this->cart_body( 0 ), wp_generate_uuid4() );
		} finally {
			remove_filter( 'query', $query_filter );
			remove_filter( 'woocommerce_logging_class', $logger_filter );
			$wpdb->suppress_errors( $previous_suppress_errors );
		}

		$this->assertSame( 503, $response->get_status() );
		$this->assertSame( (string) IdempotencyClaim::RETRY_AFTER_SECONDS, $response->get_headers()['Retry-After'] ?? '' );
		$this->assertSame( 'error', $response->get_data()['ucp']['status'] );
		$this->assertSame( array( 'IDEMPOTENCY_STORE_UNAVAILABLE' ), $this->message_codes( $response ) );
		$this->assertSame( 0, $this->stored_record_count() );
	}

	/**
	 * A malformed write is not stored, so the same key can carry the corrected one.
	 */
	public function test_a_non_2xx_response_is_not_stored(): void {
		$key      = wp_generate_uuid4();
		$response = $this->request( 'POST', '/carts', array( 'line_items' => 'not-an-array' ), $key );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 0, $this->stored_record_count() );
		$this->assertNull( IdempotencyStore::find( $key ) );
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

		IdempotencyStore::try_begin( $fresh, str_repeat( 'a', 64 ) );
		IdempotencyStore::try_begin( $orphaned, str_repeat( 'b', 64 ) );
		IdempotencyStore::try_begin( $finished, str_repeat( 'c', 64 ) );
		IdempotencyStore::finalize( $finished, 200, array( 'ok' => true ) );

		$stale = gmdate( 'Y-m-d H:i:s', time() - ( 2 * DAY_IN_SECONDS ) );

		// An orphan ages by its claim, a finished record by its completion.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ageing a row the test just created.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_ucp_idempotency_keys SET created_at = %s WHERE idempotency_key = %s",
				$stale,
				$orphaned
			)
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Same.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_ucp_idempotency_keys SET completed_at = %s WHERE idempotency_key = %s",
				$stale,
				$finished
			)
		);

		IdempotencyStore::cleanup();

		$this->assertNotNull( IdempotencyStore::find( $fresh ) );
		$this->assertNull( IdempotencyStore::find( $orphaned ) );
		$this->assertNull( IdempotencyStore::find( $finished ) );
	}

	/**
	 * Only one of several concurrent claims on a key wins.
	 */
	public function test_only_one_claim_on_a_key_wins(): void {
		$key         = wp_generate_uuid4();
		$fingerprint = str_repeat( 'd', 64 );

		$this->assertTrue( IdempotencyStore::try_begin( $key, $fingerprint ) );
		$this->assertFalse( IdempotencyStore::try_begin( $key, $fingerprint ) );
		$this->assertSame( IdempotencyStore::STATE_IN_PROGRESS, IdempotencyStore::find( $key )['state'] );
	}

	/**
	 * A stored response keeps the status the first call answered with.
	 */
	public function test_a_stored_response_keeps_its_status(): void {
		$key = wp_generate_uuid4();

		IdempotencyStore::try_begin( $key, str_repeat( 'e', 64 ) );
		IdempotencyStore::finalize( $key, 201, array( 'id' => 'cart-1' ) );

		$record = IdempotencyStore::find( $key );

		$this->assertSame( IdempotencyStore::STATE_COMPLETED, $record['state'] );
		$this->assertSame( 201, (int) $record['response_status'] );
		$this->assertSame( array( 'id' => 'cart-1' ), json_decode( $record['response_body'], true ) );
	}

	/**
	 * A different payload, route or method is a different fingerprint, and so is the same
	 * payload serialized differently: the body is compared as raw bytes, like `Content-Digest`.
	 */
	public function test_a_fingerprint_follows_body_bytes_route_and_method(): void {
		$base = new \WP_REST_Request( 'PUT', '/wc/ucp/v1/carts/abc' );
		$base->set_header( 'content-type', 'application/json' );
		$base->set_body( '{"currency":"USD","quantity":1}' );

		$other_payload = new \WP_REST_Request( 'PUT', '/wc/ucp/v1/carts/abc' );
		$other_payload->set_header( 'content-type', 'application/json' );
		$other_payload->set_body( '{"currency":"USD","quantity":2}' );

		$other_bytes = new \WP_REST_Request( 'PUT', '/wc/ucp/v1/carts/abc' );
		$other_bytes->set_header( 'content-type', 'application/json' );
		$other_bytes->set_body( '{ "quantity" : 1 , "currency" : "USD" }' );

		$other_route = new \WP_REST_Request( 'PUT', '/wc/ucp/v1/carts/def' );
		$other_route->set_header( 'content-type', 'application/json' );
		$other_route->set_body( '{"currency":"USD","quantity":1}' );

		$other_method = new \WP_REST_Request( 'POST', '/wc/ucp/v1/carts/abc' );
		$other_method->set_header( 'content-type', 'application/json' );
		$other_method->set_body( '{"currency":"USD","quantity":1}' );

		$fingerprint = RequestFingerprint::compute( $base );

		$this->assertNotSame( $fingerprint, RequestFingerprint::compute( $other_payload ) );
		$this->assertNotSame( $fingerprint, RequestFingerprint::compute( $other_bytes ) );
		$this->assertNotSame( $fingerprint, RequestFingerprint::compute( $other_route ) );
		$this->assertNotSame( $fingerprint, RequestFingerprint::compute( $other_method ) );
	}

	/**
	 * A read dispatched after a write must not touch the write's stored response.
	 *
	 * One route instance serves every dispatch in the process, and `CartsById` answers both
	 * GET and PUT from it, so a key held on the instance would be reused by the next request.
	 */
	public function test_a_later_read_does_not_overwrite_a_stored_write(): void {
		$created = $this->request( 'POST', '/carts', $this->cart_body( 0 ), wp_generate_uuid4() );
		$cart_id = $created->get_data()['id'];
		$key     = wp_generate_uuid4();
		$body    = $this->cart_body( 0, 3 );

		$updated = $this->request( 'PUT', '/carts/' . $cart_id, $body, $key );
		$this->assertSame( 200, $updated->get_status() );

		// Same route instance, same process: the GET must own no key.
		$this->request( 'GET', '/carts/' . $cart_id );

		$replay = $this->request( 'PUT', '/carts/' . $cart_id, $body, $key );

		$this->assertSame( 'true', $replay->get_headers()['Idempotency-Replayed'] ?? '' );
		$this->assertSame( $updated->get_data(), $replay->get_data() );
	}

	/**
	 * Two spellings of a key are two keys: a case-folding index would hand the second request
	 * the first one's response.
	 */
	public function test_key_case_is_significant(): void {
		$key  = wp_generate_uuid4();
		$body = $this->cart_body( 0 );

		$this->request( 'POST', '/carts', $body, strtolower( $key ) );
		$second = $this->request( 'POST', '/carts', $body, strtoupper( $key ) );

		$this->assertSame( 200, $second->get_status() );
		$this->assertArrayNotHasKey( 'Idempotency-Replayed', $second->get_headers() );
		$this->assertSame( 2, $this->stored_record_count() );
	}

	/**
	 * A claim left behind by a dead process is taken over, not refused forever.
	 */
	public function test_a_stale_claim_is_taken_over(): void {
		global $wpdb;

		$key  = wp_generate_uuid4();
		$body = $this->cart_body( 0 );

		$request = new \WP_REST_Request( 'POST', '/wc/ucp/v1/carts' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		$this->assertTrue( IdempotencyStore::try_begin( $key, RequestFingerprint::compute( $request ) ) );

		// Age the claim past the orphan window, as a process that died would leave it.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ageing a row the test just created.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_ucp_idempotency_keys SET created_at = %s WHERE idempotency_key = %s",
				gmdate( 'Y-m-d H:i:s', time() - ( IdempotencyStore::STALE_CLAIM_SECONDS + 60 ) ),
				$key
			)
		);

		$response = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( IdempotencyStore::STATE_COMPLETED, IdempotencyStore::find( $key )['state'] );
	}

	/**
	 * A claim younger than the orphan window is still refused.
	 */
	public function test_a_fresh_claim_is_not_taken_over(): void {
		$key  = wp_generate_uuid4();
		$body = $this->cart_body( 0 );

		$request = new \WP_REST_Request( 'POST', '/wc/ucp/v1/carts' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		IdempotencyStore::try_begin( $key, RequestFingerprint::compute( $request ) );

		$response = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( array( 'IDEMPOTENCY_CONFLICT' ), $this->message_codes( $response ) );
	}

	/**
	 * A shortened retention window must not free a claim whose request is still running:
	 * that would let a retry run the same write a second time.
	 */
	public function test_cleanup_spares_a_running_claim_under_short_retention(): void {
		global $wpdb;

		add_filter( 'woocommerce_ucp_idempotency_retention_seconds', static fn () => 1 );

		$running  = wp_generate_uuid4();
		$finished = wp_generate_uuid4();

		IdempotencyStore::try_begin( $running, str_repeat( 'a', 64 ) );
		IdempotencyStore::try_begin( $finished, str_repeat( 'b', 64 ) );
		IdempotencyStore::finalize( $finished, 200, array( 'ok' => true ) );

		// Older than the one-second window the filter sets, so the completed record is due
		// for deletion while the claim beside it is seconds old.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ageing a row the test just created.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_ucp_idempotency_keys SET completed_at = %s WHERE idempotency_key = %s",
				gmdate( 'Y-m-d H:i:s', time() - 30 ),
				$finished
			)
		);

		IdempotencyStore::cleanup();

		$this->assertNotNull( IdempotencyStore::find( $running ), 'A running claim must survive a shortened retention window.' );
		$this->assertNull( IdempotencyStore::find( $finished ) );
	}

	/**
	 * A server error releases the claim, so the retry is processed rather than refused.
	 */
	public function test_a_server_error_releases_the_claim(): void {
		$fail = static function () {
			throw new \RuntimeException( 'Storage went away mid-write.' );
		};
		add_action( 'woocommerce_add_to_cart', $fail );

		$key      = wp_generate_uuid4();
		$body     = $this->cart_body( 0 );
		$response = $this->request( 'POST', '/carts', $body, $key );

		$this->assertGreaterThanOrEqual( 500, $response->get_status() );
		$this->assertNull( IdempotencyStore::find( $key ) );

		remove_action( 'woocommerce_add_to_cart', $fail );

		$retry = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( 200, $retry->get_status() );
		$this->assertSame( 'success', $retry->get_data()['ucp']['status'] );
		$this->assertArrayNotHasKey( 'Idempotency-Replayed', $retry->get_headers() );
	}

	/**
	 * A replayed cart body is not cacheable.
	 */
	public function test_a_replay_is_not_cacheable(): void {
		$key  = wp_generate_uuid4();
		$body = $this->cart_body( 0 );

		$this->request( 'POST', '/carts', $body, $key );
		$replay = $this->request( 'POST', '/carts', $body, $key );

		$this->assertSame( 'no-store', $replay->get_headers()['Cache-Control'] ?? '' );
	}

	/**
	 * A refusal the gate produces is not cacheable either.
	 */
	public function test_a_gate_refusal_is_not_cacheable(): void {
		$response = $this->request( 'POST', '/carts', $this->cart_body( 0 ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'no-store', $response->get_headers()['Cache-Control'] ?? '' );
	}
}
