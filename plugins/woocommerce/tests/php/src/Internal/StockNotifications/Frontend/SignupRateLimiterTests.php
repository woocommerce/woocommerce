<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupRateLimiter;
use WC_Unit_Test_Case;

/**
 * Tests for the SignupRateLimiter class.
 */
class SignupRateLimiterTests extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var SignupRateLimiter
	 */
	private $sut;

	/**
	 * The server values seen before the test replaced them.
	 *
	 * @var array<string, string|null>
	 */
	private $original_server = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		foreach ( array( 'REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR' ) as $key ) {
			$this->original_server[ $key ] = isset( $_SERVER[ $key ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) : null;
		}

		$_SERVER['REMOTE_ADDR'] = '192.0.2.10';
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );

		$this->sut = new SignupRateLimiter();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			foreach ( $this->original_server as $key => $value ) {
				if ( null === $value ) {
					unset( $_SERVER[ $key ] );
				} else {
					$_SERVER[ $key ] = $value;
				}
			}

			wp_set_current_user( 0 );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should not rate limit the first attempt.
	 */
	public function test_first_attempt_is_not_rate_limited(): void {
		$this->assertFalse( $this->sut->is_rate_limited( 'shopper@example.com' ), 'A first sign-up attempt should go through' );
	}

	/**
	 * @testdox Should rate limit a repeated attempt from the same e-mail address.
	 */
	public function test_repeated_attempt_is_rate_limited(): void {
		$this->assertTrue( $this->sut->apply( 'shopper@example.com' ), 'The rate limit should be applied' );

		$this->assertTrue( $this->sut->is_rate_limited( 'shopper@example.com' ), 'A repeated sign-up attempt should be rate limited' );
	}

	/**
	 * @testdox Should treat e-mail addresses that differ only in case and whitespace as the same.
	 */
	public function test_email_is_normalized(): void {
		$this->sut->apply( 'shopper@example.com' );

		$this->assertTrue( $this->sut->is_rate_limited( '  SHOPPER@Example.com ' ), 'The e-mail address should be normalized before hashing' );
	}

	/**
	 * @testdox Should rate limit another e-mail address coming from the same IP address.
	 */
	public function test_other_email_from_same_ip_is_rate_limited(): void {
		$this->sut->apply( 'shopper@example.com' );

		$this->assertTrue( $this->sut->is_rate_limited( 'other@example.com' ), 'Sign-ups should also be rate limited per IP address' );
	}

	/**
	 * @testdox Should not rate limit another e-mail address coming from another IP address.
	 */
	public function test_other_email_from_other_ip_is_not_rate_limited(): void {
		$this->sut->apply( 'shopper@example.com' );

		$_SERVER['REMOTE_ADDR'] = '192.0.2.20';

		$this->assertFalse( $this->sut->is_rate_limited( 'other@example.com' ), 'A different client signing up with a different e-mail address should not be held back' );
	}

	/**
	 * @testdox Should keep rate limiting the e-mail address after the IP limit is switched off.
	 */
	public function test_email_limit_is_independent_of_the_client_limit(): void {
		$this->set_delays( 0, 600 );

		$this->sut->apply( 'shopper@example.com' );

		$_SERVER['REMOTE_ADDR'] = '192.0.2.20';

		$this->assertTrue( $this->sut->is_rate_limited( 'shopper@example.com' ), 'The same e-mail address should be rate limited from any IP address' );
		$this->assertFalse( $this->sut->is_rate_limited( 'other@example.com' ), 'With the per-IP limit off, another e-mail address should go through' );
	}

	/**
	 * @testdox Should keep rate limiting the IP address after the e-mail limit is switched off.
	 */
	public function test_client_limit_is_independent_of_the_email_limit(): void {
		$this->set_delays( 30, 0 );

		$this->sut->apply( 'shopper@example.com' );

		$this->assertTrue( $this->sut->is_rate_limited( 'other@example.com' ), 'The same client should be rate limited whichever e-mail address it uses' );

		$_SERVER['REMOTE_ADDR'] = '192.0.2.20';

		$this->assertFalse( $this->sut->is_rate_limited( 'shopper@example.com' ), 'With the per-e-mail limit off, another client should go through' );
	}

	/**
	 * @testdox Should switch rate limiting off when both delays are zero, without writing anything.
	 */
	public function test_zero_delays_disable_rate_limiting_and_write_nothing(): void {
		$this->set_delays( 0, 0 );

		$queries = $this->record_queries(
			function () {
				$this->assertTrue( $this->sut->apply( 'shopper@example.com' ), 'A disabled limiter should report success' );
				$this->assertFalse( $this->sut->is_rate_limited( 'shopper@example.com' ), 'A zero delay should let every attempt through' );
			}
		);

		foreach ( $queries as $query ) {
			$this->assertStringNotContainsString( 'wc_rate_limits', $query, 'A disabled limiter should not touch the rate limits table' );
		}
	}

	/**
	 * @testdox Should apply delays that the filter returns as numeric strings.
	 */
	public function test_numeric_string_delays_are_applied(): void {
		$this->set_delays( '90', '90' );

		$this->sut->apply( 'shopper@example.com' );

		$this->assertTrue( $this->sut->is_rate_limited( 'shopper@example.com' ), 'A numeric string delay should be coerced to an integer and applied' );
	}

	/**
	 * @testdox Should ignore forwarded headers by default.
	 */
	public function test_forwarded_header_is_ignored_by_default(): void {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.5';

		$this->sut->apply( 'shopper@example.com' );

		$_SERVER['REMOTE_ADDR'] = '192.0.2.20';

		$this->assertFalse( $this->sut->is_rate_limited( 'other@example.com' ), 'A spoofable header should not be used to key the rate limit' );
	}

	/**
	 * @testdox Should use the forwarded header when the store opts in through the filter.
	 */
	public function test_forwarded_header_is_used_when_opted_in(): void {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.5';

		add_filter(
			'woocommerce_customer_stock_notifications_signup_rate_limit_ip',
			static function () {
				return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '' ) );
			}
		);

		$this->sut->apply( 'shopper@example.com' );

		$_SERVER['REMOTE_ADDR'] = '192.0.2.20';

		$this->assertTrue( $this->sut->is_rate_limited( 'other@example.com' ), 'A store behind a proxy should be able to key the rate limit on the forwarded address' );
	}

	/**
	 * @testdox Should switch the per-IP limit off when the filtered address is not an IP address.
	 */
	public function test_invalid_ip_disables_the_ip_limit(): void {
		add_filter( 'woocommerce_customer_stock_notifications_signup_rate_limit_ip', '__return_empty_string' );

		$this->sut->apply( 'shopper@example.com' );

		$this->assertFalse( $this->sut->is_rate_limited( 'other@example.com' ), 'An unresolved IP address should not be rate limited' );
		$this->assertTrue( $this->sut->is_rate_limited( 'shopper@example.com' ), 'The per-e-mail limit should still apply' );
	}

	/**
	 * @testdox Should key a logged-in shopper on their user ID rather than their IP address.
	 */
	public function test_logged_in_user_is_keyed_on_user_id_not_ip(): void {
		wp_set_current_user( $this->factory->user->create() );

		$this->sut->apply( 'shopper@example.com' );

		$_SERVER['REMOTE_ADDR'] = '192.0.2.20';

		$this->assertTrue( $this->sut->is_rate_limited( 'other@example.com' ), 'The same logged-in user should be rate limited even from another IP address' );

		wp_set_current_user( 0 );

		$this->assertFalse( $this->sut->is_rate_limited( 'guest@example.com' ), 'A guest on that IP address should not be held back by the logged-in user limit' );
	}

	/**
	 * @testdox Should not rate limit another logged-in user coming from the same IP address.
	 */
	public function test_other_logged_in_user_from_same_ip_is_not_rate_limited(): void {
		wp_set_current_user( $this->factory->user->create() );

		$this->sut->apply( 'shopper@example.com' );

		wp_set_current_user( $this->factory->user->create() );

		$this->assertFalse( $this->sut->is_rate_limited( 'other@example.com' ), 'A different logged-in user on the same IP address should not be held back' );
	}

	/**
	 * @testdox Should still rate limit a logged-in user when the filtered IP address is invalid.
	 */
	public function test_invalid_ip_does_not_disable_the_limit_for_logged_in_user(): void {
		add_filter(
			'woocommerce_customer_stock_notifications_signup_rate_limit_ip',
			static function () {
				return 'not-an-ip';
			}
		);

		$this->set_delays( 30, 0 );

		wp_set_current_user( $this->factory->user->create() );

		$this->sut->apply( 'shopper@example.com' );

		$this->assertTrue( $this->sut->is_rate_limited( 'other@example.com' ), 'A logged-in user should be rate limited regardless of whether the IP address resolves' );
	}

	/**
	 * @testdox Should roll back the limits it already stored when one of them cannot be stored.
	 */
	public function test_apply_rolls_back_when_a_limit_cannot_be_stored(): void {
		global $wpdb;

		$suppress = $wpdb->suppress_errors( true );
		$filter   = static function ( $query ) {
			if ( false !== strpos( $query, 'stock_notifications_signup_email_' ) ) {
				return 'SELECT 1 FROM a_table_that_does_not_exist';
			}

			return $query;
		};

		add_filter( 'query', $filter );

		try {
			$applied = $this->sut->apply( 'shopper@example.com' );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}

		remove_filter( 'query', $filter );

		$this->assertFalse( $applied, 'A sign-up attempt whose limits cannot be stored should not be let through' );
		$this->assertFalse( $this->sut->is_rate_limited( 'other@example.com' ), 'The per-IP limit should have been rolled back' );
		$this->assertFalse( $this->sut->is_rate_limited( 'shopper@example.com' ), 'The limit that could not be stored should have been cleared' );
	}

	/**
	 * @testdox Should fall back to the default delays when the filter does not return an array.
	 */
	public function test_non_array_delays_fall_back_to_defaults(): void {
		add_filter(
			'woocommerce_customer_stock_notifications_signup_rate_limit_delays',
			static function () {
				return 'nope';
			}
		);

		$this->sut->apply( 'shopper@example.com' );

		$this->assertTrue( $this->sut->is_rate_limited( 'shopper@example.com' ), 'Non-array delays should fall back to the defaults, which still rate limit' );
	}

	/**
	 * @testdox Should switch rate limiting off when both delays are negative, without writing anything.
	 */
	public function test_negative_delays_disable_rate_limiting(): void {
		$this->set_delays( -30, -30 );

		$queries = $this->record_queries(
			function () {
				$this->assertTrue( $this->sut->apply( 'shopper@example.com' ), 'A disabled limiter should report success' );
				$this->assertFalse( $this->sut->is_rate_limited( 'shopper@example.com' ), 'A negative delay should let every attempt through' );
			}
		);

		foreach ( $queries as $query ) {
			$this->assertStringNotContainsString( 'wc_rate_limits', $query, 'A disabled limiter should not touch the rate limits table' );
		}
	}

	/**
	 * @testdox Should fall back to the default delays when the filter returns non-numeric values.
	 */
	public function test_non_numeric_delays_fall_back_to_defaults(): void {
		$this->set_delays( false, array( 30 ) );

		$this->sut->apply( 'shopper@example.com' );

		$this->assertTrue( $this->sut->is_rate_limited( 'shopper@example.com' ), 'Non-numeric delays should fall back to the defaults, which still rate limit' );
	}

	/**
	 * @testdox Should not fatal when the filter returns an object as a delay.
	 */
	public function test_object_delay_does_not_fatal(): void {
		$this->set_delays( new \stdClass(), new \stdClass() );

		$this->assertTrue( $this->sut->apply( 'shopper@example.com' ), 'An object delay should not cause a fatal error' );
	}

	/**
	 * @testdox Should disable the per-client limit for guests when the client address cannot be resolved.
	 */
	public function test_missing_remote_addr_disables_the_client_limit_for_guests(): void {
		unset( $_SERVER['REMOTE_ADDR'] );
		wp_set_current_user( 0 );

		$this->set_delays( 30, 0 );

		$this->sut->apply( 'shopper@example.com' );

		$this->assertFalse( $this->sut->is_rate_limited( 'other@example.com' ), 'With no resolvable client address, the per-client limit should not apply' );
	}

	/**
	 * @testdox Should keep rate limiting the e-mail address when the client address cannot be resolved.
	 */
	public function test_missing_remote_addr_still_rate_limits_the_email(): void {
		unset( $_SERVER['REMOTE_ADDR'] );
		wp_set_current_user( 0 );

		$this->set_delays( 30, 180 );

		$this->sut->apply( 'shopper@example.com' );

		$this->assertTrue( $this->sut->is_rate_limited( 'shopper@example.com' ), 'The per-e-mail limit should still apply when the client address cannot be resolved' );
	}

	/**
	 * Filter the delays used by the limiter.
	 *
	 * @param mixed $client Delay for the per-client limit.
	 * @param mixed $email  Delay for the per-e-mail limit.
	 */
	private function set_delays( $client, $email ): void {
		add_filter(
			'woocommerce_customer_stock_notifications_signup_rate_limit_delays',
			static function () use ( $client, $email ) {
				return array(
					'client' => $client,
					'email'  => $email,
				);
			}
		);
	}

	/**
	 * Run a callback and return every database query it made.
	 *
	 * @param callable $callback The callback to run.
	 * @return string[]
	 */
	private function record_queries( callable $callback ): array {
		$queries = array();
		$filter  = static function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return $query;
		};

		add_filter( 'query', $filter );

		try {
			$callback();
		} finally {
			remove_filter( 'query', $filter );
		}

		return $queries;
	}
}
