<?php
/**
 * Tests for the guard against DDL inside the per-test transaction.
 *
 * @package WooCommerce\Tests\Framework
 */

declare( strict_types = 1 );

/**
 * WC_DDL_In_Transaction_Guard_Test class.
 *
 * Queries go straight to the guard's filter callback, so nothing reaches the database.
 */
class WC_DDL_In_Transaction_Guard_Test extends WC_Unit_Test_Case {

	/**
	 * Skip the calling test in report mode, which lets undeclared DDL through.
	 */
	private function skip_unless_enforcing(): void {
		if ( WC_DDL_In_Transaction_Guard::MODE_REPORT === getenv( 'WC_DDL_GUARD' ) && ! getenv( 'CI' ) ) {
			$this->markTestSkipped( 'The DDL guard is in report mode.' );
		}
	}

	/**
	 * @testdox Undeclared DDL fails, naming the test that owns the transaction.
	 */
	public function test_undeclared_ddl_fails_naming_the_test(): void {
		$this->skip_unless_enforcing();
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'DROP statement inside the test transaction of ' . __CLASS__ . '::test_undeclared_ddl_fails_naming_the_test.' );

		WC_DDL_In_Transaction_Guard::inspect_query( 'DROP TABLE wc_ddl_guard_probe' );
	}

	/**
	 * @testdox The failure names the test without its data set.
	 *
	 * @dataProvider provide_verbs
	 *
	 * @param string $sql The DDL statement.
	 */
	public function test_failure_names_the_test_without_its_data_set( string $sql ): void {
		$this->skip_unless_enforcing();
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'inside the test transaction of ' . __CLASS__ . '::test_failure_names_the_test_without_its_data_set.' );

		WC_DDL_In_Transaction_Guard::inspect_query( $sql );
	}

	/**
	 * DDL statements that commit implicitly.
	 *
	 * @return array<string, array{string}>
	 */
	public function provide_verbs(): array {
		return array(
			'create'   => array( 'CREATE TABLE wc_ddl_guard_probe (id INT)' ),
			'alter'    => array( 'ALTER TABLE wc_ddl_guard_probe ADD COLUMN x INT' ),
			'truncate' => array( 'TRUNCATE TABLE wc_ddl_guard_probe' ),
			'rename'   => array( 'RENAME TABLE wc_ddl_guard_probe TO wc_ddl_guard_probe_2' ),
		);
	}

	/**
	 * @testdox A COMMIT or START TRANSACTION from code under test does not switch the guard off.
	 */
	public function test_transaction_statements_from_code_under_test_keep_the_guard_on(): void {
		$this->skip_unless_enforcing();
		WC_DDL_In_Transaction_Guard::inspect_query( 'COMMIT' );
		WC_DDL_In_Transaction_Guard::inspect_query( 'START TRANSACTION' );
		WC_DDL_In_Transaction_Guard::inspect_query( 'ROLLBACK' );

		$this->expectException( RuntimeException::class );
		WC_DDL_In_Transaction_Guard::inspect_query( 'ALTER TABLE wc_ddl_guard_probe ADD COLUMN x INT' );
	}

	/**
	 * @testdox Temporary table DDL passes, since it does not commit.
	 */
	public function test_passes_temporary_table_ddl(): void {
		$sql = 'CREATE TEMPORARY TABLE wc_ddl_guard_probe (id INT)';

		$this->assertSame( $sql, WC_DDL_In_Transaction_Guard::inspect_query( $sql ) );
	}

	/**
	 * @testdox DDL passes in a test that declares it.
	 *
	 * @ddlInTransaction Covers the guard's handling of the annotation.
	 */
	public function test_passes_declared_ddl(): void {
		$sql = 'DROP TABLE wc_ddl_guard_probe';

		$this->assertSame( $sql, WC_DDL_In_Transaction_Guard::inspect_query( $sql ) );
	}
}
