<?php
/**
 * ProductsController Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Core
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Core;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\ProductsController;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
use Automattic\WooCommerce\Internal\CLI\Migrator\Lib\ImportSession;
use Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks\MockPlatformFetcher;
use Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks\MockPlatformMapper;

/**
 * ProductsControllerTest class.
 */
class ProductsControllerTest extends \WC_Unit_Test_Case {

	/**
	 * The ProductsController instance under test.
	 *
	 * @var ProductsController
	 */
	private ProductsController $products_controller;

	/**
	 * Mock CredentialManager for testing.
	 *
	 * @var CredentialManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $credential_manager;

	/**
	 * Mock PlatformRegistry for testing.
	 *
	 * @var PlatformRegistry|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $platform_registry;

	/**
	 * Mock fetcher for testing.
	 *
	 * @var MockPlatformFetcher|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_fetcher;

	/**
	 * Mock mapper for testing.
	 *
	 * @var MockPlatformMapper|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_mapper;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock WP_CLI if not available.
		if ( ! class_exists( 'WP_CLI' ) ) {
			require_once __DIR__ . '/../Mocks/MockWPCLI.php';
		}

		// Create mocks.
		$this->credential_manager = $this->createMock( CredentialManager::class );
		$this->platform_registry  = $this->createMock( PlatformRegistry::class );
		$this->mock_fetcher       = $this->createMock( MockPlatformFetcher::class );
		$this->mock_mapper        = $this->createMock( MockPlatformMapper::class );

		// Create ProductsController instance.
		$this->products_controller = new ProductsController(
			$this->credential_manager,
			$this->platform_registry
		);

		// Clear any existing sessions.
		$this->clear_all_sessions();

		// Reset mock messages.
		$this->reset_wp_cli_messages();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		$this->clear_all_sessions();
		parent::tearDown();
	}

	/**
	 * Test ProductsController instantiation.
	 */
	public function test_products_controller_instantiation(): void {
		$this->assertInstanceOf( ProductsController::class, $this->products_controller );
	}

	/**
	 * Test init method exists and is callable.
	 */
	public function test_init_method(): void {
		$this->assertTrue( method_exists( $this->products_controller, 'init' ) );
		$this->assertTrue( is_callable( array( $this->products_controller, 'init' ) ) );

		// Should not throw exceptions.
		$this->products_controller->init();
		$this->assertTrue( true );
	}

	/**
	 * Test argument parsing with valid arguments.
	 */
	public function test_parse_and_validate_args_success(): void {
		$assoc_args = array(
			'platform'   => 'shopify',
			'limit'      => '100',
			'batch-size' => '25',
			'fields'     => 'name,price,sku',
			'status'     => 'active',
		);

		$this->setup_mocks_for_valid_args();

		$reflection = new \ReflectionClass( $this->products_controller );
		$method     = $reflection->getMethod( 'parse_and_validate_args' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->products_controller, $assoc_args );

		$this->assertIsArray( $result );
		$this->assertEquals( 'shopify', $result['platform'] );
		$this->assertEquals( 100, $result['limit'] );
		$this->assertEquals( 25, $result['batch_size'] );
		$this->assertContains( 'name', $result['fields'] );
		$this->assertContains( 'price', $result['fields'] );
		$this->assertContains( 'sku', $result['fields'] );
		$this->assertEquals( 'ACTIVE', $result['filters']['status'] );
	}

	/**
	 * Test argument parsing with invalid platform.
	 */
	public function test_parse_and_validate_args_invalid_platform(): void {
		$assoc_args = array( 'platform' => 'invalid_platform' );

		$this->platform_registry->expects( $this->once() )
			->method( 'resolve_platform' )
			->with( $assoc_args )
			->willReturn( false );

		$reflection = new \ReflectionClass( $this->products_controller );
		$method     = $reflection->getMethod( 'parse_and_validate_args' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->products_controller, $assoc_args );

		$this->assertEmpty( $result );
	}

	/**
	 * Test argument parsing with missing credentials.
	 */
	public function test_parse_and_validate_args_missing_credentials(): void {
		$assoc_args = array( 'platform' => 'shopify' );

		$this->platform_registry->expects( $this->once() )
			->method( 'resolve_platform' )
			->with( $assoc_args )
			->willReturn( 'shopify' );

		$this->credential_manager->expects( $this->once() )
			->method( 'has_credentials' )
			->with( 'shopify' )
			->willReturn( false );

		$reflection = new \ReflectionClass( $this->products_controller );
		$method     = $reflection->getMethod( 'parse_and_validate_args' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->products_controller, $assoc_args );

		$this->assertEmpty( $result );
	}

	/**
	 * Test field selection parsing.
	 */
	public function test_parse_field_selection(): void {
		$test_cases = array(
			// Test custom fields selection.
			array(
				'input'    => array( 'fields' => 'name,price,sku' ),
				'expected' => array( 'name', 'price', 'sku' ),
			),
			// Test field exclusion.
			array(
				'input'             => array( 'exclude-fields' => 'images,metafields' ),
				'expected_excluded' => array( 'images', 'metafields' ),
			),
			// Test default fields (when no specific fields provided).
			array(
				'input'             => array(),
				'expected_contains' => array( 'name', 'price', 'sku', 'description' ),
			),
		);

		$reflection = new \ReflectionClass( $this->products_controller );
		$method     = $reflection->getMethod( 'parse_field_selection' );
		$method->setAccessible( true );

		foreach ( $test_cases as $test_case ) {
			$this->reset_wp_cli_messages();
			$result = $method->invoke( $this->products_controller, $test_case['input'] );

			if ( isset( $test_case['expected'] ) ) {
				$this->assertEquals( $test_case['expected'], $result );
			} elseif ( isset( $test_case['expected_excluded'] ) ) {
				foreach ( $test_case['expected_excluded'] as $excluded_field ) {
					$this->assertNotContains( $excluded_field, $result );
				}
			} elseif ( isset( $test_case['expected_contains'] ) ) {
				foreach ( $test_case['expected_contains'] as $field ) {
					$this->assertContains( $field, $result );
				}
			}
		}
	}

	/**
	 * Test query filter parsing.
	 */
	public function test_parse_query_filters(): void {
		$test_cases = array(
			// Test status filter.
			array(
				'input'    => array( 'status' => 'active' ),
				'expected' => array( 'status' => 'ACTIVE' ),
			),
			// Test date filters.
			array(
				'input'         => array(
					'created-after'  => '2024-01-01',
					'created-before' => '2024-12-31',
				),
				'expected_keys' => array( 'created_after', 'created_before' ),
			),
			// Test product type filter.
			array(
				'input'    => array( 'product-type' => 'simple' ),
				'expected' => array( 'product_type' => 'simple' ),
			),
			// Test handle filter.
			array(
				'input'    => array( 'handle' => 'test-product' ),
				'expected' => array( 'handle' => 'test-product' ),
			),
		);

		$reflection = new \ReflectionClass( $this->products_controller );
		$method     = $reflection->getMethod( 'parse_query_filters' );
		$method->setAccessible( true );

		foreach ( $test_cases as $test_case ) {
			$result = $method->invoke( $this->products_controller, $test_case['input'] );

			if ( isset( $test_case['expected'] ) ) {
				foreach ( $test_case['expected'] as $key => $value ) {
					$this->assertArrayHasKey( $key, $result );
					$this->assertEquals( $value, $result[ $key ] );
				}
			} elseif ( isset( $test_case['expected_keys'] ) ) {
				foreach ( $test_case['expected_keys'] as $key ) {
					$this->assertArrayHasKey( $key, $result );
				}
			}
		}
	}

	/**
	 * Test date filter validation.
	 */
	public function test_validate_date_filter(): void {
		$reflection = new \ReflectionClass( $this->products_controller );
		$method     = $reflection->getMethod( 'validate_date_filter' );
		$method->setAccessible( true );

		// Valid dates.
		$valid_dates = array(
			'2024-01-01',
			'January 1, 2024',
			'2024/01/01',
			'01-01-2024',
		);

		foreach ( $valid_dates as $date ) {
			$result = $method->invoke( $this->products_controller, $date, 'test-filter' );
			$this->assertIsString( $result );
			$this->assertStringContainsString( '2024', $result );
		}

		// Invalid date.
		$result = $method->invoke( $this->products_controller, 'invalid-date', 'test-filter' );
		$this->assertNull( $result );
	}

	/**
	 * Test session creation.
	 */
	public function test_create_new_session(): void {
		$parsed_args = array(
			'platform' => 'shopify',
		);

		$reflection = new \ReflectionClass( $this->products_controller );
		$method     = $reflection->getMethod( 'create_new_session' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->products_controller, $parsed_args );

		$this->assertInstanceOf( ImportSession::class, $result );
		$this->assertEquals( 'shopify', $result->get_metadata()['data_source'] );
	}

	/**
	 * Test migration loop with basic batch processing.
	 */
	public function test_execute_migration_loop(): void {
		$this->setup_mock_session();
		$this->setup_mock_fetcher_for_migration_loop();

		$progress = $this->create_mock_progress_bar();

		$reflection = new \ReflectionClass( $this->products_controller );
		$method     = $reflection->getMethod( 'execute_migration_loop' );
		$method->setAccessible( true );

		// Set up parsed args.
		$parsed_args_property = $reflection->getProperty( 'parsed_args' );
		$parsed_args_property->setAccessible( true );
		$parsed_args_property->setValue(
			$this->products_controller,
			array(
				'limit'      => 100,
				'batch_size' => 20,
				'filters'    => array(),
			)
		);

		// Set up session.
		$session_property = $reflection->getProperty( 'session' );
		$session_property->setAccessible( true );
		$session = ImportSession::create(
			array(
				'data_source' => 'shopify',
				'file_name'   => 'Test Migration',
			)
		);
		$session_property->setValue( $this->products_controller, $session );

		// Execute the method.
		$method->invoke( $this->products_controller, $this->mock_fetcher, $this->mock_mapper, $progress );

		// Verify that methods were called.
		$this->assertTrue( true ); // If we reach here, the method executed without fatal errors.
	}

	/**
	 * Test complete migrate_products flow.
	 */
	public function test_migrate_products_complete_flow(): void {
		$assoc_args = array(
			'platform'   => 'shopify',
			'limit'      => '50',
			'batch-size' => '10',
		);

		$this->setup_complete_migration_mocks();

		// Mock ImportSession methods.
		$mock_session = $this->createMock( ImportSession::class );
		$mock_session->method( 'get_reentrancy_cursor' )->willReturn( null );
		$mock_session->method( 'count_all_imported_entities' )->willReturn( 0 );

		// Mock ImportSession static methods.
		$this->mock_import_session_static_methods( $mock_session );

		// Execute migration.
		ob_start(); // Capture any output.
		$this->products_controller->migrate_products( $assoc_args );
		ob_end_clean();

		// Verify success message was logged.
		$this->assertStringContainsString( 'Migration completed successfully', \WP_CLI::$last_success_message );
	}

	/**
	 * Helper method to set up mocks for valid argument parsing.
	 */
	private function setup_mocks_for_valid_args(): void {
		$this->platform_registry->expects( $this->once() )
			->method( 'resolve_platform' )
			->willReturn( 'shopify' );

		$this->credential_manager->expects( $this->once() )
			->method( 'has_credentials' )
			->with( 'shopify' )
			->willReturn( true );
	}

	/**
	 * Helper method to set up complete migration mocks.
	 */
	private function setup_complete_migration_mocks(): void {
		$this->platform_registry->expects( $this->atLeastOnce() )
			->method( 'resolve_platform' )
			->willReturn( 'shopify' );

		$this->credential_manager->expects( $this->atLeastOnce() )
			->method( 'has_credentials' )
			->willReturn( true );

		$this->platform_registry->expects( $this->atLeastOnce() )
			->method( 'get_fetcher' )
			->willReturn( $this->mock_fetcher );

		$this->platform_registry->expects( $this->atLeastOnce() )
			->method( 'get_mapper' )
			->willReturn( $this->mock_mapper );

		$this->mock_fetcher->expects( $this->atLeastOnce() )
			->method( 'fetch_total_count' )
			->willReturn( 25 );

		$this->mock_fetcher->expects( $this->atLeastOnce() )
			->method( 'fetch_batch' )
			->willReturn(
				array(
					'items'         => array(
						(object) array(
							'id'    => '1',
							'title' => 'Test Product 1',
						),
						(object) array(
							'id'    => '2',
							'title' => 'Test Product 2',
						),
					),
					'cursor'        => 'cursor123',
					'has_next_page' => false,
				)
			);
	}

	/**
	 * Helper method to set up mock session.
	 */
	private function setup_mock_session(): void {
		// Create a real session for testing.
		$session = ImportSession::create(
			array(
				'data_source' => 'shopify',
				'file_name'   => 'Test Migration Session',
			)
		);

		$reflection       = new \ReflectionClass( $this->products_controller );
		$session_property = $reflection->getProperty( 'session' );
		$session_property->setAccessible( true );
		$session_property->setValue( $this->products_controller, $session );
	}

	/**
	 * Helper method to set up mock fetcher for migration loop.
	 */
	private function setup_mock_fetcher_for_migration_loop(): void {
		$this->mock_fetcher->expects( $this->atLeastOnce() )
			->method( 'fetch_batch' )
			->willReturn(
				array(
					'items'         => array(
						(object) array(
							'id'    => '1',
							'title' => 'Test Product',
						),
					),
					'cursor'        => 'test_cursor',
					'has_next_page' => false,
				)
			);
	}

	/**
	 * Helper method to create mock progress bar.
	 */
	private function create_mock_progress_bar() {
		$progress = $this->getMockBuilder( \stdClass::class )
			->addMethods( array( 'tick', 'finish' ) )
			->getMock();

		$progress->method( 'tick' )->willReturn( true );
		$progress->method( 'finish' )->willReturn( true );

		return $progress;
	}

	/**
	 * Helper method to mock ImportSession static methods.
	 *
	 * @param ImportSession $mock_session The mock session instance.
	 */
	private function mock_import_session_static_methods( $mock_session ): void {
		// Note: This is a limitation in PHPUnit - we can't easily mock static methods.
		// In a real implementation, we might use dependency injection or a factory pattern.
		// For now, we'll rely on the actual ImportSession implementation.
	}

	/**
	 * Helper method to clear all import sessions.
	 */
	private function clear_all_sessions(): void {
		// Clean up any test sessions.
		global $wpdb;
		if ( isset( $wpdb ) ) {
			$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'wc_import_session'" );
		}
	}

	/**
	 * Helper method to reset WP_CLI mock messages.
	 */
	private function reset_wp_cli_messages(): void {
		if ( class_exists( 'WP_CLI' ) ) {
			\WP_CLI::$last_success_message = '';
			\WP_CLI::$last_error_message   = '';
			\WP_CLI::$last_log_message     = '';
			\WP_CLI::$last_warning_message = '';
			\WP_CLI::$all_log_messages     = array();
		}
	}
}
