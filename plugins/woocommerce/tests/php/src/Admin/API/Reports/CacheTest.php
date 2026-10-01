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
	 * @testdox set() stores the value for one week by default, or for the given expiration.
	 *
	 * @testWith [null, 604800]
	 *           [60, 60]
	 *
	 * @param int|null $expiration Expiration passed to set(), or null to use the default.
	 * @param int      $expected   Expected lifetime in seconds.
	 */
	public function test_set_uses_expiration( ?int $expiration, int $expected ): void {
		$before = time();

		null === $expiration ? Cache::set( 'wc_cache_test', 'value' ) : Cache::set( 'wc_cache_test', 'value', $expiration );

		$timeout = (int) get_option( '_transient_timeout_wc_cache_test' );
		$this->assertGreaterThanOrEqual( $before + $expected, $timeout );
		$this->assertLessThanOrEqual( time() + $expected, $timeout );
		$this->assertSame( 'value', Cache::get( 'wc_cache_test' ) );
	}
}
