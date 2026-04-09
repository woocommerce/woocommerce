<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Service;

use Automattic\WooCommerce\Internal\POS\Service\POSRateLimitService;
use WC_Unit_Test_Case;

/**
 * Tests for POSRateLimitService.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\Service\POSRateLimitService
 * @since 10.8.0
 */
class POSRateLimitServiceTest extends WC_Unit_Test_Case {

	private POSRateLimitService $service;

	public function setUp(): void {
		parent::setUp();
		$this->service = new POSRateLimitService();
		$this->service->reset( '192.168.1.1' );
		$this->service->reset( '10.0.0.1' );
	}

	public function tearDown(): void {
		$this->service->reset( '192.168.1.1' );
		$this->service->reset( '10.0.0.1' );
		parent::tearDown();
	}

	/**
	 * @testdox First attempt is allowed.
	 */
	public function test_first_attempt_is_allowed(): void {
		$result = $this->service->check_rate_limit( '192.168.1.1' );
		$this->assertTrue( $result );
	}

	/**
	 * @testdox 5 failures trigger 30-second lockout.
	 */
	public function test_five_failures_trigger_30_second_lockout(): void {
		for ( $i = 0; $i < 5; $i++ ) {
			$this->service->record_failure( '192.168.1.1' );
		}

		$result = $this->service->check_rate_limit( '192.168.1.1' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_rate_limited', $result->get_error_code() );
		$this->assertSame( 429, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox After lockout expires, attempts are allowed again.
	 */
	public function test_lockout_expires_and_allows_again(): void {
		for ( $i = 0; $i < 5; $i++ ) {
			$this->service->record_failure( '192.168.1.1' );
		}

		$result = $this->service->check_rate_limit( '192.168.1.1' );
		$this->assertInstanceOf( \WP_Error::class, $result );

		$this->simulate_time_passing( '192.168.1.1', 31 );

		$result = $this->service->check_rate_limit( '192.168.1.1' );
		$this->assertTrue( $result );
	}

	/**
	 * @testdox 10 failures trigger 5-minute lockout.
	 */
	public function test_ten_failures_trigger_five_minute_lockout(): void {
		for ( $i = 0; $i < 10; $i++ ) {
			$this->service->record_failure( '192.168.1.1' );
		}

		$result = $this->service->check_rate_limit( '192.168.1.1' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 429, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox 15 failures trigger permanent lock.
	 */
	public function test_fifteen_failures_trigger_permanent_lock(): void {
		for ( $i = 0; $i < 15; $i++ ) {
			$this->service->record_failure( '192.168.1.1' );
		}

		$result = $this->service->check_rate_limit( '192.168.1.1' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 429, $result->get_error_data()['status'] );
		$this->assertStringContainsString( 'administrator', $result->get_error_message() );
	}

	/**
	 * @testdox reset() clears the rate limit.
	 */
	public function test_reset_clears_rate_limit(): void {
		for ( $i = 0; $i < 15; $i++ ) {
			$this->service->record_failure( '192.168.1.1' );
		}

		$result = $this->service->check_rate_limit( '192.168.1.1' );
		$this->assertInstanceOf( \WP_Error::class, $result );

		$this->service->reset( '192.168.1.1' );

		$result = $this->service->check_rate_limit( '192.168.1.1' );
		$this->assertTrue( $result );
	}

	/**
	 * @testdox Different IPs have independent limits.
	 */
	public function test_different_ips_have_independent_limits(): void {
		for ( $i = 0; $i < 5; $i++ ) {
			$this->service->record_failure( '192.168.1.1' );
		}

		$result_locked = $this->service->check_rate_limit( '192.168.1.1' );
		$this->assertInstanceOf( \WP_Error::class, $result_locked );

		$result_other = $this->service->check_rate_limit( '10.0.0.1' );
		$this->assertTrue( $result_other );
	}

	/**
	 * Simulates time passing by manipulating the transient data directly.
	 *
	 * @param string $ip      The IP address.
	 * @param int    $seconds Seconds to simulate passing.
	 */
	private function simulate_time_passing( string $ip, int $seconds ): void {
		$key  = '_wc_pos_rate_' . hash( 'sha256', $ip );
		$data = get_transient( $key );
		if ( false !== $data && isset( $data['lockout_until'] ) ) {
			$data['lockout_until'] = time() - $seconds;
			set_transient( $key, $data, POSRateLimitService::WINDOW_SECONDS );
		}
	}
}
