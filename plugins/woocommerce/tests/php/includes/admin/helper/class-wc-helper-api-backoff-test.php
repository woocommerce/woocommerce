<?php
/**
 * Unit tests for WC_Helper_API_Backoff class
 *
 * @package WooCommerce\Tests\Admin\Helper
 */

declare(strict_types=1);

/**
 * Class WC_Helper_API_Backoff_Test
 */
class WC_Helper_API_Backoff_Test extends WC_Unit_Test_Case {

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->cleanup_transients();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		$this->cleanup_transients();

		parent::tearDown();
	}

	/**
	 * Clear the backoff transients for every known request type.
	 */
	private function cleanup_transients() {
		delete_transient( WC_Helper_API_Backoff::TRANSIENT_PREFIX . WC_Helper_API_Backoff::REQUEST_TYPE_UPDATE_CHECK );
		delete_transient( WC_Helper_API_Backoff::TRANSIENT_PREFIX . WC_Helper_API_Backoff::REQUEST_TYPE_SUBSCRIPTIONS );
	}

	/**
	 * Build a mocked rate-limited (429) response with the given headers.
	 *
	 * @param array $headers Response headers (lowercase keys).
	 * @return array A response array in the shape returned by wp_remote_post().
	 */
	private function make_rate_limited_response( array $headers ) {
		return array(
			'headers'  => $headers,
			'response' => array(
				'code'    => 429,
				'message' => 'Too Many Requests',
			),
			'body'     => '{"code":"wccom_rest_limit_reached","message":"You reached your API request limit.","data":{"status":429}}',
		);
	}

	/**
	 * Read the stored backoff expiry timestamp for a request type.
	 *
	 * @param string $request_type The request type.
	 * @return int The stored expiry timestamp, or 0 when not set.
	 */
	private function get_backoff_expiry( $request_type ) {
		return (int) get_transient( WC_Helper_API_Backoff::TRANSIENT_PREFIX . $request_type );
	}

	/**
	 * @testdox Should derive the backoff window from the Retry-After header, capped at max, defaulting when absent.
	 * @dataProvider retry_after_provider
	 *
	 * @param string $request_type     The request type to record against.
	 * @param array  $headers          The response headers.
	 * @param int    $expected_seconds The expected backoff window in seconds.
	 */
	public function test_record_from_response_honors_retry_after( string $request_type, array $headers, int $expected_seconds ): void {
		$start = time();

		WC_Helper_API_Backoff::record_from_response( $request_type, $this->make_rate_limited_response( $headers ) );

		$end    = time();
		$expiry = $this->get_backoff_expiry( $request_type );

		// The stored expiry is now + window; now was captured between $start and $end.
		$this->assertGreaterThanOrEqual(
			$start + $expected_seconds,
			$expiry,
			"Backoff window should be at least {$expected_seconds}s for {$request_type}"
		);
		$this->assertLessThanOrEqual(
			$end + $expected_seconds,
			$expiry,
			"Backoff window should be at most {$expected_seconds}s for {$request_type}"
		);
		$this->assertTrue(
			WC_Helper_API_Backoff::is_rate_limited( $request_type ),
			'Recording a 429 should put the request type into a backoff window'
		);
	}

	/**
	 * Data provider for the Retry-After window cases.
	 *
	 * update-check bounds: default 1h (3600), max 3h (10800).
	 * subscriptions bounds: default 15m (900), max 3h (10800).
	 *
	 * @return array
	 */
	public function retry_after_provider() {
		$update_check  = WC_Helper_API_Backoff::REQUEST_TYPE_UPDATE_CHECK;
		$subscriptions = WC_Helper_API_Backoff::REQUEST_TYPE_SUBSCRIPTIONS;

		return array(
			'retry-after within bounds is honored'         => array( $update_check, array( 'retry-after' => '5000' ), 5000 ),
			'global short retry-after is honored, not floored' => array( $update_check, array( 'retry-after' => '55' ), 55 ),
			'retry-after above max is capped'              => array( $update_check, array( 'retry-after' => '20000' ), 10800 ),
			'missing retry-after uses per-type default'    => array( $update_check, array(), 3600 ),
			'subscriptions missing retry-after uses its default' => array( $subscriptions, array(), 900 ),
			'zero retry-after is treated as absent'        => array( $update_check, array( 'retry-after' => '0' ), 3600 ),
			'non-numeric retry-after is treated as absent' => array( $update_check, array( 'retry-after' => 'soon' ), 3600 ),
			// X-RateLimit-Reset is intentionally ignored; only Retry-After drives the window.
			'reset without retry-after falls back to default' => array( $update_check, array( 'x-ratelimit-reset' => '9999999999' ), 3600 ),
			'retry-after wins when reset is also present'  => array(
				$update_check,
				array(
					'retry-after'       => '55',
					'x-ratelimit-reset' => '9999999999',
				),
				55,
			),
		);
	}

	/**
	 * @testdox Should no longer be rate limited once the backoff is cleared.
	 */
	public function test_clear_removes_backoff(): void {
		WC_Helper_API_Backoff::record_from_response(
			WC_Helper_API_Backoff::REQUEST_TYPE_UPDATE_CHECK,
			$this->make_rate_limited_response( array( 'retry-after' => '55' ) )
		);
		$this->assertTrue(
			WC_Helper_API_Backoff::is_rate_limited( WC_Helper_API_Backoff::REQUEST_TYPE_UPDATE_CHECK ),
			'Precondition: the request type should be rate limited after recording a 429'
		);

		WC_Helper_API_Backoff::clear( WC_Helper_API_Backoff::REQUEST_TYPE_UPDATE_CHECK );

		$this->assertFalse(
			WC_Helper_API_Backoff::is_rate_limited( WC_Helper_API_Backoff::REQUEST_TYPE_UPDATE_CHECK ),
			'Clearing the backoff should lift the rate limit'
		);
	}
}
