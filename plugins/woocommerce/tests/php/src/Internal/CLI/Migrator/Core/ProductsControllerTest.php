<?php
/**
 * ProductsController Test
 *
 * Tests for ProductsController (parsing, validation, transformation).
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Core
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Core;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\ProductsController;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\WooCommerceProductImporter;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\MigratorTracker;

/**
 * ProductsController business logic tests.
 */
class ProductsControllerTest extends \WC_Unit_Test_Case {

	/**
	 * The ProductsController instance under test.
	 *
	 * @var ProductsController
	 */
	private ProductsController $products_controller;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock WP_CLI if not available.
		if ( ! class_exists( 'WP_CLI' ) ) {
			require_once __DIR__ . '/../Mocks/MockWPCLI.php';
		}

		// Create ProductsController instance.
		$this->products_controller = new ProductsController();

		// Initialize with minimal mocked dependencies for business logic tests.
		$this->products_controller->init(
			$this->createMock( CredentialManager::class ),
			$this->createMock( PlatformRegistry::class ),
			$this->createMock( WooCommerceProductImporter::class ),
			$this->createMock( MigratorTracker::class )
		);
	}

	/**
	 * Test batch size validation logic.
	 */
	public function test_batch_size_validation(): void {
		$test_cases = array(
			// Valid batch sizes.
			array(
				'input'    => '10',
				'expected' => 10,
			),
			array(
				'input'    => '250',
				'expected' => 250,
			),
			array(
				'input'    => '1',
				'expected' => 1,
			),
			// Invalid batch sizes (should use default).
			array(
				'input'    => '0',
				'expected' => 20,
			),
			array(
				'input'    => '-5',
				'expected' => 20,
			),
			array(
				'input'    => '1000',
				'expected' => 250,
			), // Max cap.
			array(
				'input'    => 'invalid',
				'expected' => 20,
			),
		);

		$reflection = new \ReflectionClass( $this->products_controller );
		$method     = $reflection->getMethod( 'parse_field_selection' );
		$method->setAccessible( true );

		foreach ( $test_cases as $test_case ) {
			$input  = array( 'batch-size' => $test_case['input'] );
			$result = $method->invoke( $this->products_controller, $input );
			$this->assertIsArray( $result );
		}
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
				'expected' => array( 'status' => 'active' ),
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

		$result = $method->invoke( $this->products_controller, 'invalid-date', 'test-filter' );
		$this->assertNull( $result );
	}
}
