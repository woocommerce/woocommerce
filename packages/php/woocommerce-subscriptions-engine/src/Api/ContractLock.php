<?php
/**
 * ContractLock - a held per-contract lock, as returned by {@see Contracts::lock()},
 * {@see Contracts::create()} and the due dispatcher.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use LogicException;
use Serializable;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractLockState;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

defined( 'ABSPATH' ) || exit;

/**
 * One operation's hold on one contract for one request: writes that pass it belong to the
 * holder's operation, and other writes are refused with {@see ContractLockedException}. It
 * guards honest callers against accidental interleaving, not code set on bypassing it
 * (reflection, closures, direct SQL).
 */
final class ContractLock implements Serializable {

	/**
	 * Lock lifetime in seconds, from the database clock at acquire or extend.
	 */
	public const TTL_SECONDS = 600;

	/**
	 * Contract id.
	 *
	 * @var int
	 */
	private $contract_id;

	/**
	 * Operation label.
	 *
	 * @var string
	 */
	private $operation;

	/**
	 * Token and trust state; the repository reads it through a scope-bound closure.
	 *
	 * @var ContractLockState
	 */
	private $state; // @phpstan-ignore property.onlyWritten

	/**
	 * Take locks through {@see Contracts::lock()} or {@see Contracts::create()}. A lock built
	 * by any caller but the engine repository gets `_doing_it_wrong()`: it would bypass the
	 * lock lifetime.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $operation   Operation label.
	 * @param string $token       The stored token.
	 */
	public function __construct( int $contract_id, string $operation, string $token ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- two frames, no arguments: only the caller's class is read.
		$caller = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 )[1]['class'] ?? '';
		if ( ContractRepository::class !== $caller ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'Take a contract lock with Contracts::lock() or Contracts::create(); a lock built from a stored token bypasses the lock lifetime.', 'woocommerce-subscriptions-engine' ), '0.0.1' );
		}

		$this->contract_id = $contract_id;
		$this->operation   = $operation;
		$this->state       = new ContractLockState( $token );
	}

	/**
	 * The locked contract's id.
	 */
	public function get_contract_id(): int {
		return $this->contract_id;
	}

	/**
	 * The operation label the lock was taken with.
	 */
	public function get_operation(): string {
		return $this->operation;
	}

	/**
	 * Release the lock. Idempotent; never clears a lock another operation took over.
	 *
	 * @throws \RuntimeException If the release write fails (the lock then expires on its own).
	 */
	public function release(): void {
		( new ContractRepository() )->release_lock( $this );
	}

	/**
	 * Debug output without the token.
	 *
	 * @return array<string, mixed>
	 */
	public function __debugInfo(): array {
		return array(
			'contract_id' => $this->contract_id,
			'operation'   => $this->operation,
		);
	}

	// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn -- always throws.
	/**
	 * A lock never leaves the request.
	 *
	 * @return array<string, mixed>
	 * @throws LogicException Always.
	 */
	public function __serialize(): array {
		throw new LogicException( 'A contract lock cannot be serialized.' );
	}
	// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn

	/**
	 * A lock never leaves the request.
	 *
	 * @param array<string, mixed> $data Serialized data.
	 * @throws LogicException Always.
	 */
	public function __unserialize( array $data ): void {
		throw new LogicException( 'A contract lock cannot be unserialized.' );
	}

	// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn -- always throws.
	/**
	 * A lock never leaves the request. {@see Serializable} is implemented only so that a legacy
	 * `C:` payload, which skips `__unserialize()`, is refused too.
	 *
	 * @return string|null
	 * @throws LogicException Always.
	 */
	public function serialize() {
		throw new LogicException( 'A contract lock cannot be serialized.' );
	}
	// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn

	/**
	 * Refuse a legacy `C:` payload, which would otherwise build a lock without its constructor.
	 *
	 * @param string $data Serialized data.
	 * @throws LogicException Always.
	 */
	public function unserialize( $data ): void {
		throw new LogicException( 'A contract lock cannot be unserialized.' );
	}

	/**
	 * A lock has one holder.
	 *
	 * @throws LogicException Always.
	 */
	public function __clone() {
		throw new LogicException( 'A contract lock cannot be cloned.' );
	}
}
