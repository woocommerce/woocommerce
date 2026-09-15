<?php
/**
 * MigratorInterface class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;

defined( 'ABSPATH' ) || exit;

/**
 * Contract for a single section of the legacy Back In Stock Notifications migration.
 *
 * Each migrator owns one slice of the legacy data set and is fully responsible for
 * deciding which of its rows are still outstanding.
 */
interface MigratorInterface {

	/**
	 * Section slug, used in batch item prefixes, state keys and CLI `--section` values.
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Count the rows this section still has to migrate.
	 *
	 * Display only; it never drives the batch loop. A section that scans by keyset counts
	 * only the rows above the cursor it is given, since everything below it has already
	 * been visited; the sections whose candidacy is a value predicate ignore the cursor,
	 * because a settled row stops matching their predicate on its own.
	 *
	 * @param int $cursor Last identifier handled, or 0 to count from the start.
	 * @return int
	 */
	public function count_remaining( int $cursor = 0 ): int;

	/**
	 * Fetch the next batch of candidate identifiers after the given keyset cursor.
	 *
	 * Must be side-effect free: calling it twice with the same cursor returns the same
	 * batch and changes no stored state.
	 *
	 * @param int $cursor Last identifier handled in the current pass, or 0 to start a pass.
	 * @param int $size   Maximum number of identifiers to return.
	 * @return array List of identifiers, ascending.
	 */
	public function get_batch( int $cursor, int $size ): array;

	/**
	 * Migrate the given identifiers.
	 *
	 * Per-row failures are recorded and reported rather than thrown. Throw only for
	 * transient conditions that failed the whole batch and where a retry is correct.
	 *
	 * @param array  $ids    Identifiers returned by get_batch().
	 * @param Writer $writer Writer to route all persistence through.
	 * @return array Outcome counts keyed by outcome code.
	 */
	public function migrate_batch( array $ids, Writer $writer ): array;
}
