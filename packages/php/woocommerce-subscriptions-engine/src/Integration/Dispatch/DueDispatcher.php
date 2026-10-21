<?php
/**
 * DueDispatcher - notifies owners when their contracts are due.
 *
 * One recurring Action Scheduler job scans for contracts whose `next_payment_gmt` has
 * passed and whose owner registered a `contract_due_handler` ({@see ConsumerRegistry}),
 * locks each one, and calls the owner's handler with the lock. Only contracts whose schedule the engine
 * keeps (`schedule_source` `subscriptions_engine`) are dispatched; a gateway or extension
 * that keeps its own schedule triggers its own renewals. The engine decides nothing about
 * what a due contract means: renewing, skipping or clearing the due moment is the owner's call.
 * A handler is expected to move or clear the due moment; one that does not is notified
 * again by a later or overlapping tick, behind contracts locked less recently.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Dispatch
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Dispatch;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ConsumerRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Batch due dispatcher.
 */
final class DueDispatcher {

	/**
	 * Action Scheduler hook fired on each recurring scan tick.
	 */
	public const HOOK = 'woocommerce_subscriptions_engine_dispatch_due_contracts';

	/**
	 * Action Scheduler group shared with the rest of the engine's actions.
	 */
	public const GROUP = 'woocommerce_subscriptions_engine';

	/**
	 * Operation label of the lock the dispatcher takes for a due notification.
	 */
	public const LOCK_OPERATION = 'engine_due_dispatch';

	/**
	 * Scan cadence, in seconds (every 10 minutes).
	 */
	private const INTERVAL_SECONDS = 600;

	/**
	 * Contracts notified per tick; a backlog drains over successive ticks.
	 */
	private const BATCH_SIZE = 50;

	/**
	 * Logger source tag.
	 */
	private const LOG_SOURCE = 'woocommerce-subscriptions-engine';

	/**
	 * Option holding the next moment the recurring action is re-verified against the Action
	 * Scheduler store, so the common request path skips the store query.
	 */
	private const SCHEDULE_CHECK_OPTION = 'woocommerce_subscriptions_engine_due_dispatch_scheduled_check';

	/**
	 * How long a positive schedule check is trusted before re-verifying, in seconds.
	 */
	private const SCHEDULE_RECHECK_SECONDS = 3600;

	/**
	 * Repository used to scan, lock and read due contracts.
	 *
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * Build a dispatcher.
	 */
	public function __construct() {
		$this->contracts = new ContractRepository();
	}

	/**
	 * Register the scan-tick handler. Runs on every boot so Action Scheduler can dispatch a
	 * tick into {@see self::handle_tick()}; the recurring action itself is enqueued by
	 * {@see self::ensure_scheduled()}.
	 */
	public function register_hooks(): void {
		add_action( self::HOOK, array( $this, 'handle_tick' ) );
	}

	/**
	 * Enqueue the recurring scan action when an owner has a due handler, or remove it when none does.
	 *
	 * A positive check is cached in an autoloaded option and re-verified once per re-check
	 * window, so most requests skip the Action Scheduler store query. Runs on `wp_loaded`,
	 * once consumers have registered, and again when a due handler registers after that
	 * ({@see ConsumerRegistry::register()}). Without Action Scheduler (for
	 * example from a deactivation hook while WooCommerce is inactive) it does nothing.
	 */
	public static function ensure_scheduled(): void {
		if ( ! self::action_scheduler_available() ) {
			return;
		}

		if ( array() === ConsumerRegistry::get_slugs_with_contract_due_handler() ) {
			if ( false !== get_option( self::SCHEDULE_CHECK_OPTION, false ) ) {
				as_unschedule_all_actions( self::HOOK, array(), self::GROUP );
				delete_option( self::SCHEDULE_CHECK_OPTION );
			}
			return;
		}

		$next_check = get_option( self::SCHEDULE_CHECK_OPTION, 0 );
		if ( is_numeric( $next_check ) && time() < (int) $next_check ) {
			return;
		}

		if ( ! self::is_scheduled() ) {
			// `$unique = true` is a best-effort dedup; the per-contract lock keeps a duplicate
			// recurring action from notifying a contract while another run holds it.
			as_schedule_recurring_action( time() + self::INTERVAL_SECONDS, self::INTERVAL_SECONDS, self::HOOK, array(), self::GROUP, true );
		}

		update_option( self::SCHEDULE_CHECK_OPTION, time() + self::SCHEDULE_RECHECK_SECONDS, true );
	}

	/**
	 * Whether the Action Scheduler functions the schedule check calls are loaded.
	 */
	private static function action_scheduler_available(): bool {
		return function_exists( 'as_next_scheduled_action' )
			&& function_exists( 'as_schedule_recurring_action' )
			&& function_exists( 'as_unschedule_all_actions' );
	}

	/**
	 * Whether the recurring scan action is currently scheduled (queries the Action Scheduler store).
	 */
	private static function is_scheduled(): bool {
		return false !== as_next_scheduled_action( self::HOOK, array(), self::GROUP );
	}

	/**
	 * The Action Scheduler entry point, fired once per tick. Argument-less so a stray
	 * `do_action( self::HOOK, ... )` cannot reach the typed parameters of {@see self::run_batch()}.
	 */
	public function handle_tick(): void {
		$this->run_batch();
	}

	/**
	 * Notify the owners of up to `$limit` due contracts. Each is locked on the due moment the
	 * scan read (a lost lock is skipped), read, and passed to its handler with `due_at` and the
	 * `lock`, released when the handler returns. A throwing handler keeps its lock until expiry;
	 * failures are logged per contract and never stall the batch.
	 *
	 * @param DateTimeImmutable|null $now   The scan moment; defaults to now (UTC).
	 * @param int                    $limit Maximum due contracts this tick; non-positive is a no-op.
	 * @return int The number of contracts whose handler returned without throwing (a failed
	 *             release afterwards still counts); lost locks, missing contracts or handlers,
	 *             failed locks or reads, and throwing handlers are not counted.
	 */
	public function run_batch( ?DateTimeImmutable $now = null, int $limit = self::BATCH_SIZE ): int {
		if ( $limit < 1 ) {
			return 0;
		}

		$owners = ConsumerRegistry::get_slugs_with_contract_due_handler();
		if ( array() === $owners ) {
			wc_get_logger()->info(
				'DueDispatcher::run_batch(): no consumer registered a contract due handler - nothing to dispatch.',
				array( 'source' => self::LOG_SOURCE )
			);
			return 0;
		}

		$utc      = new DateTimeZone( 'UTC' );
		$scan_now = $now ?? new DateTimeImmutable( 'now', $utc );
		$due_rows = $this->contracts->find_due( $scan_now, $limit, $owners );

		$notified = 0;
		$skipped  = 0;
		$failed   = 0;

		foreach ( $due_rows as $row ) {
			$contract_id = $row['id'];

			try {
				$lock = $this->contracts->acquire_lock( $contract_id, self::LOCK_OPERATION, array( 'next_payment_gmt' => $row['next_payment_gmt'] ) );
				if ( null === $lock ) {
					++$skipped;
					continue;
				}
			} catch ( Throwable $e ) {
				++$failed;
				$this->log_failure( $contract_id, 'could not be locked', $e );
				continue;
			}

			try {
				$contract = $this->contracts->find( $contract_id );
			} catch ( Throwable $e ) {
				++$failed;
				$this->log_failure( $contract_id, 'could not be read', $e );
				$this->release( $lock );
				continue;
			}

			$handler = ConsumerRegistry::get_contract_due_handler( $row['extension_slug'] );
			if ( null === $contract || null === $handler ) {
				$this->release( $lock );
				++$skipped;
				continue;
			}

			try {
				$handler(
					ContractView::from_contract( $contract, true ),
					array(
						'due_at' => new DateTimeImmutable( $row['next_payment_gmt'], $utc ),
						'lock'   => $lock,
					)
				);
			} catch ( Throwable $e ) {
				// The lock is kept until it expires: the contract stays closed while its state is unknown.
				++$failed;
				$this->log_failure( $contract_id, 'threw in its due handler', $e );
				continue;
			}

			++$notified;
			$this->release( $lock );
		}

		if ( array() !== $due_rows ) {
			wc_get_logger()->debug(
				sprintf( 'DueDispatcher::run_batch(): %d due contract(s) - %d notified, %d skipped, %d failed.', count( $due_rows ), $notified, $skipped, $failed ),
				array( 'source' => self::LOG_SOURCE )
			);
		}

		return $notified;
	}

	/**
	 * Release a dispatch lock in its own try block: a failed release is logged and the lock
	 * expires on its own, without failing the notification or the batch.
	 *
	 * @param ContractLock $lock The dispatch lock.
	 */
	private function release( ContractLock $lock ): void {
		try {
			$this->contracts->release_lock( $lock );
		} catch ( Throwable $e ) {
			wc_get_logger()->warning(
				sprintf( 'DueDispatcher::run_batch(): failed to release the due lock on contract %d; it expires on its own. %s', $lock->get_contract_id(), $e->getMessage() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $lock->get_contract_id(),
					'stage'       => 'release',
				)
			);
		}
	}

	/**
	 * Log a contract that failed this tick.
	 *
	 * @param int       $contract_id Contract id.
	 * @param string    $what        What went wrong, e.g. "could not be read".
	 * @param Throwable $e           The failure.
	 */
	private function log_failure( int $contract_id, string $what, Throwable $e ): void {
		wc_get_logger()->error(
			sprintf( 'DueDispatcher::run_batch(): contract %d %s: %s', $contract_id, $what, $e->getMessage() ),
			array(
				'source'      => self::LOG_SOURCE,
				'contract_id' => $contract_id,
			)
		);
	}
}
