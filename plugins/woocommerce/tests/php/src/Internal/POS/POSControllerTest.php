<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\POSController;
use Automattic\WooCommerce\Internal\POS\Service\POSSessionService;
use WC_Unit_Test_Case;

/**
 * Tests for POSController.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\POSController
 */
class POSControllerTest extends WC_Unit_Test_Case {

	/**
	 * @var POSController
	 */
	private $sut;

	/**
	 * @var POSSessionService|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $session_service_mock;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->session_service_mock = $this->createMock( POSSessionService::class );
		$this->sut                  = new POSController();
		$this->sut->init( $this->session_service_mock );

		as_unschedule_all_actions( POSController::CLEANUP_ACTION_HOOK );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_actions( POSController::CLEANUP_ACTION_HOOK );
		as_unschedule_all_actions( POSController::CLEANUP_ACTION_HOOK );
		parent::tearDown();
	}

	/**
	 * @testdox register() hooks the cleanup handler to the Action Scheduler action.
	 */
	public function test_register_hooks_cleanup_action(): void {
		$this->sut->register();

		$this->assertSame(
			10,
			has_action( POSController::CLEANUP_ACTION_HOOK, array( $this->sut, 'handle_cleanup' ) )
		);
	}

	/**
	 * @testdox maybe_schedule_cleanup schedules the recurring action when none exists.
	 */
	public function test_maybe_schedule_cleanup_schedules_when_not_exists(): void {
		$this->assertFalse(
			as_has_scheduled_action( POSController::CLEANUP_ACTION_HOOK, null, POSController::CLEANUP_GROUP ),
			'Precondition: no scheduled action should exist.'
		);

		$this->sut->maybe_schedule_cleanup();

		$this->assertTrue(
			as_has_scheduled_action( POSController::CLEANUP_ACTION_HOOK, null, POSController::CLEANUP_GROUP ),
			'Cleanup action should be scheduled after maybe_schedule_cleanup.'
		);
	}

	/**
	 * @testdox maybe_schedule_cleanup does not double-schedule.
	 */
	public function test_maybe_schedule_cleanup_is_idempotent(): void {
		$this->sut->maybe_schedule_cleanup();
		$this->sut->maybe_schedule_cleanup();

		$actions = as_get_scheduled_actions(
			array(
				'hook'   => POSController::CLEANUP_ACTION_HOOK,
				'group'  => POSController::CLEANUP_GROUP,
				'status' => \ActionScheduler_Store::STATUS_PENDING,
			)
		);

		$this->assertCount(
			1,
			$actions,
			'Only one cleanup action should be scheduled even after calling maybe_schedule_cleanup twice.'
		);
	}

	/**
	 * @testdox handle_cleanup delegates to POSSessionService::cleanup_stale_sessions.
	 */
	public function test_handle_cleanup_delegates_to_session_service(): void {
		$this->session_service_mock
			->expects( $this->once() )
			->method( 'cleanup_stale_sessions' );

		$this->sut->handle_cleanup();
	}
}
