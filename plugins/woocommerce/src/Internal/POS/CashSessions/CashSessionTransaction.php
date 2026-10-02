<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

/**
 * Database transaction around a cash session write.
 *
 * The statements always run, even when a site defines WC_USE_TRANSACTIONS as false: the row locks and
 * rollbacks are what keep a session and its movements consistent, so they cannot depend on that setting.
 *
 * @since 11.3.0
 */
class CashSessionTransaction {

	/**
	 * Start a transaction.
	 *
	 * @since 11.3.0
	 */
	public function start(): void {
		wc_transaction_query( 'start', true );
	}

	/**
	 * Commit the transaction.
	 *
	 * @since 11.3.0
	 */
	public function commit(): void {
		wc_transaction_query( 'commit', true );
	}

	/**
	 * Roll back the transaction. Safe to call when none is active.
	 *
	 * @since 11.3.0
	 */
	public function rollback(): void {
		wc_transaction_query( 'rollback', true );
	}
}
