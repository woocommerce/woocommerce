<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the values of the `queue` option accepted by Automattic\WooCommerce\Queue\Scheduler.
 */
final class SchedulerQueue {
	/**
	 * Route through the active queue, `WC()->queue()`, honouring the `woocommerce_queue_class` filter.
	 *
	 * @var string
	 */
	public const ACTIVE = 'active';

	/**
	 * Route through the stock queue, ignoring a custom queue attached through `woocommerce_queue_class`.
	 * Only for callers that depend on Action Scheduler specifically.
	 *
	 * @var string
	 */
	public const DEFAULT = 'default';

	/**
	 * Returns every constant on this class, as a flat list of the option values.
	 *
	 * Unlike the product and order enums there is no filterable helper beside this one, so the list
	 * is the complete set of values the Scheduler accepts.
	 *
	 * @since 11.3.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::ACTIVE,
			self::DEFAULT,
		);
	}
}
