<?php
/**
 * DatabaseTransaction class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\Jetpack\Constants;

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
 * When the database rolls the transaction back on its own, as on a deadlock, the queries that follow do not
 * commit one by one: run() rolls them back too and returns a WP_Error, so a caller never reports a lost write.
 *
 * Raw START TRANSACTION, COMMIT or ROLLBACK queries and DDL statements still end the transaction, because
 * MySQL commits before them.
 *
 * @internal
 * @since 11.4.0
 */
final class DatabaseTransaction {

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
	 * Run the callback in a transaction. Commit when it returns, roll back when it returns a WP_Error or
	 * throws. Inside another run(), or inside a transaction that wc_transaction_query() started, the callback
	 * runs in a savepoint.
	 *
	 * @param callable $callback Callback to run.
	 * @return mixed What the callback returns, or a WP_Error when the database rolled the transaction back.
	 * @throws \Throwable What the callback throws, after the rollback.
	 */
	public static function run( callable $callback ) {
		wc_maybe_define_constant( 'WC_USE_TRANSACTIONS', true );
		if ( ! Constants::is_true( 'WC_USE_TRANSACTIONS' ) ) {
			return $callback();
		}

		$joined      = 0 === self::$depth && self::$caller_open;
		$level       = $joined ? 1 : self::$depth;
		$owned       = 0 === $level;
		self::$depth = $level;
		if ( $owned ) {
			self::query( 'SET autocommit = 0' );
		}
		self::start();
		try {
			$result = $callback();
			$kept   = self::end( $level, ! is_wp_error( $result ), self::$depth > $level );
		} catch ( \Throwable $e ) {
			self::end( $level, false );
			throw $e;
		} finally {
			if ( $owned ) {
				self::query( 'SET autocommit = 1' );
			}
			self::$depth = $joined ? 0 : self::$depth;
		}

		if ( ! $kept ) {
			return new \WP_Error(
				'woocommerce_transaction_rolled_back',
				__( 'The change was not saved because another change was saved at the same time. Try again.', 'woocommerce' ),
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
	 * Start the transaction, or set a savepoint inside the open one. Other code uses run(). wc_transaction_query()
	 * calls this method inside an open transaction, and the test bootstrap calls it one time, because the test
	 * case owns the transaction that every test runs in.
	 */
	public static function start(): void {
		if ( 0 === self::$depth ) {
			self::query( 'START TRANSACTION' );
		}
		self::query( 'SAVEPOINT wc_' . self::$depth );
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
	 * Close the given level and every level that a caller left open inside it. A commit first releases the
	 * savepoint of the level, which fails only when the database already rolled the transaction back.
	 *
	 * @param int  $level  Level to close. 0 is the transaction itself.
	 * @param bool $commit Whether to keep the changes.
	 * @param bool $open   False when a caller inside run() already closed the level, so its savepoint is gone.
	 * @return bool False when a commit found the transaction rolled back.
	 */
	private static function end( int $level, bool $commit, bool $open = true ): bool {
		$level = max( 0, $level );
		$kept  = ! $commit || ! $open || self::query( 'RELEASE SAVEPOINT wc_' . $level, true );
		if ( 0 === $level ) {
			self::query( $commit && $kept ? 'COMMIT' : 'ROLLBACK' );
		} elseif ( ! $commit ) {
			self::query( 'ROLLBACK TO SAVEPOINT wc_' . $level );
		}
		self::$depth = $level;

		return $kept;
	}

	/**
	 * Send a transaction statement.
	 *
	 * @param string $sql   Statement. The only variable part is an integer level.
	 * @param bool   $quiet Whether to keep an expected failure out of the output.
	 * @return bool Whether the statement succeeded.
	 */
	private static function query( string $sql, bool $quiet = false ): bool {
		global $wpdb;

		$suppress = $quiet ? $wpdb->suppress_errors() : null;
		$result   = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The only variable part is an integer level.
		if ( null !== $suppress ) {
			$wpdb->suppress_errors( $suppress );
		}

		return false !== $result;
	}
}
