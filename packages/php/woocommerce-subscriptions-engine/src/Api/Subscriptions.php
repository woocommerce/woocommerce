<?php
/**
 * Subscriptions - the engine's interim renewal facade.
 *
 * Renew now and read a contract's related orders. It hides the internal `Core\` /
 * `Integration\` collaborators behind a stable boundary.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\RelatedOrders;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalEngine;

defined( 'ABSPATH' ) || exit;

/**
 * Public subscriptions facade.
 *
 * Interim: removed once renewals move to extensions. Add no new methods here;
 * contract reads and writes live in {@see Contracts}.
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
