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

	const META_SESSION_CREATED     = '_woocommerce_pos_session_created';
	const META_SESSION_LAST_ACTIVE = '_woocommerce_pos_session_last_active';
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

		update_user_meta( $user_id, self::META_SESSION_CREATED, $now );
		update_user_meta( $user_id, self::META_SESSION_LAST_ACTIVE, $now );

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
	 * @param int $user_id The user ID.
	 * @return bool
	 */
	public function is_session_valid( int $user_id ): bool {
		$created     = get_user_meta( $user_id, self::META_SESSION_CREATED, true );
		$last_active = get_user_meta( $user_id, self::META_SESSION_LAST_ACTIVE, true );

		if ( '' === $created || false === $created || '' === $last_active || false === $last_active ) {
			return false;
		}

		$now          = time();
		$session_ttl  = (int) apply_filters( 'woocommerce_pos_session_ttl', self::DEFAULT_SESSION_TTL );
		$idle_timeout = (int) apply_filters( 'woocommerce_pos_idle_timeout', self::DEFAULT_IDLE_TIMEOUT );

		if ( ( $now - (int) $created ) > $session_ttl ) {
			return false;
		}

		if ( ( $now - (int) $last_active ) > $idle_timeout ) {
			return false;
		}

		return true;
	}

	/**
	 * Updates the last active timestamp for a user's session.
	 *
	 * @since 10.8.0
	 * @param int $user_id The user ID.
	 */
	public function touch_session( int $user_id ): void {
		update_user_meta( $user_id, self::META_SESSION_LAST_ACTIVE, time() );
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
		delete_user_meta( $user_id, self::META_SESSION_CREATED );
		delete_user_meta( $user_id, self::META_SESSION_LAST_ACTIVE );
	}

	/**
	 * Cleans up stale POS sessions older than 24 hours.
	 *
	 * @since 10.8.0
	 */
	public function cleanup_stale_sessions(): void {
		$users = get_users(
			array(
				'meta_key' => self::META_SESSION_CREATED, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'fields'   => 'ID',
			)
		);

		$now            = time();
		$stale_threshold = 86400;

		foreach ( $users as $user_id ) {
			$created = (int) get_user_meta( $user_id, self::META_SESSION_CREATED, true );

			if ( ( $now - $created ) <= $stale_threshold ) {
				continue;
			}

			$user_id_int = (int) $user_id;
			$passwords   = WP_Application_Passwords::get_user_application_passwords( $user_id_int );
			foreach ( $passwords as $pw ) {
				if ( str_starts_with( $pw['name'], self::APP_PASSWORD_PREFIX ) ) {
					WP_Application_Passwords::delete_application_password( $user_id_int, $pw['uuid'] );
				}
			}

			delete_user_meta( $user_id_int, self::META_SESSION_CREATED );
			delete_user_meta( $user_id_int, self::META_SESSION_LAST_ACTIVE );
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
				WP_Application_Passwords::delete_application_password( $user_id, $pw['uuid'] );
			}
		}
	}
}
