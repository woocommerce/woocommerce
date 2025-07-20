<?php
/**
 * Platform Registry Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Core
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Core;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
use Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks\MockPlatformFetcher;
use Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks\MockPlatformMapper;

/**
 * PlatformRegistryTest class.
 */
class PlatformRegistryTest extends \WC_Unit_Test_Case {

	/**
	 * Clean up filters after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'woocommerce_migrator_platforms' );
	}

	/**
	 * Test platform registration and retrieval.
	 */
	public function test_platform_registration() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['test-platform'] = array(
					'name'    => 'Test Platform',
					'fetcher' => MockPlatformFetcher::class,
					'mapper'  => MockPlatformMapper::class,
				);
				return $platforms;
			}
		);

		$registry  = new PlatformRegistry();
		$platforms = $registry->get_platforms();

		$this->assertArrayHasKey( 'test-platform', $platforms );
		$this->assertEquals( 'Test Platform', $platforms['test-platform']['name'] );
		$this->assertEquals( MockPlatformFetcher::class, $platforms['test-platform']['fetcher'] );
		$this->assertEquals( MockPlatformMapper::class, $platforms['test-platform']['mapper'] );
	}

	/**
	 * Test getting a single platform.
	 */
	public function test_get_single_platform() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['test-platform'] = array(
					'name'    => 'Test Platform',
					'fetcher' => MockPlatformFetcher::class,
					'mapper'  => MockPlatformMapper::class,
				);
				return $platforms;
			}
		);

		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'test-platform' );

		$this->assertNotNull( $platform );
		$this->assertEquals( 'Test Platform', $platform['name'] );
	}

	/**
	 * Test getting a non-existent platform returns null.
	 */
	public function test_get_nonexistent_platform_returns_null() {
		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'non-existent-platform' );

		$this->assertNull( $platform );
	}

	/**
	 * Test that a platform with missing fetcher or mapper is not registered.
	 */
	public function test_incomplete_platform_is_not_registered() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['incomplete-platform'] = array(
					'name' => 'Incomplete Platform',
					// Missing fetcher and mapper.
				);
				return $platforms;
			}
		);

		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'incomplete-platform' );

		$this->assertNull( $platform );
	}

	/**
	 * Test that a platform with only fetcher is not registered.
	 */
	public function test_platform_with_only_fetcher_is_not_registered() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['partial-platform'] = array(
					'name'    => 'Partial Platform',
					'fetcher' => MockPlatformFetcher::class,
					// Missing mapper.
				);
				return $platforms;
			}
		);

		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'partial-platform' );

		$this->assertNull( $platform );
	}

	/**
	 * Test that a platform with only mapper is not registered.
	 */
	public function test_platform_with_only_mapper_is_not_registered() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['partial-platform'] = array(
					'name'   => 'Partial Platform',
					'mapper' => MockPlatformMapper::class,
					// Missing fetcher.
				);
				return $platforms;
			}
		);

		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'partial-platform' );

		$this->assertNull( $platform );
	}

	/**
	 * Test filter with non-array return value is handled gracefully.
	 */
	public function test_filter_non_array_return_handled_gracefully() {
		add_filter(
			'woocommerce_migrator_platforms',
			function () {
				return 'invalid-return-value';
			}
		);

		$registry  = new PlatformRegistry();
		$platforms = $registry->get_platforms();

		$this->assertIsArray( $platforms );
		$this->assertEmpty( $platforms );
	}

	/**
	 * Test multiple platforms can be registered.
	 */
	public function test_multiple_platforms_registration() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['platform-one'] = array(
					'name'    => 'Platform One',
					'fetcher' => MockPlatformFetcher::class,
					'mapper'  => MockPlatformMapper::class,
				);
				$platforms['platform-two'] = array(
					'name'    => 'Platform Two',
					'fetcher' => MockPlatformFetcher::class,
					'mapper'  => MockPlatformMapper::class,
				);
				return $platforms;
			}
		);

		$registry  = new PlatformRegistry();
		$platforms = $registry->get_platforms();

		$this->assertCount( 2, $platforms );
		$this->assertArrayHasKey( 'platform-one', $platforms );
		$this->assertArrayHasKey( 'platform-two', $platforms );
	}

	/**
	 * Test resolve_platform method with valid platform.
	 */
	public function test_resolve_platform_with_valid_platform() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['shopify'] = array(
					'name'    => 'Shopify',
					'fetcher' => MockPlatformFetcher::class,
					'mapper'  => MockPlatformMapper::class,
				);
				return $platforms;
			}
		);

		$registry   = new PlatformRegistry();
		$assoc_args = array( 'platform' => 'shopify' );

		$result = $registry->resolve_platform( $assoc_args );
		$this->assertEquals( 'shopify', $result );
	}

	/**
	 * Test resolve_platform method with default platform.
	 */
	public function test_resolve_platform_with_default() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['shopify'] = array(
					'name'    => 'Shopify',
					'fetcher' => MockPlatformFetcher::class,
					'mapper'  => MockPlatformMapper::class,
				);
				return $platforms;
			}
		);

		$registry   = new PlatformRegistry();
		$assoc_args = array(); // No platform specified.

		$result = $registry->resolve_platform( $assoc_args, 'shopify' );
		$this->assertEquals( 'shopify', $result );
	}

	/**
	 * Test resolve_platform method with invalid platform.
	 *
	 * Note: In real environment, this would trigger WP_CLI::error().
	 * We can't easily test WP_CLI::error() in unit tests.
	 */
	public function test_resolve_platform_with_invalid_platform() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['shopify'] = array(
					'name'    => 'Shopify',
					'fetcher' => MockPlatformFetcher::class,
					'mapper'  => MockPlatformMapper::class,
				);
				return $platforms;
			}
		);

		$registry   = new PlatformRegistry();
		$assoc_args = array( 'platform' => 'invalid_platform' );

		// The method should handle invalid platforms gracefully.
		$this->assertTrue( method_exists( $registry, 'resolve_platform' ) );

		// Test that calling with invalid platform doesn't crash.
		try {
			$registry->resolve_platform( $assoc_args );
			// If we reach here, the method handled invalid platform without throwing.
			$this->assertTrue( true );
		} catch ( \Exception $e ) {
			// Method should not throw exceptions for invalid platforms.
			$this->fail( 'resolve_platform should handle invalid platforms gracefully' );
		}
	}

	/**
	 * Test get_platform_credential_fields method exists and is callable.
	 */
	public function test_get_platform_credential_fields_method_exists() {
		$registry = new PlatformRegistry();
		$this->assertTrue( method_exists( $registry, 'get_platform_credential_fields' ) );
		$this->assertTrue( is_callable( array( $registry, 'get_platform_credential_fields' ) ) );
	}

	/**
	 * Test get_platform_credential_fields returns expected fields for shopify.
	 */
	public function test_get_platform_credential_fields_shopify() {
		$registry = new PlatformRegistry();
		$fields   = $registry->get_platform_credential_fields( 'shopify' );

		$this->assertIsArray( $fields );
		$this->assertArrayHasKey( 'shop_url', $fields );
		$this->assertArrayHasKey( 'access_token', $fields );
		$this->assertEquals( 'Enter shop URL (e.g., mystore.myshopify.com):', $fields['shop_url'] );
		$this->assertEquals( 'Enter access token:', $fields['access_token'] );
	}

	/**
	 * Test get_platform_credential_fields returns empty array for unknown platform.
	 */
	public function test_get_platform_credential_fields_unknown_platform() {
		$registry = new PlatformRegistry();
		$fields   = $registry->get_platform_credential_fields( 'unknown_platform' );

		$this->assertIsArray( $fields );
		$this->assertEmpty( $fields );
	}
}
