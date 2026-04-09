<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Service;

use Automattic\WooCommerce\Internal\POS\Service\POSSessionService;
use WC_Unit_Test_Case;
use WP_Application_Passwords;

/**
 * Tests for POSSessionService.
 *
 * @since 10.8.0
 */
class POSSessionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @var POSSessionService
	 */
	private POSSessionService $service;

	/**
	 * @var int
	 */
	private int $user_id;

	/**
	 * @var int
	 */
	private int $user_id_2;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			$this->markTestSkipped( 'WP_Application_Passwords is not available.' );
		}

		$this->service   = new POSSessionService();
		$this->user_id   = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->user_id_2 = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( isset( $this->user_id ) ) {
			$this->cleanup_app_passwords( $this->user_id );
			wp_delete_user( $this->user_id );
		}
		if ( isset( $this->user_id_2 ) ) {
			$this->cleanup_app_passwords( $this->user_id_2 );
			wp_delete_user( $this->user_id_2 );
		}
		parent::tearDown();
	}

	/**
	 * @testdox create_session returns password, uuid, and expires keys.
	 */
	public function test_create_session_returns_expected_keys(): void {
		$result = $this->service->create_session( $this->user_id, 'register-1' );

		$this->assertArrayHasKey( 'password', $result );
		$this->assertArrayHasKey( 'uuid', $result );
		$this->assertArrayHasKey( 'expires', $result );
		$this->assertNotEmpty( $result['password'] );
		$this->assertNotEmpty( $result['uuid'] );
		$this->assertIsInt( $result['expires'] );
	}

	/**
	 * @testdox create_session sets session created and last active meta.
	 */
	public function test_create_session_sets_meta(): void {
		$session  = $this->service->create_session( $this->user_id, 'register-1' );
		$sessions = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );

		$this->assertIsArray( $sessions );
		$this->assertArrayHasKey( $session['uuid'], $sessions );
		$this->assertSame( $sessions[ $session['uuid'] ]['created'], $sessions[ $session['uuid'] ]['last_active'] );
	}

	/**
	 * @testdox create_session revokes previous password for same user and register.
	 */
	public function test_create_session_revokes_previous_for_same_register(): void {
		$first  = $this->service->create_session( $this->user_id, 'register-1' );
		$second = $this->service->create_session( $this->user_id, 'register-1' );

		$this->assertNotSame( $first['uuid'], $second['uuid'] );

		$passwords = WP_Application_Passwords::get_user_application_passwords( $this->user_id );
		$pos_passwords = array_filter(
			$passwords,
			function ( $pw ) {
				return str_starts_with( $pw['name'], 'WooCommerce POS - register-1' );
			}
		);

		$this->assertCount( 1, $pos_passwords );
	}

	/**
	 * @testdox create_session does NOT revoke password for a different register.
	 */
	public function test_create_session_does_not_revoke_different_register(): void {
		$first  = $this->service->create_session( $this->user_id, 'register-1' );
		$second = $this->service->create_session( $this->user_id, 'register-2' );

		$passwords = WP_Application_Passwords::get_user_application_passwords( $this->user_id );
		$pos_passwords = array_filter(
			$passwords,
			function ( $pw ) {
				return str_starts_with( $pw['name'], 'WooCommerce POS' );
			}
		);

		$this->assertCount( 2, $pos_passwords );
	}

	/**
	 * @testdox is_session_valid returns true for a fresh session.
	 */
	public function test_is_session_valid_returns_true_for_fresh_session(): void {
		$session = $this->service->create_session( $this->user_id, 'register-1' );

		$this->assertTrue( $this->service->is_session_valid( $this->user_id, $session['uuid'] ) );
	}

	/**
	 * @testdox is_session_valid returns false when absolute TTL is exceeded.
	 */
	public function test_is_session_valid_returns_false_when_ttl_exceeded(): void {
		$session            = $this->service->create_session( $this->user_id, 'register-1' );
		$sessions           = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );
		$expired_time       = time() - 43201;
		$sessions[ $session['uuid'] ]['created']     = $expired_time;
		$sessions[ $session['uuid'] ]['last_active'] = time();
		update_user_meta( $this->user_id, POSSessionService::META_SESSIONS, $sessions );

		$this->assertFalse( $this->service->is_session_valid( $this->user_id, $session['uuid'] ) );
	}

	/**
	 * @testdox is_session_valid returns false when idle timeout is exceeded.
	 */
	public function test_is_session_valid_returns_false_when_idle_timeout_exceeded(): void {
		$session          = $this->service->create_session( $this->user_id, 'register-1' );
		$sessions         = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );
		$idle_time        = time() - 1801;
		$sessions[ $session['uuid'] ]['last_active'] = $idle_time;
		update_user_meta( $this->user_id, POSSessionService::META_SESSIONS, $sessions );

		$this->assertFalse( $this->service->is_session_valid( $this->user_id, $session['uuid'] ) );
	}

	/**
	 * @testdox is_session_valid returns false when no session exists.
	 */
	public function test_is_session_valid_returns_false_when_no_session(): void {
		$this->assertFalse( $this->service->is_session_valid( $this->user_id, 'missing-session' ) );
	}

	/**
	 * @testdox touch_session updates last active timestamp.
	 */
	public function test_touch_session_updates_last_active(): void {
		$session                          = $this->service->create_session( $this->user_id, 'register-1' );
		$sessions                         = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );
		$old_last_active                  = time() - 60;
		$sessions[ $session['uuid'] ]['last_active'] = $old_last_active;
		update_user_meta( $this->user_id, POSSessionService::META_SESSIONS, $sessions );

		$this->service->touch_session( $this->user_id, $session['uuid'] );

		$updated_sessions = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );
		$new_last_active  = (int) $updated_sessions[ $session['uuid'] ]['last_active'];

		$this->assertGreaterThan( $old_last_active, $new_last_active );
	}

	/**
	 * @testdox revoke_session deletes the Application Password and clears meta.
	 */
	public function test_revoke_session_deletes_password_and_meta(): void {
		$session = $this->service->create_session( $this->user_id, 'register-1' );

		$this->service->revoke_session( $this->user_id, $session['uuid'] );

		$passwords = WP_Application_Passwords::get_user_application_passwords( $this->user_id );
		$this->assertEmpty( $passwords );

		$sessions = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );
		$this->assertEmpty( $sessions );
	}

	/**
	 * @testdox cleanup_stale_sessions removes old sessions and passwords.
	 */
	public function test_cleanup_stale_sessions_removes_old_sessions(): void {
		$session    = $this->service->create_session( $this->user_id, 'register-1' );
		$sessions   = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );
		$old_time   = time() - 86401;
		$sessions[ $session['uuid'] ]['created'] = $old_time;
		update_user_meta( $this->user_id, POSSessionService::META_SESSIONS, $sessions );

		$this->service->cleanup_stale_sessions();

		$passwords = WP_Application_Passwords::get_user_application_passwords( $this->user_id );
		$pos_passwords = array_filter(
			$passwords,
			function ( $pw ) {
				return str_starts_with( $pw['name'], 'WooCommerce POS' );
			}
		);
		$this->assertEmpty( $pos_passwords );

		$remaining_sessions = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );
		$this->assertEmpty( $remaining_sessions );
	}

	/**
	 * @testdox cleanup_stale_sessions does not remove fresh sessions.
	 */
	public function test_cleanup_stale_sessions_preserves_fresh_sessions(): void {
		$session = $this->service->create_session( $this->user_id, 'register-1' );

		$this->service->cleanup_stale_sessions();

		$passwords = WP_Application_Passwords::get_user_application_passwords( $this->user_id );
		$pos_passwords = array_filter(
			$passwords,
			function ( $pw ) {
				return str_starts_with( $pw['name'], 'WooCommerce POS' );
			}
		);
		$this->assertNotEmpty( $pos_passwords );

		$sessions = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );
		$this->assertArrayHasKey( $session['uuid'], $sessions );
	}

	/**
	 * @testdox touch_session only updates the active session for the matching UUID.
	 */
	public function test_touch_session_only_updates_matching_uuid(): void {
		$session_one = $this->service->create_session( $this->user_id, 'register-1' );
		$session_two = $this->service->create_session( $this->user_id, 'register-2' );
		$sessions    = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );

		$sessions[ $session_one['uuid'] ]['last_active'] = time() - 120;
		$sessions[ $session_two['uuid'] ]['last_active'] = time() - 240;
		update_user_meta( $this->user_id, POSSessionService::META_SESSIONS, $sessions );

		$this->service->touch_session( $this->user_id, $session_one['uuid'] );

		$updated_sessions = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );
		$this->assertGreaterThan(
			$updated_sessions[ $session_two['uuid'] ]['last_active'],
			$updated_sessions[ $session_one['uuid'] ]['last_active']
		);
	}

	/**
	 * @testdox cleanup_stale_sessions preserves fresh sessions for the same user.
	 */
	public function test_cleanup_stale_sessions_only_removes_stale_uuid(): void {
		$stale_session = $this->service->create_session( $this->user_id, 'register-1' );
		$fresh_session = $this->service->create_session( $this->user_id, 'register-2' );
		$sessions      = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );

		$sessions[ $stale_session['uuid'] ]['created'] = time() - 86401;
		update_user_meta( $this->user_id, POSSessionService::META_SESSIONS, $sessions );

		$this->service->cleanup_stale_sessions();

		$updated_sessions = get_user_meta( $this->user_id, POSSessionService::META_SESSIONS, true );
		$this->assertArrayNotHasKey( $stale_session['uuid'], $updated_sessions );
		$this->assertArrayHasKey( $fresh_session['uuid'], $updated_sessions );
	}

	/**
	 * Removes all application passwords for a user.
	 *
	 * @param int $user_id The user ID.
	 */
	private function cleanup_app_passwords( int $user_id ): void {
		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			return;
		}
		$passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );
		foreach ( $passwords as $pw ) {
			WP_Application_Passwords::delete_application_password( $user_id, $pw['uuid'] );
		}
	}
}
