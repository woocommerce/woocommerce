<?php
/**
 * Fails a test that runs schema-changing SQL inside its transaction.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/**
 * Guard against DDL inside the per-test transaction.
 *
 * WP_UnitTestCase isolates each test with START TRANSACTION / ROLLBACK. A
 * non-temporary CREATE, DROP, ALTER, TRUNCATE or RENAME carries an implicit
 * commit, so every write made earlier in the same test escapes the rollback
 * and leaks into the tests that follow. This hooks the `query` filter after
 * WordPress has had its chance to rewrite CREATE/DROP TABLE into TEMPORARY
 * variants, and reports or fails any remaining DDL that is not allowlisted.
 */
final class WC_DDL_In_Transaction_Guard {

	const MODE_ENFORCE = 'enforce';
	const MODE_REPORT  = 'report';

	/**
	 * Whether a per-test transaction is currently open.
	 *
	 * @var bool
	 */
	private static $in_transaction = false;

	/**
	 * Allowlisted sites, as 'Class::method' or 'Class::*'.
	 *
	 * @var array<string, true>
	 */
	private static $allowlist = array();

	/**
	 * One of the MODE_* constants.
	 *
	 * @var string
	 */
	private static $mode = self::MODE_ENFORCE;

	/**
	 * Sites already reported in report mode, to keep the output readable.
	 *
	 * @var array<string, true>
	 */
	private static $reported = array();

	/**
	 * Install the guard.
	 *
	 * @param string[] $allowlist Sites that may run DDL in a transaction.
	 * @param string   $mode      One of the MODE_* constants.
	 */
	public static function register( array $allowlist, string $mode ): void {
		self::$allowlist = array_fill_keys( $allowlist, true );
		// Anything but report enforces. There is no off switch: the allowlist is the escape hatch.
		self::$mode = self::MODE_REPORT === $mode ? self::MODE_REPORT : self::MODE_ENFORCE;
		// Late priority: WP_UnitTestCase rewrites CREATE/DROP TABLE to TEMPORARY at priority 10.
		add_filter( 'query', array( self::class, 'inspect_query' ), PHP_INT_MAX );
	}

	/**
	 * Track the transaction and check DDL against the allowlist.
	 *
	 * @param string $query The SQL about to run.
	 * @return string The query, unchanged.
	 * @throws RuntimeException In enforce mode, for DDL not on the allowlist.
	 */
	public static function inspect_query( $query ) {
		$sql  = ltrim( (string) $query );
		$head = strtoupper( substr( $sql, 0, 20 ) );

		if ( 0 === strpos( $head, 'START TRANSACTION' ) || 0 === strpos( $head, 'BEGIN' ) ) {
			self::$in_transaction = true;
			return $query;
		}
		if ( 0 === strpos( $head, 'ROLLBACK' ) || 0 === strpos( $head, 'COMMIT' ) ) {
			self::$in_transaction = false;
			return $query;
		}
		if ( ! self::$in_transaction ) {
			return $query;
		}

		$verb = strtok( $head, " \t\n(" );
		if ( ! in_array( $verb, array( 'CREATE', 'DROP', 'ALTER', 'TRUNCATE', 'RENAME' ), true ) ) {
			return $query;
		}
		// Temporary tables carry no implicit commit.
		if ( false !== stripos( substr( $sql, 0, 40 ), 'TEMPORARY' ) ) {
			return $query;
		}

		$site = self::current_site();
		if ( self::is_allowed( $site ) ) {
			return $query;
		}

		$message = sprintf(
			'%s statement inside a test transaction, from %s. DDL commits implicitly, so every write made so far in this test escapes the rollback and leaks into later tests. Move it to wpSetUpBeforeClass(), or add %s to tests/legacy/framework/ddl-in-transaction-allowlist.php with the reason.',
			$verb,
			$site,
			$site
		);

		if ( self::MODE_REPORT === self::$mode ) {
			$key = $site . '|' . $verb;
			if ( ! isset( self::$reported[ $key ] ) ) {
				self::$reported[ $key ] = true;
				fwrite( STDERR, "\n#DDL-GUARD# {$site} | {$verb}\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Test-runner console output, not a filesystem write.
			}
			return $query;
		}

		throw new RuntimeException( esc_html( $message ) );
	}

	/**
	 * Name the test that issued the query, as 'Class::method'.
	 *
	 * @return string
	 */
	private static function current_site(): string {
		$fallback = '';
		foreach ( debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS ) as $frame ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
			if ( ! isset( $frame['object'], $frame['function'] ) || ! ( $frame['object'] instanceof PHPUnit\Framework\TestCase ) ) {
				continue;
			}
			$site = get_class( $frame['object'] ) . '::' . $frame['function'];
			if ( 0 === strpos( $frame['function'], 'test' ) ) {
				return $site;
			}
			if ( '' === $fallback ) {
				$fallback = $site;
			}
		}
		return '' !== $fallback ? $fallback : 'unknown';
	}

	/**
	 * Whether a site is allowlisted, exactly or by class wildcard.
	 *
	 * @param string $site 'Class::method'.
	 * @return bool
	 */
	private static function is_allowed( string $site ): bool {
		if ( isset( self::$allowlist[ $site ] ) ) {
			return true;
		}
		$class = strtok( $site, ':' );
		return isset( self::$allowlist[ $class . '::*' ] );
	}
}
