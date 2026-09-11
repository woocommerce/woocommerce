<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;
use Automattic\WooCommerce\Internal\ProductFilters\CacheController;
use WC_Cache_Helper;
use WC_Unit_Test_Case;

/**
 * Tests for the CacheController class.
 */
class CacheControllerTest extends WC_Unit_Test_Case {
	/**
	 * @testdox A product attributes lookup table update invalidates the filter data cache.
	 */
	public function test_lookup_table_update_invalidates_filter_data_cache(): void {
		// Populating the transient version is what makes need_cleanup() true, i.e. "there is a cache to clean".
		$version_before = WC_Cache_Helper::get_transient_version( CacheController::CACHE_GROUP );
		set_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT, 5 );

		wc_get_container()->get( CacheController::class )->register();

		do_action( 'woocommerce_product_attributes_lookup_updated', 123, LookupDataStore::ACTION_INSERT );

		$this->assertNotSame( $version_before, WC_Cache_Helper::get_transient_version( CacheController::CACHE_GROUP ) );
		$this->assertFalse( get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT ) );
	}
}
