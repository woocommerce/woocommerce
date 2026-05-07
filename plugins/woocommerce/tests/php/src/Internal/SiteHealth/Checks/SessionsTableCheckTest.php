<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\SessionsTableCheck;
use WC_Unit_Test_Case;

class SessionsTableCheckTest extends WC_Unit_Test_Case {

	public function test_recommended_when_threshold_exceeded() {
		$check = $this->getMockBuilder( SessionsTableCheck::class )
			->onlyMethods( array( 'count_sessions' ) )
			->getMock();
		$check->method( 'count_sessions' )->willReturn( 200_000 );
		$this->assertSame( 'recommended', $check->run()['status'] );
	}

	public function test_good_when_below_threshold() {
		$check = $this->getMockBuilder( SessionsTableCheck::class )
			->onlyMethods( array( 'count_sessions' ) )
			->getMock();
		$check->method( 'count_sessions' )->willReturn( 1_000 );
		$this->assertSame( 'good', $check->run()['status'] );
	}

	public function test_threshold_filter_applies() {
		add_filter( 'woocommerce_site_health_check_sessions_table_threshold', fn() => 1 );
		$check = $this->getMockBuilder( SessionsTableCheck::class )
			->onlyMethods( array( 'count_sessions' ) )
			->getMock();
		$check->method( 'count_sessions' )->willReturn( 5 );
		$this->assertSame( 'recommended', $check->run()['status'] );
		remove_all_filters( 'woocommerce_site_health_check_sessions_table_threshold' );
	}
}
