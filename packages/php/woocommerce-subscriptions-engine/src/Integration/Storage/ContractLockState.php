<?php
/**
 * ContractLockState - the private state behind a {@see ContractLock}.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock;

defined( 'ABSPATH' ) || exit;

/**
 * Token, trust window and extend / release flags of one lock. Held by the lock and read by
 * {@see ContractRepository} through {@see ContractLock::get_state()}; any other caller gets
 * `_doing_it_wrong()`.
 *
 * Trust: child-table writes skip the database check for {@see self::TRUST_WINDOW_SECONDS}
 * after the last stamp (acquire, extend or verify). Times are monotonic, in seconds.
 *
 * @internal Engine storage only.
 */
final class ContractLockState {

	/**
	 * Seconds child writes trust a lock after its last database stamp, and the minimum
	 * remaining lifetime a verify requires.
	 */
	private const TRUST_WINDOW_SECONDS = 60;

	/**
	 * Engine-generated token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Monotonic moment until which child writes trust the lock.
	 *
	 * @var float
	 */
	private $trusted_until = 0.0;

	/**
	 * Monotonic estimate of the stored expiry.
	 *
	 * @var float
	 */
	private $expires_estimate = 0.0;

	/**
	 * Whether the single extend allowance is used.
	 *
	 * @var bool
	 */
	private $extended = false;

	/**
	 * Whether the lock was released.
	 *
	 * @var bool
	 */
	private $released = false;

	/**
	 * State of a lock just stamped with a full TTL.
	 *
	 * @param string $token The stored token.
	 */
	public function __construct( string $token ) {
		$this->token = $token;
		$this->stamp( ContractLock::TTL_SECONDS );
	}

	/**
	 * The token.
	 */
	public function get_token(): string {
		return $this->token;
	}

	/**
	 * Record a database stamp that left `$remaining_seconds` until expiry (acquire, extend or
	 * verify): trust for the window, or less when the lock ends sooner, and not at all with a
	 * window or less left ({@see self::is_trusted()}).
	 *
	 * @param int $remaining_seconds Seconds until the stored expiry.
	 */
	public function stamp( int $remaining_seconds ): void {
		$now = self::now();

		$this->expires_estimate = $now + $remaining_seconds;
		$trust                  = min( self::TRUST_WINDOW_SECONDS, $remaining_seconds - self::TRUST_WINDOW_SECONDS );
		$this->trusted_until    = $this->released || $trust <= 0 ? 0.0 : $now + $trust;
	}

	/**
	 * Whether child writes may trust the lock without a database check.
	 */
	public function is_trusted(): bool {
		return ! $this->released && self::now() < $this->trusted_until;
	}

	/**
	 * Whether the stored expiry is, by the local estimate, too close for a verify to trust the
	 * lock for a full window.
	 */
	public function is_near_expiry(): bool {
		return $this->expires_estimate - self::now() <= 2 * self::TRUST_WINDOW_SECONDS;
	}

	/**
	 * Whether the extend allowance is used.
	 */
	public function has_extended(): bool {
		return $this->extended;
	}

	/**
	 * Use the extend allowance.
	 */
	public function mark_extended(): void {
		$this->extended = true;
	}

	/**
	 * Whether the lock was released.
	 */
	public function is_released(): bool {
		return $this->released;
	}

	/**
	 * Mark the lock released and stop trusting it.
	 */
	public function mark_released(): void {
		$this->released      = true;
		$this->trusted_until = 0.0;
	}

	/**
	 * Monotonic now in seconds. The `hrtime()` array form stays exact on 32-bit builds.
	 */
	private static function now(): float {
		$time = hrtime();

		return is_array( $time ) ? $time[0] + $time[1] / 1e9 : microtime( true );
	}
}
