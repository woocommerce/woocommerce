<?php
/**
 * DatabaseTransaction class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Utilities\StringUtil;

defined( 'ABSPATH' ) || exit;

/**
 * A database transaction that one caller owns from start to end.
 *
 * While the transaction is open, wc_transaction_query() sets savepoints instead of starting and ending
 * transactions, so a caller that opens and closes its own transaction inside it does not end it early.
 * When run() ends, it always commits or rolls back what it opened, even when a caller inside it left a
 * transaction open. Outside run(), wc_transaction_query() works as it always did.
 *
 * When a caller started a transaction with wc_transaction_query() before run(), run() works in a savepoint
 * inside it, so the caller's commit or rollback decides the outcome, as on code without run().
 *
 * When a query inside run() hits a deadlock, a lock wait timeout or a MariaDB snapshot conflict, the database
 * rolls back the transaction or that one query, so run() rolls back the rest and returns a WP_Error. When run() owns the transaction, autocommit
 * is off while it runs, so the queries after a deadlock roll back too. Inside a transaction that a caller
 * started, they commit one by one, as on code without run().
 *
 * Raw START TRANSACTION, COMMIT or ROLLBACK queries and DDL statements still end the transaction, because
 * MySQL commits before them. The queries after them commit or roll back when run() ends.
 *
 * @internal
 * @since 11.4.0
 */
final class DatabaseTransaction {

	/**
	 * Error numbers of a snapshot conflict (MariaDB with innodb_snapshot_isolation), a lock wait timeout and a deadlock.
	 */
	private const LOST_WRITE_ERRNOS = array( 1020, 1205, 1213 );

	/**
	 * Number of open levels: the transaction, then one per savepoint. 0 when no transaction is owned.
	 *
	 * @var int
	 */
	private static $depth = 0;

	/**
	 * Whether wc_transaction_query() started a transaction outside run() that it has not ended yet.
	 *
	 * @var bool
	 */
	private static $caller_open = false;

	/**
	 * Whether the outermost run() watches its queries for an error that lost the write.
	 *
	 * @var bool
	 */
	private static $watching = false;

	/**
	 * Whether a query inside the outermost run() hit an error that lost the write.
	 *
	 * @var bool
	 */
	private static $lost = false;

	/**
	 * Run the callback in a transaction. Commit when it returns, roll back when it returns a WP_Error or
	 * throws. Inside another run(), or inside a transaction that wc_transaction_query() started, the callback
	 * runs in a savepoint.
	 *
	 * @param callable $callback Callback to run.
	 * @return mixed What the callback returns, or a WP_Error when a query hit an error that lost the write.
	 * @throws \Throwable What the callback throws, after the rollback.
	 */
	public static function run( callable $callback ) {
		wc_maybe_define_constant( 'WC_USE_TRANSACTIONS', true );
		if ( ! Constants::is_true( 'WC_USE_TRANSACTIONS' ) ) {
			return $callback();
		}

		$joined      = 0 === self::$depth && self::$caller_open;
		$level       = $joined ? 1 : self::$depth;
		$outermost   = ! self::$watching;
		$autocommit  = 0 === $level && self::is_autocommit_on();
		self::$depth = $level;
		$started     = false;
		try {
			if ( $autocommit ) {
				self::query( 'SET autocommit = 0' );
			}
			self::start();
			$started = true;
			if ( $outermost ) {
				self::$lost     = false;
				self::$watching = true;
				add_filter( 'query', array( self::class, 'watch' ) );
			}
			$result = $callback();
			self::end( $level, ! is_wp_error( $result ) );
			$lost = self::$lost;
		} catch ( \Throwable $e ) {
			// Rolling back to a savepoint that was never set is itself a database error.
			if ( $started ) {
				self::end( $level, false );
			}
			throw $e;
		} finally {
			if ( $outermost ) {
				remove_filter( 'query', array( self::class, 'watch' ) );
				self::$watching = false;
				self::$lost     = false;
			}
			if ( $autocommit ) {
				self::query( 'SET autocommit = 1' );
			}
			self::$depth = $joined ? 0 : $level;
		}

		if ( $lost && ! is_wp_error( $result ) ) {
			return new \WP_Error(
				'woocommerce_transaction_rolled_back',
				__( 'The change might not have been saved because another change ran at the same time. Check it and try again.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		return $result;
	}

	/**
	 * Whether run() owns an open transaction.
	 *
	 * @return bool
	 */
	public static function is_open(): bool {
		return self::$depth > 0;
	}

	/**
	 * Record a start, commit or rollback that wc_transaction_query() sent outside run().
	 *
	 * @param string $type Types: start, commit, rollback.
	 */
	public static function track_caller( string $type ): void {
		self::$caller_open = 'commit' !== $type && 'rollback' !== $type;
	}

	/**
	 * Note an error of the previous query that lost the write. WordPress applies the query filter before it
	 * clears the error of the previous query.
	 *
	 * @param mixed $query Query, returned unchanged.
	 * @return mixed
	 */
	public static function watch( $query ) {
		self::check_last_error();
		return $query;
	}

	/**
	 * Start the transaction, or set a savepoint inside the open one. Other code uses run(). wc_transaction_query()
	 * calls this method inside an open transaction, and the test bootstrap calls it one time, because the test
	 * case owns the transaction that every test runs in.
	 */
	public static function start(): void {
		self::query( 0 === self::$depth ? 'START TRANSACTION' : 'SAVEPOINT wc_' . self::$depth );
		++self::$depth;
	}

	/**
	 * Release or roll back the latest savepoint for wc_transaction_query(). At the first level, commit or roll
	 * back the transaction, as wc_transaction_query() always did.
	 *
	 * @param bool $commit Whether to keep the changes.
	 */
	public static function close_latest( bool $commit ): void {
		self::end( self::$depth - 1, $commit );
	}

	/**
	 * Close the given level and every level that a caller left open inside it. After an error that lost the
	 * write, roll back instead of committing. A COMMIT can fail too, when Galera finds a certification conflict.
	 *
	 * @param int  $level  Level to close. 0 is the transaction itself.
	 * @param bool $commit Whether to keep the changes.
	 */
	private static function end( int $level, bool $commit ): void {
		self::check_last_error();
		$commit = $commit && ! self::$lost;
		$level  = max( 0, $level );
		if ( 0 === $level ) {
			self::query( $commit ? 'COMMIT' : 'ROLLBACK' );
		} else {
			// A deadlock already dropped the savepoint, so rolling back to it is expected to fail.
			self::query( ( $commit ? 'RELEASE SAVEPOINT wc_' : 'ROLLBACK TO SAVEPOINT wc_' ) . $level, self::$lost );
		}
		self::check_last_error();
		self::$depth = $level;
	}

	/**
	 * Mark the transaction lost when the previous query hit one of LOST_WRITE_ERRNOS. The server ends the
	 * message of each with "try restarting transaction", which covers database drivers other than mysqli.
	 */
	private static function check_last_error(): void {
		global $wpdb;

		if ( ! self::$watching || self::$lost || empty( $wpdb->last_error ) ) {
			return;
		}

		$dbh        = $wpdb instanceof \wpdb ? $wpdb->__get( 'dbh' ) : null;
		$errno      = $dbh instanceof \mysqli ? mysqli_errno( $dbh ) : 0; // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_errno -- wpdb does not expose the error number.
		self::$lost = in_array( $errno, self::LOST_WRITE_ERRNOS, true )
			|| StringUtil::ends_with( rtrim( (string) $wpdb->last_error ), 'try restarting transaction' );
	}

	/**
	 * Whether the connection commits each query on its own, which MySQL does by default.
	 *
	 * @return bool
	 */
	private static function is_autocommit_on(): bool {
		global $wpdb;

		return '1' === (string) $wpdb->get_var( 'SELECT @@autocommit' );
	}

	/**
	 * Send a transaction statement.
	 *
	 * @param string $sql   Statement. The only variable part is an integer level.
	 * @param bool   $quiet Whether to keep an expected failure out of the output.
	 */
	private static function query( string $sql, bool $quiet = false ): void {
		global $wpdb;

		$suppress = $quiet ? $wpdb->suppress_errors() : null;
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The only variable part is an integer level.
		if ( null !== $suppress ) {
			$wpdb->suppress_errors( $suppress );
		}
	}
}
