<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * A class of utilities for dealing with Action Scheduler across the versions of it that may be loaded.
 */
class ActionSchedulerUtil {

	/**
	 * Is a matching action currently scheduled (pending or in-progress)?
	 *
	 * Prefers `as_has_scheduled_action`, which only exists since Action Scheduler 3.3.0: another plugin
	 * can load an older copy early enough to win the version race against the one bundled with
	 * WooCommerce, and a bare call is then a fatal. Falls back to the much older `as_next_scheduled_action`,
	 * which on such a copy may report only pending actions, not in-progress ones - an accepted trade.
	 *
	 * Reports false when Action Scheduler is not loaded at all, indistinguishable from "nothing is
	 * scheduled", and raises a doing-it-wrong notice so the condition is visible under debugging.
	 * A caller that treats a negative answer as licence to discard state should check
	 * {@see self::can_check_scheduled_actions()} first.
	 *
	 * Delegates to the stock queue, which owns this logic; new code should use
	 * Automattic\WooCommerce\Utilities\Scheduler::has_scheduled_action() instead.
	 *
	 * @since 11.2.0
	 *
	 * @param string     $hook  The hook of the action.
	 * @param array|null $args  Args that have been passed to the action. Null matches any args.
	 * @param string     $group The group the action is assigned to.
	 *
	 * @return bool True if a matching action is scheduled, false otherwise.
	 */
	public static function has_scheduled_action( string $hook, ?array $args = null, string $group = '' ): bool {
		return ( new \WC_Options_Aware_Action_Queue() )->has_scheduled_action( $hook, $args, $group );
	}

	/**
	 * Can {@see self::has_scheduled_action()} actually query Action Scheduler, or would it report false
	 * only because neither function it relies on is loaded?
	 *
	 * @since 11.2.0
	 *
	 * @return bool True if a scheduled-action check can be answered, false otherwise.
	 */
	public static function can_check_scheduled_actions(): bool {
		return function_exists( 'as_has_scheduled_action' ) || function_exists( 'as_next_scheduled_action' );
	}
}
