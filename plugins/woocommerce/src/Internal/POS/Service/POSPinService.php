<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\Utilities\Users;
use WP_Error;
use WP_User_Query;

/**
 * Stores and verifies POS PINs.
 *
 * PINs are stored as a self-describing record in per-site user meta (via
 * Users::*_site_user_meta(), aligning with the blog-scoped POS capabilities on multisite)
 * and shipped to the mobile client (via the staff list endpoint) so the client can
 * validate PIN entry locally.
 *
 * Hash format: PBKDF2-HMAC-SHA-256, 10k iterations, 16-byte random salt, 32-byte hash.
 * This is native on iOS (CommonCrypto / CryptoKit) and Android (SecretKeyFactory with
 * PBKDF2WithHmacSHA256, API 26+). Brute-forcing the 4-digit PIN space against a stolen
 * hash takes ~100 seconds with the chosen cost factor.
 *
 * @since 11.0.0
 * @internal
 */
class POSPinService {

	public const PIN_META_KEY   = 'woocommerce_pos_pin';
	public const ALGO           = 'pbkdf2-sha256';
	public const ITERATIONS     = 10000;
	public const MAX_ITERATIONS = 100000;
	public const SALT_BYTES     = 16;
	public const HASH_BYTES     = 32;
	public const PIN_LENGTH     = 4;

	/**
	 * Set or replace a user's POS PIN.
	 *
	 * PINs are the sole operator identifier on the POS device (the merchant taps a
	 * 4-digit code to identify themselves at the till), so a collision between two
	 * staff members is unresolvable — the device would have no way to tell who is
	 * keying in 1234. To prevent that, this method PBKDF2-verifies the candidate
	 * against every other stored PIN record and rejects on first match.
	 *
	 * The target must already have POS access (hold a `woocommerce_pos_*` capability).
	 * The uniqueness scan only covers POS staff, so a PIN stored on a non-POS user would
	 * be invisible to it and become a latent collision the moment that user is later
	 * granted POS caps — this method rejects that case rather than create the record.
	 *
	 * The uniqueness check is best-effort: it is a read-then-write, so two near-simultaneous
	 * calls for different users could both pass before either writes and end up sharing a PIN.
	 * A PIN is stored as a per-record salted hash, so it cannot be guarded by a unique DB index
	 * the way core enforces SKUs ({@see WC_Product_Data_Store_CPT::obtain_lock_on_sku_for_concurrent_requests()}),
	 * and WordPress offers no portable atomic "reserve value" primitive — mirroring the same
	 * documented trade-off in {@see \Automattic\WooCommerce\Api\Mutations\Products\CreateProduct}.
	 * If strict uniqueness is ever required, the caller should enforce it at a higher layer
	 * (e.g. a mutex around the REST handler) rather than assume this service guarantees it.
	 *
	 * @param int    $user_id The target user ID.
	 * @param string $pin     The plaintext 4-digit PIN. Must match the PIN_LENGTH constant.
	 * @return true|WP_Error  True on success, WP_Error when the user lacks POS access, the
	 *                        PIN format is invalid, the PIN is already in use by another user,
	 *                        or the record could not be written.
	 *
	 * @since 11.0.0
	 */
	public function set_pin( int $user_id, string $pin ) {
		if ( ! $this->validate_pin_format( $pin ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_pin_format',
				__( 'PIN must be exactly 4 digits.', 'woocommerce' ),
				array( 'status' => 422 )
			);
		}

		if ( ! Capabilities::has_pos_access( $user_id ) ) {
			return new WP_Error(
				'woocommerce_pos_no_pos_access',
				__( 'A POS PIN can only be set for a staff member with POS access.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( $this->is_pin_used_by_other_user( $pin, $user_id ) ) {
			return new WP_Error(
				'woocommerce_pos_pin_in_use',
				__( 'This PIN is already in use by another staff member. Choose a different PIN.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		$salt = random_bytes( self::SALT_BYTES );
		$hash = hash_pbkdf2( 'sha256', $pin, $salt, self::ITERATIONS, self::HASH_BYTES, true );

		// base64 encoding here is used purely to make the binary salt/hash storable in
		// user meta and JSON-serializable on the wire; it is not used to obscure code.
		$record = array(
			'algo'       => self::ALGO,
			'iterations' => self::ITERATIONS,
			'salt'       => base64_encode( $salt ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'hash'       => base64_encode( $hash ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		);

		// Store the PIN per-site so it stays aligned with the blog-scoped POS capabilities on
		// multisite (Users::update_site_user_meta suffixes the blog prefix, so the key still
		// matches the woocommerce_% uninstall sweep). A user's PIN is only meaningful on sites
		// where they hold POS access, and the uniqueness scan below is itself blog-scoped, so
		// PIN storage must share that scope.
		//
		// update_user_meta() also returns false for a same-value write, but the fresh random
		// salt above means the record always differs from what is stored — false here is
		// unambiguously a failed write, and returning success would leave the old PIN live.
		if ( false === Users::update_site_user_meta( $user_id, self::PIN_META_KEY, $record ) ) {
			return new WP_Error(
				'woocommerce_pos_pin_save_failed',
				__( 'The PIN could not be saved. Please try again.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Whether the given plaintext PIN collides with one already stored on another POS-access user.
	 *
	 * The PIN is the sole operator identifier at the till, so two staff sharing one would be
	 * unresolvable on the device. set_pin() calls this internally; it is also public for the
	 * wp-admin add-staff flow, which must check uniqueness *before* the user exists (so it can't go
	 * through set_pin) — that caller passes 0 to scan every existing record.
	 *
	 * Scoping the scan to POS-access users — `Capabilities::pos_staff_user_query_args()` selects
	 * holders of any `woocommerce_pos_*` capability — keeps stale PIN meta on non-POS users from
	 * causing phantom collisions. Cost is bounded by the number of active staff (a handful), one
	 * PBKDF2 evaluation per row. The candidate user is excluded so an idempotent re-set ("save the
	 * same PIN again") is allowed.
	 *
	 * @param string $pin             Plaintext PIN candidate. Assumed format-validated.
	 * @param int    $exclude_user_id User being assigned the PIN; excluded from the scan. Pass 0 at
	 *                                create time, when the user does not exist yet.
	 * @return bool
	 */
	public function is_pin_used_by_other_user( string $pin, int $exclude_user_id = 0 ): bool {
		// Select every POS-access user except the candidate, so the new PIN can be PBKDF2-compared
		// against each stored hash and rejected on the first match.
		$user_query = new WP_User_Query(
			array_merge(
				Capabilities::pos_staff_user_query_args(),
				array(
					'fields'  => 'ID',
					// WP_User_Query treats 0 as "no limit" (core WC convention): scan every staff record.
					'number'  => 0,
					'exclude' => array( $exclude_user_id ),
				)
			)
		);

		foreach ( $user_query->get_results() as $other_id ) {
			$record = Users::get_site_user_meta( (int) $other_id, self::PIN_META_KEY, true );
			if ( is_array( $record ) && $this->verify_pin( $pin, $record ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove a user's PIN.
	 *
	 * @param int $user_id The target user ID.
	 *
	 * @since 11.0.0
	 */
	public function delete_pin( int $user_id ): void {
		Users::delete_site_user_meta( $user_id, self::PIN_META_KEY );
	}

	/**
	 * Check whether a user has a PIN set.
	 *
	 * A record that verify_pin() would reject (wrong algo, out-of-range iterations,
	 * malformed salt/hash) reads as no PIN, so "has a PIN" always means "has a PIN
	 * that can actually verify".
	 *
	 * @param int $user_id The target user ID.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public function has_pin( int $user_id ): bool {
		$record = Users::get_site_user_meta( $user_id, self::PIN_META_KEY, true );
		return null !== $this->decode_pin_record( $record );
	}

	/**
	 * Return the public PIN record for a user, suitable for embedding in the staff
	 * list payload sent to mobile clients. Returns null if no PIN is set.
	 *
	 * The record contains the algorithm, iteration count, salt, and hash — everything
	 * needed for the client to validate an entered PIN locally. The plaintext PIN
	 * never leaves the device that set it.
	 *
	 * Only well-formed records are exposed: a record that verify_pin() would reject
	 * returns null instead of shipping to clients, so a corrupted or hostile meta value
	 * (e.g. a huge iteration count that would make the client's PBKDF2 hang) never
	 * reaches a device.
	 *
	 * @param int $user_id The target user ID.
	 * @return array{algo:string,iterations:int,salt:string,hash:string}|null
	 *
	 * @since 11.0.0
	 */
	public function get_public_pin_record( int $user_id ): ?array {
		$record  = Users::get_site_user_meta( $user_id, self::PIN_META_KEY, true );
		$decoded = $this->decode_pin_record( $record );
		if ( null === $decoded ) {
			return null;
		}

		return array(
			'algo'       => self::ALGO,
			'iterations' => $decoded['iterations'],
			'salt'       => base64_encode( $decoded['salt'] ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'hash'       => base64_encode( $decoded['hash'] ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		);
	}

	/**
	 * Verify a plaintext PIN against a stored record. Used server-side by the wp-admin
	 * staff page to confirm the current PIN before allowing changes.
	 *
	 * @param string $pin    The plaintext PIN to verify.
	 * @param array  $record The stored PIN record (algo, iterations, salt, hash).
	 * @return bool          True if the PIN matches, false otherwise.
	 *
	 * @since 11.0.0
	 */
	public function verify_pin( string $pin, array $record ): bool {
		if ( ! $this->validate_pin_format( $pin ) ) {
			return false;
		}

		$decoded = $this->decode_pin_record( $record );
		if ( null === $decoded ) {
			return false;
		}

		$actual_hash = hash_pbkdf2( 'sha256', $pin, $decoded['salt'], $decoded['iterations'], self::HASH_BYTES, true );

		return hash_equals( $decoded['hash'], $actual_hash );
	}

	/**
	 * Validate the PIN string against the expected wire format.
	 *
	 * @param string $pin The PIN to validate.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public function validate_pin_format( string $pin ): bool {
		return self::PIN_LENGTH === strlen( $pin ) && ctype_digit( $pin );
	}

	/**
	 * Validate and decode a stored PIN record, or null if it is malformed.
	 *
	 * Single source of truth for what a well-formed record is, shared by has_pin(),
	 * get_public_pin_record(), and verify_pin() so a record is only ever reported,
	 * exposed to clients, or verified if it matches the format set_pin() writes.
	 *
	 * The iteration count drives PBKDF2's cost. The record comes from user meta, so a corrupted
	 * or hostile value (e.g. a billion iterations) would make every uniqueness scan — or a mobile
	 * client validating locally — hang; bound it and treat anything out of range as malformed.
	 * MAX_ITERATIONS leaves headroom to raise the cost over time while still verifying
	 * historical records.
	 *
	 * @param mixed $record The raw meta value (array with algo, iterations, salt, hash).
	 * @return array{iterations:int,salt:string,hash:string}|null Decoded binary salt/hash and
	 *                                                            iterations, or null when malformed.
	 */
	private function decode_pin_record( $record ): ?array {
		if ( ! is_array( $record ) ) {
			return null;
		}

		$algo       = (string) ( $record['algo'] ?? '' );
		$iterations = (int) ( $record['iterations'] ?? 0 );
		$salt_b64   = (string) ( $record['salt'] ?? '' );
		$hash_b64   = (string) ( $record['hash'] ?? '' );

		if ( self::ALGO !== $algo || $iterations <= 0 || $iterations > self::MAX_ITERATIONS || '' === $salt_b64 || '' === $hash_b64 ) {
			return null;
		}

		// Decode the stored binary salt/hash back from their base64 envelope (see set_pin).
		$salt = base64_decode( $salt_b64, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$hash = base64_decode( $hash_b64, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $salt || false === $hash ) {
			return null;
		}

		// A well-formed record always carries a SALT_BYTES salt and a HASH_BYTES hash (the fixed
		// PBKDF2-SHA256 format set_pin writes). Reject any other size as malformed before anything
		// spends PBKDF2 cycles on it.
		if ( self::SALT_BYTES !== strlen( $salt ) || self::HASH_BYTES !== strlen( $hash ) ) {
			return null;
		}

		return array(
			'iterations' => $iterations,
			'salt'       => $salt,
			'hash'       => $hash,
		);
	}
}
