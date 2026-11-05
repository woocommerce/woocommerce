<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\DatabaseTransaction;
use WC_Unit_Test_Case;

/**
 * Tests for DatabaseTransaction and wc_transaction_query().
 *
 * The test bootstrap owns the transaction that every test runs in. These tests reset the owned depth to 0,
 * and replace each transaction statement with SELECT 1, so they can read the statements of a request that
 * owns no transaction without ending the test transaction.
 */
class DatabaseTransactionTest extends WC_Unit_Test_Case {

	/**
	 * Transaction statements, in order.
	 *
	 * @var string[]
	 */
	private $statements = array();

	/**
	 * Statements that fail instead of running.
	 *
	 * @var string[]
	 */
	private $failing = array();

	/**
	 * Owned depth before the test.
	 *
	 * @var int
	 */
	private $depth;

	/**
	 * Whether a caller transaction was open before the test.
	 *
	 * @var bool
	 */
	private $caller_open;

	/**
	 * Start every test with no owned transaction, and record the transaction statements instead of sending them.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->depth       = $this->set_depth( 0 );
		$this->caller_open = $this->set_caller_open( false );
		add_filter( 'query', array( $this, 'record_statement' ), 1 );
	}

	/**
	 * Restore the owned depth of the test bootstrap.
	 */
	public function tearDown(): void {
		remove_filter( 'query', array( $this, 'record_statement' ), 1 );
		$this->set_depth( $this->depth );
		$this->set_caller_open( $this->caller_open );
		parent::tearDown();
	}

	/**
	 * Record a transaction statement, and send SELECT 1 in its place, or a failing statement for those in $failing.
	 *
	 * @param string $query Query.
	 * @return string
	 */
	public function record_statement( $query ) {
		foreach ( array( 'START TRANSACTION', 'COMMIT', 'ROLLBACK', 'SAVEPOINT', 'RELEASE SAVEPOINT', 'SET autocommit' ) as $prefix ) {
			if ( 0 === strpos( $query, $prefix ) ) {
				$this->statements[] = $query;
				return in_array( $query, $this->failing, true ) ? 'RELEASE SAVEPOINT wc_missing' : 'SELECT 1';
			}
		}
		return $query;
	}

	/**
	 * @testdox wc_transaction_query() should send plain transaction statements when no transaction is owned.
	 */
	public function test_transaction_query_works_as_before_outside_an_owned_transaction(): void {
		wc_transaction_query( 'start' );
		wc_transaction_query( 'start' );
		wc_transaction_query( 'commit' );
		wc_transaction_query( 'rollback' );

		$this->assertSame( array( 'START TRANSACTION', 'START TRANSACTION', 'COMMIT', 'ROLLBACK' ), $this->statements );
		$this->assertFalse( DatabaseTransaction::is_open() );
	}

	/**
	 * @testdox run() should own the transaction, and wc_transaction_query() inside it should set savepoints.
	 */
	public function test_run_turns_nested_transactions_into_savepoints(): void {
		$result = DatabaseTransaction::run(
			static function () {
				wc_transaction_query( 'start' );
				wc_transaction_query( 'start' );
				wc_transaction_query( 'rollback' );
				wc_transaction_query( 'commit' );
				return 'done';
			}
		);

		$this->assertSame( 'done', $result );
		$this->assertSame(
			array( 'SET autocommit = 0', 'START TRANSACTION', 'SAVEPOINT wc_0', 'SAVEPOINT wc_1', 'SAVEPOINT wc_2', 'ROLLBACK TO SAVEPOINT wc_2', 'RELEASE SAVEPOINT wc_1', 'RELEASE SAVEPOINT wc_0', 'COMMIT', 'SET autocommit = 1' ),
			$this->statements
		);
		$this->assertFalse( DatabaseTransaction::is_open() );
	}

	/**
	 * @testdox run() should commit what it opened when a caller inside it leaves a transaction open.
	 */
	public function test_run_closes_a_transaction_that_a_caller_left_open(): void {
		DatabaseTransaction::run(
			static function () {
				wc_transaction_query( 'start' );
				return null;
			}
		);
		wc_transaction_query( 'start' );

		$this->assertSame(
			array( 'SET autocommit = 0', 'START TRANSACTION', 'SAVEPOINT wc_0', 'SAVEPOINT wc_1', 'RELEASE SAVEPOINT wc_0', 'COMMIT', 'SET autocommit = 1', 'START TRANSACTION' ),
			$this->statements
		);
	}

	/**
	 * @testdox run() should use a savepoint inside a transaction that wc_transaction_query() started, and leave the end to the caller.
	 */
	public function test_run_joins_a_transaction_that_a_caller_started(): void {
		wc_transaction_query( 'start' );
		DatabaseTransaction::run(
			static function () {
				wc_transaction_query( 'start' );
				wc_transaction_query( 'commit' );
				return null;
			}
		);
		$this->assertFalse( DatabaseTransaction::is_open() );
		DatabaseTransaction::run( static fn() => new \WP_Error( 'refused', 'Refused.' ) );
		wc_transaction_query( 'rollback' );
		DatabaseTransaction::run( static fn() => null );

		$this->assertSame(
			array( 'START TRANSACTION', 'SAVEPOINT wc_1', 'SAVEPOINT wc_2', 'RELEASE SAVEPOINT wc_2', 'RELEASE SAVEPOINT wc_1', 'SAVEPOINT wc_1', 'ROLLBACK TO SAVEPOINT wc_1', 'ROLLBACK', 'SET autocommit = 0', 'START TRANSACTION', 'SAVEPOINT wc_0', 'RELEASE SAVEPOINT wc_0', 'COMMIT', 'SET autocommit = 1' ),
			$this->statements
		);
		$this->assertFalse( DatabaseTransaction::is_open() );
	}

	/**
	 * @testdox run() should roll back when the callback returns a WP_Error or throws.
	 */
	public function test_run_rolls_back_on_error(): void {
		$result = DatabaseTransaction::run( static fn() => new \WP_Error( 'refused', 'Refused.' ) );
		$this->assertWPError( $result );

		try {
			DatabaseTransaction::run(
				static function () {
					throw new \RuntimeException( 'Disk full.' );
				}
			);
			$this->fail( 'The exception should reach the caller.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Disk full.', $e->getMessage() );
		}

		$this->assertSame(
			array( 'SET autocommit = 0', 'START TRANSACTION', 'SAVEPOINT wc_0', 'ROLLBACK', 'SET autocommit = 1', 'SET autocommit = 0', 'START TRANSACTION', 'SAVEPOINT wc_0', 'ROLLBACK', 'SET autocommit = 1' ),
			$this->statements
		);
		$this->assertFalse( DatabaseTransaction::is_open() );
	}

	/**
	 * @testdox run() should roll back and return a WP_Error when the database already rolled its transaction back.
	 */
	public function test_run_reports_a_transaction_the_database_rolled_back(): void {
		$this->failing = array( 'RELEASE SAVEPOINT wc_0' );

		$result = DatabaseTransaction::run( static fn() => 'done' );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_transaction_rolled_back', $result->get_error_code() );
		$this->assertSame(
			array( 'SET autocommit = 0', 'START TRANSACTION', 'SAVEPOINT wc_0', 'RELEASE SAVEPOINT wc_0', 'ROLLBACK', 'SET autocommit = 1' ),
			$this->statements
		);
		$this->assertFalse( DatabaseTransaction::is_open() );
	}

	/**
	 * @testdox run() should return a WP_Error when the database rolled back the transaction that a caller started.
	 */
	public function test_run_reports_a_rolled_back_caller_transaction(): void {
		$this->failing = array( 'RELEASE SAVEPOINT wc_1' );
		wc_transaction_query( 'start' );

		$result = DatabaseTransaction::run( static fn() => 'done' );

		$this->assertWPError( $result );
		$this->assertSame( array( 'START TRANSACTION', 'SAVEPOINT wc_1', 'RELEASE SAVEPOINT wc_1' ), $this->statements );
		$this->assertFalse( DatabaseTransaction::is_open() );
	}

	/**
	 * @testdox run() should commit without a check when a caller inside it already committed the transaction.
	 */
	public function test_run_accepts_a_transaction_that_a_caller_committed(): void {
		$result = DatabaseTransaction::run(
			static function () {
				wc_transaction_query( 'commit' );
				return 'done';
			}
		);

		$this->assertSame( 'done', $result );
		$this->assertSame(
			array( 'SET autocommit = 0', 'START TRANSACTION', 'SAVEPOINT wc_0', 'RELEASE SAVEPOINT wc_0', 'COMMIT', 'COMMIT', 'SET autocommit = 1' ),
			$this->statements
		);
	}

	/**
	 * Set whether a caller transaction is open.
	 *
	 * @param bool $open New value.
	 * @return bool The value before.
	 */
	private function set_caller_open( bool $open ): bool {
		$property = new \ReflectionProperty( DatabaseTransaction::class, 'caller_open' );
		$property->setAccessible( true );
		$before = $property->getValue();
		$property->setValue( null, $open );
		return $before;
	}

	/**
	 * Set the owned depth.
	 *
	 * @param int $depth New depth.
	 * @return int The depth before.
	 */
	private function set_depth( int $depth ): int {
		$property = new \ReflectionProperty( DatabaseTransaction::class, 'depth' );
		$property->setAccessible( true );
		$before = $property->getValue();
		$property->setValue( null, $depth );
		return $before;
	}
}
