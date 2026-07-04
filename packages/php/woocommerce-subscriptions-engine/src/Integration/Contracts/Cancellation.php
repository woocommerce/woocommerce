<?php
/**
 * Cancellation - cancel a subscription contract, immediately or at period end.
 *
 * A focused contract-management operation (deliberately not a catch-all manager)
 * covering the one cancel intent in its two modes: {@see self::cancel()} tears the
 * contract down NOW (transition to cancelled, close any charge caught mid-flight,
 * announce it), while {@see self::cancel_at_period_end()} winds it down gracefully
 * (transition to pending-cancellation, stamp the end date, keep serving until the
 * period lapses). Lives under `Integration\Contracts` so contract lifecycle stays
 * separate from the renewal money-path.
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
	 * Cancel `$contract`: transition to cancelled and close any mid-charge cycle.
	 *
	 * Status moves through the Core state machine ({@see Contract::set_status()}), which raises
	 * a `DomainException` on an illegal transition. When the chain's most-recent cycle is still
	 * `pending` (a charge caught mid-flight) it is transitioned `cancelled` so a stale claim is
	 * not left open; a settled cycle is untouched. The due scan only selects active contracts,
	 * so a cancelled contract simply stops being picked up - there is no per-contract schedule
	 * to clear.
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

		$previous = $contract->get_status();
		$contract->set_status( ContractStatus::CANCELLED );

		// Compare-and-set on the status read above: a concurrent transition (another
		// request, the renewal engine's settle) makes this write miss loudly rather
		// than be clobbered.
		if ( ! $this->contracts->update_if_status( $contract, $previous ) ) {
			throw new \DomainException( 'Cancellation::cancel(): the contract state changed concurrently; nothing was written.' );
		}

		// Close a charge caught mid-flight: a still-pending head cycle is cancelled so no stale
		// claim is left open. A settled (billed/failed/cancelled) cycle is left as is.
		$current = $this->contracts->find_chain_head( $id );
		if ( null !== $current && $current->get_status()->equals( CycleStatus::pending() ) ) {
			$current->set_status( CycleStatus::cancelled() );
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
	 * Wind `$contract` down at the end of the current period: transition to
	 * pending-cancellation and stamp the end date.
	 *
	 * Status moves through the Core state machine ({@see Contract::set_status()}), which
	 * raises a `DomainException` on an illegal transition. The contract keeps serving
	 * until the current period ends, so the next-payment moment is recorded as the
	 * contract `end_gmt` (when not already set) for a first-class "cancels on" date, and
	 * the next-payment date is deliberately LEFT in place so the contract lapses at the
	 * date rather than being torn down now.
	 *
	 * The due scan already refuses to charge a non-active contract ({@see RenewalEngine::process()}
	 * skips it with no order), so no renewal fires while it winds down.
	 *
	 * TODO: terminating a PENDING_CANCELLATION contract (ACTIVE has lapsed) when its date
	 * arrives - moving it to CANCELLED/EXPIRED at period end - is a follow-up slice. The
	 * current dispatcher only skips a non-active contract; it does not yet transition it
	 * terminal at the date, so a wound-down contract stays PENDING_CANCELLATION until a
	 * later terminate-at-date pass lands. No charge occurs in the meantime.
	 *
	 * @param Contract $contract Contract to wind down. Must have an id, and be ACTIVE.
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
		$contract->set_status( ContractStatus::PENDING_CANCELLATION );

		// The end of the current period is the next-payment moment: the contract is
		// honoured up to (not through) it. Record it as the contract end when not already
		// set, so reads have a first-class "cancels on" date.
		if ( null === $contract->get_end_gmt() && null !== $contract->get_next_payment_gmt() ) {
			$contract->set_end_gmt( $contract->get_next_payment_gmt() );
		}

		// Compare-and-set on the status read above: a concurrent transition makes this
		// write miss loudly rather than be clobbered.
		if ( ! $this->contracts->update_if_status( $contract, $previous ) ) {
			throw new \DomainException( 'Cancellation::cancel_at_period_end(): the contract state changed concurrently; nothing was written.' );
		}

		// Intentionally leave the next-payment date in place: the contract lapses at the date (see the TODO above).

		/**
		 * Fires after a contract is set to wind down at the end of the current period.
		 *
		 * @param Contract $contract The pending-cancellation contract.
		 */
		do_action( self::CONTRACT_PENDING_CANCELLATION_ACTION, $contract );

		return true;
	}
}
