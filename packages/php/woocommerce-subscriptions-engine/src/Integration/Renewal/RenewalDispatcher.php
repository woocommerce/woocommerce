<?php
/**
 * RenewalDispatcher - the autonomous batch renewal scanner.
 *
 * One recurring Action Scheduler job drives every scheduled renewal: each tick runs
 * {@see self::run_batch()} over the cycle-aware due-index. The class owns the recurring
 * action's registration, scheduling, and hook callback.
 *
 * The create-as-claim ({@see RenewalEngine}) plus the cycle crash-recovery lease keep
 * overlap correct, so the scan needs no claim of its own: a contract picked up twice
 * (a slow tick overlapping the next) bills at most once.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ConsumerRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Batch renewal dispatcher.
 */
final class RenewalDispatcher {

	/**
	 * Action Scheduler hook fired on each recurring scan tick.
	 *
	 * Public so tooling and tests can inspect, run, or cancel the recurring action via
	 * `as_next_scheduled_action()` and friends.
	 */
	public const HOOK = 'woocommerce_subscriptions_engine_dispatch_due_renewals';

	/**
	 * Action Scheduler group - used for admin filterability (Tools -> Scheduled Actions)
	 * and bulk teardown. Shared with the rest of the engine's actions.
	 */
	public const GROUP = 'woocommerce_subscriptions_engine';

	/**
	 * Default scan cadence, in seconds (every 10 minutes). Frequent enough that a due
	 * renewal fires close to its moment without scanning so often it churns the index.
	 */
	private const INTERVAL_SECONDS = 600;

	/**
	 * Contracts processed per tick. Bounds the work (and the open order/charge volume)
	 * of a single run; a backlog drains over successive ticks.
	 */
	private const BATCH_SIZE = 50;

	/**
	 * Logger source tag.
	 */
	private const LOG_SOURCE = 'woocommerce-subscriptions-engine';

	/**
	 * Option holding the next moment the recurring action is re-verified against the Action
	 * Scheduler store. Autoloaded (bulk-loaded, effectively free per request), so the common
	 * path skips the AS store query {@see self::is_scheduled()} would otherwise run every load.
	 */
	private const SCHEDULE_CHECK_OPTION = 'woocommerce_subscriptions_engine_dispatch_scheduled_check';

	/**
	 * How long a positive schedule check is trusted before re-verifying, in seconds. Bounds the
	 * staleness if the recurring action is ever cleared externally: the next check past this
	 * window re-creates it, so the dispatcher self-heals rather than stopping silently.
	 */
	private const SCHEDULE_RECHECK_SECONDS = 3600;

	/**
	 * Repository used to scan the contract due-index.
	 *
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * The money-path the scan drives per due contract.
	 *
	 * @var RenewalEngine
	 */
	private $engine;

	/**
	 * The read-only selector that turns a due scan row into the cycle to bill.
	 *
	 * @var RenewalSelector
	 */
	private $selector;

	/**
	 * Build a dispatcher over the given collaborators.
	 *
	 * @param ContractRepository|null $contracts Contract repository; default instance when omitted.
	 * @param RenewalEngine|null      $engine    Renewal engine; default instance when omitted.
	 * @param RenewalSelector|null    $selector  Cycle selector; default instance when omitted.
	 */
	public function __construct( ?ContractRepository $contracts = null, ?RenewalEngine $engine = null, ?RenewalSelector $selector = null ) {
		$this->contracts = $contracts ?? new ContractRepository();
		$this->engine    = $engine ?? new RenewalEngine();
		$this->selector  = $selector ?? new RenewalSelector();
	}

	/**
	 * Register the scan-tick handler on THIS instance.
	 *
	 * Must run on every boot (not just activation) so Action Scheduler can dispatch a tick
	 * back into {@see self::handle_tick()}. A plain `add_action`, safe to call before Action
	 * Scheduler has loaded; the recurring action itself is enqueued later via
	 * {@see self::ensure_scheduled()}. Instance-based (not static) so the boot-built
	 * dispatcher - with whatever collaborators it was constructed over - is the one the
	 * tick runs.
	 */
	public function register_hooks(): void {
		add_action( self::HOOK, array( $this, 'handle_tick' ) );
	}

	/**
	 * Enqueue the recurring scan action when one is not already scheduled.
	 *
	 * Call once Action Scheduler is available (Bootstrap runs it on `action_scheduler_init`,
	 * the moment AS declares its `as_*` functions ready). Gated on the consumer registry: a store with no consumer extension runs no
	 * renewals, so it carries no recurring scan action either - one already scheduled is
	 * removed on the first gated boot after the last consumer deactivates. To avoid an Action Scheduler
	 * store query on every request, a positive result is cached in an autoloaded option and
	 * re-verified only once per re-check window - bounded staleness that self-heals if the
	 * action is ever cleared. Within a re-verify it still guards with the `is_scheduled()`
	 * fast-path plus a best-effort store-level dedup.
	 */
	public static function ensure_scheduled(): void {
		// No consumer, no scan - and a store that previously scheduled the recurring action
		// removes it here, so the job does not keep ticking against the gate after every
		// consumer deactivates. The check option doubles as the "scheduled before" marker,
		// keeping the common no-consumer path to a single autoloaded option read; nothing is
		// re-cached, so a consumer registering later schedules promptly.
		if ( ConsumerRegistry::is_empty() ) {
			if ( false !== get_option( self::SCHEDULE_CHECK_OPTION, false ) ) {
				as_unschedule_all_actions( self::HOOK, array(), self::GROUP );
				delete_option( self::SCHEDULE_CHECK_OPTION );
			}
			return;
		}

		// Skip the Action Scheduler store query while a recent positive check is still trusted.
		$next_check = get_option( self::SCHEDULE_CHECK_OPTION, 0 );
		if ( is_numeric( $next_check ) && time() < (int) $next_check ) {
			return;
		}

		if ( self::is_scheduled() ) {
			update_option( self::SCHEDULE_CHECK_OPTION, time() + self::SCHEDULE_RECHECK_SECONDS, true );
			return;
		}

		// $unique = true is a best-effort store-level dedup: Action Scheduler checks for an
		// existing pending/running action before inserting, but that is not an atomic unique
		// constraint, so two concurrent first-boots could still create two rows. The downstream
		// create-as-claim cycle UNIQUE prevents any double-charge regardless; at worst a duplicate
		// recurring row means redundant scan work until it is cleared. With the is_scheduled()
		// fast-path this keeps the common case to a single recurring action.
		as_schedule_recurring_action(
			time() + self::INTERVAL_SECONDS,
			self::INTERVAL_SECONDS,
			self::HOOK,
			array(),
			self::GROUP,
			true
		);

		update_option( self::SCHEDULE_CHECK_OPTION, time() + self::SCHEDULE_RECHECK_SECONDS, true );
	}

	/**
	 * Whether the recurring scan action is currently scheduled. Private - it queries the
	 * Action Scheduler store, so per-request callers must go through the option-cached
	 * {@see self::ensure_scheduled()} instead.
	 */
	private static function is_scheduled(): bool {
		return false !== as_next_scheduled_action( self::HOOK, array(), self::GROUP );
	}

	/**
	 * Triggers a batch run - the Action Scheduler dispatch entry point, fired once per tick.
	 *
	 * A thin wrapper around {@see self::run_batch()}, needed (vs registering that method
	 * directly) because a stray `do_action( self::HOOK, ... )` carrying arguments would
	 * reach run_batch's typed parameters and fatal; the argument-less wrapper absorbs
	 * whatever the hook carries.
	 */
	public function handle_tick(): void {
		$this->run_batch();
	}

	/**
	 * Run one scan tick over up to `$limit` due contracts: gate, then drive every due renewal.
	 *
	 * The processing gate comes first - with no registered consumer the engine charges
	 * nothing and the run returns immediately. Otherwise the cycle-aware scan returns the
	 * actionable contracts due at `$now`; each is run through read-only selection and, when a
	 * cycle is due, billed via {@see RenewalEngine::process()}. A pre-flight impossibility
	 * ({@see RenewalNotProcessable}) parks the contract; any other throw is logged - so one bad
	 * contract cannot stall the batch. A backlog larger than `$limit` drains over successive ticks.
	 *
	 * @param DateTimeImmutable|null $now   The scan moment; defaults to now (UTC). Injectable for tests.
	 * @param int                    $limit Maximum due contracts to process this tick; defaults to
	 *                                      the batch size. A non-positive limit is a no-op returning 0.
	 * @return int The number of actionable due contracts scanned this tick (0 when gated).
	 *             A billed/skipped/failed breakdown is logged at debug level.
	 */
	public function run_batch( ?DateTimeImmutable $now = null, int $limit = self::BATCH_SIZE ): int {
		if ( $limit < 1 ) {
			return 0;
		}

		if ( ConsumerRegistry::is_empty() ) {
			wc_get_logger()->info(
				'RenewalDispatcher::run(): no consumer extension is registered - skipping the renewal scan (charging nothing).',
				array( 'source' => self::LOG_SOURCE )
			);
			return 0;
		}

		$now        = $now ?? new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$candidates = $this->contracts->find_due( $now, $limit );

		$billed  = 0;
		$skipped = 0;
		$failed  = 0;

		foreach ( $candidates as $candidate ) {
			try {
				$cycle_count = $this->selector->select_scheduled_cycle( $candidate, $now );
				if ( null === $cycle_count ) {
					++$skipped;
					continue;
				}
				$renewal_intent = new RenewalIntent( $candidate->get_contract_id(), $cycle_count );
				if ( null === $this->engine->process( $renewal_intent, $now ) ) {
					// A skip or idempotent no-op (a live claim, an already-settled cycle).
					++$skipped;
				} else {
					++$billed;
				}
			} catch ( RenewalNotProcessable $e ) {
				++$failed;
				// Pre-flight impossibility (e.g. an unresolvable plan): park so the contract
				// leaves the due window and cannot re-poison the scan; a repair re-arms it.
				$this->engine->park( $candidate->get_contract_id() );
				wc_get_logger()->warning(
					sprintf( 'RenewalDispatcher::run(): parking contract %d - %s', $candidate->get_contract_id(), $e->getMessage() ),
					array(
						'source'      => self::LOG_SOURCE,
						'contract_id' => $candidate->get_contract_id(),
					)
				);
			} catch ( Throwable $e ) {
				// One contract's failure must not stall the batch (or make AS retry the
				// whole tick forever). Log and continue to the next due contract.
				++$failed;
				wc_get_logger()->error(
					sprintf( 'RenewalDispatcher::run(): processing contract %d threw: %s', $candidate->get_contract_id(), $e->getMessage() ),
					array(
						'source'      => self::LOG_SOURCE,
						'contract_id' => $candidate->get_contract_id(),
					)
				);
			}
		}

		if ( array() !== $candidates ) {
			wc_get_logger()->debug(
				sprintf( 'RenewalDispatcher::run(): scanned %d candidate(s) - %d billed, %d skipped, %d failed.', count( $candidates ), $billed, $skipped, $failed ),
				array( 'source' => self::LOG_SOURCE )
			);
		}

		return count( $candidates );
	}
}
