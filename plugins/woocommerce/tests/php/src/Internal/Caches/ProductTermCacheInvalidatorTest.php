<?php
/**
 * ProductTermCacheInvalidator tests.
 *
 * @package WooCommerce\Tests\Internal\Caches
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\ProductTermCacheInvalidator;
use WC_Cache_Helper;
use WC_Unit_Test_Case;

/**
 * Tests for ProductTermCacheInvalidator.
 */
final class ProductTermCacheInvalidatorTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var ProductTermCacheInvalidator
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( ProductTermCacheInvalidator::class );
	}

	/**
	 * @testdox Registers taxonomy cache invalidation.
	 */
	public function test_registers_taxonomy_cache_invalidation(): void {
		$this->assertSame(
			10,
			has_action( 'clean_taxonomy_cache', array( $this->sut, 'handle_clean_taxonomy_cache' ) ),
			'The product term cache invalidator should register its taxonomy cache callback.'
		);
	}

	/**
	 * @testdox Invalidates cached product terms for product taxonomies.
	 */
	public function test_invalidates_product_taxonomy_cache_group(): void {
		$cache_group   = 'product_terms_product_cat';
		$prefix_before = WC_Cache_Helper::get_cache_prefix( $cache_group );

		$this->sut->handle_clean_taxonomy_cache( 'product_cat' );

		$prefix_after = WC_Cache_Helper::get_cache_prefix( $cache_group );
		$this->assertNotSame( $prefix_before, $prefix_after, 'Product taxonomy cache prefixes should be invalidated.' );
	}

	/**
	 * @testdox Does not invalidate cached product terms for non-product taxonomies.
	 */
	public function test_does_not_invalidate_non_product_taxonomy_cache_group(): void {
		$cache_group   = 'product_terms_category';
		$prefix_before = WC_Cache_Helper::get_cache_prefix( $cache_group );

		$this->sut->handle_clean_taxonomy_cache( 'category' );

		$prefix_after = WC_Cache_Helper::get_cache_prefix( $cache_group );
		$this->assertSame( $prefix_before, $prefix_after, 'Non-product taxonomy cache prefixes should remain unchanged.' );
	}
}
