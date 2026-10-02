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
 * variants, and reports or fails any remaining DDL the test has not declared.
 * A COMMIT, START TRANSACTION or BEGIN from code under test commits the
 * transaction too, and is handled the same way, as is a test that ends
 * without its tearDown() reaching the framework's ROLLBACK.
 *
 * A test whose subject is schema code declares it with a `@ddlInTransaction`
 * annotation, at method or class level, followed by the reason. Tests that
 * should move their DDL out of the transaction but have not yet are listed in
 * ddl-in-transaction-allowlist.php, which may only shrink.
 */
final class WC_DDL_In_Transaction_Guard {

	const MODE_ENFORCE = 'enforce';
	const MODE_REPORT  = 'report';

	const ANNOTATION = 'ddlInTransaction';

	// Pseudo-verb for a test whose transaction was still open when the next one started.
	const UNFINISHED = 'UNFINISHED';

	/**
	 * The test that owns the open transaction, as 'Class::method', or null outside one.
	 *
	 * @var string|null
	 */
	private static $current_test = null;

	/**
	 * Whether the current test declares its DDL with the annotation.
	 *
	 * @var bool
	 */
	private static $current_test_declares_ddl = false;

	/**
	 * Tests allowed to run DDL until they are fixed, as 'Class::method' or 'Class::*'.
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
	 * @param string[] $allowlist Tests that may run DDL in a transaction until they are fixed.
	 * @param string   $mode      One of the MODE_* constants.
	 */
	public static function register( array $allowlist, string $mode ): void {
		self::$allowlist = array_fill_keys( $allowlist, true );
		// Anything but report enforces. There is no off switch: the annotation and the allowlist are the escape hatches.
		self::$mode = self::MODE_REPORT === $mode ? self::MODE_REPORT : self::MODE_ENFORCE;
		// Late priority: WP_UnitTestCase rewrites CREATE/DROP TABLE to TEMPORARY at priority 10.
		add_filter( 'query', array( self::class, 'inspect_query' ), PHP_INT_MAX );
	}

	/**
	 * Track the test's transaction and check statements that would commit it.
	 *
	 * @param string $query The SQL about to run.
	 * @return string The query, unchanged.
	 * @throws RuntimeException In enforce mode, for an undeclared commit; always, for an annotation without a reason.
	 */
	public static function inspect_query( $query ) {
		$sql  = ltrim( (string) $query );
		$head = strtoupper( substr( $sql, 0, 20 ) );

		// Only the framework's own statements open and close the tracked transaction.
		if ( 0 === strpos( $head, 'START TRANSACTION' ) || 0 === strpos( $head, 'BEGIN' ) ) {
			$frame = self::framework_frame( 'start_transaction' );
			if ( null !== $frame && ( $frame['object'] ?? null ) instanceof PHPUnit\Framework\TestCase ) {
				$previous                        = self::$current_test;
				self::$current_test              = get_class( $frame['object'] ) . '::' . $frame['object']->getName( false );
				self::$current_test_declares_ddl = self::declares_ddl( $frame['object'] );
				if ( null !== $previous ) {
					self::flag( $previous, self::UNFINISHED );
				}
				return $query;
			}
			// From code under test, starting a transaction commits the open one.
			$verb = 0 === strpos( $head, 'BEGIN' ) ? 'BEGIN' : 'START TRANSACTION';
		} elseif ( 0 === strpos( $head, 'ROLLBACK' ) ) {
			// A ROLLBACK from code under test discards the test's writes rather than leaking them.
			if ( null !== self::framework_frame( 'tear_down' ) ) {
				self::$current_test = null;
			}
			return $query;
		} elseif ( 0 === strpos( $head, 'COMMIT' ) ) {
			// WP_UnitTestCase_Base::commit_transaction() runs between classes, after the last test's rollback.
			if ( null !== self::framework_frame( 'commit_transaction' ) ) {
				$previous           = self::$current_test;
				self::$current_test = null;
				if ( null !== $previous ) {
					self::flag( $previous, self::UNFINISHED );
				}
				return $query;
			}
			$verb = 'COMMIT';
		} else {
			$verb = strtok( $head, " \t\n(" );
			if ( ! in_array( $verb, array( 'CREATE', 'DROP', 'ALTER', 'TRUNCATE', 'RENAME' ), true ) ) {
				return $query;
			}
			// Temporary tables carry no implicit commit. Match the keyword, not a table name that contains it.
			if ( preg_match( '/^(?:CREATE|DROP)\s+TEMPORARY\s+TABLE\b/i', $sql ) ) {
				return $query;
			}
		}

		if ( null !== self::$current_test && ! self::$current_test_declares_ddl ) {
			self::flag( self::$current_test, $verb );
		}
		return $query;
	}

	/**
	 * Report or fail a statement that commits a test's transaction, unless the test is allowlisted.
	 *
	 * @param string $test 'Class::method'.
	 * @param string $verb The SQL verb, or UNFINISHED.
	 * @throws RuntimeException In enforce mode.
	 */
	private static function flag( string $test, string $verb ): void {
		if ( self::is_allowed( $test ) ) {
			return;
		}

		if ( self::MODE_REPORT === self::$mode ) {
			$key = $test . '|' . $verb;
			if ( ! isset( self::$reported[ $key ] ) ) {
				self::$reported[ $key ] = true;
				fwrite( STDERR, "\n#DDL-GUARD# {$test} | {$verb}\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Test-runner console output, not a filesystem write.
			}
			return;
		}

		if ( self::UNFINISHED === $verb ) {
			$message = sprintf(
				'%s ended without WP_UnitTestCase_Base::tear_down() rolling back its transaction, so the next transaction statement committed every write it made. Make sure its tearDown() reaches parent::tearDown(), even when setUp() skips the test, and that setUp() carries no @before annotation, which makes PHPUnit run it twice.',
				$test
			);
		} elseif ( in_array( $verb, array( 'COMMIT', 'START TRANSACTION', 'BEGIN' ), true ) ) {
			$message = sprintf(
				'%s from code under test inside the test transaction of %s. It commits the transaction, so every write made so far in this test escapes the rollback and leaks into later tests. If committing is what the test is about, annotate the test with @%s and the reason, and restore anything it writes before the commit.',
				$verb,
				$test,
				self::ANNOTATION
			);
		} else {
			$message = sprintf(
				'%s statement inside the test transaction of %s. DDL commits implicitly, so every write made so far in this test escapes the rollback and leaks into later tests. Move it to wpSetUpBeforeClass(). If the DDL is what the test is about, annotate the test with @%s and the reason, and restore anything it writes before the DDL.',
				$verb,
				$test,
				self::ANNOTATION
			);
		}

		throw new RuntimeException( esc_html( $message ) );
	}

	/**
	 * The backtrace frame of a WP_UnitTestCase_Base method that issued the current query, if any.
	 *
	 * @param string $method 'start_transaction', 'tear_down' or 'commit_transaction'.
	 * @return array<string, mixed>|null
	 */
	private static function framework_frame( string $method ): ?array {
		// inspect_query <- WP_Hook::apply_filters <- apply_filters <- wpdb::query <- $method.
		foreach ( debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 8 ) as $frame ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
			if ( isset( $frame['class'] ) && $method === $frame['function'] && 'WP_UnitTestCase_Base' === $frame['class'] ) {
				return $frame;
			}
		}
		return null;
	}

	/**
	 * Whether the test declares its DDL, at method or class level.
	 *
	 * @param PHPUnit\Framework\TestCase $test The test about to run.
	 * @return bool
	 * @throws RuntimeException When the annotation has no reason.
	 */
	private static function declares_ddl( PHPUnit\Framework\TestCase $test ): bool {
		$annotations = PHPUnit\Util\Test::parseTestMethodAnnotations( get_class( $test ), $test->getName( false ) );
		foreach ( array( 'method', 'class' ) as $depth ) {
			if ( ! isset( $annotations[ $depth ][ self::ANNOTATION ] ) ) {
				continue;
			}
			if ( '' === trim( implode( '', $annotations[ $depth ][ self::ANNOTATION ] ) ) ) {
				throw new RuntimeException( esc_html( sprintf( '@%s on %s needs a reason: why the DDL is the subject under test.', self::ANNOTATION, get_class( $test ) . '::' . $test->getName( false ) ) ) );
			}
			return true;
		}
		return false;
	}

	/**
	 * Whether a test is allowlisted, exactly or by class wildcard.
	 *
	 * @param string $test 'Class::method'.
	 * @return bool
	 */
	private static function is_allowed( string $test ): bool {
		if ( isset( self::$allowlist[ $test ] ) ) {
			return true;
		}
		$class = strtok( $test, ':' );
		return isset( self::$allowlist[ $class . '::*' ] );
	}
}
