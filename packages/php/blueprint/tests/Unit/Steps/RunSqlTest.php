<?php

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\Blueprint\Steps\RunSql;

/**
 * Unit tests for RunSql class.
 */
class RunSqlTest extends TestCase {
	/**
	 * Test the static get_step_name method.
	 */
	public function testGetStepName() {
		$this->assertEquals( 'runSql', RunSql::get_step_name() );
	}

	/**
	 * Test that from_table_row prepends the portable table-prefix placeholder
	 * to the table name instead of a live database prefix, so the exported SQL
	 * stays portable across sites with different table prefixes.
	 */
	public function testFromTableRowUsesPlaceholderPrefix() {
		$step = RunSql::from_table_row(
			array( 'name' => 'Zone A' ),
			'woocommerce_shipping_zones'
		);

		$this->assertInstanceOf( RunSql::class, $step );

		$sql = $step->prepare_json_array()['sql']['contents'];

		$this->assertStringContainsString(
			RunSql::TABLE_PREFIX_PLACEHOLDER . 'woocommerce_shipping_zones',
			$sql
		);
		// Defaults to an idempotent replace-into so re-imports don't duplicate rows.
		$this->assertStringStartsWith( 'replace into', $sql );
	}

	/**
	 * Test that from_table_row honors a custom query type.
	 */
	public function testFromTableRowAcceptsCustomType() {
		$step = RunSql::from_table_row(
			array( 'name' => 'Zone A' ),
			'woocommerce_shipping_zones',
			'insert'
		);

		$sql = $step->prepare_json_array()['sql']['contents'];

		$this->assertStringStartsWith( 'insert', $sql );
	}
}
