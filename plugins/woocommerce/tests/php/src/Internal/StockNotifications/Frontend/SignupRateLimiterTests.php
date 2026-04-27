<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupRateLimiter;

/**
 * Unit tests for SignupRateLimiter.
 */
class SignupRateLimiterTests extends \WC_Unit_Test_Case {

	/**
	 * @var SignupRateLimiter
	 */
	private SignupRateLimiter $sut;

	/**
	 * @before
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new SignupRateLimiter();

		// Tight thresholds keep the tests focused and fast.
		add_filter( 'woocommerce_bis_signup_rate_limit_max_per_ip', array( $this, 'ip_threshold' ) );
		add_filter( 'woocommerce_bis_signup_rate_limit_max_per_email', array( $this, 'email_threshold' ) );
	}

	/**
	 * @after
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_bis_signup_rate_limit_max_per_ip', array( $this, 'ip_threshold' ) );
		remove_filter( 'woocommerce_bis_signup_rate_limit_max_per_email', array( $this, 'email_threshold' ) );
		$this->clear_transients_by_prefix( SignupRateLimiter::IP_PREFIX );
		$this->clear_transients_by_prefix( SignupRateLimiter::EMAIL_PREFIX );
		parent::tearDown();
	}

	/**
	 * IP threshold for tests.
	 */
	public function ip_threshold(): int {
		return 3;
	}

	/**
	 * Email threshold for tests.
	 */
	public function email_threshold(): int {
		return 2;
	}

	/**
	 * @testdox Attempts under the IP threshold are not rate-limited.
	 */
	public function test_under_ip_threshold_passes(): void {
		$ip    = '203.0.113.1';
		$email = 'a@example.com';

		$this->assertFalse( $this->sut->is_rate_limited( $ip, $email ) );
		$this->sut->record_attempt( $ip, $email );
		$this->assertFalse( $this->sut->is_rate_limited( $ip, $email ) );
	}

	/**
	 * @testdox The IP threshold trips when the per-IP counter reaches the maximum.
	 */
	public function test_ip_threshold_trips(): void {
		$ip = '203.0.113.2';

		// Use distinct emails so the per-email counter never trips first.
		for ( $i = 0; $i < 3; $i++ ) {
			$this->sut->record_attempt( $ip, "user{$i}@example.com" );
		}

		$this->assertTrue( $this->sut->is_rate_limited( $ip, 'anyone@example.com' ) );
	}

	/**
	 * @testdox The email threshold trips when the per-email counter reaches the maximum.
	 */
	public function test_email_threshold_trips(): void {
		$email = 'shared@example.com';

		// Use distinct IPs so the per-IP counter never trips first.
		$this->sut->record_attempt( '203.0.113.3', $email );
		$this->sut->record_attempt( '203.0.113.4', $email );

		$this->assertTrue( $this->sut->is_rate_limited( '203.0.113.5', $email ) );
	}

	/**
	 * @testdox Different IPs get independent counters.
	 */
	public function test_different_ips_are_independent(): void {
		$ip_a = '203.0.113.10';
		$ip_b = '203.0.113.11';

		// Fill IP A to its threshold with distinct emails.
		$this->sut->record_attempt( $ip_a, 'a1@example.com' );
		$this->sut->record_attempt( $ip_a, 'a2@example.com' );
		$this->sut->record_attempt( $ip_a, 'a3@example.com' );

		$this->assertTrue( $this->sut->is_rate_limited( $ip_a, 'a4@example.com' ) );
		$this->assertFalse( $this->sut->is_rate_limited( $ip_b, 'b1@example.com' ) );
	}

	/**
	 * @testdox Different emails get independent counters.
	 */
	public function test_different_emails_are_independent(): void {
		$email_a = 'a@example.com';
		$email_b = 'b@example.com';

		// Fill email A to its threshold from distinct IPs.
		$this->sut->record_attempt( '203.0.113.20', $email_a );
		$this->sut->record_attempt( '203.0.113.21', $email_a );

		$this->assertTrue( $this->sut->is_rate_limited( '203.0.113.22', $email_a ) );
		$this->assertFalse( $this->sut->is_rate_limited( '203.0.113.22', $email_b ) );
	}

	/**
	 * @testdox Resetting the IP counter allows further attempts.
	 */
	public function test_reset_for_ip_allows_further_attempts(): void {
		$ip = '203.0.113.30';

		for ( $i = 0; $i < 3; $i++ ) {
			$this->sut->record_attempt( $ip, "user{$i}@example.com" );
		}
		$this->assertTrue( $this->sut->is_rate_limited( $ip, 'fresh@example.com' ) );

		$this->sut->reset_for_ip( $ip );
		$this->assertFalse( $this->sut->is_rate_limited( $ip, 'fresh@example.com' ) );
	}

	/**
	 * @testdox Resetting the email counter allows further attempts.
	 */
	public function test_reset_for_email_allows_further_attempts(): void {
		$email = 'reset-me@example.com';

		$this->sut->record_attempt( '203.0.113.40', $email );
		$this->sut->record_attempt( '203.0.113.41', $email );
		$this->assertTrue( $this->sut->is_rate_limited( '203.0.113.42', $email ) );

		$this->sut->reset_for_email( $email );
		$this->assertFalse( $this->sut->is_rate_limited( '203.0.113.42', $email ) );
	}

	/**
	 * @testdox Counter TTL expiry resets the counter.
	 */
	public function test_ttl_clears_counter(): void {
		$ip = '203.0.113.50';

		$this->sut->record_attempt( $ip, 'expires@example.com' );

		// Simulate TTL expiry by deleting the transient directly, which
		// mirrors the effect of the TTL firing in the object cache.
		delete_transient( SignupRateLimiter::IP_PREFIX . md5( $ip ) );
		delete_transient( SignupRateLimiter::EMAIL_PREFIX . md5( 'expires@example.com' ) );

		$this->assertFalse( $this->sut->is_rate_limited( $ip, 'expires@example.com' ) );
	}

	/**
	 * @testdox Empty IP or email skips that scope.
	 */
	public function test_empty_inputs_skip_scope(): void {
		// With no IP and an email, only the email counter accumulates.
		for ( $i = 0; $i < 2; $i++ ) {
			$this->sut->record_attempt( '', 'only-email@example.com' );
		}
		$this->assertTrue( $this->sut->is_rate_limited( '', 'only-email@example.com' ) );
		$this->assertFalse( $this->sut->is_rate_limited( '', 'other@example.com' ) );

		// With an IP but no email, only the IP counter accumulates.
		for ( $i = 0; $i < 3; $i++ ) {
			$this->sut->record_attempt( '203.0.113.60', '' );
		}
		$this->assertTrue( $this->sut->is_rate_limited( '203.0.113.60', '' ) );
	}

	/**
	 * Delete all transients with a given prefix. MySQL-backed, bypasses object cache.
	 *
	 * @param string $prefix Transient prefix.
	 */
	private function clear_transients_by_prefix( string $prefix ): void {
		global $wpdb;
		$like = $wpdb->esc_like( '_transient_' . $prefix ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		$like = $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		wp_cache_flush();
	}
}
