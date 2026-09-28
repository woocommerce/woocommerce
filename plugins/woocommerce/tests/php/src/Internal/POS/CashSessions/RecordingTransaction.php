<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\CashSessions;

use Automattic\WooCommerce\Internal\POS\CashSessions\CashSessionTransaction;

/**
 * Transaction double that records calls instead of running statements.
 *
 * Tests run inside a transaction that the framework rolls back; a real START TRANSACTION would commit it.
 */
class RecordingTransaction extends CashSessionTransaction {

	/**
	 * Calls in order: start, commit or rollback.
	 *
	 * @var string[]
	 */
	public array $calls = array();

	/**
	 * Record a start.
	 */
	public function start(): void {
		$this->calls[] = 'start';
	}

	/**
	 * Record a commit.
	 */
	public function commit(): void {
		$this->calls[] = 'commit';
	}

	/**
	 * Record a rollback.
	 */
	public function rollback(): void {
		$this->calls[] = 'rollback';
	}
}
