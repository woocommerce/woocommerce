<?php
/**
 * Cancellation - cancel a subscription contract, immediately or at period end.
 *
 * A focused contract-management operation (deliberately not a catch-all manager)
 * covering the one cancel intent in its two modes: {@see self::cancel()} tears the
 * contract down NOW (transition to cancelled, close any charge caught mid-flight,
 * announce it), while {@see self::cancel_at_period_end()} winds it down gracefully
 * (transition to pending-cancellation, stamp the end date, keep serving until the
 * period lapses). Both modes disarm the contract's next-due moment themselves: the batch
 * due scan keys on `next_payment_gmt` and a registered owner, so the flow stops renewals by
 * clearing its own due moment rather than relying on status. Their preconditions are
 * the flow's own, not rules of the status primitive. Lives under `Integration\Contracts`
 * so contract lifecycle stays separate from the renewal money-path.
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
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Cancel a contract.
 */
final class Cancellation {

	/**
	 * Action fired after a contract is cancelled, with `( $contract )`.
	 */
	public const CONTRACT_CANCELLED_ACTION = 'woocommerce_subscriptions_engine_contract_cancelled';

	/**
	 * Action fired after a contract is set to wind down at period end, with `( $contract )`.
	 */
	public const CONTRACT_PENDING_CANCELLATION_ACTION = 'woocommerce_subscriptions_engine_contract_pending_cancellation';

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
	 * Cancel `$contract`: move it to cancelled, disarm its next-due moment, and close any
	 * mid-charge cycle.
	 *
	 * Only an active, on-hold or pending-cancellation contract can be cancelled; cancelling an
	 * already cancelled contract is an idempotent no-op that still succeeds and fires the
	 * action. Any other status - including one that is not registered - raises a
	 * `DomainException`. The next-payment date and any hold anchor are cleared so the due scan
	 * never selects the contract again. When the chain's most-recent cycle is still `pending`
	 * (a charge caught mid-flight) it is transitioned `cancelled` so a stale claim is not left
	 * open; a settled cycle is untouched.
	 *
	 * @param Contract $contract Contract to cancel. Must have an id.
	 * @return bool True when the contract was cancelled and persisted.
	 * @throws RuntimeException If the contract has no id.
	 * @throws \DomainException If the contract cannot be cancelled from its current state, or its state changed concurrently.
	 */
	public function cancel( Contract $contract ): bool {
		$id = $contract->get_id();
		if ( null === $id ) {
			throw new RuntimeException( 'Cancellation::cancel(): cannot cancel a contract that has no id.' );
		}

		$previous   = $contract->get_status();
		$cancelable = array( ContractStatus::ACTIVE, ContractStatus::ON_HOLD, ContractStatus::PENDING_CANCELLATION, ContractStatus::CANCELLED );
		if ( ! in_array( $previous, $cancelable, true ) ) {
			throw new \DomainException( 'Cancellation::cancel(): only an active, on-hold or pending-cancellation contract can be cancelled.' );
		}

		if ( ContractStatus::CANCELLED !== $previous ) {
			$contract->set_status( ContractStatus::CANCELLED );
			$contract->set_next_payment_gmt( null );
			$contract->set_meta( Hold::ANCHOR_META_KEY, null );
		}

		// Compare-and-set on the status read above: a concurrent transition (another
		// request, the renewal engine's settle) makes this write miss loudly rather
		// than be clobbered.
		if ( ! $this->contracts->update_if_status( $contract, $previous ) ) {
			throw new \DomainException( 'Cancellation::cancel(): the contract state changed concurrently; nothing was written.' );
		}

		// Close a charge caught mid-flight: a still-pending head cycle is cancelled so no stale
		// claim is left open. A settled (billed/failed/cancelled) cycle is left as is.
		$current = $this->contracts->find_chain_head( $id );
		if ( null !== $current && $current->get_status()->equals( new CycleStatus( CycleStatus::PENDING ) ) ) {
			$current->set_status( new CycleStatus( CycleStatus::CANCELLED ) );
			$this->contracts->update_cycle( $current );
		}

		/**
		 * Fires after a contract is cancelled.
		 *
		 * @param Contract $contract The cancelled contract.
		 */
		do_action( self::CONTRACT_CANCELLED_ACTION, $contract );

		return true;
	}

	/**
	 * Wind `$contract` down at the end of the current period: move it to
	 * pending-cancellation, stamp the end date, and disarm its next-due moment.
	 *
	 * Only an active or on-hold contract can be wound down; winding down an already
	 * pending-cancellation contract is an idempotent no-op that still succeeds and fires the
	 * action. Any other status - including one that is not registered - raises a
	 * `DomainException`. The contract keeps serving until the current period ends, so the
	 * next-due moment (the next-payment date, or for a held contract the hold anchor) is
	 * recorded as the contract `end_gmt` when not already set, for a first-class "cancels on"
	 * date. The next-payment date and any hold anchor are then cleared, so no renewal fires
	 * while the contract winds down.
	 *
	 * TODO: terminating a PENDING_CANCELLATION contract when its `end_gmt` arrives - moving
	 * it to CANCELLED/EXPIRED at period end - is a follow-up slice. The contract now has no
	 * next-due moment, so it stays PENDING_CANCELLATION (and is never charged) until a later
	 * terminate-at-date pass ends it at its `end_gmt`.
	 *
	 * @param Contract $contract Contract to wind down. Must have an id, and be ACTIVE or ON_HOLD.
	 * @return bool True when the contract was wound down and persisted.
	 * @throws RuntimeException If the contract has no id.
	 * @throws \DomainException If the contract cannot be wound down from its current state, or its state changed concurrently.
	 */
	public function cancel_at_period_end( Contract $contract ): bool {
		$id = $contract->get_id();
		if ( null === $id ) {
			throw new RuntimeException( 'Cancellation::cancel_at_period_end(): cannot cancel a contract that has no id.' );
		}

		$previous = $contract->get_status();
		if ( ! in_array( $previous, array( ContractStatus::ACTIVE, ContractStatus::ON_HOLD, ContractStatus::PENDING_CANCELLATION ), true ) ) {
			throw new \DomainException( 'Cancellation::cancel_at_period_end(): only an active or on-hold contract can be cancelled at period end.' );
		}

		if ( ContractStatus::PENDING_CANCELLATION !== $previous ) {
			$contract->set_status( ContractStatus::PENDING_CANCELLATION );

			// The end of the current period is the next-due moment: the contract is honoured
			// up to (not through) it. A held contract's moment lives in the hold anchor.
			$period_end = $contract->get_next_payment_gmt() ?? ( $contract->get_meta()[ Hold::ANCHOR_META_KEY ] ?? null );
			if ( null === $contract->get_end_gmt() && null !== $period_end && '' !== $period_end ) {
				$contract->set_end_gmt( $period_end );
			}

			$contract->set_meta( Hold::ANCHOR_META_KEY, null );
			$contract->set_next_payment_gmt( null );
		}

		// Compare-and-set on the status read above: a concurrent transition makes this
		// write miss loudly rather than be clobbered.
		if ( ! $this->contracts->update_if_status( $contract, $previous ) ) {
			throw new \DomainException( 'Cancellation::cancel_at_period_end(): the contract state changed concurrently; nothing was written.' );
		}

		/**
		 * Fires after a contract is set to wind down at the end of the current period.
		 *
		 * @param Contract $contract The pending-cancellation contract.
		 */
		do_action( self::CONTRACT_PENDING_CANCELLATION_ACTION, $contract );

		return true;
	}
}
