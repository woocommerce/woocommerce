<?php
/**
 * Integration tests for Schema_Installer.
 *
 * @package WooCommerce\Subscriptions\Engine
 */

declare( strict_types=1 );

namespace WooCommerce\Subscriptions\Engine\Tests\Integration\Integration\Storage;

use Engine_Integration_Test_Case;
use WooCommerce\Subscriptions\Engine\Integration\Storage\Schema_Installer;

/**
 * @covers \WooCommerce\Subscriptions\Engine\Integration\Storage\Schema_Installer
 */
class Schema_Installer_Test extends Engine_Integration_Test_Case {

	/**
	 * The six baseline tables the installer owns.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function table_provider(): array {
		return array(
			array( Schema_Installer::TABLE_PLAN_GROUPS ),
			array( Schema_Installer::TABLE_PLANS ),
			array( Schema_Installer::TABLE_CONTRACTS ),
			array( Schema_Installer::TABLE_CONTRACT_ITEMS ),
			array( Schema_Installer::TABLE_CONTRACT_ADDRESSES ),
			array( Schema_Installer::TABLE_CONTRACT_META ),
		);
	}

	/**
	 * @dataProvider table_provider
	 *
	 * @param string $logical Logical table identifier.
	 */
	public function test_each_baseline_table_exists( string $logical ): void {
		global $wpdb;

		$table = Schema_Installer::get_table_name( $logical );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		$this->assertSame( $table, $found, "Expected table {$table} to exist." );
	}

	public function test_version_option_is_set_after_install(): void {
		$this->assertTrue( Schema_Installer::is_current() );
		$this->assertSame( Schema_Installer::VERSION, get_option( Schema_Installer::VERSION_OPTION ) );
	}

	public function test_install_is_idempotent(): void {
		// Running install again must not error or change the recorded version.
		Schema_Installer::install();

		$this->assertSame( Schema_Installer::VERSION, get_option( Schema_Installer::VERSION_OPTION ) );
	}

	public function test_unknown_table_identifier_throws(): void {
		$this->expectException( \InvalidArgumentException::class );
		Schema_Installer::get_table_name( 'not_a_table' );
	}

	public function test_plans_table_has_owner_column(): void {
		global $wpdb;

		$table = Schema_Installer::get_table_name( Schema_Installer::TABLE_PLANS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$column = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'owner' ) );

		$this->assertSame( 'owner', $column );
	}

	public function test_contracts_table_has_owner_column(): void {
		global $wpdb;

		$table = Schema_Installer::get_table_name( Schema_Installer::TABLE_CONTRACTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$column = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'owner' ) );

		$this->assertSame( 'owner', $column );
	}
}
