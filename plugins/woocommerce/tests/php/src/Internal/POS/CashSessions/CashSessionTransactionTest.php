<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\CashSessions;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\POS\CashSessions\CashSessionTransaction;
use WC_Unit_Test_Case;

/**
 * Tests for the CashSessionTransaction class.
 */
class CashSessionTransactionTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CashSessionTransaction
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CashSessionTransaction();
	}

	/**
	 * @testdox Should issue transaction statements even when WC_USE_TRANSACTIONS is false.
	 */
	public function test_statements_run_without_wc_use_transactions(): void {
		$this->assertFalse( Constants::is_true( 'WC_USE_TRANSACTIONS' ), 'The test bootstrap disables WooCommerce transactions' );

		$statements = array();
		$capture    = function ( $query ) use ( &$statements ) {
			if ( in_array( $query, array( 'START TRANSACTION', 'COMMIT', 'ROLLBACK' ), true ) ) {
				$statements[] = $query;
				// Keep the transaction the test framework rolls back after the test.
				return 'SELECT 1';
			}
			return $query;
		};

		add_filter( 'query', $capture );
		try {
			$this->sut->start();
			$this->sut->commit();
			$this->sut->rollback();
		} finally {
			// Removed here because the framework rolls back before it restores hooks.
			remove_filter( 'query', $capture );
		}

		$this->assertSame( array( 'START TRANSACTION', 'COMMIT', 'ROLLBACK' ), $statements );
	}
}
