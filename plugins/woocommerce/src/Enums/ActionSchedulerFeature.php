<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the Action Scheduler features WooCommerce core relies on and that an older loaded copy may lack.
 *
 * Probed by {@see \Automattic\WooCommerce\Internal\Utilities\ActionSchedulerUtil::supports()}.
 */
final class ActionSchedulerFeature {

	/**
	 * The `$unique` argument of the scheduling functions. Added in Action Scheduler 3.5.0.
	 *
	 * @var string
	 */
	public const UNIQUE = 'unique';

	/**
	 * The `$priority` argument of the scheduling functions. Added in Action Scheduler 3.6.0.
	 *
	 * @var string
	 */
	public const PRIORITY = 'priority';

	/**
	 * Unique actions that take the action args into account, so actions that differ only by args can
	 * coexist. Added in Action Scheduler 4.0.0; earlier copies check uniqueness by hook and group alone.
	 *
	 * @var string
	 */
	public const UNIQUE_ARGS = 'unique_args';

	/**
	 * The `as_has_scheduled_action()` function, which sees pending and in-progress actions. Added in Action Scheduler 3.3.0.
	 *
	 * @var string
	 */
	public const HAS_SCHEDULED_ACTION = 'has_scheduled_action';

	/**
	 * The `as_enqueue_async_action()` function. Added in Action Scheduler 3.0.0.
	 *
	 * @var string
	 */
	public const ASYNC = 'async';
}
