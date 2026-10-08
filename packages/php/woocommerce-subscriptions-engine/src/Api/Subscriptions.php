<?php
/**
 * Subscriptions - the engine's interim lifecycle and renewal facade.
 *
 * Cancel, hold, reactivate, renew now, and read a contract's related orders. It hides
 * the internal `Core\` / `Integration\` collaborators behind a stable boundary.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\RelatedOrders;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Cancellation;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Hold;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Reactivation;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalEngine;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Public subscriptions facade.
 *
 * Interim: removed once lifecycle flows and renewals move to extensions. Add no new
 * methods here; contract reads and writes live in {@see Contracts}.
 *
 * Final and static-only: a stateless entry point, not an extension seam.
 */
final class Subscriptions {

	/**
	 * The orders related to a contract (the origin order, plus renewals / switches /
	 * resubscribes), newest first - the portal detail's related-orders read kept
	 * facade-only so a consumer never reaches into the order-linkage internals.
	 *
	 * Returns live `WC_Order` objects; presentation shaping is the caller's job.
	 * A long-running contract accumulates one renewal order per period, so paging
	 * consumers pass a window; the default stays "all".
	 *
	 * @param int $contract_id Contract id.
	 * @param int $limit       Maximum orders to return; any negative (default -1) for all, 0 for none.
	 * @param int $offset      Orders to skip (for paging). Default 0.
	 * @return array<int, WC_Order> Related orders, newest first.
	 */
	public static function get_related_orders( int $contract_id, int $limit = -1, int $offset = 0 ): array {
		return ( new RelatedOrders() )->for_contract( $contract_id, $limit, $offset );
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
	 * Put a subscription contract on hold (suspend billing).
	 *
	 * @param int $contract_id Contract id.
	 * @return bool True when the contract was found and held; false when not found.
	 * @throws \DomainException If the contract cannot be held from its current state.
	 */
	public static function hold( int $contract_id ): bool {
		$contract = ( new ContractRepository() )->find( $contract_id );
		if ( null === $contract ) {
			return false;
		}

		return ( new Hold() )->hold( $contract );
	}

	/**
	 * Reactivate a held subscription contract (resume billing, recompute the next date).
	 *
	 * @param int $contract_id Contract id.
	 * @return bool True when the contract was found and reactivated; false when not found.
	 * @throws \DomainException If the contract cannot be reactivated from its current state.
	 */
	public static function reactivate( int $contract_id ): bool {
		$contract = ( new ContractRepository() )->find( $contract_id );
		if ( null === $contract ) {
			return false;
		}

		return ( new Reactivation() )->reactivate( $contract );
	}

	/**
	 * Cancel a subscription contract at the end of the current billing period.
	 *
	 * @param int $contract_id Contract id.
	 * @return bool True when the contract was found and wound down; false when not found.
	 * @throws \DomainException If the contract cannot be wound down from its current state.
	 */
	public static function cancel_at_period_end( int $contract_id ): bool {
		$contract = ( new ContractRepository() )->find( $contract_id );
		if ( null === $contract ) {
			return false;
		}

		return ( new Cancellation() )->cancel_at_period_end( $contract );
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
