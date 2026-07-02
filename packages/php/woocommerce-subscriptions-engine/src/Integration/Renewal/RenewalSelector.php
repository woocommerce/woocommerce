<?php
/**
 * RenewalSelector - read-only selection: given a contract's head-cycle fields, decide
 * which cycle to bill, or nothing. It performs no writes and touches no gateway; it turns
 * a {@see RenewalCandidate} into the cycle number to bill, or null to skip. The caller
 * builds the {@see RenewalIntent} the money-path executes.
 *
 * It encodes two selection policies over the same `process()` primitive (which bills whatever
 * cycle it is handed and owns no due policy):
 *
 * - scheduled ({@see self::select_scheduled_cycle()}): advance to the next cycle once the current
 *   period has ended (the due-guard), or retry a still-in-flight head. The guard anchors on the
 *   head's `ends_at_gmt` - immutable once settled - so it is race-free: an overlapping run that
 *   reads a just-billed head sees its end still in the future and does not charge ahead.
 * - admin-triggered ({@see self::select_manual_cycle()}): force the next cycle regardless of the
 *   due-guard, or retry a failed/stalled head - the admin is deciding, not the schedule.
 *
 * WordPress-free by construction: `$now` is passed in.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal;

use DateTimeImmutable;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\RenewalCandidate;

defined( 'ABSPATH' ) || exit;

/**
 * The renewal cycle selector - scheduled and admin-triggered policies.
 */
final class RenewalSelector {

	/**
	 * Resolve the cycle a scheduled renewal should bill for this candidate, or null to skip.
	 *
	 * @param RenewalCandidate  $candidate The candidate (contract + head fields).
	 * @param DateTimeImmutable $now       The scan moment.
	 * @return int|null The cycle count to bill, or null when nothing is due.
	 */
	public function select_scheduled_cycle( RenewalCandidate $candidate, DateTimeImmutable $now ): ?int {
		$count = $candidate->get_head_count();
		if ( null === $count ) {
			// A countless head is a corrupt chain the scan should not surface; refuse to guess.
			return null;
		}

		$status = $candidate->get_head_status();

		// Head settled forward: advance to the next cycle, but only once its period has begun.
		// The guard is the charge-ahead defence - a just-billed head whose period runs into the
		// future is not yet due for its successor.
		if ( CycleStatus::BILLED === $status || CycleStatus::CANCELLED === $status ) {
			if ( ! self::has_period_ended( $candidate->get_head_ends_at_gmt(), $now ) ) {
				return null;
			}
			return $count + 1;
		}

		// Head still in flight: retry the same cycle. The money-path reclaims a stalled one
		// (the scan only surfaces a pending head whose crash-recovery lease has expired).
		if ( CycleStatus::PENDING === $status ) {
			return $count;
		}

		// failed (awaits dunning) / processing (awaits its gateway): not selectable here. The
		// scan already excludes them; this is a defensive skip.
		return null;
	}

	/**
	 * Resolve the cycle a manual (admin-triggered) renewal should bill for this candidate, or
	 * null to skip. Unlike the scheduled path this applies no due-guard - the admin is forcing
	 * the renewal - so a settled head advances to the next cycle even before its period ends,
	 * while a failed or still-pending head is re-attempted at its own count. A `processing` head
	 * (awaiting its gateway) or a countless head is not manually renewable.
	 *
	 * @param RenewalCandidate $candidate The candidate (contract + head fields).
	 * @return int|null The cycle count to bill, or null when nothing is renewable.
	 */
	public function select_manual_cycle( RenewalCandidate $candidate ): ?int {
		$count = $candidate->get_head_count();
		if ( null === $count ) {
			return null;
		}

		$status = $candidate->get_head_status();

		// Settled forward: force the next cycle. Its period continues from the previous end, so
		// the schedule is preserved (a prepay), not reset to now.
		if ( CycleStatus::BILLED === $status || CycleStatus::CANCELLED === $status ) {
			return $count + 1;
		}

		// Failed (retry) or still in flight (reclaim a stalled one): re-attempt the same cycle.
		if ( CycleStatus::FAILED === $status || CycleStatus::PENDING === $status ) {
			return $count;
		}

		// processing: awaiting its gateway - a manual trigger cannot preempt an in-flight charge.
		return null;
	}

	/**
	 * Whether a period ending at `$ends_at_gmt` has ended by `$now` - the scheduled due-guard.
	 * An unparseable end is treated as not ended (never charge ahead on bad data). Internal to
	 * selection: `process()` bills whatever cycle it is handed and applies no due policy of its own.
	 *
	 * @param string            $ends_at_gmt The head period end (GMT string).
	 * @param DateTimeImmutable $now         The scan moment.
	 */
	private static function has_period_ended( string $ends_at_gmt, DateTimeImmutable $now ): bool {
		$ends_at = strtotime( $ends_at_gmt . ' UTC' );
		return false !== $ends_at && $ends_at <= $now->getTimestamp();
	}
}
