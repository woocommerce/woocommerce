<?php
/**
 * FraudProtectionChallengeManager class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Manages OTP challenge lifecycle and storage.
 *
 * This class handles the creation, verification, and management of OTP challenges
 * using WordPress transients for storage. Storage operations are abstracted to
 * allow for future migration to a database table if needed.
 *
 * @since 10.4.0
 */
class FraudProtectionChallengeManager {

	/**
	 * Transient key prefix for OTP challenges.
	 */
	private const TRANSIENT_PREFIX = 'wc_fraud_otp_';

	/**
	 * Challenge expiration time in seconds (60 minutes).
	 */
	private const CHALLENGE_EXPIRATION = 3600;

	/**
	 * Maximum number of attempts allowed per challenge.
	 */
	private const MAX_ATTEMPTS = 3;

	/**
	 * OTP code length.
	 */
	private const OTP_LENGTH = 6;

	/**
	 * Challenge ID length.
	 */
	private const CHALLENGE_ID_LENGTH = 20;

	/**
	 * Logger source identifier.
	 */
	private const LOGGER_SOURCE = 'woo-fraud-protection-otp';

	/**
	 * Error code: invalid OTP.
	 */
	public const ERROR_OTP_INVALID = 'otp_invalid';

	/**
	 * Error code: OTP expired.
	 */
	public const ERROR_OTP_EXPIRED = 'otp_expired';

	/**
	 * Error code: maximum attempts reached.
	 */
	public const ERROR_MAX_ATTEMPTS = 'max_attempts';

	/**
	 * Error code: challenge not found.
	 */
	public const ERROR_CHALLENGE_NOT_FOUND = 'challenge_not_found';

	/**
	 * Create a new OTP challenge.
	 *
	 * Generates a unique challenge ID and a 6-digit OTP code, then stores
	 * the challenge data in a transient with 60-minute expiration.
	 *
	 * @param string $session_key WooCommerce session key.
	 * @param string $email       Email address for the challenge.
	 * @return array Challenge data with keys: challenge_id, otp_code, expires_at, attempts_remaining.
	 */
	public function create_challenge( string $session_key, string $email ): array {
		$challenge_id = $this->generate_challenge_id();
		$otp_code     = $this->generate_otp_code();
		$generated_at = time();
		$expires_at   = $generated_at + self::CHALLENGE_EXPIRATION;

		$challenge_data = array(
			'challenge_id' => $challenge_id,
			'session_key'  => $session_key,
			'email'        => $email,
			'otp_code'     => $otp_code,
			'generated_at' => $generated_at,
			'expires_at'   => $expires_at,
			'attempts'     => 0,
		);

		$this->save_challenge( $challenge_id, $challenge_data );

		$this->log_info(
			sprintf(
				'OTP challenge created: %s | Session: %s | Email: %s | Expires: %s',
				$challenge_id,
				$session_key,
				$email,
				gmdate( 'Y-m-d H:i:s', $expires_at )
			)
		);

		return array(
			'challenge_id'       => $challenge_id,
			'otp_code'           => $otp_code,
			'expires_at'         => $expires_at,
			'attempts_remaining' => self::MAX_ATTEMPTS,
		);
	}

	/**
	 * Get an existing challenge by ID.
	 *
	 * @param string $challenge_id Challenge ID.
	 * @return array|null Challenge data or null if not found.
	 */
	public function get_challenge( string $challenge_id ): ?array {
		return $this->load_challenge( $challenge_id );
	}

	/**
	 * Verify an OTP code for a challenge.
	 *
	 * Checks if the provided OTP code matches the stored code, validates
	 * expiration, and tracks verification attempts.
	 *
	 * @param string $challenge_id Challenge ID.
	 * @param string $otp_code     OTP code to verify.
	 * @return array|\WP_Error Success array or WP_Error on failure.
	 */
	public function verify_otp( string $challenge_id, string $otp_code ) {
		$challenge = $this->load_challenge( $challenge_id );

		if ( null === $challenge ) {
			$this->log_error(
				sprintf(
					'Verification failed - challenge not found: %s',
					$challenge_id
				)
			);
			return new \WP_Error(
				self::ERROR_CHALLENGE_NOT_FOUND,
				__( 'Verification challenge not found or has expired.', 'woocommerce' )
			);
		}

		// Check if challenge has expired.
		if ( time() > $challenge['expires_at'] ) {
			$this->delete_challenge( $challenge_id );
			$this->log_error(
				sprintf(
					'Verification failed - challenge expired: %s | Session: %s',
					$challenge_id,
					$challenge['session_key']
				)
			);
			return new \WP_Error(
				self::ERROR_OTP_EXPIRED,
				__( 'Verification code has expired. Please request a new code.', 'woocommerce' )
			);
		}

		// Check if maximum attempts reached.
		if ( $challenge['attempts'] >= self::MAX_ATTEMPTS ) {
			$this->delete_challenge( $challenge_id );
			$this->log_error(
				sprintf(
					'Verification failed - max attempts reached: %s | Session: %s',
					$challenge_id,
					$challenge['session_key']
				)
			);
			return new \WP_Error(
				self::ERROR_MAX_ATTEMPTS,
				__( 'Maximum verification attempts exceeded. Please request a new code.', 'woocommerce' )
			);
		}

		// Increment attempts counter.
		$challenge['attempts']++;
		$this->save_challenge( $challenge_id, $challenge );

		// Verify OTP code.
		if ( $otp_code !== $challenge['otp_code'] ) {
			$remaining_attempts = self::MAX_ATTEMPTS - $challenge['attempts'];
			$this->log_error(
				sprintf(
					'Verification failed - invalid OTP: %s | Session: %s | Attempts remaining: %d',
					$challenge_id,
					$challenge['session_key'],
					$remaining_attempts
				)
			);
			return new \WP_Error(
				self::ERROR_OTP_INVALID,
				sprintf(
					/* translators: %d: number of remaining attempts */
					__( 'Invalid verification code. %d attempts remaining.', 'woocommerce' ),
					$remaining_attempts
				),
				array( 'attempts_remaining' => $remaining_attempts )
			);
		}

		// Verification successful - delete challenge.
		$this->delete_challenge( $challenge_id );

		$this->log_info(
			sprintf(
				'OTP verification successful: %s | Session: %s | Email: %s',
				$challenge_id,
				$challenge['session_key'],
				$challenge['email']
			)
		);

		return array(
			'success'      => true,
			'challenge'    => $challenge,
			'verified_at'  => time(),
		);
	}

	/**
	 * Increment the attempts counter for a challenge.
	 *
	 * Used to track email send attempts separately from verification attempts.
	 *
	 * @param string $challenge_id Challenge ID.
	 * @return bool True on success, false if challenge not found.
	 */
	public function increment_attempts( string $challenge_id ): bool {
		$challenge = $this->load_challenge( $challenge_id );

		if ( null === $challenge ) {
			return false;
		}

		$challenge['attempts']++;
		$this->save_challenge( $challenge_id, $challenge );

		$this->log_info(
			sprintf(
				'Challenge attempts incremented: %s | Total attempts: %d',
				$challenge_id,
				$challenge['attempts']
			)
		);

		return true;
	}

	/**
	 * Delete a challenge.
	 *
	 * @param string $challenge_id Challenge ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_challenge( string $challenge_id ): bool {
		$result = $this->remove_challenge_storage( $challenge_id );

		if ( $result ) {
			$this->log_info(
				sprintf(
					'Challenge deleted: %s',
					$challenge_id
				)
			);
		}

		return $result;
	}

	/**
	 * Get the remaining attempts for a challenge.
	 *
	 * @param string $challenge_id Challenge ID.
	 * @return int|\WP_Error Remaining attempts or WP_Error if challenge not found.
	 */
	public function get_remaining_attempts( string $challenge_id ) {
		$challenge = $this->load_challenge( $challenge_id );

		if ( null === $challenge ) {
			return new \WP_Error(
				self::ERROR_CHALLENGE_NOT_FOUND,
				__( 'Challenge not found.', 'woocommerce' )
			);
		}

		return max( 0, self::MAX_ATTEMPTS - $challenge['attempts'] );
	}

	/**
	 * Check if a challenge has expired.
	 *
	 * @param string $challenge_id Challenge ID.
	 * @return bool True if expired, false otherwise.
	 */
	public function is_challenge_expired( string $challenge_id ): bool {
		$challenge = $this->load_challenge( $challenge_id );

		if ( null === $challenge ) {
			return true;
		}

		return time() > $challenge['expires_at'];
	}

	/**
	 * Generate a unique challenge ID.
	 *
	 * Uses wp_generate_password to create a 20-character random string
	 * without special characters.
	 *
	 * @return string Challenge ID.
	 */
	private function generate_challenge_id(): string {
		return wp_generate_password( self::CHALLENGE_ID_LENGTH, false );
	}

	/**
	 * Generate a 6-digit OTP code.
	 *
	 * Uses wp_rand to generate a random 6-digit numeric code.
	 *
	 * @return string OTP code.
	 */
	private function generate_otp_code(): string {
		return str_pad( (string) wp_rand( 0, 999999 ), self::OTP_LENGTH, '0', STR_PAD_LEFT );
	}

	/**
	 * Save challenge data to storage.
	 *
	 * Abstracted storage method to allow for future migration from transients
	 * to a custom database table if needed.
	 *
	 * @param string $challenge_id  Challenge ID.
	 * @param array  $challenge_data Challenge data.
	 * @return bool True on success, false on failure.
	 */
	private function save_challenge( string $challenge_id, array $challenge_data ): bool {
		$transient_key = $this->get_transient_key( $challenge_id );
		return set_transient( $transient_key, $challenge_data, self::CHALLENGE_EXPIRATION );
	}

	/**
	 * Load challenge data from storage.
	 *
	 * Abstracted storage method to allow for future migration from transients
	 * to a custom database table if needed.
	 *
	 * @param string $challenge_id Challenge ID.
	 * @return array|null Challenge data or null if not found.
	 */
	private function load_challenge( string $challenge_id ): ?array {
		$transient_key = $this->get_transient_key( $challenge_id );
		$challenge     = get_transient( $transient_key );

		return false !== $challenge ? $challenge : null;
	}

	/**
	 * Remove challenge data from storage.
	 *
	 * Abstracted storage method to allow for future migration from transients
	 * to a custom database table if needed.
	 *
	 * @param string $challenge_id Challenge ID.
	 * @return bool True on success, false on failure.
	 */
	private function remove_challenge_storage( string $challenge_id ): bool {
		$transient_key = $this->get_transient_key( $challenge_id );
		return delete_transient( $transient_key );
	}

	/**
	 * Get the transient key for a challenge.
	 *
	 * @param string $challenge_id Challenge ID.
	 * @return string Transient key.
	 */
	private function get_transient_key( string $challenge_id ): string {
		return self::TRANSIENT_PREFIX . $challenge_id;
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message Log message.
	 * @return void
	 */
	private function log_info( string $message ): void {
		$logger = wc_get_logger();
		$logger->info( $message, array( 'source' => self::LOGGER_SOURCE ) );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Error message.
	 * @return void
	 */
	private function log_error( string $message ): void {
		$logger = wc_get_logger();
		$logger->error( $message, array( 'source' => self::LOGGER_SOURCE ) );
	}
}
