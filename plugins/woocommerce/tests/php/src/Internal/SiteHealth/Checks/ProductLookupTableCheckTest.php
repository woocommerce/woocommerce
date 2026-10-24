<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\ProductLookupTableCheck;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductLookupTableCheck class.
 */
class ProductLookupTableCheckTest extends WC_Unit_Test_Case {

	/** Verifies a recommended status when lookup-table drift exceeds the threshold. */
	public function test_recommended_when_drift_above_threshold() {
		$check = $this->getMockBuilder( ProductLookupTableCheck::class )
			->onlyMethods( array( 'count_lookup_rows', 'count_published_products' ) )
			->getMock();
		// 100 products, 50 lookup rows = 50% drift (above default 5%).
		$check->method( 'count_lookup_rows' )->willReturn( 50 );
		$check->method( 'count_published_products' )->willReturn( 100 );
		$this->assertSame( 'recommended', $check->run()['status'] );
	}

	/** Verifies a good status when lookup-table drift is below the threshold. */
	public function test_good_when_drift_below_threshold() {
		$check = $this->getMockBuilder( ProductLookupTableCheck::class )
			->onlyMethods( array( 'count_lookup_rows', 'count_published_products' ) )
			->getMock();
		// 100 products, 101 lookup rows = 1% drift (below default 5%).
		$check->method( 'count_lookup_rows' )->willReturn( 101 );
		$check->method( 'count_published_products' )->willReturn( 100 );
		$this->assertSame( 'good', $check->run()['status'] );
	}

	/** Verifies a recommended status when there are products but zero lookup rows. */
	public function test_recommended_when_zero_lookup_rows_with_products() {
		$check = $this->getMockBuilder( ProductLookupTableCheck::class )
			->onlyMethods( array( 'count_lookup_rows', 'count_published_products' ) )
			->getMock();
		$check->method( 'count_lookup_rows' )->willReturn( 0 );
		$check->method( 'count_published_products' )->willReturn( 10 );
		$this->assertSame( 'recommended', $check->run()['status'] );
	}

	/** Verifies the threshold filter overrides the default drift threshold. */
	public function test_threshold_filter_applies() {
		add_filter( 'woocommerce_site_health_check_product_lookup_table_threshold', fn() => 0 );
		$check = $this->getMockBuilder( ProductLookupTableCheck::class )
			->onlyMethods( array( 'count_lookup_rows', 'count_published_products' ) )
			->getMock();
		// Any non-zero drift should now trigger recommended.
		$check->method( 'count_lookup_rows' )->willReturn( 99 );
		$check->method( 'count_published_products' )->willReturn( 100 );
		$this->assertSame( 'recommended', $check->run()['status'] );
		remove_all_filters( 'woocommerce_site_health_check_product_lookup_table_threshold' );
	}
}
