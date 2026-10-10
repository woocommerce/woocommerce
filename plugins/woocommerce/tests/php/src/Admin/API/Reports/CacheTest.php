<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports;

use Automattic\WooCommerce\Admin\API\Reports\Cache;
use WC_Unit_Test_Case;

/**
 * Tests for the reports Cache class.
 */
class CacheTest extends WC_Unit_Test_Case {

	/**
	 * @testdox set() stores the value for one week by default, or for the filtered expiration when it is valid.
	 *
	 * @testWith [null, 604800]
	 *           [60, 60]
	 *           [0.5, 1]
	 *           [-60, 604800]
	 *           ["soon", 604800]
	 *
	 * @param mixed $expiration Filtered expiration, or null to leave the default.
	 * @param int   $expected   Expected lifetime in seconds.
	 */
	public function test_set_uses_expiration( $expiration, int $expected ): void {
		$filter = fn() => $expiration;
		if ( null !== $expiration ) {
			add_filter( 'woocommerce_reports_cache_expiration', $filter );
		}
		$before = time();

		Cache::set( 'wc_cache_test', 'value' );

		remove_filter( 'woocommerce_reports_cache_expiration', $filter );
		$timeout = (int) get_option( '_transient_timeout_wc_cache_test' );
		$this->assertGreaterThanOrEqual( $before + $expected, $timeout );
		$this->assertLessThanOrEqual( time() + $expected, $timeout );
		$this->assertSame( 'value', Cache::get( 'wc_cache_test' ) );
	}
}
