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
 * PINs are stored as a self-describing record in per-site user meta (aligning with the
 * blog-scoped POS capabilities on multisite) and shipped to the mobile client via the
 * staff list endpoint for local PIN-entry validation.
 *
 * Hash format: PBKDF2-HMAC-SHA-256, 10k iterations, 16-byte salt, 32-byte hash — native
 * on both iOS and Android. Brute-forcing the 4-digit space against a stolen hash takes
 * ~100 seconds; the PIN identifies the operator at the till and is not a credential.
 *
 * @since 11.0.0
 * @internal
 */
class POSPinService {

	public const PIN_META_KEY = 'woocommerce_pos_pin';
	public const ALGO         = 'pbkdf2-sha256';
	public const ITERATIONS   = 10000;
	public const SALT_BYTES   = 16;
	public const HASH_BYTES   = 32;
	public const PIN_LENGTH   = 4;

	/**
	 * Set or replace a user's POS PIN.
	 *
	 * The PIN is the sole operator identifier at the till, so a collision between two staff
	 * members is unresolvable on the device; the candidate is PBKDF2-verified against every
	 * other stored record and rejected on first match. The target must already hold POS
	 * access: the uniqueness scan only covers POS staff, so a PIN on a non-POS user would
	 * become a latent collision the moment they later gain access.
	 *
	 * The uniqueness check is best-effort read-then-write: near-simultaneous calls can end
	 * up sharing a PIN. A per-record salted hash cannot be guarded by a unique DB index, and
	 * WordPress has no portable atomic "reserve value" primitive — the same documented
	 * trade-off as {@see \Automattic\WooCommerce\Api\Mutations\Products\CreateProduct}.
	 * Callers needing strict uniqueness must serialize at a higher layer.
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

		// Stored per-site to stay aligned with the blog-scoped POS capabilities on multisite
		// (the suffixed key still matches the woocommerce_% uninstall sweep). The fresh random
		// salt means the record always differs from what is stored, so false from
		// update_user_meta() is unambiguously a failed write, never a same-value no-op.
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
	 * set_pin() calls this internally; it is also public for the wp-admin add-staff flow, which
	 * checks uniqueness before the user exists — that caller passes 0 to scan every record.
	 *
	 * The query selects *candidates* (capability__in also matches explicitly denied caps), so
	 * each row is refined with the resolved-capability check Capabilities::has_pos_access()
	 * before its PIN is compared — stale PIN meta on users without actual POS access cannot
	 * cause phantom collisions. Cost is bounded by the number of active staff, one PBKDF2
	 * evaluation per row. The candidate user is excluded so an idempotent re-set is allowed.
	 *
	 * @param string $pin             Plaintext PIN candidate; a malformed PIN is never "in use".
	 * @param int    $exclude_user_id User being assigned the PIN; excluded from the scan. Pass 0 at
	 *                                create time, when the user does not exist yet.
	 * @return bool
	 */
	public function is_pin_used_by_other_user( string $pin, int $exclude_user_id = 0 ): bool {
		if ( ! $this->validate_pin_format( $pin ) ) {
			return false;
		}

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
			// Candidates can include explicitly denied caps (see docblock); skip before hashing.
			if ( ! Capabilities::has_pos_access( (int) $other_id ) ) {
				continue;
			}

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
	 * Check whether a user has a PIN set. A record that verify_pin() would reject reads
	 * as no PIN, so "has a PIN" always means "has a PIN that can actually verify".
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
	 * Return the public PIN record for a user — algorithm, iterations, salt, hash:
	 * everything a mobile client needs to validate an entered PIN locally (the plaintext
	 * PIN never leaves the device that set it). Returns null when no PIN is set or the
	 * stored record is malformed, so a corrupted or hostile meta value (e.g. a huge
	 * iteration count that would hang the client's PBKDF2) never ships to a device.
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
	 * Single source of truth for what a well-formed record is — shared by has_pin(),
	 * get_public_pin_record(), and verify_pin(), so a record is only ever reported,
	 * exposed, or verified if it matches the format set_pin() writes. That includes the
	 * exact ITERATIONS count, rejecting both hostile inflation (a huge count would hang
	 * the uniqueness scan or a client) and a cost downgrade. If the cost is ever raised,
	 * this check must accept previously shipped values so historical records keep verifying.
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

		if ( self::ALGO !== $algo || self::ITERATIONS !== $iterations || '' === $salt_b64 || '' === $hash_b64 ) {
			return null;
		}

		// Decode the stored binary salt/hash back from their base64 envelope (see set_pin).
		$salt = base64_decode( $salt_b64, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$hash = base64_decode( $hash_b64, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $salt || false === $hash ) {
			return null;
		}

		// Reject any size other than the fixed SALT_BYTES/HASH_BYTES format before hashing.
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
