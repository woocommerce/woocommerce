<?php
/**
 * Cancellation - cancel a subscription contract.
 *
 * A focused contract-management operation (deliberately not a catch-all manager):
 * transition the contract to cancelled, close any charge caught mid-flight, clear its
 * pending renewal, and announce it. Lives under `Integration\Contracts` so contract
 * lifecycle stays separate from the renewal money-path.
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
	 */
	public function cancel( Contract $contract ): bool {
		$id = $contract->get_id();
		if ( null === $id ) {
			throw new RuntimeException( 'Cancellation::cancel(): cannot cancel a contract that has no id.' );
		}

		$contract->set_status( ContractStatus::CANCELLED );
		$this->contracts->update( $contract );

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
}
