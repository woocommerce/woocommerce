<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi;

use Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler;
use WC_Unit_Test_Case;

/**
 * Tests for POSSessionHandler.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler
 */
class POSSessionHandlerTest extends WC_Unit_Test_Case {

	/**
	 * @testdox generate_customer_id ignores authenticated user and returns a guest-style id.
	 */
	public function test_generate_customer_id_is_guest_even_when_user_logged_in(): void {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$handler = new POSSessionHandler();
		$id      = $handler->generate_customer_id();

		$this->assertIsString( $id );
		$this->assertSame( 't_', substr( $id, 0, 2 ) );
		$this->assertNotSame( (string) $admin_id, $id );

		// Calling again must yield a fresh id — every transaction gets its own.
		$this->assertNotSame( $id, $handler->generate_customer_id() );

		wp_delete_user( $admin_id );
	}

	/**
	 * Regression for "items linger across transactions even after killing the
	 * app" — mobile HTTP stacks persist the WC session cookie, which the
	 * parent's `init_session_cookie` would re-resolve to the prior cart row
	 * and then (because the cashier is logged in) migrate it onto the
	 * cashier's WP user_id. POSSessionHandler must ignore the cookie entirely.
	 *
	 * @testdox init_session_cookie ignores a present WC session cookie and starts fresh.
	 */
	public function test_init_session_cookie_ignores_existing_cookie_and_starts_fresh(): void {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Simulate a previously-persisted cookie pointing at an earlier
		// transaction's customer_id.
		$previous_customer_id = 't_previousxxxxxxxxxxxxxxxxxxxx';
		$expiration           = time() + DAY_IN_SECONDS;
		$expiring             = time() + ( DAY_IN_SECONDS - HOUR_IN_SECONDS );
		$cookie_hash          = hash_hmac( 'md5', $previous_customer_id . '|' . $expiration, wp_hash( $previous_customer_id . '|' . $expiration ) );
		$cookie_value         = $previous_customer_id . '|' . $expiration . '|' . $expiring . '|' . $cookie_hash;
		$cookie_name          = (string) apply_filters( 'woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH );

		$_COOKIE[ $cookie_name ] = $cookie_value;

		try {
			$handler = new POSSessionHandler();
			$handler->init_session_cookie();

			$customer_id = $handler->get_customer_id();
			$this->assertNotSame(
				$previous_customer_id,
				$customer_id,
				'A new POS request must not adopt a previously-persisted cookie customer_id.'
			);
			$this->assertNotSame(
				(string) $admin_id,
				$customer_id,
				'A new POS request must not adopt the authenticated user_id either (no migration).'
			);
			$this->assertSame( 't_', substr( $customer_id, 0, 2 ), 'Customer ID must be a fresh guest-style hash.' );
		} finally {
			unset( $_COOKIE[ $cookie_name ] );
			wp_delete_user( $admin_id );
		}
	}
}
