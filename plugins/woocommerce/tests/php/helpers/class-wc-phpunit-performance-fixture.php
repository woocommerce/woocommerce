<?php
/**
 * Real lifecycle exercised by the performance recorder's own tests.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/** Exercises hooks, successful cases, skips and failures in an isolated result. */
class WC_PHPUnit_Performance_Fixture extends PHPUnit\Framework\TestCase {

	/** Issue class-level fixture work. */
	public static function setUpBeforeClass(): void {
		$GLOBALS['wpdb']->get_var( 'SELECT 1' );
	}

	/** Issue class-level cleanup work. */
	public static function tearDownAfterClass(): void {
		$GLOBALS['wpdb']->get_var( 'SELECT 1' );
	}

	/** Issue per-case fixture work. */
	protected function setUp(): void {
		$GLOBALS['wpdb']->get_var( 'SELECT 1' );
	}

	/** Issue per-case cleanup work. */
	protected function tearDown(): void {
		$GLOBALS['wpdb']->get_var( 'SELECT 1' );
	}

	/** A passing case. */
	public function test_pass(): void {
		$this->assertSame( '1', $GLOBALS['wpdb']->get_var( 'SELECT 1' ) );
	}

	/** A skip is captured in the inner result, never the outer suite. */
	public function test_skip(): void {
		$this->markTestSkipped( 'Recorder fixture skip.' );
	}

	/** A failure is captured in the inner result, never the outer suite. */
	public function test_failure(): void {
		$this->fail( 'Recorder fixture failure.' );
	}
}
