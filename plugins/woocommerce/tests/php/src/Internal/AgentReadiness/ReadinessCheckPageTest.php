<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCheckPage;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreConditions;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use WC_Unit_Test_Case;

/**
 * Tests for the Sales readiness view builder.
 */
class ReadinessCheckPageTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ReadinessCheckPage
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		StoreOutputChecksTest::block_requests();
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		delete_option( AgentReadiness::REPORT_OPTION );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		$this->sut = wc_get_container()->get( ReadinessCheckPage::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		delete_option( 'woocommerce_feature_agent_readiness_enabled' );
		parent::tearDown();
	}

	/**
	 * A scan whose is_pending answers come from a list, one per call.
	 *
	 * @param array<int, bool> $answers Pending answers in call order.
	 * @return ScheduledScan
	 */
	private function scan_with_pending_answers( array $answers ): ScheduledScan {
		$scan          = new class() extends ScheduledScan {
			/**
			 * Pending answers left to return.
			 *
			 * @var array<int, bool>
			 */
			public array $answers = array();

			/**
			 * Next answer from the list, or false when the list is empty.
			 */
			public function is_pending(): bool {
				return (bool) array_shift( $this->answers );
			}

			/**
			 * Never stalled in these race tests.
			 */
			public function is_stalled(): bool {
				return false;
			}
		};
		$scan->answers = $answers;
		return $scan;
	}

	/**
	 * Re-init the page with a scan stub that returns the given pending answers.
	 *
	 * @param array<int, bool> $answers Pending answers in call order.
	 */
	private function with_pending_answers( array $answers ): void {
		$this->sut->init(
			wc_get_container()->get( FeaturesController::class ),
			wc_get_container()->get( StoreConditions::class ),
			wc_get_container()->get( AgentReadiness::class ),
			$this->scan_with_pending_answers( $answers )
		);
	}

	/**
	 * @testdox A scan that finishes while the report is read still reports pending, so the client polls once more.
	 */
	public function test_view_keeps_pending_when_scan_finishes_during_report_read(): void {
		$this->with_pending_answers( array( true, false ) );

		$view = $this->sut->view();

		$this->assertTrue( $view['run']['pending'], 'Completion during the report read must not clear pending' );
	}

	/**
	 * @testdox A scan queued while the report is read still reports pending, so the client keeps polling.
	 */
	public function test_view_reports_pending_when_scan_is_queued_during_report_read(): void {
		$this->with_pending_answers( array( false, true ) );

		$view = $this->sut->view();

		$this->assertTrue( $view['run']['pending'], 'A queue during the report read must not hide the new scan' );
	}

	/**
	 * @testdox With no scan before or after the report read, the view is not pending.
	 */
	public function test_view_is_not_pending_when_no_scan_runs(): void {
		$this->with_pending_answers( array( false, false ) );

		$view = $this->sut->view();

		$this->assertFalse( $view['run']['pending'], 'No scan must leave pending false' );
	}
}
