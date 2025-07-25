<?php
/**
 * Platform Registry Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Core
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Core;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;

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
					'fetcher' => 'TestFetcher',
					'mapper'  => 'TestMapper',
				);
				return $platforms;
			}
		);

		$registry  = new PlatformRegistry();
		$platforms = $registry->get_platforms();

		$this->assertArrayHasKey( 'test-platform', $platforms );
		$this->assertEquals( 'Test Platform', $platforms['test-platform']['name'] );
		$this->assertEquals( 'TestFetcher', $platforms['test-platform']['fetcher'] );
		$this->assertEquals( 'TestMapper', $platforms['test-platform']['mapper'] );
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
					'fetcher' => 'TestFetcher',
					'mapper'  => 'TestMapper',
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
					'fetcher' => 'TestFetcher',
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
					'mapper' => 'TestMapper',
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
					'fetcher' => 'TestFetcher',
					'mapper'  => 'TestMapper',
				);
				$platforms['platform-two'] = array(
					'name'    => 'Platform Two',
					'fetcher' => 'TestFetcher',
					'mapper'  => 'TestMapper',
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
	 * Test that platform registration works correctly.
	 */
	public function test_platform_registration_workflow() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['shopify'] = array(
					'name'    => 'Shopify',
					'fetcher' => 'TestFetcher',
					'mapper'  => 'TestMapper',
				);
				return $platforms;
			}
		);

		$registry = new PlatformRegistry();

		// Test that registered platform can be retrieved.
		$platform = $registry->get_platform( 'shopify' );
		$this->assertIsArray( $platform );
		$this->assertEquals( 'Shopify', $platform['name'] );
	}

	/**
	 * Test resolve_platform method exists and is callable.
	 *
	 * Note: Full testing requires WP_CLI which is not available in test environment.
	 */
	public function test_resolve_platform_method_exists() {
		$registry = new PlatformRegistry();
		$this->assertTrue( method_exists( $registry, 'resolve_platform' ) );
		$this->assertTrue( is_callable( array( $registry, 'resolve_platform' ) ) );
	}

	/**
	 * Test that PlatformRegistry can handle platform validation.
	 *
	 * Note: This test only verifies method structure since WP_CLI calls
	 * would cause failures in the test environment.
	 */
	public function test_platform_validation_structure() {
		$registry = new PlatformRegistry();

		// Test that the registry can check for platform existence.
		$this->assertNull( $registry->get_platform( 'nonexistent_platform' ) );

		// Test that get_platforms returns an array.
		$this->assertIsArray( $registry->get_platforms() );
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
