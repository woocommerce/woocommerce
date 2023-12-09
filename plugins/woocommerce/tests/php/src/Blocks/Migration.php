<?php

namespace Automattic\WooCommerce\Blocks\Tests\Utils;

use Automattic\WooCommerce\Blocks\Migration;
use Automattic\WooCommerce\Blocks\Options;
use Mockery;

class MigrationTest extends \WP_UnitTestCase {
	use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		delete_option( Options::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE );
		delete_option( Options::WC_BLOCK_VERSION );
	}

	protected function tearDown(): void {
		\Mockery::close();
		parent::tearDown();
	}

	private function set_db_upgrades( Migration $mock, array $data ) {
		$reflection = new \ReflectionClass( Migration::class );
		$property   = $reflection->getProperty( 'db_upgrades' );
		$property->setAccessible( true );
		$property->setValue(
			$mock,
			$data
		);

		return $mock;
	}

	public function test_migrations_run() {
		update_option( Options::WC_BLOCK_VERSION, '1.0.0' );

		$mock = Mockery::mock( Migration::class )->makePartial();

		$mock = $this->set_db_upgrades(
			$mock,
			array(
				'2.0.0' => array(
					'execute_migration_2_0_0',
					'execute_second_migration_2_0_0',
				),
			)
		);

		$mock->expects()->execute_migration_2_0_0()->once();
		$mock->expects()->execute_second_migration_2_0_0()->once();

		$mock->run_migrations();
	}

	public function test_multiple_migrations_run() {
		update_option( Options::WC_BLOCK_VERSION, '0.0.9' );

		$mock = Mockery::mock( Migration::class )->makePartial();

		$mock = $this->set_db_upgrades(
			$mock,
			array(
				'1.0.0' => array(
					'execute_migration_1_0_0',
				),
				'2.0.0' => array(
					'execute_migration_2_0_0',
				),
			)
		);

		$mock->expects()->execute_migration_1_0_0()->once();
		$mock->expects()->execute_migration_2_0_0()->once();

		$mock->run_migrations();
	}

	public function test_skip_migrations() {
		update_option( Options::WC_BLOCK_VERSION, '2.0.0' );

		$mock = Mockery::mock( Migration::class )->makePartial();

		$mock = $this->set_db_upgrades(
			$mock,
			array(
				'2.0.0' => array(
					'execute_migration_2_0_0',
				),
			)
		);

		$mock->expects()->execute_migration_2_0_0()->never();

		$mock->run_migrations();
	}

	public function test_skip_migrations_when_missing_version_option() {

		$mock = Mockery::mock( Migration::class )->makePartial();

		$mock = $this->set_db_upgrades(
			$mock,
			array(
				'2.0.0' => array(
					'execute_migration_2_0_0',
				),
			)
		);

		$mock->expects()->execute_migration_2_0_0()->never();

		$mock->run_migrations();
	}

}
