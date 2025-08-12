<?php
/**
 * Products Command Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Commands
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Commands;

use Automattic\WooCommerce\Internal\CLI\Migrator\Commands\ProductsCommand;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\ProductsController;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify\ShopifyFetcher;

/**
 * Test cases for ProductsCommand.
 */
class ProductsCommandTest extends \WC_Unit_Test_Case {

	/**
	 * The ProductsCommand instance under test.
	 *
	 * @var ProductsCommand
	 */
	private ProductsCommand $command;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WP_CLI' ) ) {
			require_once __DIR__ . '/../Mocks/MockWPCLI.php';
		}

		$this->command = new ProductsCommand();
	}

	/**
	 * Test that missing credentials prevents migration and shows error message.
	 */
	public function test_missing_credentials_prevents_migration() {
		// Mock CredentialManager to return false for has_credentials.
		$credential_manager = $this->createMock( CredentialManager::class );
		$credential_manager->expects( $this->once() )
			->method( 'has_credentials' )
			->with( 'shopify' )
			->willReturn( false );

		$credential_manager->expects( $this->once() )
			->method( 'setup_credentials' );

		$platform_registry = $this->createMock( PlatformRegistry::class );
		$platform_registry->expects( $this->once() )
			->method( 'resolve_platform' )
			->willReturn( 'shopify' );

		$platform_registry->expects( $this->once() )
			->method( 'get_platform_credential_fields' )
			->willReturn( array( 'api_key' => 'API Key' ) );

		$products_controller = $this->createMock( ProductsController::class );

		$products_controller->expects( $this->never() )
			->method( 'migrate_products' );

		$this->command->init( $credential_manager, $platform_registry, $products_controller );

		\WP_CLI::$last_success_message = '';
		\WP_CLI::$last_log_message     = '';

		$this->command->__invoke( array(), array( 'platform' => 'shopify' ) );

		$this->assertStringContainsString( 'not found', \WP_CLI::$last_log_message );
		$this->assertStringContainsString( 'Credentials saved successfully', \WP_CLI::$last_success_message );
	}

	/**
	 * Test that count requests don't trigger migration.
	 */
	public function test_count_request_prevents_migration() {
		// Create mocked dependencies.
		$credential_manager = $this->createMock( CredentialManager::class );
		$platform_registry  = $this->createMock( PlatformRegistry::class );
		$products_controller = $this->createMock( ProductsController::class );

		// Mock credentials exist.
		$credential_manager->expects( $this->once() )
			->method( 'has_credentials' )
			->with( 'shopify' )
			->willReturn( true );

		// Mock platform resolution.
		$platform_registry->expects( $this->once() )
			->method( 'resolve_platform' )
			->willReturn( 'shopify' );

		// Mock fetcher for count.
		$mock_fetcher = $this->createMock( ShopifyFetcher::class );
		$mock_fetcher->expects( $this->once() )
			->method( 'fetch_total_count' )
			->willReturn( 42 );

		$platform_registry->expects( $this->once() )
			->method( 'get_fetcher' )
			->willReturn( $mock_fetcher );

		// ProductsController should NEVER be called for count requests.
		$products_controller->expects( $this->never() )
			->method( 'migrate_products' );

		$this->command->init( $credential_manager, $platform_registry, $products_controller );

		// Reset mock messages.
		\WP_CLI::$last_success_message = '';
		\WP_CLI::$last_log_message     = '';

		// Call with count flag - should NOT trigger migration.
		$this->command->__invoke( array(), array( 'count' => true ) );

		// Should show count message.
		$this->assertStringContainsString( '42', \WP_CLI::$last_success_message );
	}

	/**
	 * Test that status filters are properly passed to fetcher.
	 */
	public function test_count_request_builds_filter_arguments() {
		// Create mocked dependencies.
		$credential_manager = $this->createMock( CredentialManager::class );
		$platform_registry  = $this->createMock( PlatformRegistry::class );
		$products_controller = $this->createMock( ProductsController::class );

		$credential_manager->expects( $this->once() )
			->method( 'has_credentials' )
			->willReturn( true );

		$platform_registry->expects( $this->once() )
			->method( 'resolve_platform' )
			->willReturn( 'shopify' );

		$mock_fetcher = $this->createMock( ShopifyFetcher::class );
		$mock_fetcher->expects( $this->once() )
			->method( 'fetch_total_count' )
			->with( array( 'status' => 'active' ) )
			->willReturn( 100 );

		$platform_registry->expects( $this->once() )
			->method( 'get_fetcher' )
			->willReturn( $mock_fetcher );

		$this->command->init( $credential_manager, $platform_registry, $products_controller );

		\WP_CLI::$last_success_message = '';

		$this->command->__invoke( array(), array( 'count' => true, 'status' => 'active' ) );

		// Should include status in success message.
		$this->assertStringContainsString( 'active', \WP_CLI::$last_success_message );
	}

}
