<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests;

/**
 * Tests that WC_Unit_Test_Case::setUp() runs the attribute taxonomy cleanup.
 *
 * The rowless taxonomy is staged before parent::setUp(), the way an earlier test leaves it,
 * so the assertion covers the setup call itself. An attribute row cannot be staged the same
 * way, because a write before parent::setUp() lands outside the per-test transaction; the
 * kept-row case is covered in UnitTestCaseTearDownTest.
 */
class UnitTestCaseSetUpTest extends \WC_Unit_Test_Case {

	/**
	 * A pa_* taxonomy with no attribute row behind it.
	 */
	private const STALE_TAXONOMY = 'pa_stale_before_setup';

	/**
	 * Stage the leftover an earlier test would leave, then run the base setup.
	 */
	public function setUp(): void {
		global $wc_product_attributes;

		register_taxonomy( self::STALE_TAXONOMY, 'product' );
		$wc_product_attributes[ self::STALE_TAXONOMY ] = (object) array( 'attribute_name' => 'stale_before_setup' );

		parent::setUp();
	}

	/**
	 * Drop the staged leftover in case setup did not.
	 */
	public function tearDown(): void {
		global $wc_product_attributes;

		unregister_taxonomy( self::STALE_TAXONOMY );
		unset( $wc_product_attributes[ self::STALE_TAXONOMY ] );

		parent::tearDown();
	}

	/**
	 * @testdox Setup unregisters a pa_* taxonomy an earlier test left without an attribute row.
	 */
	public function test_setup_drops_a_leftover_attribute_taxonomy(): void {
		global $wc_product_attributes;

		$this->assertFalse( taxonomy_exists( self::STALE_TAXONOMY ), 'Setup should unregister a pa_* taxonomy with no attribute row.' );
		$this->assertArrayNotHasKey( self::STALE_TAXONOMY, (array) $wc_product_attributes, 'Setup should drop its $wc_product_attributes entry.' );
	}
}
