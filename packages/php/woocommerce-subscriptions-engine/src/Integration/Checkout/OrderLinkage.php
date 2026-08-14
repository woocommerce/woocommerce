<?php
/**
 * Order-side meta keys linking orders to contracts, making the relationship
 * queryable from the order side (the contract row carries the reverse
 * `origin_order_id`). The engine owns these keys; consumers read them through
 * this class rather than hard-coding the strings.
 *
 * Written to WooCommerce order meta, which works under both HPOS and the legacy
 * CPT order store.
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
	 * Stored as a stringified integer (order meta is a flat string table).
	 */
	public const META_CONTRACT_ID = '_subscription_contract_id';

	/**
	 * Order meta key holding the relation type - see the `RELATION_*` constants.
	 */
	public const META_RELATION_TYPE = '_subscription_relation_type';

	/**
	 * The order whose checkout created the contract (the contract's `origin_order_id`).
	 */
	public const RELATION_PARENT = 'parent';

	/**
	 * A renewal order - created by the renewal engine when a cycle bills.
	 */
	public const RELATION_RENEWAL = 'renewal';

	/**
	 * A switch order - customer moved between plans.
	 */
	public const RELATION_SWITCH = 'switch';

	/**
	 * A resubscribe order - customer restarted a previously-cancelled contract.
	 */
	public const RELATION_RESUBSCRIBE = 'resubscribe';

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
	 * Throw if `$relation` is not one of the known relation types, so a typoed
	 * relation fails loudly rather than silently querying to an empty result.
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
