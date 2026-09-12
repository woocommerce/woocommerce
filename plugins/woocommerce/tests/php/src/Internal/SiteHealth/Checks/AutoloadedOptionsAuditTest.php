<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\AutoloadedOptionsAudit;
use WC_Unit_Test_Case;

/**
 * Tests for the AutoloadedOptionsAudit class.
 */
class AutoloadedOptionsAuditTest extends WC_Unit_Test_Case {

	/**
	 * The audit is recommended when the total autoloaded options size exceeds 800KB.
	 */
	public function test_recommended_when_total_exceeds_800kb() {
		$audit = $this->getMockBuilder( AutoloadedOptionsAudit::class )
			->onlyMethods( array( 'query_total_size', 'query_largest_wc_options' ) )
			->getMock();
		$audit->method( 'query_total_size' )->willReturn( 900_000 );
		$audit->method( 'query_largest_wc_options' )->willReturn( array() );
		$this->assertSame( 'recommended', $audit->run()['status'] );
	}

	/**
	 * The audit is recommended when a single WooCommerce autoloaded option exceeds 100KB.
	 */
	public function test_recommended_when_single_wc_option_exceeds_100kb() {
		$audit = $this->getMockBuilder( AutoloadedOptionsAudit::class )
			->onlyMethods( array( 'query_total_size', 'query_largest_wc_options' ) )
			->getMock();
		$audit->method( 'query_total_size' )->willReturn( 100 );
		$audit->method( 'query_largest_wc_options' )->willReturn(
			array(
				array(
					'option_name' => 'woocommerce_big_option',
					'size'        => 200_000,
				),
			)
		);
		$this->assertSame( 'recommended', $audit->run()['status'] );
	}

	/**
	 * The audit is good when both the total size and every individual option are under their thresholds.
	 */
	public function test_good_when_under_thresholds() {
		$audit = $this->getMockBuilder( AutoloadedOptionsAudit::class )
			->onlyMethods( array( 'query_total_size', 'query_largest_wc_options' ) )
			->getMock();
		$audit->method( 'query_total_size' )->willReturn( 100 );
		$audit->method( 'query_largest_wc_options' )->willReturn(
			array(
				array(
					'option_name' => 'woocommerce_small_option',
					'size'        => 50,
				),
			)
		);
		$this->assertSame( 'good', $audit->run()['status'] );
	}

	/**
	 * The total threshold filter lowers the limit so a small total is reported as recommended.
	 */
	public function test_total_threshold_filter_applies() {
		add_filter( 'woocommerce_site_health_check_autoloaded_options_threshold', fn() => 1 );
		$audit = $this->getMockBuilder( AutoloadedOptionsAudit::class )
			->onlyMethods( array( 'query_total_size', 'query_largest_wc_options' ) )
			->getMock();
		$audit->method( 'query_total_size' )->willReturn( 100 );
		$audit->method( 'query_largest_wc_options' )->willReturn( array() );
		$this->assertSame( 'recommended', $audit->run()['status'] );
		remove_all_filters( 'woocommerce_site_health_check_autoloaded_options_threshold' );
	}

	/**
	 * The per-option threshold filter lowers the limit so a small option is reported as recommended.
	 */
	public function test_per_option_threshold_filter_applies() {
		add_filter( 'woocommerce_site_health_check_autoloaded_options_per_option_threshold', fn() => 100 );
		$audit = $this->getMockBuilder( AutoloadedOptionsAudit::class )
			->onlyMethods( array( 'query_total_size', 'query_largest_wc_options' ) )
			->getMock();
		$audit->method( 'query_total_size' )->willReturn( 50 );
		$audit->method( 'query_largest_wc_options' )->willReturn(
			array(
				array(
					'option_name' => 'woocommerce_medium_option',
					'size'        => 200,
				),
			)
		);
		$this->assertSame( 'recommended', $audit->run()['status'] );
		remove_all_filters( 'woocommerce_site_health_check_autoloaded_options_per_option_threshold' );
	}
}
