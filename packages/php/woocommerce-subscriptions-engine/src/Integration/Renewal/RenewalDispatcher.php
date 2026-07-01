<?php
/**
 * RenewalDispatcher - the autonomous batch renewal scanner.
 *
 * One recurring Action Scheduler job replaces the superseded one-job-per-contract
 * model: on each tick it scans the contract due-index and drives every due renewal
 * through the single synchronous money-path ({@see RenewalEngine::process_due()}).
 * Before charging anything it consults the processing gate
 * ({@see ConsumerRegistry::is_empty()}) - with no registered consumer the engine is
 * inert and the whole run is skipped.
 *
 * The create-as-claim ({@see RenewalEngine}) plus the cycle crash-recovery lease keep
 * overlap correct, so the scan needs no claim of its own: a contract picked up twice
 * (a slow tick overlapping the next) bills at most once.
 *
 * Integration zone: WordPress-native. Owns the recurring-action registration and the
 * AS hook callback, mirroring {@see RenewalScheduler}'s Action Scheduler conventions.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Registry\ConsumerRegistry;
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
	private const SCHEDULE_CHECK_OPTION = 'wc_subscriptions_engine_dispatch_scheduled_check';

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
	 * Build a dispatcher over the given collaborators.
	 *
	 * @param ContractRepository|null $contracts Contract repository; default instance when omitted.
	 * @param RenewalEngine|null      $engine    Renewal engine; default instance when omitted.
	 */
	public function __construct( ?ContractRepository $contracts = null, ?RenewalEngine $engine = null ) {
		$this->contracts = $contracts ?? new ContractRepository();
		$this->engine    = $engine ?? new RenewalEngine();
	}

	/**
	 * Register the scan-tick handler.
	 *
	 * Must run on every boot (not just activation) so Action Scheduler can dispatch a tick
	 * back into {@see self::handle_tick()}. A plain `add_action`, safe to call before Action
	 * Scheduler has loaded; the recurring action itself is enqueued later via
	 * {@see self::ensure_scheduled()}.
	 */
	public static function register_hooks(): void {
		add_action( self::HOOK, array( __CLASS__, 'handle_tick' ) );
	}

	/**
	 * Enqueue the recurring scan action when one is not already scheduled.
	 *
	 * Call once Action Scheduler is available (e.g. on `init`), since it uses the `as_*`
	 * functions. To avoid an Action Scheduler store query on every request, a positive result is
	 * cached in an autoloaded option and re-verified only once per re-check window - bounded
	 * staleness that self-heals if the action is ever cleared. Within a re-verify it still guards
	 * with the `is_scheduled()` fast-path plus a best-effort store-level dedup.
	 */
	public static function ensure_scheduled(): void {
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
	 * Whether the recurring scan action is currently scheduled.
	 */
	public static function is_scheduled(): bool {
		return false !== as_next_scheduled_action( self::HOOK, array(), self::GROUP );
	}

	/**
	 * Action Scheduler dispatch entry point - fires once per scan tick.
	 *
	 * Static so it can be registered as a plain callback; routes through an instance
	 * `run()` so dispatch and any synchronous test driver share one code path.
	 */
	public static function handle_tick(): void {
		( new self() )->run();
	}

	/**
	 * Run one scan tick at the default batch size.
	 *
	 * @param DateTimeImmutable|null $now The scan moment; defaults to now (UTC). Injectable for tests.
	 * @return int The number of due contracts processed this tick (0 when gated).
	 */
	public function run( ?DateTimeImmutable $now = null ): int {
		return $this->run_batch( $now, self::BATCH_SIZE );
	}

	/**
	 * Run one scan tick over up to `$limit` due contracts: gate, then drive every due renewal.
	 *
	 * The processing gate comes first - with no registered consumer the engine charges
	 * nothing and the run returns immediately. Otherwise the contract due-index is scanned
	 * for up to `$limit` contracts due at `$now` and each is advanced through the money-path.
	 * A throw from one contract is logged and the scan continues, so one bad contract cannot
	 * stall the whole batch. A backlog larger than `$limit` drains over successive ticks.
	 *
	 * @param DateTimeImmutable|null $now   The scan moment; defaults to now (UTC).
	 * @param int                    $limit Maximum due contracts to process this tick.
	 * @return int The number of due contracts processed this tick (0 when gated).
	 */
	public function run_batch( ?DateTimeImmutable $now, int $limit ): int {
		if ( ConsumerRegistry::is_empty() ) {
			wc_get_logger()->info(
				'RenewalDispatcher::run(): no consumer extension is registered - skipping the renewal scan (charging nothing).',
				array( 'source' => self::LOG_SOURCE )
			);
			return 0;
		}

		$now     = $now ?? new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$due_ids = $this->contracts->find_due( $now, $limit );

		foreach ( $due_ids as $contract_id ) {
			try {
				$this->engine->process_due( $contract_id );
			} catch ( Throwable $e ) {
				// One contract's failure must not stall the batch (or make AS retry the
				// whole tick forever). Log and continue to the next due contract.
				wc_get_logger()->error(
					sprintf( 'RenewalDispatcher::run(): processing contract %d threw: %s', $contract_id, $e->getMessage() ),
					array(
						'source'      => self::LOG_SOURCE,
						'contract_id' => $contract_id,
					)
				);
			}
		}

		return count( $due_ids );
	}
}
