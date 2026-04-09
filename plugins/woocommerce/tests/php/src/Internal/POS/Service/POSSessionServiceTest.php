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

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			$this->markTestSkipped( 'WP_Application_Passwords is not available.' );
		}

		$this->service   = new POSSessionService();
		$this->user_id   = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->user_id_2 = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
	}

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
		$this->service->create_session( $this->user_id, 'register-1' );

		$created     = get_user_meta( $this->user_id, '_woocommerce_pos_session_created', true );
		$last_active = get_user_meta( $this->user_id, '_woocommerce_pos_session_last_active', true );

		$this->assertNotEmpty( $created );
		$this->assertNotEmpty( $last_active );
		$this->assertSame( $created, $last_active );
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
		$this->service->create_session( $this->user_id, 'register-1' );

		$this->assertTrue( $this->service->is_session_valid( $this->user_id ) );
	}

	/**
	 * @testdox is_session_valid returns false when absolute TTL is exceeded.
	 */
	public function test_is_session_valid_returns_false_when_ttl_exceeded(): void {
		$this->service->create_session( $this->user_id, 'register-1' );

		$expired_time = time() - 43201;
		update_user_meta( $this->user_id, '_woocommerce_pos_session_created', $expired_time );
		update_user_meta( $this->user_id, '_woocommerce_pos_session_last_active', time() );

		$this->assertFalse( $this->service->is_session_valid( $this->user_id ) );
	}

	/**
	 * @testdox is_session_valid returns false when idle timeout is exceeded.
	 */
	public function test_is_session_valid_returns_false_when_idle_timeout_exceeded(): void {
		$this->service->create_session( $this->user_id, 'register-1' );

		$idle_time = time() - 1801;
		update_user_meta( $this->user_id, '_woocommerce_pos_session_last_active', $idle_time );

		$this->assertFalse( $this->service->is_session_valid( $this->user_id ) );
	}

	/**
	 * @testdox is_session_valid returns false when no session exists.
	 */
	public function test_is_session_valid_returns_false_when_no_session(): void {
		$this->assertFalse( $this->service->is_session_valid( $this->user_id ) );
	}

	/**
	 * @testdox touch_session updates last active timestamp.
	 */
	public function test_touch_session_updates_last_active(): void {
		$this->service->create_session( $this->user_id, 'register-1' );

		$old_last_active = time() - 60;
		update_user_meta( $this->user_id, '_woocommerce_pos_session_last_active', $old_last_active );

		$this->service->touch_session( $this->user_id );

		$new_last_active = (int) get_user_meta(
			$this->user_id,
			'_woocommerce_pos_session_last_active',
			true
		);

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

		$created     = get_user_meta( $this->user_id, '_woocommerce_pos_session_created', true );
		$last_active = get_user_meta( $this->user_id, '_woocommerce_pos_session_last_active', true );
		$this->assertEmpty( $created );
		$this->assertEmpty( $last_active );
	}

	/**
	 * @testdox cleanup_stale_sessions removes old sessions and passwords.
	 */
	public function test_cleanup_stale_sessions_removes_old_sessions(): void {
		$this->service->create_session( $this->user_id, 'register-1' );

		$old_time = time() - 86401;
		update_user_meta( $this->user_id, '_woocommerce_pos_session_created', $old_time );

		$this->service->cleanup_stale_sessions();

		$passwords = WP_Application_Passwords::get_user_application_passwords( $this->user_id );
		$pos_passwords = array_filter(
			$passwords,
			function ( $pw ) {
				return str_starts_with( $pw['name'], 'WooCommerce POS' );
			}
		);
		$this->assertEmpty( $pos_passwords );

		$created = get_user_meta( $this->user_id, '_woocommerce_pos_session_created', true );
		$this->assertEmpty( $created );
	}

	/**
	 * @testdox cleanup_stale_sessions does not remove fresh sessions.
	 */
	public function test_cleanup_stale_sessions_preserves_fresh_sessions(): void {
		$this->service->create_session( $this->user_id, 'register-1' );

		$this->service->cleanup_stale_sessions();

		$passwords = WP_Application_Passwords::get_user_application_passwords( $this->user_id );
		$pos_passwords = array_filter(
			$passwords,
			function ( $pw ) {
				return str_starts_with( $pw['name'], 'WooCommerce POS' );
			}
		);
		$this->assertNotEmpty( $pos_passwords );

		$created = get_user_meta( $this->user_id, '_woocommerce_pos_session_created', true );
		$this->assertNotEmpty( $created );
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
