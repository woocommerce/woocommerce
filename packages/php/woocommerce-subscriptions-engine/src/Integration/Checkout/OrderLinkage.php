<?php
/**
 * OrderLinkage - the order-side meta keys that link orders to contracts.
 *
 * The order <-> contract relationship is recorded on order meta so it is
 * queryable from the order side without loading the contract:
 *
 *  - `_subscription_contract_id`   (int)    - which contract this order belongs to.
 *  - `_subscription_relation_type` (string) - `parent | renewal | switch | resubscribe`.
 *
 * The contract row carries the reverse direction (`origin_order_id`); these
 * order-meta keys make the relationship symmetric. The engine owns these keys;
 * consumers that need to detect "is this a renewal order?" read them through
 * this class rather than hard-coding the strings.
 *
 * Integration zone: WordPress-native. The keys are written to WooCommerce
 * order meta (`WC_Order::update_meta_data()`), which works under both HPOS and
 * the legacy CPT order store.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Contract <-> order meta-key catalogue.
 */
final class OrderLinkage {

	/**
	 * Order meta key holding the contract id this order belongs to.
	 *
	 * Stored as a stringified integer (order meta is a flat string table). Read
	 * via `(int) $order->get_meta( OrderLinkage::META_CONTRACT_ID )`.
	 */
	const META_CONTRACT_ID = '_subscription_contract_id';

	/**
	 * Order meta key holding the relation type - see the `RELATION_*` constants.
	 */
	const META_RELATION_TYPE = '_subscription_relation_type';

	/**
	 * The order whose checkout created the contract - the contract row's
	 * `origin_order_id`. Tagged on the order side too so the relationship is
	 * queryable from either direction.
	 */
	const RELATION_PARENT = 'parent';

	/**
	 * A renewal order - created by the renewal engine when a cycle bills.
	 */
	const RELATION_RENEWAL = 'renewal';

	/**
	 * A switch order - customer moved between plans (later milestone).
	 */
	const RELATION_SWITCH = 'switch';

	/**
	 * A resubscribe order - customer restarted a previously-cancelled contract
	 * (later milestone).
	 */
	const RELATION_RESUBSCRIBE = 'resubscribe';

	/**
	 * All recognized relation types.
	 *
	 * @return array<int, string>
	 */
	public static function relation_types(): array {
		return array(
			self::RELATION_PARENT,
			self::RELATION_RENEWAL,
			self::RELATION_SWITCH,
			self::RELATION_RESUBSCRIBE,
		);
	}

	/**
	 * Throw if `$relation` is not one of the known relation types.
	 *
	 * Centralising the check keeps callers from querying for a typoed relation
	 * (`'renewals'`, `'parent_order'`) and silently getting an empty result.
	 *
	 * @param string $relation Candidate relation type.
	 * @throws InvalidArgumentException If `$relation` is not recognized.
	 */
	public static function assert_relation( string $relation ): void {
		if ( ! in_array( $relation, self::relation_types(), true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Unknown contract-order relation type: "%s". Expected one of: %s.',
					esc_html( $relation ),
					esc_html( implode( ', ', self::relation_types() ) )
				)
			);
		}
	}
}
