<?php
/**
 * POSPinService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

use Automattic\WooCommerce\Internal\StoreActors\ActorAccessRepository;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Stores and verifies POS PINs against the actor access table
 * (wc_store_actor_access, context='pos', location_id=0).
 *
 * The PIN record is shipped to the mobile client via the staff list endpoint
 * so the client can validate PIN entry locally. The plaintext PIN never leaves
 * the device that set it.
 *
 * Hash format: PBKDF2-HMAC-SHA-256, 10k iterations, 16-byte random salt,
 * 32-byte hash. This is native on iOS (CommonCrypto / CryptoKit) and Android
 * (SecretKeyFactory with PBKDF2WithHmacSHA256, API 26+). Brute-forcing the
 * 4-digit PIN space against a stolen hash takes ~100 seconds with the chosen
 * cost factor.
 *
 * @internal Owned by the Point of Sale staff (actors) feature.
 * @since 10.9.0
 */
class POSPinService {

	public const ALGO       = 'pbkdf2-sha256';
	public const ITERATIONS = 10000;
	public const SALT_BYTES = 16;
	public const HASH_BYTES = 32;
	public const PIN_LENGTH = 4;

	/**
	 * @var ActorAccessRepository
	 */
	private ActorAccessRepository $access_repo;

	/**
	 * DI init.
	 *
	 * @param ActorAccessRepository $access_repo Repository for the actor access table.
	 * @return void
	 *
	 * @internal
	 */
	final public function init( ActorAccessRepository $access_repo ): void {
		$this->access_repo = $access_repo;
	}

	/**
	 * Set or replace an actor's POS PIN. Requires an existing POS access row
	 * for the actor — callers must create the actor + access row first.
	 *
	 * Enforces PIN uniqueness across all *active* POS access rows: because
	 * staff log in to POS using only a PIN (no username), two staff sharing
	 * the same PIN is unresolvable. The check walks active access rows and
	 * PBKDF2-verifies the candidate PIN against each existing salted hash,
	 * excluding the current actor so that "set the same PIN again" remains
	 * a valid no-op rather than a collision against itself.
	 *
	 * @param int    $actor_id The target actor ID.
	 * @param string $pin      The plaintext PIN. Must match PIN_LENGTH.
	 * @return true|WP_Error
	 */
	public function set_pin( int $actor_id, string $pin ) {
		if ( ! $this->validate_pin_format( $pin ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_pin_format',
				__( 'PIN must be exactly 4 digits.', 'woocommerce' ),
				array( 'status' => 422 )
			);
		}

		$access = $this->access_repo->get_for_actor( $actor_id );
		if ( null === $access ) {
			return new WP_Error(
				'woocommerce_pos_actor_no_access',
				__( 'Cannot set PIN — this POS staff member has no POS access assignment.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		if ( null !== $this->find_actor_with_pin( $pin, $actor_id ) ) {
			return new WP_Error(
				'woocommerce_pos_pin_in_use',
				__( 'This PIN is already in use by another POS staff member. Choose a different PIN.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		$salt = random_bytes( self::SALT_BYTES );
		$hash = hash_pbkdf2( 'sha256', $pin, $salt, self::ITERATIONS, self::HASH_BYTES, true );

		$ok = $this->access_repo->update(
			(int) $access['access_id'],
			array(
				'credential_type'       => ActorAccessRepository::CREDENTIAL_TYPE_PIN,
				'credential_algo'       => self::ALGO,
				'credential_iterations' => self::ITERATIONS,
				'credential_salt'       => base64_encode( $salt ),
				'credential_hash'       => base64_encode( $hash ),
				'credential_updated_at' => gmdate( 'Y-m-d H:i:s' ),
			)
		);

		if ( ! $ok ) {
			return new WP_Error(
				'woocommerce_pos_pin_persist_failed',
				__( 'Failed to persist PIN.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Remove an actor's POS PIN.
	 *
	 * @param int $actor_id The target actor ID.
	 * @return bool
	 */
	public function delete_pin( int $actor_id ): bool {
		return $this->access_repo->clear_pos_credential( $actor_id );
	}

	/**
	 * Check whether an actor has a POS PIN set.
	 *
	 * @param int $actor_id The target actor ID.
	 * @return bool
	 */
	public function has_pin( int $actor_id ): bool {
		$access = $this->access_repo->get_for_actor( $actor_id );
		return null !== $access && ! empty( $access['credential_hash'] );
	}

	/**
	 * Return the public PIN record for an actor, suitable for embedding in
	 * the staff list payload sent to mobile clients. Returns null if no PIN
	 * is set.
	 *
	 * @param int $actor_id The target actor ID.
	 * @return array{algo:string,iterations:int,salt:string,hash:string,updated_at:?string}|null
	 */
	public function get_public_pin_record( int $actor_id ): ?array {
		$access = $this->access_repo->get_for_actor( $actor_id );
		if ( null === $access || empty( $access['credential_hash'] ) ) {
			return null;
		}

		return array(
			'algo'       => (string) ( $access['credential_algo'] ?? self::ALGO ),
			'iterations' => (int) ( $access['credential_iterations'] ?? self::ITERATIONS ),
			'salt'       => (string) ( $access['credential_salt'] ?? '' ),
			'hash'       => (string) ( $access['credential_hash'] ?? '' ),
			'updated_at' => isset( $access['credential_updated_at'] ) ? (string) $access['credential_updated_at'] : null,
		);
	}

	/**
	 * Verify a plaintext PIN against a stored record. Used server-side by
	 * the wp-admin Staff page when callers re-enter the current PIN.
	 *
	 * @param string $pin    The plaintext PIN to verify.
	 * @param array  $record The stored PIN record (algo, iterations, salt, hash).
	 * @return bool
	 */
	public function verify_pin( string $pin, array $record ): bool {
		if ( ! $this->validate_pin_format( $pin ) ) {
			return false;
		}

		$algo       = (string) ( $record['algo'] ?? '' );
		$iterations = (int) ( $record['iterations'] ?? 0 );
		$salt_b64   = (string) ( $record['salt'] ?? '' );
		$hash_b64   = (string) ( $record['hash'] ?? '' );

		if ( self::ALGO !== $algo || $iterations <= 0 || '' === $salt_b64 || '' === $hash_b64 ) {
			return false;
		}

		$salt          = base64_decode( $salt_b64, true );
		$expected_hash = base64_decode( $hash_b64, true );
		if ( false === $salt || false === $expected_hash ) {
			return false;
		}

		$actual_hash = hash_pbkdf2( 'sha256', $pin, $salt, $iterations, self::HASH_BYTES, true );

		return hash_equals( $expected_hash, $actual_hash );
	}

	/**
	 * Find the actor ID (if any) whose stored PIN matches the candidate.
	 *
	 * Walks active POS access rows that carry credentials and PBKDF2-verifies
	 * the candidate PIN against each — because each PIN is stored with a
	 * unique random salt, a direct hash compare across rows isn't possible.
	 * For typical POS staff list sizes (tens, not thousands), the cost is
	 * negligible (~10 ms per verify × N).
	 *
	 * Used by set_pin() to enforce uniqueness, and available to callers that
	 * need the same lookup for other reasons (e.g. future server-side PIN
	 * login validation).
	 *
	 * @param string   $pin              Candidate plaintext PIN.
	 * @param int|null $exclude_actor_id Actor ID to skip (typically the actor
	 *                                   whose PIN is being set). null to check all.
	 * @return int|null Matching actor ID, or null if no active staff member uses this PIN.
	 */
	public function find_actor_with_pin( string $pin, ?int $exclude_actor_id = null ): ?int {
		if ( ! $this->validate_pin_format( $pin ) ) {
			return null;
		}

		foreach ( $this->access_repo->list_active_for_context( ActorAccessRepository::CONTEXT_POS ) as $row ) {
			$actor_id = (int) ( $row['actor_id'] ?? 0 );
			if ( $actor_id <= 0 ) {
				continue;
			}
			if ( null !== $exclude_actor_id && $actor_id === $exclude_actor_id ) {
				continue;
			}
			if ( empty( $row['credential_hash'] ) ) {
				continue;
			}

			$record = array(
				'algo'       => (string) ( $row['credential_algo'] ?? '' ),
				'iterations' => (int) ( $row['credential_iterations'] ?? 0 ),
				'salt'       => (string) ( $row['credential_salt'] ?? '' ),
				'hash'       => (string) ( $row['credential_hash'] ?? '' ),
			);

			if ( $this->verify_pin( $pin, $record ) ) {
				return $actor_id;
			}
		}

		return null;
	}

	/**
	 * Validate a PIN string against the expected wire format (4 digits).
	 *
	 * @param string $pin The PIN to validate.
	 * @return bool
	 */
	public function validate_pin_format( string $pin ): bool {
		return 1 === preg_match( '/^\d{' . self::PIN_LENGTH . '}$/', $pin );
	}
}
