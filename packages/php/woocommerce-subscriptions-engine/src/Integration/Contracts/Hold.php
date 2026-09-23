<?php
/**
 * Hold - put an active subscription contract on hold (suspend billing).
 *
 * A focused contract-management operation (deliberately not a catch-all manager),
 * mirroring {@see Cancellation}: transition the contract ACTIVE -> ON_HOLD through the
 * Core state machine and announce it. No charge fires while held because the batch due
 * scan only bills active contracts, so there is no per-contract schedule to clear. The
 * contract keeps its `next_payment_gmt` so the held duration is recoverable on
 * {@see Reactivation}. Lives under `Integration\Contracts` so contract lifecycle stays
 * separate from the renewal money-path.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts;

use RuntimeException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Put a contract on hold.
 */
final class Hold {

	/**
	 * Action fired after a contract is put on hold, with `( $contract )`.
	 */
	public const CONTRACT_HELD_ACTION = 'woocommerce_subscriptions_engine_contract_held';

	/**
	 * Contract repository.
	 *
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * Construct.
	 *
	 * @param ContractRepository|null $contracts Contract repository; default instance when omitted.
	 */
	public function __construct( ?ContractRepository $contracts = null ) {
		$this->contracts = $contracts ?? new ContractRepository();
	}

	/**
	 * Hold `$contract`: transition it to on-hold.
	 *
	 * Status moves through the Core state machine ({@see Contract::set_status()}), which
	 * raises a `DomainException` on an illegal transition (e.g. holding a terminal
	 * contract). The current cycle is immutable and is NOT touched; only the live
	 * contract status moves. No charge fires while held because the batch due scan only
	 * bills active contracts - there is no per-contract schedule to clear. The
	 * `next_payment_gmt` is preserved so {@see Reactivation} can recompute the schedule
	 * forward.
	 *
	 * @param Contract $contract Contract to hold. Must have an id, and be ACTIVE.
	 * @return bool True when the contract was held and persisted.
	 * @throws RuntimeException If the contract has no id.
	 * @throws \DomainException If the contract cannot be held from its current state, or its state changed concurrently.
	 */
	public function hold( Contract $contract ): bool {
		$id = $contract->get_id();
		if ( null === $id ) {
			throw new RuntimeException( 'Hold::hold(): cannot hold a contract that has no id.' );
		}

		$previous = $contract->get_status();
		$contract->set_status( ContractStatus::ON_HOLD );

		// Compare-and-set on the status read above: a concurrent transition (another
		// request, the renewal engine) makes this write miss loudly rather than be
		// clobbered.
		if ( ! $this->contracts->update_if_status( $contract, $previous ) ) {
			throw new \DomainException( 'Hold::hold(): the contract state changed concurrently; nothing was written.' );
		}

		/**
		 * Fires after a contract is put on hold.
		 *
		 * @param Contract $contract The held contract.
		 */
		do_action( self::CONTRACT_HELD_ACTION, $contract );

		return true;
	}
}
