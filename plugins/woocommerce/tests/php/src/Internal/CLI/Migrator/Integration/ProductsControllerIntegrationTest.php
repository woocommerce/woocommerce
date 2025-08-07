<?php
/**
 * ProductsController Integration Test
 *
 * Tests the integration between ProductsCommand and ProductsController
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Integration
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Integration;

use Automattic\WooCommerce\Internal\CLI\Migrator\Commands\ProductsCommand;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\ProductsController;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
use Automattic\WooCommerce\Internal\CLI\Migrator\Lib\ImportSession;
use Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Fixtures\MockShopifyData;
use Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks\MockPlatformFetcher;
use Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks\MockPlatformMapper;

/**
 * ProductsControllerIntegrationTest class.
 *
 * Tests the complete integration flow from CLI command to controller execution.
 */
class ProductsControllerIntegrationTest extends \WC_Unit_Test_Case {

	/**
	 * Real ProductsCommand instance for testing.
	 *
	 * @var ProductsCommand
	 */
	private ProductsCommand $products_command;

	/**
	 * Real ProductsController instance for testing.
	 *
	 * @var ProductsController
	 */
	private ProductsController $products_controller;

	/**
	 * Real CredentialManager instance for testing.
	 *
	 * @var CredentialManager
	 */
	private CredentialManager $credential_manager;

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

		// Create real instances for integration testing.
		$this->credential_manager  = new CredentialManager();
		$this->platform_registry   = $this->createMock( PlatformRegistry::class );
		$this->products_controller = new ProductsController( $this->credential_manager, $this->platform_registry );
		$this->products_command    = new ProductsCommand();

		// Create mocks for external dependencies.
		$this->mock_fetcher = $this->createMock( MockPlatformFetcher::class );
		$this->mock_mapper  = $this->createMock( MockPlatformMapper::class );

		// Set up credentials for testing.
		$this->setup_test_credentials();

		// Clean up any existing sessions.
		$this->clear_all_sessions();

		// Reset mock messages.
		$this->reset_wp_cli_messages();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		$this->clear_all_sessions();
		$this->clear_test_credentials();
		parent::tearDown();
	}

	/**
	 * Test complete integration flow: ProductsCommand → ProductsController → Session Management.
	 */
	public function test_complete_integration_flow(): void {
		// Set up platform registry mocks.
		$this->setup_platform_registry_mocks();
		$this->setup_fetcher_and_mapper_mocks();

		// Initialize command with dependencies.
		$this->products_command->init(
			$this->credential_manager,
			$this->platform_registry,
			$this->products_controller
		);

		// Execute migration command.
		$assoc_args = MockShopifyData::get_mock_command_args(
			array(
				'limit'      => '10',
				'batch-size' => '5',
			)
		);

		// Capture output and execute.
		ob_start();
		$this->products_command->__invoke( array(), $assoc_args );
		$output = ob_get_clean();

		// Verify success message was displayed.
		$this->assertStringContainsString( 'Migration completed successfully', \WP_CLI::$last_success_message );

		// Verify session was created and used.
		$active_session = ImportSession::get_active();
		$this->assertInstanceOf( ImportSession::class, $active_session );
		$this->assertEquals( 'shopify', $active_session->get_metadata()['data_source'] );
	}

	/**
	 * Test session resumption integration.
	 */
	public function test_session_resumption_integration(): void {
		// Create an existing session.
		$existing_session = ImportSession::create( MockShopifyData::get_mock_session_data() );
		$existing_session->set_reentrancy_cursor( 'cursor_12345' );
		$existing_session->bump_total_number_of_entities( array( 'post' => 100 ) );
		$existing_session->bump_imported_entities_counts( array( 'post' => 25 ) );

		$this->setup_platform_registry_mocks();
		$this->setup_fetcher_and_mapper_mocks_for_resume();

		// Initialize command.
		$this->products_command->init(
			$this->credential_manager,
			$this->platform_registry,
			$this->products_controller
		);

		// Execute migration with resume flag.
		$assoc_args = MockShopifyData::get_mock_command_args(
			array(
				'resume' => true,
				'limit'  => '50',
			)
		);

		ob_start();
		$this->products_command->__invoke( array(), $assoc_args );
		ob_end_clean();

		// Verify session was resumed, not recreated.
		$resumed_session = ImportSession::get_active();
		$this->assertEquals( $existing_session->get_id(), $resumed_session->get_id() );
		$this->assertStringContainsString( 'Resuming migration session', \WP_CLI::$last_success_message );
	}

	/**
	 * Test argument validation integration.
	 */
	public function test_argument_validation_integration(): void {
		$this->setup_platform_registry_mocks();

		$this->products_command->init(
			$this->credential_manager,
			$this->platform_registry,
			$this->products_controller
		);

		// Test with valid field selection.
		$assoc_args = MockShopifyData::get_mock_command_args(
			array(
				'fields'         => 'name,price,sku',
				'exclude-fields' => 'images,metafields',
				'status'         => 'active',
			)
		);

		$this->setup_fetcher_and_mapper_mocks();

		ob_start();
		$this->products_command->__invoke( array(), $assoc_args );
		ob_end_clean();

		// Verify field selection was processed.
		$this->assertStringContainsString( 'Selected fields for migration', \WP_CLI::$last_log_message );
		$this->assertTrue( true ); // Test passes if no exceptions thrown.
	}

	/**
	 * Test error handling integration.
	 */
	public function test_error_handling_integration(): void {
		// Clear credentials to simulate missing credentials error.
		$this->clear_test_credentials();

		$this->platform_registry->expects( $this->once() )
			->method( 'resolve_platform' )
			->willReturn( 'shopify' );

		$this->products_command->init(
			$this->credential_manager,
			$this->platform_registry,
			$this->products_controller
		);

		// Execute migration - should handle missing credentials.
		$assoc_args = array( 'platform' => 'shopify' );

		ob_start();
		$this->products_command->__invoke( array(), $assoc_args );
		ob_end_clean();

		// Should have triggered credential setup flow.
		$this->assertStringContainsString( 'not found', \WP_CLI::$last_log_message );
	}

	/**
	 * Test count operation does not trigger migration controller.
	 */
	public function test_count_operation_bypass(): void {
		$this->setup_platform_registry_mocks();

		// Mock fetcher for count operation.
		$this->mock_fetcher->expects( $this->once() )
			->method( 'fetch_total_count' )
			->willReturn( 150 );

		$this->platform_registry->expects( $this->once() )
			->method( 'get_fetcher' )
			->willReturn( $this->mock_fetcher );

		$this->products_command->init(
			$this->credential_manager,
			$this->platform_registry,
			$this->products_controller
		);

		// Execute count operation.
		$assoc_args = array( 'count' => true );

		ob_start();
		$this->products_command->__invoke( array(), $assoc_args );
		ob_end_clean();

		// Verify count was displayed but no migration occurred.
		$this->assertStringContainsString( '150 products', \WP_CLI::$last_success_message );
		$this->assertNull( ImportSession::get_active() ); // No session should be created.
	}

	/**
	 * Test fetch operation does not trigger migration controller.
	 */
	public function test_fetch_operation_bypass(): void {
		$this->setup_platform_registry_mocks();

		// Mock fetcher for fetch operation.
		$mock_products = MockShopifyData::get_mock_products( 2 );
		$mock_response = MockShopifyData::get_mock_batch_response( $mock_products, 'cursor123', false );

		$this->mock_fetcher->expects( $this->once() )
			->method( 'fetch_batch' )
			->willReturn( $mock_response );

		$this->platform_registry->expects( $this->once() )
			->method( 'get_fetcher' )
			->willReturn( $this->mock_fetcher );

		$this->products_command->init(
			$this->credential_manager,
			$this->platform_registry,
			$this->products_controller
		);

		// Execute fetch operation.
		$assoc_args = array(
			'fetch' => true,
			'limit' => '2',
		);

		ob_start();
		$this->products_command->__invoke( array(), $assoc_args );
		ob_end_clean();

		// Verify products were displayed but no migration occurred.
		$this->assertStringContainsString( 'Successfully fetched 2 products', \WP_CLI::$last_success_message );
		$this->assertNull( ImportSession::get_active() ); // No session should be created.
	}

	/**
	 * Helper method to set up test credentials.
	 */
	private function setup_test_credentials(): void {
		$credentials = MockShopifyData::get_mock_credentials( 'shopify' );
		$this->credential_manager->save_credentials( 'shopify', $credentials );
	}

	/**
	 * Helper method to clear test credentials.
	 */
	private function clear_test_credentials(): void {
		$this->credential_manager->delete_credentials( 'shopify' );
	}

	/**
	 * Helper method to set up platform registry mocks.
	 */
	private function setup_platform_registry_mocks(): void {
		$this->platform_registry->method( 'resolve_platform' )
			->willReturn( 'shopify' );

		$this->platform_registry->method( 'get_fetcher' )
			->willReturn( $this->mock_fetcher );

		$this->platform_registry->method( 'get_mapper' )
			->willReturn( $this->mock_mapper );
	}

	/**
	 * Helper method to set up fetcher and mapper mocks.
	 */
	private function setup_fetcher_and_mapper_mocks(): void {
		$mock_products = MockShopifyData::get_mock_products( 3 );

		$this->mock_fetcher->method( 'fetch_total_count' )
			->willReturn( 25 );

		$this->mock_fetcher->method( 'fetch_batch' )
			->willReturn( MockShopifyData::get_mock_batch_response( $mock_products, 'cursor123', false ) );
	}

	/**
	 * Helper method to set up mocks for session resumption.
	 */
	private function setup_fetcher_and_mapper_mocks_for_resume(): void {
		$mock_products = MockShopifyData::get_mock_products( 2 );

		$this->mock_fetcher->method( 'fetch_total_count' )
			->willReturn( 100 );

		$this->mock_fetcher->method( 'fetch_batch' )
			->will(
				$this->onConsecutiveCalls(
					MockShopifyData::get_mock_batch_response( $mock_products, 'cursor456', true ),
					MockShopifyData::get_mock_batch_response( array(), null, false )
				)
			);
	}

	/**
	 * Helper method to clear all import sessions.
	 */
	private function clear_all_sessions(): void {
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
