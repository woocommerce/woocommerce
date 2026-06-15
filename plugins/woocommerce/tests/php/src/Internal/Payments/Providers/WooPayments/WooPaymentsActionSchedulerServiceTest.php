<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use ActionScheduler_Store;
use ActionScheduler;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsActionSchedulerService;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsActionSchedulerService class.
 */
class WooPaymentsActionSchedulerServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsActionSchedulerService
	 */
	private $sut;

	/**
	 * Test hook name.
	 *
	 * @var string
	 */
	private string $hook = 'wcpay_native_payments_test_action';

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( WooPaymentsActionSchedulerService::class );
		$this->unschedule_test_actions();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->unschedule_test_actions();
		parent::tearDown();
	}

	/**
	 * @testdox The scheduler uses the preserved WooPayments group.
	 */
	public function test_group_id_preserves_woopayments_group(): void {
		$this->assertSame( 'woocommerce_payments', WooPaymentsActionSchedulerService::GROUP_ID );
	}

	/**
	 * @testdox Scheduling avoids duplicate pending actions with the same hook, args, and group.
	 */
	public function test_schedule_job_avoids_duplicate_pending_actions(): void {
		$args = array( 'event_id' => 'evt_123' );

		$this->sut->schedule_job( $this->hook, $args );
		$this->sut->schedule_job( $this->hook, $args );

		$this->assertSame( 1, $this->count_pending_actions( $this->hook, $args ) );
	}

	/**
	 * @testdox Scheduling keeps distinct args as distinct actions.
	 */
	public function test_schedule_job_keeps_distinct_args_as_distinct_actions(): void {
		$this->sut->schedule_job( $this->hook, array( 'event_id' => 'evt_123' ) );
		$this->sut->schedule_job( $this->hook, array( 'event_id' => 'evt_456' ) );

		$this->assertSame( 1, $this->count_pending_actions( $this->hook, array( 'event_id' => 'evt_123' ) ) );
		$this->assertSame( 1, $this->count_pending_actions( $this->hook, array( 'event_id' => 'evt_456' ) ) );
	}

	/**
	 * @testdox A running action does not block scheduling the next page.
	 */
	public function test_schedule_job_does_not_treat_running_actions_as_pending_duplicates(): void {
		$args      = array();
		$action_id = as_schedule_single_action( time(), $this->hook, $args, 'woocommerce_payments' );

		ActionScheduler::store()->log_execution( $action_id );

		$this->sut->schedule_job( $this->hook, $args );

		$this->assertSame( 1, $this->count_pending_actions( $this->hook, $args ) );

		ActionScheduler::store()->mark_complete( $action_id );
	}

	/**
	 * Count pending test actions.
	 *
	 * @param string              $hook Hook name.
	 * @param array<string,mixed> $args Action args.
	 * @return int
	 */
	private function count_pending_actions( string $hook, array $args ): int {
		$actions = as_get_scheduled_actions(
			array(
				'hook'   => $hook,
				'args'   => $args,
				'group'  => 'woocommerce_payments',
				'status' => ActionScheduler_Store::STATUS_PENDING,
			)
		);

		return count( $actions );
	}

	/**
	 * Remove test actions from Action Scheduler.
	 */
	private function unschedule_test_actions(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( $this->hook, null, 'woocommerce_payments' );
		}
	}
}
