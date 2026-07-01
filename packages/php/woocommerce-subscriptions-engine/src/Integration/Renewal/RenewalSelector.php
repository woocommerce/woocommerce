<?php
/**
 * RenewalSelector - read-only selection: given a due contract's head cycle and the scan
 * moment, decide which cycle to bill, or nothing. It performs no writes and touches no
 * gateway; it turns a {@see DueRenewal} scan row into a {@see RenewalIntent} the money-path
 * executes, or null to skip.
 *
 * This encodes the scheduled-renewal policy: advance to the next cycle once the current
 * period has ended (the due-guard), or retry a still-in-flight head. The guard anchors on
 * the head cycle's `ends_at_gmt` - immutable once the cycle is settled - so it is race-free:
 * an overlapping run that reads a just-billed head sees its end still in the future and does
 * not charge the next cycle ahead of time. A future trigger (admin retry, customer early
 * renewal) is a different selector producing an intent over the same processing path; only
 * the policy here (which cycle, whether the guard applies) differs.
 *
 * Integration zone, but WordPress-free by construction: `$now` is passed in.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal;

use DateTimeImmutable;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\DueRenewal;

defined( 'ABSPATH' ) || exit;

/**
 * The scheduled-renewal cycle selector.
 */
final class RenewalSelector {

	/**
	 * Resolve the cycle to bill for a due contract, or null to skip.
	 *
	 * @param DueRenewal        $due The due scan row (contract + head fields).
	 * @param DateTimeImmutable $now The scan moment.
	 * @return RenewalIntent|null The cycle to bill, or null when nothing is due.
	 */
	public function select( DueRenewal $due, DateTimeImmutable $now ): ?RenewalIntent {
		$count = $due->get_head_count();
		if ( null === $count ) {
			// A countless head is a corrupt chain the scan should not surface; refuse to guess.
			return null;
		}

		$status = $due->get_head_status();

		// Head settled forward: advance to the next cycle, but only once its period has begun.
		// The guard is the charge-ahead defence - a just-billed head whose period runs into the
		// future is not yet due for its successor.
		if ( CycleStatus::BILLED === $status || CycleStatus::CANCELLED === $status ) {
			if ( ! self::has_period_ended( $due->get_head_ends_at_gmt(), $now ) ) {
				return null;
			}
			return new RenewalIntent( $due->get_contract_id(), $count + 1 );
		}

		// Head still in flight: retry the same cycle. The money-path reclaims a stalled one
		// (the scan only surfaces a pending head whose crash-recovery lease has expired).
		if ( CycleStatus::PENDING === $status ) {
			return new RenewalIntent( $due->get_contract_id(), $count );
		}

		// failed (awaits dunning) / processing (awaits its gateway): not selectable here. The
		// scan already excludes them; this is a defensive skip.
		return null;
	}

	/**
	 * Whether a period ending at `$ends_at_gmt` has ended by `$now` - the shared due-guard.
	 * An unparseable end is treated as not ended (never charge ahead on bad data).
	 *
	 * The single source of truth for the guard: the money-path ({@see RenewalEngine::process()})
	 * re-checks it against a freshly re-read head before claiming, so both layers agree by
	 * construction rather than carrying their own copy of the comparison.
	 *
	 * @param string            $ends_at_gmt The head period end (GMT string).
	 * @param DateTimeImmutable $now         The scan moment.
	 */
	public static function has_period_ended( string $ends_at_gmt, DateTimeImmutable $now ): bool {
		$ends_at = strtotime( $ends_at_gmt . ' UTC' );
		return false !== $ends_at && $ends_at <= $now->getTimestamp();
	}
}
