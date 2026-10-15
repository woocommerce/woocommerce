<?php
/**
 * ContractLockedException - a contract operation refused because another operation
 * holds the contract's lock.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use DateTimeImmutable;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown when a write is refused because another operation holds the contract's lock,
 * or because the caller's own lock was lost. Carries the
 * holder's operation label and expiry (null when the holder released meanwhile), never a token.
 */
final class ContractLockedException extends RuntimeException {

	/**
	 * Contract id.
	 *
	 * @var int
	 */
	private $contract_id;

	/**
	 * The holder's operation label, or null.
	 *
	 * @var string|null
	 */
	private $operation;

	/**
	 * The holder's lock expiry (UTC), or null.
	 *
	 * @var DateTimeImmutable|null
	 */
	private $locked_until;

	/**
	 * Describe the conflict.
	 *
	 * @param int                    $contract_id  Contract id.
	 * @param string|null            $operation    The holder's operation label, or null when unknown.
	 * @param DateTimeImmutable|null $locked_until The holder's lock expiry (UTC), or null when unknown.
	 */
	public function __construct( int $contract_id, ?string $operation, ?DateTimeImmutable $locked_until ) {
		$message = null === $operation
			? sprintf( 'Contract %d is locked by another operation.', $contract_id )
			: sprintf( 'Contract %d is locked by another operation (%s).', $contract_id, $operation );

		parent::__construct( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the message is not output.

		$this->contract_id  = $contract_id;
		$this->operation    = $operation;
		$this->locked_until = $locked_until;
	}

	/**
	 * Contract id.
	 */
	public function get_contract_id(): int {
		return $this->contract_id;
	}

	/**
	 * The holder's operation label, or null when unknown.
	 */
	public function get_operation(): ?string {
		return $this->operation;
	}

	/**
	 * The holder's lock expiry (UTC), or null when unknown.
	 */
	public function get_locked_until(): ?DateTimeImmutable {
		return $this->locked_until;
	}
}
