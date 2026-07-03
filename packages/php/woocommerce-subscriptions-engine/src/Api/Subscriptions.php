<?php
/**
 * Subscriptions - the engine's public consumer facade.
 *
 * The one surface consumers (a host plugin's admin UI, tests) import to read and act
 * on subscriptions: read the contract and its cycle history, cancel, and run a renewal
 * now. It hides the internal `Core\` / `Integration\` collaborators (the repository,
 * the renewal engine) behind a stable boundary, so the internals stay refactorable.
 *
 * Interim return types: it returns the core entities ({@see Contract}, {@see Cycle})
 * and `WC_Order` directly for now; richer read-model views are a planned follow-up, so
 * consumers also reference those types until the views land. `Api\` is the public
 * surface, not a third internal zone - the two-zone (Core/Integration) model still
 * describes the internals.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Cancellation;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalEngine;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Public subscriptions facade.
 *
 * Final and static-only: a stateless entry point, not an extension seam.
 */
final class Subscriptions {

	/**
	 * Fetch a subscription contract by id.
	 *
	 * @param int $contract_id Contract id.
	 * @return Contract|null The contract, or null when none exists.
	 */
	public static function get( int $contract_id ): ?Contract {
		return ( new ContractRepository() )->find( $contract_id );
	}

	/**
	 * List the most-recent subscription contracts, newest first.
	 *
	 * @param int $limit  Maximum contracts to return.
	 * @param int $offset Contracts to skip (for paging).
	 * @return array<int, Contract> Contracts newest first.
	 */
	public static function list( int $limit = 20, int $offset = 0 ): array {
		return ( new ContractRepository() )->query(
			array(
				'limit'  => $limit,
				'offset' => $offset,
			)
		);
	}

	/**
	 * Fetch a window of the contract's billing cycle history, newest first.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $limit       Maximum cycles to return.
	 * @return array<int, Cycle> Cycles newest first.
	 */
	public static function get_history( int $contract_id, int $limit = 20 ): array {
		return ( new ContractRepository() )->find_cycle_history( $contract_id, Cycle::KIND_BILLING, $limit );
	}

	/**
	 * Cancel a subscription contract.
	 *
	 * @param int $contract_id Contract id.
	 * @return bool True when the contract was found and cancelled; false when not found.
	 */
	public static function cancel( int $contract_id ): bool {
		$contract = ( new ContractRepository() )->find( $contract_id );
		if ( null === $contract ) {
			return false;
		}

		return ( new Cancellation() )->cancel( $contract );
	}

	/**
	 * Renew the contract now on an admin's request, regardless of the schedule. A settled cycle
	 * is billed ahead of its due date (the period continues from the previous end, so the schedule
	 * is preserved); a failed cycle is retried. Returns null when the contract is not renewable
	 * (no chain, awaiting a gateway, or inactive).
	 *
	 * @param int $contract_id Contract id.
	 * @return WC_Order|null The renewal order, or null when the renewal was skipped.
	 */
	public static function renew_now( int $contract_id ): ?WC_Order {
		return ( new RenewalEngine() )->renew_now( $contract_id );
	}
}
