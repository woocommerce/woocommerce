<?php

namespace Automattic\WooCommerce\Blocks\Tests\Utils;

use Automattic\WooCommerce\Blocks\Migration;
use Automattic\WooCommerce\Blocks\Options;

class MigrationTest extends \WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
		delete_option( Options::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE );
		delete_option( Options::WC_BLOCK_VERSION );
	}

	public function test_migrations_run() {
		update_option( Options::WC_BLOCK_VERSION, '1.0.0' );

		$mock = $this->createPartialMock( Migration::class, array( 'execute_migration_2_0_0', 'execute_second_migration_2_0_0' ) );

		$reflection = new \ReflectionClass( Migration::class );
		$property   = $reflection->getProperty( 'db_upgrades' );
		$property->setAccessible( true );
		$property->setValue(
			$mock,
			array(
				'2.0.0' => array(
					'execute_migration_2_0_0',
					'execute_second_migration_2_0_0',
				),
			)
		);

		$mock->expects( $this->once() )
		->method( 'execute_migration_2_0_0' );
		$mock->expects( $this->once() )
		->method( 'execute_second_migration_2_0_0' );

		$mock->run_migrations();

	}

	public function test_multiple_migrations_run() {
		update_option( Options::WC_BLOCK_VERSION, '0.0.9' );

		$mock = $this->createPartialMock( Migration::class, array( 'execute_migration_1_0_0', 'execute_migration_2_0_0' ) );

		$reflection = new \ReflectionClass( Migration::class );
		$property   = $reflection->getProperty( 'db_upgrades' );
		$property->setAccessible( true );
		$property->setValue(
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

		$mock->expects( $this->once() )
		->method( 'execute_migration_1_0_0' );

		$mock->expects( $this->once() )
		->method( 'execute_migration_2_0_0' );

		$mock->run_migrations();

	}

	public function test_skip_migrations() {
		update_option( Options::WC_BLOCK_VERSION, '2.0.0' );

		$mock = $this->createPartialMock( Migration::class, array( 'execute_migration_2_0_0' ) );

		$reflection = new \ReflectionClass( Migration::class );
		$property   = $reflection->getProperty( 'db_upgrades' );
		$property->setAccessible( true );
		$property->setValue(
			$mock,
			array(
				'2.0.0' => array(
					'execute_migration_2_0_0',
				),
			)
		);

		$mock->expects( $this->exactly( 0 ) )
		->method( 'execute_migration_2_0_0' );

		$mock->run_migrations();

	}

	public function test_skip_migrations_when_missing_version_option() {

		$mock = $this->createPartialMock( Migration::class, array( 'execute_migration_2_0_0' ) );

		$reflection = new \ReflectionClass( Migration::class );
		$property   = $reflection->getProperty( 'db_upgrades' );
		$property->setAccessible( true );
		$property->setValue(
			$mock,
			array(
				'2.0.0' => array(
					'execute_migration_2_0_0',
				),
			)
		);

		$mock->expects( $this->exactly( 0 ) )
		->method( 'execute_migration_2_0_0' );

		$mock->run_migrations();

	}

}
