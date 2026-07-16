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
 * Final and static-only: a stateless entry point, not an extension seam.
 */
final class Subscriptions {

	/**
	 * Fetch a subscription contract by id, with its frozen plan terms hydrated.
	 *
	 * The returned contract carries its plan snapshot ({@see Contract::get_plan_snapshot()}),
	 * so a consumer reads the billing cadence straight off the snapshot - no live
	 * plan-repository join.
	 *
	 * @param int $contract_id Contract id.
	 * @return Contract|null The contract, or null when none exists.
	 */
	public static function get( int $contract_id ): ?Contract {
		return ( new ContractRepository() )->find( $contract_id );
	}

	/**
	 * List subscription contracts for an admin list screen - newest first by default, or
	 * filtered / sorted / paged / searched via a WooCommerce-style args array (cf.
	 * `wc_get_orders()`). The status + search filter matches {@see self::count()}, so a page
	 * and its total describe the same set.
	 *
	 * @param array<string, mixed> $args {
	 *     Optional. Query args.
	 *
	 *     @type int    $limit   Maximum contracts to return. Default 20.
	 *     @type int    $offset  Contracts to skip (for paging). Default 0.
	 *     @type string $status  Filter to one status ({@see \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus}); ignored when empty or invalid.
	 *     @type string $orderby One of id, next_payment, total, start; default id.
	 *     @type string $order   ASC or DESC (case-insensitive); default DESC.
	 *     @type string $search  Numeric term matches contract id or origin order id; text term matches the owning customer.
	 * }
	 * @return array<int, Contract> Contracts in the requested order.
	 */
	public static function list( array $args = array() ): array {
		return ( new ContractRepository() )->query( $args );
	}

	/**
	 * The contract count per status - the read behind an admin list's status views bar.
	 * Keyed by every {@see \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus} value (absent statuses are 0); the `All` total
	 * is the caller's `array_sum()`. Independent of any search or paging.
	 *
	 * @return array<string, int> Status => count, every known status present.
	 */
	public static function count_by_status(): array {
		return ( new ContractRepository() )->count_by_status();
	}

	/**
	 * The number of contracts matching a list filter - the total behind a list view's
	 * pagination. Honours the SAME status + search args as {@see self::list()} and ignores
	 * paging / sort.
	 *
	 * @param array<string, mixed> $args Query args (only `status` and `search` are read).
	 * @return int The matching contract count.
	 */
	public static function count( array $args = array() ): int {
		return ( new ContractRepository() )->count( $args );
	}

	/**
	 * The line-item count for a page of contracts - the read behind an admin list's
	 * "Items" column. One grouped scan over the given ids, returned as a map keyed by
	 * every requested id (ids with no items are 0), so a list renders an items count
	 * per row without a per-row query. Ids are de-duplicated and int-cast.
	 *
	 * @param array<int, int> $contract_ids Contract ids to count items for.
	 * @return array<int, int> Contract id => line-item count, one entry per requested id.
	 */
	public static function item_counts( array $contract_ids ): array {
		return ( new ContractRepository() )->count_items_by_contract( $contract_ids );
	}

	/**
	 * List a single customer's subscription contracts, newest first - the customer
	 * portal's owner-scoped list read.
	 *
	 * Owner-scoped by construction: the customer id is supplied by the caller (the
	 * authenticated user at the REST boundary), never inferred, so it never returns
	 * another customer's contracts. Returns interim {@see Contract} entities, each with its
	 * frozen plan terms hydrated ({@see Contract::get_plan_snapshot()}) so a list row's
	 * cadence is read off the snapshot.
	 *
	 * @param int $customer_id Owning customer id.
	 * @param int $limit       Maximum contracts to return.
	 * @param int $offset      Contracts to skip (for paging).
	 * @return array<int, Contract> The customer's contracts, newest first.
	 */
	public static function list_for_customer( int $customer_id, int $limit = 20, int $offset = 0 ): array {
		return ( new ContractRepository() )->find_by_customer_id(
			$customer_id,
			array(
				'limit'  => $limit,
				'offset' => $offset,
			)
		);
	}

	/**
	 * Fetch a contract a customer owns - the customer portal's ownership-checked read.
	 *
	 * Returns null for BOTH an unknown id AND a contract owned by another customer (the
	 * asymmetric not-found rule), so a caller cannot probe for the existence of a
	 * contract it does not own.
	 *
	 * The returned contract carries its frozen plan terms ({@see Contract::get_plan_snapshot()}),
	 * so the cadence is read off the snapshot with no live plan-repository join.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $customer_id Customer that must own the contract.
	 * @return Contract|null The contract when owned by `$customer_id`, else null.
	 * @phpstan-impure
	 */
	public static function get_for_customer( int $contract_id, int $customer_id ): ?Contract {
		return ( new ContractRepository() )->find_for_customer( $contract_id, $customer_id );
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
