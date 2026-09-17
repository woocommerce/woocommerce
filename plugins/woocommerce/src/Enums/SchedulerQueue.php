<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the values of the `queue` option accepted by Automattic\WooCommerce\Utilities\Scheduler.
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
}
