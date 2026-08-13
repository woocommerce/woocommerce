<?php
/**
 * Shopify Platform Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Shopify
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Shopify;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify\ShopifyFetcher;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify\ShopifyMapper;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify\ShopifyPlatform;

/**
 * Test cases for Shopify platform registration and integration.
 */
class ShopifyPlatformTest extends \WC_Unit_Test_Case {

	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		parent::setUp();
		// Initialize the Shopify platform (simulating what Runner does).
		ShopifyPlatform::init();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		// Remove all filters to ensure clean state.
		remove_all_filters( 'woocommerce_migrator_platforms' );
	}

	/**
	 * Test that the Shopify platform is registered correctly.
	 */
	public function test_shopify_platform_is_registered() {
		$registry  = new PlatformRegistry();
		$platforms = $registry->get_platforms();

		$this->assertArrayHasKey( 'shopify', $platforms, 'Shopify platform should be registered.' );

		// Assert platform configuration is correct.
		$shopify_config = $platforms['shopify'];
		$this->assertEquals( 'Shopify', $shopify_config['name'], 'Shopify platform name should be "Shopify".' );
		$this->assertEquals( ShopifyFetcher::class, $shopify_config['fetcher'], 'Shopify fetcher class should be ShopifyFetcher.' );
		$this->assertEquals( ShopifyMapper::class, $shopify_config['mapper'], 'Shopify mapper class should be ShopifyMapper.' );
		$this->assertEquals( 'Import products and data from Shopify stores', $shopify_config['description'], 'Shopify description should be correct.' );
	}

	/**
	 * Test that the Shopify platform can be retrieved individually.
	 */
	public function test_get_shopify_platform() {
		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'shopify' );

		$this->assertNotNull( $platform, 'Shopify platform should be retrievable.' );
		$this->assertEquals( 'Shopify', $platform['name'] );
		$this->assertEquals( ShopifyFetcher::class, $platform['fetcher'] );
		$this->assertEquals( ShopifyMapper::class, $platform['mapper'] );
	}

	/**
	 * Test that ShopifyPlatform::init() registers the filter correctly.
	 */
	public function test_shopify_platform_init_registers_filter() {
		// Start fresh - remove existing filters.
		remove_all_filters( 'woocommerce_migrator_platforms' );

		$registry  = new PlatformRegistry();
		$platforms = $registry->get_platforms();
		$this->assertArrayNotHasKey( 'shopify', $platforms, 'Shopify should not be registered before init.' );

		// Initialize and test.
		ShopifyPlatform::init();
		$registry  = new PlatformRegistry();
		$platforms = $registry->get_platforms();
		$this->assertArrayHasKey( 'shopify', $platforms, 'Shopify should be registered after init.' );
	}

	/**
	 * Test that the registered platform has the expected structure.
	 */
	public function test_shopify_platform_structure() {
		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'shopify' );

		$this->assertIsArray( $platform, 'Platform should be an array.' );
		$this->assertArrayHasKey( 'name', $platform, 'Platform should have a name.' );
		$this->assertArrayHasKey( 'description', $platform, 'Platform should have a description.' );
		$this->assertArrayHasKey( 'fetcher', $platform, 'Platform should have a fetcher.' );
		$this->assertArrayHasKey( 'mapper', $platform, 'Platform should have a mapper.' );

		// Verify the classes exist and are valid.
		$this->assertTrue( class_exists( $platform['fetcher'] ), 'Fetcher class should exist.' );
		$this->assertTrue( class_exists( $platform['mapper'] ), 'Mapper class should exist.' );
	}

	/**
	 * Test that multiple calls to init() don't create duplicate registrations.
	 */
	public function test_multiple_init_calls_safe() {
		// Call init multiple times.
		ShopifyPlatform::init();
		ShopifyPlatform::init();
		ShopifyPlatform::init();

		$registry  = new PlatformRegistry();
		$platforms = $registry->get_platforms();

		// Should still only have one shopify platform.
		$this->assertCount( 1, $platforms, 'Should only have one platform registered despite multiple init calls.' );
		$this->assertArrayHasKey( 'shopify', $platforms );
	}

	/**
	 * Test that the filter integration works correctly.
	 */
	public function test_filter_integration() {
		// Test that our platform is added to existing platforms.
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['existing-platform'] = array(
					'name'    => 'Existing Platform',
					'fetcher' => 'ExistingFetcher',
					'mapper'  => 'ExistingMapper',
				);
				return $platforms;
			},
			5 // Lower priority to run before Shopify.
		);

		$registry  = new PlatformRegistry();
		$platforms = $registry->get_platforms();

		$this->assertCount( 2, $platforms, 'Should have both existing and Shopify platforms.' );
		$this->assertArrayHasKey( 'existing-platform', $platforms );
		$this->assertArrayHasKey( 'shopify', $platforms );
	}
}
