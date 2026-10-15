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
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

defined( 'ABSPATH' ) || exit;

/**
 * One operation's hold on one contract, for the synchronous section of one request.
 * A write that passes the lock is part of the holder's operation; any other write to the
 * contract is refused with {@see ContractLockedException} while the lock lives.
 *
 * Fencing: contract-row writes check the token and expiry in the statement; child-table
 * writes trust the lock for its TTL minus a margin, then extend it once or re-verify.
 * The token is engine-generated and never readable (reflection is outside the contract).
 */
final class ContractLock {

	/**
	 * Lock lifetime in seconds, from the database clock at acquire or extend.
	 */
	public const TTL_SECONDS = 600;

	/**
	 * Seconds before the stored expiry after which child writes stop trusting the lock.
	 */
	private const TRUST_MARGIN_SECONDS = 60;

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
	 * Engine-generated token; never exposed. The repository reads it through a scope-bound closure.
	 *
	 * @var string
	 */
	private $token; // @phpstan-ignore property.onlyWritten

	/**
	 * Monotonic `hrtime()` moment (ns) until which child writes trust the lock.
	 *
	 * @var int
	 */
	private $trust_deadline_ns = 0;

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
	 * Use {@see self::issue()}.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $operation   Operation label.
	 * @param string $token       Engine-generated token.
	 */
	private function __construct( int $contract_id, string $operation, string $token ) {
		$this->contract_id = $contract_id;
		$this->operation   = $operation;
		$this->token       = $token;
	}

	/**
	 * Build a lock the repository has just stored.
	 *
	 * @internal Engine storage only.
	 *
	 * @param int    $contract_id   Contract id.
	 * @param string $operation     Operation label.
	 * @param string $token         The stored token.
	 * @param int    $trust_seconds Seconds until the stored expiry.
	 */
	public static function issue( int $contract_id, string $operation, string $token, int $trust_seconds ): self {
		$lock = new self( $contract_id, $operation, $token );
		$lock->trust_for( $trust_seconds );

		return $lock;
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
	 * Extend the lock to a full TTL from the database's now. Allowed once per lock.
	 *
	 * @throws ContractLockedException If the lock was lost or released.
	 * @throws LogicException If the lock was already extended.
	 */
	public function extend(): void {
		( new ContractRepository() )->extend_lock( $this );
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
	 * Whether child writes may trust the lock without a database check.
	 *
	 * @internal Engine storage only.
	 */
	public function is_trusted(): bool {
		return ! $this->released && self::now_ns() < $this->trust_deadline_ns;
	}

	/**
	 * Trust the lock until the margin before an expiry `$remaining_seconds` away.
	 *
	 * @internal Engine storage only.
	 *
	 * @param int $remaining_seconds Seconds until the stored expiry.
	 */
	public function trust_for( int $remaining_seconds ): void {
		$this->trust_deadline_ns = self::now_ns() + max( 0, $remaining_seconds - self::TRUST_MARGIN_SECONDS ) * 1000000000;
	}

	/**
	 * Whether the extend allowance is used.
	 *
	 * @internal Engine storage only.
	 */
	public function has_extended(): bool {
		return $this->extended;
	}

	/**
	 * Use the extend allowance.
	 *
	 * @internal Engine storage only.
	 */
	public function mark_extended(): void {
		$this->extended = true;
	}

	/**
	 * Whether the lock was released.
	 *
	 * @internal Engine storage only.
	 */
	public function is_released(): bool {
		return $this->released;
	}

	/**
	 * Mark the lock released and stop trusting it.
	 *
	 * @internal Engine storage only.
	 */
	public function mark_released(): void {
		$this->released          = true;
		$this->trust_deadline_ns = 0;
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
			'extended'    => $this->extended,
			'released'    => $this->released,
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

	/**
	 * Monotonic now in nanoseconds.
	 */
	private static function now_ns(): int {
		return (int) hrtime( true );
	}
}
