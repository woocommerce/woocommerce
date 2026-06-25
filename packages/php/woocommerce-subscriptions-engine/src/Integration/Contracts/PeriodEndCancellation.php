<?php
/**
 * PeriodEndCancellation - wind a subscription contract down at the end of the current
 * billing period (graceful cancel).
 *
 * A focused contract-management operation (deliberately not a catch-all manager),
 * mirroring {@see Cancellation}: transition the contract ACTIVE -> PENDING_CANCELLATION
 * through the Core state machine, record the contract end date as the current
 * next-payment moment, and announce it. The next-payment moment is deliberately left in
 * place so the contract lapses at that date; the batch due scan does not charge a
 * non-active contract in the meantime. For an immediate cancel, use {@see Cancellation}
 * instead. Lives under `Integration\Contracts` so contract lifecycle stays separate from
 * the renewal money-path.
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
 * Cancel a contract at the end of the current billing period.
 */
final class PeriodEndCancellation {

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
	 */
	public function cancel_at_period_end( Contract $contract ): bool {
		$id = $contract->get_id();
		if ( null === $id ) {
			throw new RuntimeException( 'PeriodEndCancellation::cancel_at_period_end(): cannot cancel a contract that has no id.' );
		}

		$contract->set_status( ContractStatus::PENDING_CANCELLATION );

		// The end of the current period is the next-payment moment: the contract is
		// honoured up to (not through) it. Record it as the contract end when not already
		// set, so reads have a first-class "cancels on" date.
		if ( null === $contract->get_end_gmt() && null !== $contract->get_next_payment_gmt() ) {
			$contract->set_end_gmt( $contract->get_next_payment_gmt() );
		}

		$this->contracts->update( $contract );

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
