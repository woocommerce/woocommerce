<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\POSController;
use Automattic\WooCommerce\Internal\POS\Service\POSSessionService;
use WC_Unit_Test_Case;
use WP_User;

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
		remove_all_actions( 'application_password_did_authenticate' );
		remove_all_filters( 'rest_authentication_errors' );
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

	/**
	 * @testdox validate_pos_session touches session when session is valid.
	 */
	public function test_validate_pos_session_touches_valid_session(): void {
		$this->sut->register();

		$user = $this->createMock( WP_User::class );
		$user->ID = 42;

		$this->session_service_mock
			->expects( $this->once() )
			->method( 'is_session_valid' )
			->with( 42, 'test-uuid-123' )
			->willReturn( true );

		$this->session_service_mock
			->expects( $this->once() )
			->method( 'touch_session' )
			->with( 42, 'test-uuid-123' );

		$app_password = array(
			'name' => 'WooCommerce POS - register-1 - 2026-01-01 00:00:00',
			'uuid' => 'test-uuid-123',
		);

		$this->sut->validate_pos_session( $user, $app_password );

		$error = $this->sut->enforce_pos_session_error( null );
		$this->assertNull( $error );
	}

	/**
	 * @testdox validate_pos_session stores auth error when session is expired.
	 */
	public function test_validate_pos_session_rejects_expired_session(): void {
		$this->sut->register();

		$user = $this->createMock( WP_User::class );
		$user->ID = 42;

		$this->session_service_mock
			->expects( $this->once() )
			->method( 'is_session_valid' )
			->with( 42, 'test-uuid-123' )
			->willReturn( false );

		$app_password = array(
			'name' => 'WooCommerce POS - register-1 - 2026-01-01 00:00:00',
			'uuid' => 'test-uuid-123',
		);

		$this->sut->validate_pos_session( $user, $app_password );

		$error = $this->sut->enforce_pos_session_error( null );
		$this->assertInstanceOf( \WP_Error::class, $error );
		$this->assertSame( 'woocommerce_pos_session_expired', $error->get_error_code() );
	}

	/**
	 * @testdox validate_pos_session ignores non-POS Application Passwords.
	 */
	public function test_validate_pos_session_ignores_non_pos_passwords(): void {
		$this->sut->register();

		$user = $this->createMock( WP_User::class );
		$user->ID = 42;

		$this->session_service_mock
			->expects( $this->never() )
			->method( 'is_session_valid' );

		$app_password = array(
			'name' => 'My Custom App',
			'uuid' => 'test-uuid-456',
		);

		$this->sut->validate_pos_session( $user, $app_password );

		$error = $this->sut->enforce_pos_session_error( null );
		$this->assertNull( $error );
	}

	/**
	 * @testdox register adds the application_password_did_authenticate action.
	 */
	public function test_register_hooks_session_validation(): void {
		remove_all_actions( 'application_password_did_authenticate' );
		remove_all_filters( 'rest_authentication_errors' );

		$this->sut->register();

		$this->assertSame(
			10,
			has_action( 'application_password_did_authenticate', array( $this->sut, 'validate_pos_session' ) )
		);
		$this->assertSame(
			99,
			has_filter( 'rest_authentication_errors', array( $this->sut, 'enforce_pos_session_error' ) )
		);
	}
}
