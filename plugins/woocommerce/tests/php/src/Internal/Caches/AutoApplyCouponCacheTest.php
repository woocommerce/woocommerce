<?php
/**
 * AutoApplyCouponCacheTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\AutoApplyCouponCache;
use WC_Helper_Coupon;
use WC_Unit_Test_Case;

/**
 * Tests for the AutoApplyCouponCache class.
 */
class AutoApplyCouponCacheTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var AutoApplyCouponCache
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( AutoApplyCouponCache::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			delete_transient( AutoApplyCouponCache::TRANSIENT_KEY );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Prime the transient with a value that any invalidation would clear.
	 *
	 * @return void
	 */
	private function prime_cache(): void {
		set_transient( AutoApplyCouponCache::TRANSIENT_KEY, array( 'primed' ), HOUR_IN_SECONDS );
	}

	/**
	 * @testdox Writing the auto-apply meta of a coupon directly invalidates the cache.
	 */
	public function test_writing_auto_apply_meta_directly_invalidates_the_cache(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'cache-meta-write' );
		$this->prime_cache();

		// An importer, a migration or WP-CLI writes the meta without going through the CRUD.
		update_post_meta( $coupon->get_id(), 'auto_apply', 'yes' );

		$this->assertFalse( get_transient( AutoApplyCouponCache::TRANSIENT_KEY ) );

		$coupon->delete( true );
	}

	/**
	 * @testdox Unpublishing a coupon outside the CRUD invalidates the cache.
	 */
	public function test_unpublishing_a_coupon_invalidates_the_cache(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'cache-unpublish' );
		$this->prime_cache();

		// A bulk or quick edit changes the status without loading the coupon CRUD.
		wp_update_post(
			array(
				'ID'          => $coupon->get_id(),
				'post_status' => 'draft',
			)
		);

		$this->assertFalse( get_transient( AutoApplyCouponCache::TRANSIENT_KEY ) );

		$coupon->delete( true );
	}

	/**
	 * @testdox Deleting a coupon invalidates the cache.
	 */
	public function test_deleting_a_coupon_invalidates_the_cache(): void {
		$coupon    = WC_Helper_Coupon::create_coupon( 'cache-delete' );
		$coupon_id = $coupon->get_id();
		$this->prime_cache();

		wp_delete_post( $coupon_id, true );

		$this->assertFalse( get_transient( AutoApplyCouponCache::TRANSIENT_KEY ) );
	}

	/**
	 * @testdox Meta and status changes on other post types leave the cache alone.
	 */
	public function test_changes_to_other_post_types_do_not_invalidate_the_cache(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$this->prime_cache();

		update_post_meta( $product->get_id(), 'auto_apply', 'yes' );

		$this->assertSame( array( 'primed' ), get_transient( AutoApplyCouponCache::TRANSIENT_KEY ) );

		$product->delete( true );
	}

	/**
	 * @testdox Unrelated meta keys on a coupon leave the cache alone.
	 */
	public function test_unrelated_coupon_meta_does_not_invalidate_the_cache(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'cache-unrelated-meta' );
		$this->prime_cache();

		update_post_meta( $coupon->get_id(), 'some_other_key', 'value' );

		$this->assertSame( array( 'primed' ), get_transient( AutoApplyCouponCache::TRANSIENT_KEY ) );

		$coupon->delete( true );
	}
}
