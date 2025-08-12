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
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify\ShopifyFetcher;
use WP_Error;

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
	 * Mock CredentialManager for testing.
	 *
	 * @var CredentialManager
	 */
	private CredentialManager $credential_manager;

	/**
	 * Mock PlatformRegistry for testing.
	 *
	 * @var PlatformRegistry
	 */
	private PlatformRegistry $platform_registry;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->credential_manager = new CredentialManager();
		$this->platform_registry  = new PlatformRegistry();
		$this->command            = new ProductsCommand();
	}

	/**
	 * Test that ProductsCommand can be instantiated.
	 */
	public function test_products_command_instantiation() {
		$this->assertInstanceOf( ProductsCommand::class, $this->command );
	}

	/**
	 * Test dependency injection via init method.
	 */
	public function test_dependency_injection_via_init() {
		$this->assertTrue( method_exists( $this->command, 'init' ) );

		// Test that init method can be called without errors.
		try {
			$this->command->init( $this->credential_manager, $this->platform_registry );
			$this->assertTrue( true );
		} catch ( \Exception $e ) {
			$this->fail( 'init method should not throw exceptions: ' . $e->getMessage() );
		}
	}

	/**
	 * Test that the command has the required __invoke method.
	 */
	public function test_invoke_method_exists() {
		$this->assertTrue( method_exists( $this->command, '__invoke' ) );
		$this->assertTrue( is_callable( array( $this->command, '__invoke' ) ) );
	}

	/**
	 * Test that the command can be initialized and dependencies are properly injected.
	 */
	public function test_dependency_injection_properties() {
		$this->command->init( $this->credential_manager, $this->platform_registry );

		$this->assertTrue( true );
	}

	/**
	 * Test handle_count_request with successful count response.
	 */
	public function test_handle_count_request_success() {
		// Mock WP_CLI if not available.
		if ( ! class_exists( 'WP_CLI' ) ) {
			require_once __DIR__ . '/../Mocks/MockWPCLI.php';
		}

		// Create mock fetcher.
		$mock_fetcher = $this->createMock( ShopifyFetcher::class );
		$mock_fetcher->expects( $this->once() )
			->method( 'fetch_total_count' )
			->with( array() )
			->willReturn( 1023 );

		// Mock platform registry to return the mock fetcher.
		$mock_registry = $this->createMock( PlatformRegistry::class );
		$mock_registry->expects( $this->once() )
			->method( 'get_fetcher' )
			->with( 'shopify' )
			->willReturn( $mock_fetcher );

		$this->command->init( $this->credential_manager, $mock_registry );

		// Use reflection to call private method.
		$reflection = new \ReflectionClass( $this->command );
		$method     = $reflection->getMethod( 'handle_count_request' );
		$method->setAccessible( true );

		// Reset mock messages.
		\WP_CLI::$last_success_message = '';
		\WP_CLI::$last_log_message     = '';

		$method->invoke( $this->command, 'shopify', array() );

		// Check that success message was called with the count.
		$this->assertStringContainsString( '1023', \WP_CLI::$last_success_message );
		$this->assertStringContainsString( 'products', \WP_CLI::$last_success_message );
	}

	/**
	 * Test handle_count_request with status filter.
	 */
	public function test_handle_count_request_with_status_filter() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			require_once __DIR__ . '/../Mocks/MockWPCLI.php';
		}

		$mock_fetcher = $this->createMock( ShopifyFetcher::class );
		$mock_fetcher->expects( $this->once() )
			->method( 'fetch_total_count' )
			->with( array( 'status' => 'active' ) )
			->willReturn( 1021 );

		$mock_registry = $this->createMock( PlatformRegistry::class );
		$mock_registry->expects( $this->once() )
			->method( 'get_fetcher' )
			->with( 'shopify' )
			->willReturn( $mock_fetcher );

		$this->command->init( $this->credential_manager, $mock_registry );

		$reflection = new \ReflectionClass( $this->command );
		$method     = $reflection->getMethod( 'handle_count_request' );
		$method->setAccessible( true );

		// Reset mock messages.
		\WP_CLI::$last_success_message = '';
		\WP_CLI::$last_log_message     = '';

		$method->invoke( $this->command, 'shopify', array( 'status' => 'active' ) );

		// Check that success message was called with the count and status.
		$this->assertStringContainsString( '1021', \WP_CLI::$last_success_message );
		$this->assertStringContainsString( 'active', \WP_CLI::$last_success_message );
	}

	/**
	 * Test handle_count_request with error response (returns 0).
	 */
	public function test_handle_count_request_error() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			require_once __DIR__ . '/../Mocks/MockWPCLI.php';
		}

		$mock_fetcher = $this->createMock( ShopifyFetcher::class );
		$mock_fetcher->expects( $this->once() )
			->method( 'fetch_total_count' )
			->willReturn( 0 ); // Returns 0 on error, not WP_Error.

		$mock_registry = $this->createMock( PlatformRegistry::class );
		$mock_registry->expects( $this->once() )
			->method( 'get_fetcher' )
			->with( 'shopify' )
			->willReturn( $mock_fetcher );

		$this->command->init( $this->credential_manager, $mock_registry );

		$reflection = new \ReflectionClass( $this->command );
		$method     = $reflection->getMethod( 'handle_count_request' );
		$method->setAccessible( true );

		// Reset mock messages.
		\WP_CLI::$last_success_message = '';
		\WP_CLI::$last_log_message     = '';

		$method->invoke( $this->command, 'shopify', array() );

		// Should show the "no products found" message when count is 0.
		$this->assertStringContainsString( 'No products found or unable to fetch count', \WP_CLI::$last_log_message );
	}

	/**
	 * Test handle_fetch_request with successful fetch response.
	 */
	public function test_handle_fetch_request_success() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			require_once __DIR__ . '/../Mocks/MockWPCLI.php';
		}

		// Mock product data - each item should have a 'node' property (GraphQL edge structure).
		$mock_products = array(
			(object) array(
				'node' => (object) array(
					'id'       => 'gid://shopify/Product/123',
					'title'    => 'Test Product 1',
					'status'   => 'ACTIVE',
					'variants' => (object) array(
						'edges' => array(
							(object) array(
								'node' => (object) array(
									'id'    => 'gid://shopify/ProductVariant/456',
									'title' => 'Default Title',
								),
							),
						),
					),
				),
			),
			(object) array(
				'node' => (object) array(
					'id'       => 'gid://shopify/Product/124',
					'title'    => 'Test Product 2',
					'status'   => 'DRAFT',
					'variants' => (object) array(
						'edges' => array(),
					),
				),
			),
		);

		$mock_response = array(
			'items'         => $mock_products,
			'hasNextPage'   => true,
			'has_next_page' => true,
			'cursor'        => 'cursor123',
		);

		$mock_fetcher = $this->createMock( ShopifyFetcher::class );
		$mock_fetcher->expects( $this->once() )
			->method( 'fetch_batch' )
			->with(
				array(
					'limit'        => 5,
					'after_cursor' => null,
				)
			)
			->willReturn( $mock_response );

		$mock_registry = $this->createMock( PlatformRegistry::class );
		$mock_registry->expects( $this->once() )
			->method( 'get_fetcher' )
			->with( 'shopify' )
			->willReturn( $mock_fetcher );

		$this->command->init( $this->credential_manager, $mock_registry );

		$reflection = new \ReflectionClass( $this->command );
		$method     = $reflection->getMethod( 'handle_fetch_request' );
		$method->setAccessible( true );

		// Reset mock messages.
		\WP_CLI::$last_success_message = '';
		\WP_CLI::$last_log_message     = '';
		\WP_CLI::$all_log_messages     = array();

		$method->invoke( $this->command, 'shopify', array( 'limit' => '5' ) );

		// Convert all log messages to a single string for easier assertion.
		$all_output = implode( ' ', \WP_CLI::$all_log_messages );

		// The command should log information about the products found.
		$this->assertStringContainsString( 'Test Product 1', $all_output );
		$this->assertStringContainsString( 'Test Product 2', $all_output );
		$this->assertStringContainsString( 'ACTIVE', $all_output );
		$this->assertStringContainsString( 'DRAFT', $all_output );
		$this->assertStringContainsString( 'More products available', $all_output );
		$this->assertStringContainsString( '--after=cursor123', $all_output );
	}

	/**
	 * Test handle_fetch_request with cursor pagination.
	 */
	public function test_handle_fetch_request_with_cursor() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			require_once __DIR__ . '/../Mocks/MockWPCLI.php';
		}

		$mock_products = array(
			(object) array(
				'node' => (object) array(
					'id'       => 'gid://shopify/Product/125',
					'title'    => 'Test Product 3',
					'status'   => 'ACTIVE',
					'variants' => (object) array(
						'edges' => array(),
					),
				),
			),
		);

		$mock_response = array(
			'items'         => $mock_products,
			'hasNextPage'   => false,
			'has_next_page' => false,
			'cursor'        => 'cursor456',
		);

		$mock_fetcher = $this->createMock( ShopifyFetcher::class );
		$mock_fetcher->expects( $this->once() )
			->method( 'fetch_batch' )
			->with(
				array(
					'limit'        => 3,
					'after_cursor' => 'cursor123',
				)
			)
			->willReturn( $mock_response );

		$mock_registry = $this->createMock( PlatformRegistry::class );
		$mock_registry->expects( $this->once() )
			->method( 'get_fetcher' )
			->with( 'shopify' )
			->willReturn( $mock_fetcher );

		$this->command->init( $this->credential_manager, $mock_registry );

		$reflection = new \ReflectionClass( $this->command );
		$method     = $reflection->getMethod( 'handle_fetch_request' );
		$method->setAccessible( true );

		// Reset mock messages.
		\WP_CLI::$last_success_message = '';
		\WP_CLI::$last_log_message     = '';
		\WP_CLI::$all_log_messages     = array();

		$method->invoke(
			$this->command,
			'shopify',
			array(
				'limit' => '3',
				'after' => 'cursor123',
			)
		);

		// Convert all log messages to a single string for easier assertion.
		$all_output = implode( ' ', \WP_CLI::$all_log_messages );

		$this->assertStringContainsString( 'Test Product 3', $all_output );
		$this->assertStringNotContainsString( 'More products available', $all_output );
	}

	/**
	 * Test handle_fetch_request with error response (empty result).
	 */
	public function test_handle_fetch_request_error() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			require_once __DIR__ . '/../Mocks/MockWPCLI.php';
		}

		// Mock an error response - fetch_batch returns empty array on error.
		$mock_error_response = array(
			'items'         => array(),
			'cursor'        => null,
			'has_next_page' => false,
		);

		$mock_fetcher = $this->createMock( ShopifyFetcher::class );
		$mock_fetcher->expects( $this->once() )
			->method( 'fetch_batch' )
			->with(
				array(
					'limit'        => 5,
					'after_cursor' => null,
				)
			)
			->willReturn( $mock_error_response );

		$mock_registry = $this->createMock( PlatformRegistry::class );
		$mock_registry->expects( $this->once() )
			->method( 'get_fetcher' )
			->with( 'shopify' )
			->willReturn( $mock_fetcher );

		$this->command->init( $this->credential_manager, $mock_registry );

		$reflection = new \ReflectionClass( $this->command );
		$method     = $reflection->getMethod( 'handle_fetch_request' );
		$method->setAccessible( true );

		// Reset mock messages.
		\WP_CLI::$last_success_message = '';
		\WP_CLI::$last_log_message     = '';

		$method->invoke( $this->command, 'shopify', array( 'limit' => '5' ) );

		// Should show "No products found" when items array is empty.
		$this->assertStringContainsString( 'No products found', \WP_CLI::$last_log_message );
	}

	/**
	 * Test handle_fetch_request with empty results.
	 */
	public function test_handle_fetch_request_empty_results() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			require_once __DIR__ . '/../Mocks/MockWPCLI.php';
		}

		$mock_response = array(
			'items'         => array(),
			'hasNextPage'   => false,
			'has_next_page' => false,
			'cursor'        => null,
		);

		$mock_fetcher = $this->createMock( ShopifyFetcher::class );
		$mock_fetcher->expects( $this->once() )
			->method( 'fetch_batch' )
			->with(
				array(
					'limit'        => 5,
					'after_cursor' => null,
				)
			)
			->willReturn( $mock_response );

		$mock_registry = $this->createMock( PlatformRegistry::class );
		$mock_registry->expects( $this->once() )
			->method( 'get_fetcher' )
			->willReturn( $mock_fetcher );

		$this->command->init( $this->credential_manager, $mock_registry );

		$reflection = new \ReflectionClass( $this->command );
		$method     = $reflection->getMethod( 'handle_fetch_request' );
		$method->setAccessible( true );

		// Reset mock messages.
		\WP_CLI::$last_success_message = '';
		\WP_CLI::$last_log_message     = '';

		$method->invoke( $this->command, 'shopify', array( 'limit' => '5' ) );

		$this->assertStringContainsString( 'No products found', \WP_CLI::$last_log_message );
		$this->assertStringNotContainsString( 'More products available', \WP_CLI::$last_log_message );
	}
}
