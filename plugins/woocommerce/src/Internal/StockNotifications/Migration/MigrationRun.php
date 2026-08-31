<?php
/**
 * MigrationRun class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\MigratorInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\OptionsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\ProductMetaMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;

defined( 'ABSPATH' ) || exit;

/**
 * Assembles the parts of one migration run.
 *
 * The three entry points — the CLI, the Tools screen and `MigrationBatchProcessor` — each need
 * the same migrators over the same `Reporter`, and the CLI additionally needs a writer and a
 * section list. Built here rather than resolved from the container, which cannot reflect over
 * their constructor arguments.
 *
 * The prefix says which you get. A `get_` method memoizes, so asking twice returns the same
 * instance — that matters for the parts that carry state across a run: the notifications
 * migrator holds the known-loss counters, the settings migrator remembers which values it has
 * already settled, and the run state owns the cursors. A `build_` method constructs fresh
 * every call; the parts it builds hold nothing worth sharing.
 */
class MigrationRun {

	/**
	 * Outcome collector every part of this run reports through.
	 *
	 * @var Reporter
	 */
	private Reporter $reporter;

	/**
	 * The notifications migrator, built on first use.
	 *
	 * @var NotificationsMigrator|null
	 */
	private ?NotificationsMigrator $notifications = null;

	/**
	 * The settings migrator, built on first use.
	 *
	 * @var OptionsMigrator|null
	 */
	private ?OptionsMigrator $options = null;

	/**
	 * The run state, built on first use.
	 *
	 * @var MigrationState|null
	 */
	private ?MigrationState $state = null;

	/**
	 * Constructor.
	 *
	 * @param Reporter|null $reporter Outcome collector to share, or null to start a fresh one.
	 */
	public function __construct( ?Reporter $reporter = null ) {
		$this->reporter = $reporter ?? new Reporter();
	}

	/**
	 * The outcome collector every part of this run reports through.
	 *
	 * @return Reporter
	 */
	public function get_reporter(): Reporter {
		return $this->reporter;
	}

	/**
	 * The notifications migrator this run uses.
	 *
	 * Exposed separately because it is the one migrator a caller reads back from: the known
	 * losses a run reports are counters it accumulated while walking its rows.
	 *
	 * @return NotificationsMigrator
	 */
	public function get_notifications_migrator(): NotificationsMigrator {
		return $this->notifications ??= new NotificationsMigrator( $this->reporter );
	}

	/**
	 * The settings migrator this run uses.
	 *
	 * @return OptionsMigrator
	 */
	public function get_options_migrator(): OptionsMigrator {
		return $this->options ??= new OptionsMigrator( $this->reporter );
	}

	/**
	 * The run state this run's cursors, counts and losses go through.
	 *
	 * One instance for the whole run, since a dry run's state lives only in memory: two
	 * non-persisting instances would each keep their own copy, and a cursor reset on one
	 * would be invisible to the loop reading the other.
	 *
	 * The flag is read on the first call only — a run does not change mode part-way.
	 *
	 * @param bool $dry_run Whether the run should keep its state to itself.
	 * @return MigrationState
	 */
	public function get_state( bool $dry_run ): MigrationState {
		return $this->state ??= new MigrationState( ! $dry_run );
	}

	/**
	 * The batched section migrators, keyed by slug and in the order they must run.
	 *
	 * @param bool $dry_run Whether the run discards its writes. The product-meta section needs
	 *                      to know: it normally leans on its own writes to shrink the candidate
	 *                      set, and pages by cursor instead when there are none.
	 * @return array<string, MigratorInterface>
	 */
	public function build_migrators( bool $dry_run = false ): array {
		return array(
			// Memoized: it counts this run's known losses, so every caller needs the same one.
			'notifications' => $this->get_notifications_migrator(),
			// Not memoized: it holds the shared Reporter and a mode flag fixed for the run, so
			// two instances behave identically and neither holds anything the other would miss.
			'product-meta'  => new ProductMetaMigrator( $this->reporter, $dry_run ),
		);
	}

	/**
	 * The writer this run persists through.
	 *
	 * A live run shares the container's instance; a dry run gets one of its own, since the
	 * flag is per-instance.
	 *
	 * @param bool $dry_run Whether the run should discard its writes.
	 * @return Writer
	 */
	public function build_writer( bool $dry_run ): Writer {
		return $dry_run ? new Writer( true ) : wc_get_container()->get( Writer::class );
	}

	/**
	 * The sections to run, in canonical order regardless of the order requested.
	 *
	 * @param string[] $requested Section slugs asked for, or an empty array for all of them.
	 * @return string[]
	 */
	public function resolve_sections( array $requested ): array {
		if ( empty( $requested ) ) {
			return Constants::SECTION_ORDER;
		}

		return array_values( array_intersect( Constants::SECTION_ORDER, $requested ) );
	}

	/**
	 * The requested slugs that name no section, for a caller to refuse on.
	 *
	 * @param string[] $requested Section slugs asked for.
	 * @return string[]
	 */
	public function unknown_sections( array $requested ): array {
		return array_values( array_diff( $requested, Constants::SECTION_ORDER ) );
	}
}
