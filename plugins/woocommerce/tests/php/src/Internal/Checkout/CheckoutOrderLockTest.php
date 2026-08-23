<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Checkout;

use Automattic\WooCommerce\Internal\Checkout\CheckoutOrderLock;
use WC_Unit_Test_Case;

/**
 * Tests for the CheckoutOrderLock class.
 */
class CheckoutOrderLockTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CheckoutOrderLock
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CheckoutOrderLock();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wc\\_checkout\\_order\\_lock\\_%'" );
		parent::tearDown();
	}

	/**
	 * Read the raw stored value of a lock's options-table row, or null when no lock is held.
	 *
	 * @param string $key Lock key.
	 * @return string|null
	 */
	private function lock_row_value( string $key ): ?string {
		global $wpdb;

		$option_name = CheckoutOrderLock::LOCK_OPTION_PREFIX . md5( $key );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$value = $wpdb->get_var(
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option_name ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return null === $value ? null : (string) $value;
	}

	/**
	 * @testdox Should acquire a free lock and return a non-null token.
	 */
	public function test_acquire_returns_token_when_lock_is_free(): void {
		$token = $this->sut->acquire( 'customer-1' );

		$this->assertNotNull( $token, 'A free lock must be acquirable.' );
	}

	/**
	 * @testdox Should persist an options-table row matching the returned token while the lock is held.
	 */
	public function test_acquire_persists_matching_lock_row(): void {
		$token = $this->sut->acquire( 'customer-1' );

		$this->assertSame( $token, $this->lock_row_value( 'customer-1' ), 'The stored row value must match the returned token.' );
	}

	/**
	 * @testdox Should delete the lock row on release.
	 */
	public function test_release_deletes_the_lock_row(): void {
		$token = $this->sut->acquire( 'customer-1' );
		$this->sut->release( 'customer-1', $token );

		$this->assertNull( $this->lock_row_value( 'customer-1' ), 'The lock row must be gone after release.' );
	}

	/**
	 * @testdox Should scope locks independently per key, so one key's lock never blocks another key.
	 */
	public function test_locks_are_scoped_independently_per_key(): void {
		$token_a = $this->sut->acquire( 'customer-a' );
		$token_b = $this->sut->acquire( 'customer-b' );

		$this->assertNotNull( $token_a, 'The first key must acquire its own lock.' );
		$this->assertNotNull( $token_b, 'A different key must acquire its own lock, unaffected by the first.' );
		$this->assertNotSame( $token_a, $token_b );
	}

	/**
	 * @testdox Should fail to acquire, and leave the row untouched, when another request holds a recently-acquired (not yet stale) lock.
	 */
	public function test_acquire_fails_when_another_request_holds_a_fresh_lock(): void {
		global $wpdb;

		// Simulate another request holding the lock: insert the row directly with an "acquired at" time of now.
		$foreign_token = number_format( microtime( true ), 6, '.', '' );
		$wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => CheckoutOrderLock::LOCK_OPTION_PREFIX . md5( 'customer-1' ),
				'option_value' => $foreign_token,
				'autoload'     => 'no',
			),
			array( '%s', '%s', '%s' )
		);

		$token = $this->sut->acquire( 'customer-1' );

		$this->assertNull( $token, 'Acquisition must fail while another request holds a lock that is not yet stale.' );
		$this->assertSame(
			$foreign_token,
			$this->lock_row_value( 'customer-1' ),
			'A holder that is not yet stale must be left in place, not stolen or released, even once this caller gives up waiting.'
		);
	}

	/**
	 * @testdox Should take over a stale lock left by a crashed request, even though it is older than the (much shorter) wait budget.
	 */
	public function test_acquire_takes_over_a_stale_lock(): void {
		global $wpdb;

		// A lock row acquired long enough ago to exceed STALE_LOCK_THRESHOLD represents a crashed holder.
		$stale_token = number_format( microtime( true ) - CheckoutOrderLock::STALE_LOCK_THRESHOLD - 1, 6, '.', '' );
		$wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => CheckoutOrderLock::LOCK_OPTION_PREFIX . md5( 'customer-1' ),
				'option_value' => $stale_token,
				'autoload'     => 'no',
			),
			array( '%s', '%s', '%s' )
		);

		$token = $this->sut->acquire( 'customer-1' );

		$this->assertNotNull( $token, 'A stale lock must be takeable over.' );
		$this->assertNotSame( $stale_token, $token );
		$this->assertSame( $token, $this->lock_row_value( 'customer-1' ) );
	}

	/**
	 * @testdox Should not delete a lock row whose value no longer matches the token being released (taken over by another request).
	 */
	public function test_release_leaves_a_lock_taken_over_by_another_request(): void {
		global $wpdb;

		$token = $this->sut->acquire( 'customer-1' );

		// Simulate another request taking over the lock by overwriting the row's value directly.
		$other_token = number_format( microtime( true ) + 1, 6, '.', '' );
		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => $other_token ),
			array( 'option_name' => CheckoutOrderLock::LOCK_OPTION_PREFIX . md5( 'customer-1' ) )
		);

		$this->sut->release( 'customer-1', $token );

		$this->assertSame(
			$other_token,
			$this->lock_row_value( 'customer-1' ),
			'Release must be scoped to the caller\'s own token, so a lock another request now owns is not deleted.'
		);
	}
}
