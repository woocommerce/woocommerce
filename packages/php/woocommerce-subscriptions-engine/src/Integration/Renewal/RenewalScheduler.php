<?php
/**
 * RenewalScheduler - the superseded one-job-per-contract Action Scheduler bridge.
 *
 * @deprecated Superseded by the batch {@see RenewalDispatcher}, which scans the
 * contract due-index on one recurring action instead of enqueuing a row per
 * contract. {@see RenewalEngine::schedule()} no longer enqueues here; the only
 * remaining use is {@see self::unschedule()} / {@see self::is_scheduled()} to
 * clear or inspect any pre-existing per-contract rows. Left in place (unused for
 * new scheduling) to drain leftovers; slated for removal once the consumer rewire
 * lands.
 *
 * Owns the AS hook and group conventions for those legacy rows. Integration zone:
 * WordPress-native. Calls Action Scheduler's `as_*()` functions directly.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal;

use DateTimeImmutable;
use DateTimeZone;

defined( 'ABSPATH' ) || exit;

/**
 * Action Scheduler bridge for renewals.
 */
final class RenewalScheduler {

	/**
	 * Action Scheduler hook fired when a contract's renewal is due.
	 *
	 * Public so tooling and tests can inspect or cancel pending actions via
	 * `as_has_scheduled_action()` and friends.
	 */
	public const HOOK = 'woocommerce_subscriptions_engine_process_renewal';

	/**
	 * Action Scheduler group - used for admin filterability (Tools ->
	 * Scheduled Actions) and bulk teardown.
	 */
	public const GROUP = 'woocommerce_subscriptions_engine';

	/**
	 * Enqueue an AS action for `$contract_id` at `$when`.
	 *
	 * Does NOT clear pre-existing pending actions - {@see RenewalEngine::schedule()}
	 * owns the clear-then-enqueue that keeps the single-row-per-contract
	 * invariant. Calling this directly without first unscheduling will produce
	 * duplicate rows.
	 *
	 * `$when->getTimestamp()` is a UTC unix timestamp regardless of the
	 * argument's timezone, so the dispatch moment is unambiguous.
	 *
	 * @param int               $contract_id Contract whose renewal is being scheduled.
	 * @param DateTimeImmutable $when        When the renewal should fire.
	 */
	public static function schedule( int $contract_id, DateTimeImmutable $when ): void {
		as_schedule_single_action(
			$when->getTimestamp(),
			self::HOOK,
			array( $contract_id ),
			self::GROUP
		);
	}

	/**
	 * Cancel any pending AS action for `$contract_id`.
	 *
	 * AS matches on hook + args + group, so passing `[ $contract_id ]` scopes
	 * the cancel to that one contract. No-op when nothing is pending.
	 *
	 * @param int $contract_id Contract whose pending renewal to clear.
	 */
	public static function unschedule( int $contract_id ): void {
		as_unschedule_all_actions( self::HOOK, array( $contract_id ), self::GROUP );
	}

	/**
	 * Whether a renewal action is currently pending for `$contract_id`.
	 *
	 * @param int $contract_id Contract to query.
	 */
	public static function is_scheduled( int $contract_id ): bool {
		return false !== as_next_scheduled_action( self::HOOK, array( $contract_id ), self::GROUP );
	}

	/**
	 * The moment the next pending renewal will fire for `$contract_id`, or null
	 * if nothing is queued.
	 *
	 * AS returns an int unix timestamp (UTC) for a pending action, or
	 * `false` / `0` when nothing future is queued.
	 *
	 * @param int $contract_id Contract to query.
	 * @return DateTimeImmutable|null UTC moment of the next renewal, or null.
	 */
	public static function next_scheduled( int $contract_id ): ?DateTimeImmutable {
		$timestamp = as_next_scheduled_action( self::HOOK, array( $contract_id ), self::GROUP );
		if ( ! is_int( $timestamp ) || $timestamp <= 0 ) {
			return null;
		}

		return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( new DateTimeZone( 'UTC' ) );
	}
}
