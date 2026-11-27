<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
use WC_Unit_Test_Case;

/**
 * Shared setup for the scheduled readiness scan tests.
 */
abstract class ScheduledScanTestCase extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ScheduledScan
	 */
	protected $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		// Report tests stay offline: store output checks would load the test site's pages.
		StoreOutputChecksTest::block_requests();
		add_filter( 'woocommerce_agent_readiness_rules_url', '__return_empty_string' );
		delete_option( AgentReadiness::REPORT_OPTION );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		as_unschedule_all_actions( ScheduledScan::DAILY_HOOK );
		as_unschedule_all_actions( ScheduledScan::NEXT_HOOK );
		delete_option( AgentReadiness::SCAN_OPTION );
		$this->sut = wc_get_container()->get( ScheduledScan::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		as_unschedule_all_actions( ScheduledScan::DAILY_HOOK );
		as_unschedule_all_actions( ScheduledScan::NEXT_HOOK );
		parent::tearDown();
	}

	/**
	 * Run the scan's batch actions as Action Scheduler would, until the scan stores its report.
	 *
	 * @return int Batch actions run.
	 */
	protected function run_batches(): int {
		$runs = 0;
		while ( $runs < 20 && is_array( get_option( AgentReadiness::SCAN_OPTION ) ) ) {
			$this->sut->run_batch( get_option( AgentReadiness::SCAN_OPTION )['id'] );
			++$runs;
		}
		return $runs;
	}

	/**
	 * Create published simple products quickly, without images, so they fail the image check.
	 *
	 * @param int $count Products to create.
	 * @return array<int, int> Product ids.
	 */
	protected static function create_products( int $count ): array {
		$ids = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$id = wp_insert_post(
				array(
					'post_type'   => 'product',
					'post_status' => 'publish',
					'post_title'  => 'Product ' . $i,
				)
			);
			wp_set_object_terms( $id, 'simple', 'product_type' );
			update_post_meta( $id, '_regular_price', '10' );
			update_post_meta( $id, '_price', '10' );
			$ids[] = $id;
		}
		return $ids;
	}
}
