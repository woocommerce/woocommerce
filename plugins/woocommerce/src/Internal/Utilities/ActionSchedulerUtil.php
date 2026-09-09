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
	 * `as_has_scheduled_action` is the cheapest way to answer this, but it only exists since Action
	 * Scheduler 3.3.0. Another plugin can register an older copy of Action Scheduler early enough to
	 * win the version race against the copy bundled with WooCommerce, so calling it directly risks a
	 * `Call to undefined function` fatal. `as_next_scheduled_action` has been part of the API for far
	 * longer, so it is the fallback - the same one WooCommerce already used at the call sites that
	 * were guarded before this helper existed. On a copy old enough to lack `as_has_scheduled_action`
	 * the fallback may only report pending actions rather than pending and in-progress ones, which
	 * can mean a duplicate scheduling attempt on an already-degraded install. That is the accepted
	 * trade for not fataling.
	 *
	 * @since 11.2.0
	 *
	 * @param string     $hook  The hook of the action.
	 * @param array|null $args  Args that have been passed to the action. Null matches any args.
	 * @param string     $group The group the action is assigned to.
	 *
	 * When Action Scheduler is not loaded at all this reports false, which a caller cannot tell apart
	 * from a genuine "nothing is scheduled". That is fine for the usual shape of caller, which
	 * schedules the action immediately afterwards and would fail on that call anyway. A caller that
	 * instead treats a negative answer as licence to discard state should check {@see self::is_available()}
	 * first.
	 *
	 * @return bool True if a matching action is scheduled, false otherwise (including when Action Scheduler isn't loaded at all).
	 */
	public static function has_scheduled_action( string $hook, ?array $args = null, string $group = '' ): bool {
		foreach ( array( 'as_has_scheduled_action', 'as_next_scheduled_action' ) as $function ) {
			// PHPStan sees the Action Scheduler copy bundled with WooCommerce and concludes both functions
			// always exist. The runtime case this guard exists for is precisely the one it cannot see.
			// @phpstan-ignore-next-line function.alreadyNarrowedType -- see comment above.
			if ( function_exists( $function ) ) {
				// `as_next_scheduled_action` returns the timestamp of the next pending action, hence the cast.
				return (bool) $function( $hook, $args, $group );
			}
		}

		return false;
	}

	/**
	 * Is Action Scheduler loaded well enough to answer a scheduled-action question?
	 *
	 * Lets a caller distinguish "no matching action" from "no answer available" before acting
	 * destructively on a false from {@see self::has_scheduled_action()}.
	 *
	 * @since 11.2.0
	 *
	 * @return bool True if Action Scheduler is available, false otherwise.
	 */
	public static function is_available(): bool {
		return function_exists( 'as_has_scheduled_action' ) || function_exists( 'as_next_scheduled_action' );
	}
}
