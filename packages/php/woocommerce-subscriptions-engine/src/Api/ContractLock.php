<?php
/**
 * ContractLock - a held per-contract lock, as returned by {@see Contracts::lock()},
 * {@see Contracts::create_locked()} and the due dispatcher.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use LogicException;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractLockState;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

defined( 'ABSPATH' ) || exit;

/**
 * One operation's hold on one contract, for the synchronous section of one request.
 * A write that passes the lock is part of the holder's operation; any other write to the
 * contract is refused with {@see ContractLockedException} while the lock lives.
 *
 * Fencing: contract-row writes check the token and expiry in the statement; child-table
 * writes trust the lock for 60 seconds from its last database stamp, then extend it once
 * when it is near expiry or re-verify it. The token and the trust state are engine-only:
 * reflection, array casts, `var_export()` and `debug_zval_dump()` are outside the contract.
 */
final class ContractLock {

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
	 * Built by the repository through a scope-bound closure.
	 *
	 * @param int               $contract_id Contract id.
	 * @param string            $operation   Operation label.
	 * @param ContractLockState $state       Token and trust state.
	 */
	private function __construct( int $contract_id, string $operation, ContractLockState $state ) {
		$this->contract_id = $contract_id;
		$this->operation   = $operation;
		$this->state       = $state;
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

	/**
	 * A lock never leaves the request.
	 *
	 * @throws LogicException Always.
	 */
	public function __wakeup(): void {
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
