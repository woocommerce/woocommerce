<?php
/**
 * Tests for WC_Session_Handler.
 */

declare(strict_types=1);

/**
 * WC_Session_Handler tests.
 */
class WC_Session_Handler_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Direct persisted session reads replace a stale object-cache entry with the latest data.
	 */
	public function test_get_session_data_from_storage_reads_latest_persisted_data(): void {
		global $wpdb;

		$customer_id = wc_rand_hash( 't_', 30 );
		$handler     = new WC_Session_Handler();
		$this->set_test_session( $handler, $customer_id );

		$cache_key = WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP ) . $customer_id;
		$table     = $wpdb->prefix . 'woocommerce_sessions';

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$table,
				array(
					'session_key'    => $customer_id,
					'session_value'  => maybe_serialize( array( 'cart_version' => 'latest' ) ),
					'session_expiry' => time() + DAY_IN_SECONDS,
				),
				array( '%s', '%s', '%d' )
			);
			wp_cache_set( $cache_key, array( 'cart_version' => 'stale' ), WC_SESSION_CACHE_GROUP );

			$this->assertSame( array( 'cart_version' => 'stale' ), $handler->get_session_data() );
			$this->assertSame( array( 'cart_version' => 'latest' ), $handler->get_session_data_from_storage() );
			$this->assertSame( array( 'cart_version' => 'latest' ), $handler->get_session_data() );
		} finally {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table, array( 'session_key' => $customer_id ), array( '%s' ) );
			wp_cache_delete( $cache_key, WC_SESSION_CACHE_GROUP );
		}
	}

	/**
	 * @testdox A stale cache can reject one checkout but is refreshed for the next request.
	 */
	public function test_stale_cache_is_refreshed_after_concurrency_check(): void {
		global $wpdb;

		$customer_id = wc_rand_hash( 't_', 30 );
		$stale_data  = array( 'cart_version' => 'stale' );
		$latest_data = array( 'cart_version' => 'latest' );
		$handler     = new WC_Session_Handler();
		$table       = $wpdb->prefix . 'woocommerce_sessions';
		$cache_key   = WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP ) . $customer_id;
		$this->set_test_session( $handler, $customer_id );

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$table,
				array(
					'session_key'    => $customer_id,
					'session_value'  => maybe_serialize( $latest_data ),
					'session_expiry' => time() + DAY_IN_SECONDS,
				),
				array( '%s', '%s', '%d' )
			);
			wp_cache_set( $cache_key, $stale_data, WC_SESSION_CACHE_GROUP );

			$cached_data = $handler->get_session_data();
			\Automattic\WooCommerce\StoreApi\Utilities\SessionConcurrencyGuard::capture( $handler, $cached_data );
			$this->assertTrue( \Automattic\WooCommerce\StoreApi\Utilities\SessionConcurrencyGuard::has_changed( $handler ) );

			$next_handler = new WC_Session_Handler();
			$this->set_test_session( $next_handler, $customer_id );
			$next_request_data = $next_handler->get_session_data();
			\Automattic\WooCommerce\StoreApi\Utilities\SessionConcurrencyGuard::capture( $next_handler, $next_request_data );

			$this->assertSame( $latest_data, $next_request_data );
			$this->assertFalse( \Automattic\WooCommerce\StoreApi\Utilities\SessionConcurrencyGuard::has_changed( $next_handler ) );
		} finally {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table, array( 'session_key' => $customer_id ), array( '%s' ) );
			wp_cache_delete( $cache_key, WC_SESSION_CACHE_GROUP );
		}
	}

	/**
	 * @testdox A subclass inheriting core storage still reads fresh persisted data.
	 */
	public function test_get_session_data_from_storage_reads_fresh_data_for_noop_subclass(): void {
		global $wpdb;

		$customer_id = wc_rand_hash( 't_', 30 );
		$handler     = new class() extends WC_Session_Handler {};
		$this->set_test_session( $handler, $customer_id );

		$cache_key = WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP ) . $customer_id;
		$table     = $wpdb->prefix . 'woocommerce_sessions';

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$table,
				array(
					'session_key'    => $customer_id,
					'session_value'  => maybe_serialize( array( 'cart_version' => 'latest' ) ),
					'session_expiry' => time() + DAY_IN_SECONDS,
				),
				array( '%s', '%s', '%d' )
			);
			wp_cache_set( $cache_key, array( 'cart_version' => 'stale' ), WC_SESSION_CACHE_GROUP );

			$this->assertSame( array( 'cart_version' => 'stale' ), $handler->get_session_data() );
			$this->assertSame( array( 'cart_version' => 'latest' ), $handler->get_session_data_from_storage() );
			$this->assertSame( array( 'cart_version' => 'latest' ), $handler->get_session_data() );
		} finally {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table, array( 'session_key' => $customer_id ), array( '%s' ) );
			wp_cache_delete( $cache_key, WC_SESSION_CACHE_GROUP );
		}
	}

	/**
	 * @testdox Direct persisted reads respect storage overridden by a custom session handler.
	 */
	public function test_get_session_data_from_storage_respects_custom_session_handlers(): void {
		$customer_id = wc_rand_hash( 't_', 30 );
		$handler     = new class() extends WC_Session_Handler {
			/**
			 * Custom backend data.
			 *
			 * @var array
			 */
			public $custom_data = array( 'cart_version' => 'custom-backend' );

			/**
			 * Set the active session for the test.
			 *
			 * @param string $customer_id Customer ID.
			 */
			public function set_test_session( string $customer_id ): void {
				$this->_customer_id        = $customer_id;
				$this->_has_cookie         = true;
				$this->_session_expiration = time() + DAY_IN_SECONDS;
			}

			/**
			 * Read from the simulated custom backend.
			 *
			 * @param string $customer_id Customer ID.
			 * @param mixed  $default_value Default value.
			 * @return mixed
			 */
			public function get_session( $customer_id, $default_value = false ) {
				return $this->custom_data;
			}
		};
		$handler->set_test_session( $customer_id );

		$this->assertSame( array( 'cart_version' => 'custom-backend' ), $handler->get_session_data_from_storage() );
	}

	/**
	 * @testdox Persisted session changes exclude unsaved changes made by the current request.
	 */
	public function test_detects_only_external_persisted_session_changes(): void {
		global $wpdb;

		$customer_id   = wc_rand_hash( 't_', 30 );
		$initial_data  = array( 'cart_version' => 'initial' );
		$external_data = array( 'cart_version' => 'external' );
		$handler       = new WC_Session_Handler();
		$table         = $wpdb->prefix . 'woocommerce_sessions';
		$cache_key     = WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP ) . $customer_id;

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$table,
				array(
					'session_key'    => $customer_id,
					'session_value'  => maybe_serialize( $initial_data ),
					'session_expiry' => time() + DAY_IN_SECONDS,
				),
				array( '%s', '%s', '%d' )
			);
			$this->set_test_session( $handler, $customer_id, $initial_data );

			$handler->set( 'current_request', 'changed' );
			$this->assertFalse( \Automattic\WooCommerce\StoreApi\Utilities\SessionConcurrencyGuard::has_changed( $handler ), 'Unsaved changes from this request should not look external.' );

			$handler->save_data();
			$this->assertFalse( \Automattic\WooCommerce\StoreApi\Utilities\SessionConcurrencyGuard::has_changed( $handler ), 'Saving this request should update its persisted baseline.' );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array( 'session_value' => maybe_serialize( $external_data ) ),
				array( 'session_key' => $customer_id ),
				array( '%s' ),
				array( '%s' )
			);
			$this->assertTrue( \Automattic\WooCommerce\StoreApi\Utilities\SessionConcurrencyGuard::has_changed( $handler ), 'A later persisted change should be detected.' );
		} finally {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table, array( 'session_key' => $customer_id ), array( '%s' ) );
			wp_cache_delete( $cache_key, WC_SESSION_CACHE_GROUP );
		}
	}

	/**
	 * Set active state on an exact core session handler.
	 *
	 * @param WC_Session_Handler $handler Session handler.
	 * @param string             $customer_id Customer ID.
	 * @param array|null         $session_data Optional in-memory and persisted baseline data.
	 */
	private function set_test_session( WC_Session_Handler $handler, string $customer_id, ?array $session_data = null ): void {
		$properties = array(
			'_customer_id'        => $customer_id,
			'_has_cookie'         => true,
			'_session_expiration' => time() + DAY_IN_SECONDS,
		);

		if ( null !== $session_data ) {
			$properties['_data'] = $session_data;
		}

		foreach ( $properties as $name => $value ) {
			$property = new ReflectionProperty( $handler, $name );
			$property->setAccessible( true );
			$property->setValue( $handler, $value );
		}

		if ( null !== $session_data ) {
			\Automattic\WooCommerce\StoreApi\Utilities\SessionConcurrencyGuard::capture( $handler, $session_data );
		}
	}
}
