<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

defined( 'ABSPATH' ) || exit;

use WP_Application_Passwords;
use WP_Error;

/**
 * Manages POS session lifecycle via WordPress Application Passwords.
 *
 * @since 10.8.0
 */
class POSSessionService {

	const META_SESSIONS           = '_woocommerce_pos_sessions';
	const APP_PASSWORD_PREFIX      = 'WooCommerce POS';
	const DEFAULT_SESSION_TTL      = 43200;
	const DEFAULT_IDLE_TIMEOUT     = 1800;

	/**
	 * Creates a new POS session for a user on a specific register.
	 *
	 * Revokes any existing POS Application Passwords for the same register,
	 * then creates a new Application Password and stores session metadata.
	 *
	 * @since 10.8.0
	 * @param int    $user_id     The user ID.
	 * @param string $register_id The register identifier.
	 * @return array{password: string, uuid: string, expires: int}|WP_Error
	 */
	public function create_session( int $user_id, string $register_id ) {
		$this->revoke_pos_passwords_for_register( $user_id, $register_id );

		$name   = self::APP_PASSWORD_PREFIX . ' - ' . $register_id . ' - ' . gmdate( 'Y-m-d H:i:s' );
		$result = WP_Application_Passwords::create_new_application_password(
			$user_id,
			array( 'name' => $name )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$password = $result[0];
		$item     = $result[1];
		$now      = time();

		$sessions                  = $this->get_sessions( $user_id );
		$sessions[ $item['uuid'] ] = array(
			'created'     => $now,
			'last_active' => $now,
			'register_id' => $register_id,
		);
		update_user_meta( $user_id, self::META_SESSIONS, $sessions );

		$ttl = (int) apply_filters( 'woocommerce_pos_session_ttl', self::DEFAULT_SESSION_TTL );

		return array(
			'password' => $password,
			'uuid'     => $item['uuid'],
			'expires'  => $now + $ttl,
		);
	}

	/**
	 * Checks whether a user's POS session is still valid.
	 *
	 * @since 10.8.0
	 * @param int    $user_id The user ID.
	 * @param string $uuid    The Application Password UUID.
	 * @return bool
	 */
	public function is_session_valid( int $user_id, string $uuid ): bool {
		$session = $this->get_session( $user_id, $uuid );

		if ( null === $session ) {
			return false;
		}

		$now          = time();
		$session_ttl  = (int) apply_filters( 'woocommerce_pos_session_ttl', self::DEFAULT_SESSION_TTL );
		$idle_timeout = (int) apply_filters( 'woocommerce_pos_idle_timeout', self::DEFAULT_IDLE_TIMEOUT );

		if ( ( $now - (int) $session['created'] ) > $session_ttl ) {
			return false;
		}

		if ( ( $now - (int) $session['last_active'] ) > $idle_timeout ) {
			return false;
		}

		return true;
	}

	/**
	 * Updates the last active timestamp for a user's session.
	 *
	 * @since 10.8.0
	 * @param int    $user_id The user ID.
	 * @param string $uuid    The Application Password UUID.
	 */
	public function touch_session( int $user_id, string $uuid ): void {
		$sessions = $this->get_sessions( $user_id );
		if ( ! isset( $sessions[ $uuid ] ) ) {
			return;
		}

		$sessions[ $uuid ]['last_active'] = time();
		update_user_meta( $user_id, self::META_SESSIONS, $sessions );
	}

	/**
	 * Revokes a POS session by deleting the Application Password and clearing metadata.
	 *
	 * @since 10.8.0
	 * @param int    $user_id The user ID.
	 * @param string $uuid    The Application Password UUID.
	 */
	public function revoke_session( int $user_id, string $uuid ): void {
		WP_Application_Passwords::delete_application_password( $user_id, $uuid );

		$sessions = $this->get_sessions( $user_id );
		unset( $sessions[ $uuid ] );

		if ( empty( $sessions ) ) {
			delete_user_meta( $user_id, self::META_SESSIONS );
			return;
		}

		update_user_meta( $user_id, self::META_SESSIONS, $sessions );
	}

	/**
	 * Cleans up stale POS sessions older than 24 hours.
	 *
	 * @since 10.8.0
	 */
	public function cleanup_stale_sessions(): void {
		$users = get_users(
			array(
				'meta_key' => self::META_SESSIONS, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'fields'   => 'ID',
			)
		);

		$now             = time();
		$stale_threshold = DAY_IN_SECONDS;

		foreach ( $users as $user_id ) {
			$user_id_int = (int) $user_id;
			$sessions    = $this->get_sessions( $user_id_int );
			if ( empty( $sessions ) ) {
				continue;
			}

			$passwords    = WP_Application_Passwords::get_user_application_passwords( $user_id_int );
			$active_uuids = array();
			$did_change   = false;

			foreach ( $sessions as $uuid => $session ) {
				if ( ( $now - (int) $session['created'] ) <= $stale_threshold ) {
					$active_uuids[ $uuid ] = $session;
					continue;
				}

				$did_change = true;
				foreach ( $passwords as $pw ) {
					if ( $pw['uuid'] === $uuid && str_starts_with( $pw['name'], self::APP_PASSWORD_PREFIX ) ) {
						WP_Application_Passwords::delete_application_password( $user_id_int, $pw['uuid'] );
						break;
					}
				}
			}

			if ( ! $did_change ) {
				continue;
			}

			if ( empty( $active_uuids ) ) {
				delete_user_meta( $user_id_int, self::META_SESSIONS );
				continue;
			}

			update_user_meta( $user_id_int, self::META_SESSIONS, $active_uuids );
		}
	}

	/**
	 * Revokes all POS Application Passwords for a specific register.
	 *
	 * @param int    $user_id     The user ID.
	 * @param string $register_id The register identifier.
	 */
	private function revoke_pos_passwords_for_register( int $user_id, string $register_id ): void {
		$passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );
		$prefix    = self::APP_PASSWORD_PREFIX . ' - ' . $register_id;

		foreach ( $passwords as $pw ) {
			if ( str_starts_with( $pw['name'], $prefix ) ) {
				$this->revoke_session( $user_id, $pw['uuid'] );
			}
		}
	}

	/**
	 * Return all POS sessions for a user.
	 *
	 * @param int $user_id The user ID.
	 * @return array<string, array{created:int,last_active:int,register_id:string}>
	 */
	private function get_sessions( int $user_id ): array {
		$sessions = get_user_meta( $user_id, self::META_SESSIONS, true );

		return is_array( $sessions ) ? $sessions : array();
	}

	/**
	 * Return a single POS session for a user.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $uuid    The application password UUID.
	 * @return array{created:int,last_active:int,register_id:string}|null
	 */
	private function get_session( int $user_id, string $uuid ): ?array {
		$sessions = $this->get_sessions( $user_id );

		return isset( $sessions[ $uuid ] ) && is_array( $sessions[ $uuid ] )
			? $sessions[ $uuid ]
			: null;
	}
}
