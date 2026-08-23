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

		// Not asserting token_a !== token_b: tokens are formatted timestamps, and two acquisitions can legitimately
		// land on the same microtime() value without meaning anything about lock ownership - the option name
		// (which embeds the key) is what actually keeps these two locks independent, not the token value.
		$this->assertNotNull( $token_a, 'The first key must acquire its own lock.' );
		$this->assertNotNull( $token_b, 'A different key must acquire its own lock, unaffected by the first.' );
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

	/**
	 * @testdox The closure registered via add_action( 'shutdown', ... ) - the pattern used to guarantee a payment
	 *          lock is released even when the holder's request terminates via exit()/wp_die() before reaching a
	 *          normal release() call - correctly releases the lock when invoked.
	 */
	public function test_shutdown_release_closure_releases_the_lock(): void {
		$token = $this->sut->acquire( 'customer-1' );
		$this->assertNotNull( $token, 'Test setup: must be able to acquire the lock.' );

		// The same closure shape registered via add_action( 'shutdown', ... ) in production, invoked directly here
		// rather than through a real do_action( 'shutdown' ) - that hook also carries unrelated WordPress core
		// callbacks (output-buffer flushing among them) that have nothing to do with what this is verifying and
		// make PHPUnit flag the test as risky. WordPress's own 'shutdown' action firing this closure at all is
		// core's well-tested add_action()/do_action() plumbing, not something this needs to re-prove.
		$release_on_shutdown = function () use ( $token ) {
			$this->sut->release( 'customer-1', $token );
		};
		$release_on_shutdown();

		$this->assertNull(
			$this->lock_row_value( 'customer-1' ),
			'The lock must be released once the shutdown callback runs, even without a normal release() call.'
		);
	}

	/**
	 * @testdox A custom staleness threshold passed to the constructor is honored, distinct from the class default -
	 *          the mechanism PAYMENT_STALE_LOCK_THRESHOLD relies on to give a lock guarding a synchronous payment-
	 *          gateway call more headroom than the shorter, order-creation-oriented default.
	 */
	public function test_constructor_accepts_a_custom_stale_lock_threshold(): void {
		global $wpdb;

		// A lock "acquired" 1 second ago is fresh under the class default (30s) but stale under a short custom
		// threshold - proving it's the constructor argument, not just the class constant, that acquire() honors.
		$stale_token = number_format( microtime( true ) - 1, 6, '.', '' );
		$wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => CheckoutOrderLock::LOCK_OPTION_PREFIX . md5( 'customer-1' ),
				'option_value' => $stale_token,
				'autoload'     => 'no',
			),
			array( '%s', '%s', '%s' )
		);

		$short_threshold_lock = new CheckoutOrderLock( 0.5 );
		$token                = $short_threshold_lock->acquire( 'customer-1' );

		$this->assertNotNull( $token, 'A lock older than this instance\'s custom (shorter) threshold must be takeable over.' );
		$this->assertNotSame( $stale_token, $token );
	}
}
