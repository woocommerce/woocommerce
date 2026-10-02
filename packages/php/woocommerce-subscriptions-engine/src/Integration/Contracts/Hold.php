<?php
/**
 * Hold - put an active subscription contract on hold (suspend billing).
 *
 * A focused contract-management operation (deliberately not a catch-all manager),
 * mirroring {@see Cancellation}: move the contract ACTIVE -> ON_HOLD, disarm its
 * next-due moment, and announce it. The batch due scan keys on `next_payment_gmt` and a
 * registered owner (its active-status predicate is a renewal-flow condition, see
 * {@see ContractRepository::find_due()}), so the flow disarms its own due moment rather
 * than relying on status to stop billing. The cleared moment is kept in contract meta
 * ({@see self::ANCHOR_META_KEY}) so {@see Reactivation} can recompute the schedule
 * forward from it. Its preconditions are its own, not a rule of the status primitive.
 *
 * Interim: moves out of the engine with the lifecycle flows (hold / reactivate /
 * cancel and their routes).
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
	 * Contract meta key holding the next-due moment cleared by a hold - the moment
	 * {@see Reactivation} recomputes forward from.
	 *
	 * Interim: moves out of the engine with the lifecycle flows (hold / reactivate /
	 * cancel and their routes).
	 */
	public const ANCHOR_META_KEY = '_hold_next_payment_gmt';

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
	 * Hold `$contract`: move it to on-hold and disarm its next-due moment.
	 *
	 * Only an active contract can be held; holding an already on-hold contract is an
	 * idempotent no-op that still succeeds and fires the action (nothing is rewritten,
	 * so the stored anchor survives). Any other status - including one that is not
	 * registered - raises a `DomainException`. The current cycle is immutable and is
	 * NOT touched.
	 *
	 * @param Contract $contract Contract to hold. Must have an id, and be ACTIVE (or already ON_HOLD).
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
		if ( ContractStatus::ACTIVE !== $previous && ContractStatus::ON_HOLD !== $previous ) {
			throw new \DomainException( 'Hold::hold(): only an active contract can be held.' );
		}

		if ( ContractStatus::ACTIVE === $previous ) {
			// A contract with no next-due moment stores no anchor (null removes the key).
			$contract->set_status( ContractStatus::ON_HOLD );
			$contract->set_meta( self::ANCHOR_META_KEY, $contract->get_next_payment_gmt() );
			$contract->set_next_payment_gmt( null );
		}

		// Compare-and-set on the status read above: a concurrent transition (another
		// request, the renewal engine) makes this write miss loudly rather than be
		// clobbered.
		//
		// Not atomic: the status/date row write and the anchor meta write are separate
		// statements, not one transaction. If the meta write fails after the row write,
		// the contract is held without an anchor and reactivation leaves it unscheduled
		// (it falls back to the cleared, null next payment). Accepted for this interim
		// flow - the repository has no transaction wrapper today - and the consumer that
		// takes over hold owns the durable shape.
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
