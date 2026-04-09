<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Manages POS PIN operations including hashing, validation, and HMAC blind index lookup.
 *
 * @since 10.8.0
 */
class POSPinService {

	const PIN_HASH_META_KEY  = '_woocommerce_pos_pin';
	const PIN_INDEX_META_KEY = '_woocommerce_pos_pin_index';

	/**
	 * Common PINs that cannot be assigned to POS users.
	 *
	 * @var string[]
	 */
	private const BLOCKED_PINS = array(
		'0000',
		'1111',
		'2222',
		'3333',
		'4444',
		'5555',
		'6666',
		'7777',
		'8888',
		'9999',
		'1234',
		'4321',
		'1122',
		'1212',
		'2580',
		'0001',
		'0101',
		'1010',
		'1001',
		'2345',
		'3456',
		'4567',
		'5678',
		'6789',
		'7890',
		'1313',
		'1414',
		'1515',
		'1616',
		'1717',
		'1818',
		'1919',
		'2020',
		'2121',
		'2323',
		'2525',
		'1123',
		'1235',
		'1357',
		'2468',
		'0007',
		'0011',
		'0069',
		'0911',
		'1004',
		'1776',
		'2000',
		'2001',
		'5683',
		'6969',
		'7007',
	);

	/**
	 * Validates that a PIN is 4-6 numeric digits.
	 *
	 * @since 10.8.0
	 * @param string $pin The PIN to validate.
	 * @return bool
	 */
	public function validate_pin_format( string $pin ): bool {
		return 1 === preg_match( '/^\d{4,6}$/', $pin );
	}

	/**
	 * Checks whether a PIN is in the blocked list.
	 *
	 * @since 10.8.0
	 * @param string $pin The PIN to check.
	 * @return bool
	 */
	public function is_pin_blocked( string $pin ): bool {
		return in_array( $pin, self::BLOCKED_PINS, true );
	}

	/**
	 * Hashes a PIN using WordPress bcrypt hashing.
	 *
	 * @since 10.8.0
	 * @param string $pin The PIN to hash.
	 * @return string The bcrypt hash.
	 */
	public function hash_pin( string $pin ): string {
		return wp_hash_password( $pin );
	}

	/**
	 * Verifies a PIN against a bcrypt hash.
	 *
	 * @since 10.8.0
	 * @param string $pin  The PIN to verify.
	 * @param string $hash The bcrypt hash to verify against.
	 * @return bool
	 */
	public function verify_pin( string $pin, string $hash ): bool {
		return wp_check_password( $pin, $hash );
	}

	/**
	 * Computes the HMAC blind index for a PIN.
	 *
	 * @since 10.8.0
	 * @param string $pin The PIN to compute the index for.
	 * @return string A 64-character hex HMAC-SHA256 digest.
	 */
	public function compute_pin_index( string $pin ): string {
		return hash_hmac( 'sha256', $pin, wp_salt( 'auth' ) );
	}

	/**
	 * Sets a PIN for a user after validating format, blocked list, and uniqueness.
	 *
	 * Returns true on success, or a WP_Error with a generic 'invalid_pin' code
	 * for all failure types to prevent enumeration.
	 *
	 * @since 10.8.0
	 * @param int    $user_id The user ID.
	 * @param string $pin     The PIN to set.
	 * @return true|WP_Error
	 */
	public function set_pin( int $user_id, string $pin ) {
		if ( ! user_can( $user_id, 'woocommerce_pos_access' ) ) {
			return $this->pin_error();
		}

		if ( ! $this->validate_pin_format( $pin ) || $this->is_pin_blocked( $pin ) ) {
			return $this->pin_error();
		}

		$index = $this->compute_pin_index( $pin );

		if ( $this->is_index_taken( $index, $user_id ) ) {
			return $this->pin_error();
		}

		update_user_meta( $user_id, self::PIN_HASH_META_KEY, $this->hash_pin( $pin ) );
		update_user_meta( $user_id, self::PIN_INDEX_META_KEY, $index );

		return true;
	}

	/**
	 * Deletes a user's PIN and its index.
	 *
	 * @since 10.8.0
	 * @param int $user_id The user ID.
	 */
	public function delete_pin( int $user_id ): void {
		delete_user_meta( $user_id, self::PIN_HASH_META_KEY );
		delete_user_meta( $user_id, self::PIN_INDEX_META_KEY );
	}

	/**
	 * Checks whether a user has a PIN set.
	 *
	 * @since 10.8.0
	 * @param int $user_id The user ID.
	 * @return bool
	 */
	public function has_pin( int $user_id ): bool {
		$hash = get_user_meta( $user_id, self::PIN_HASH_META_KEY, true );
		return '' !== $hash && false !== $hash;
	}

	/**
	 * Looks up a user by PIN using the HMAC blind index for O(1) lookup,
	 * then verifies with bcrypt.
	 *
	 * @since 10.8.0
	 * @param string $pin The PIN to look up.
	 * @return int|null The user ID, or null if not found.
	 */
	public function lookup_user_by_pin( string $pin ): ?int {
		global $wpdb;

		$index = $this->compute_pin_index( $pin );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				self::PIN_INDEX_META_KEY,
				$index
			)
		);

		if ( ! $row ) {
			return null;
		}

		$user_id = (int) $row->user_id;
		$hash    = get_user_meta( $user_id, self::PIN_HASH_META_KEY, true );

		if ( ! $hash || ! $this->verify_pin( $pin, $hash ) ) {
			return null;
		}

		return $user_id;
	}

	/**
	 * Checks whether the given HMAC index is already used by a different user.
	 *
	 * @param string $index   The HMAC index to check.
	 * @param int    $user_id The current user ID (excluded from the check).
	 * @return bool
	 */
	private function is_index_taken( string $index, int $user_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing_user_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s AND user_id != %d LIMIT 1",
				self::PIN_INDEX_META_KEY,
				$index,
				$user_id
			)
		);

		return null !== $existing_user_id;
	}

	/**
	 * Returns a generic WP_Error for all PIN validation failures.
	 *
	 * @return WP_Error
	 */
	private function pin_error(): WP_Error {
		return new WP_Error(
			'invalid_pin',
			__( 'The provided PIN is not acceptable. Please choose a different PIN.', 'woocommerce' )
		);
	}
}
