<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\ActionSchedulerStats;
use WC_Unit_Test_Case;

class ActionSchedulerStatsTest extends WC_Unit_Test_Case {

	public function test_overdue_check_recommended_when_threshold_exceeded() {
		$stats = $this->getMockBuilder( ActionSchedulerStats::class )
			->onlyMethods( array( 'count_overdue_actions', 'count_total_actions' ) )
			->getMock();
		$stats->method( 'count_overdue_actions' )->willReturn( 100 );
		$this->assertSame( 'recommended', $stats->run_overdue()['status'] );
	}

	public function test_overdue_check_good_when_below_threshold() {
		$stats = $this->getMockBuilder( ActionSchedulerStats::class )
			->onlyMethods( array( 'count_overdue_actions', 'count_total_actions' ) )
			->getMock();
		$stats->method( 'count_overdue_actions' )->willReturn( 5 );
		$this->assertSame( 'good', $stats->run_overdue()['status'] );
	}

	public function test_total_check_recommended_when_threshold_exceeded() {
		$stats = $this->getMockBuilder( ActionSchedulerStats::class )
			->onlyMethods( array( 'count_overdue_actions', 'count_total_actions' ) )
			->getMock();
		$stats->method( 'count_total_actions' )->willReturn( 600_000 );
		$this->assertSame( 'recommended', $stats->run_total()['status'] );
	}

	public function test_threshold_filter_applies_to_overdue() {
		add_filter( 'woocommerce_site_health_check_action_scheduler_overdue_threshold', fn() => 1 );
		$stats = $this->getMockBuilder( ActionSchedulerStats::class )
			->onlyMethods( array( 'count_overdue_actions', 'count_total_actions' ) )
			->getMock();
		$stats->method( 'count_overdue_actions' )->willReturn( 5 );
		$this->assertSame( 'recommended', $stats->run_overdue()['status'] );
		remove_all_filters( 'woocommerce_site_health_check_action_scheduler_overdue_threshold' );
	}
}
