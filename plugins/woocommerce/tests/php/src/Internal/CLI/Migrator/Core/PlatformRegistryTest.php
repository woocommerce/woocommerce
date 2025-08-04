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

	/**
	 * Test that platforms with empty string fetcher are not registered.
	 */
	public function test_platform_with_empty_string_fetcher_not_registered() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['empty-fetcher-platform'] = array(
					'name'    => 'Empty Fetcher Platform',
					'fetcher' => '', // Empty string.
					'mapper'  => 'TestMapper',
				);
				return $platforms;
			}
		);

		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'empty-fetcher-platform' );

		$this->assertNull( $platform );
	}

	/**
	 * Test that platforms with empty string mapper are not registered.
	 */
	public function test_platform_with_empty_string_mapper_not_registered() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['empty-mapper-platform'] = array(
					'name'    => 'Empty Mapper Platform',
					'fetcher' => 'TestFetcher',
					'mapper'  => '', // Empty string.
				);
				return $platforms;
			}
		);

		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'empty-mapper-platform' );

		$this->assertNull( $platform );
	}

	/**
	 * Test that get_fetcher throws exception for non-existent fetcher class.
	 */
	public function test_get_fetcher_for_non_existent_class_throws_exception() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['invalid-fetcher-platform'] = array(
					'name'    => 'Invalid Fetcher Platform',
					'fetcher' => 'NonExistentFetcherClass',
					'mapper'  => 'TestMapper',
				);
				return $platforms;
			}
		);

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid fetcher class for platform invalid-fetcher-platform. Class NonExistentFetcherClass does not exist.' );

		$registry = new PlatformRegistry();
		$registry->get_fetcher( 'invalid-fetcher-platform' );
	}

	/**
	 * Test that get_mapper throws exception for non-existent mapper class.
	 */
	public function test_get_mapper_for_non_existent_class_throws_exception() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['invalid-mapper-platform'] = array(
					'name'    => 'Invalid Mapper Platform',
					'fetcher' => 'TestFetcher',
					'mapper'  => 'NonExistentMapperClass',
				);
				return $platforms;
			}
		);

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid mapper class for platform invalid-mapper-platform. Class NonExistentMapperClass does not exist.' );

		$registry = new PlatformRegistry();
		$registry->get_mapper( 'invalid-mapper-platform' );
	}

	/**
	 * Test that get_fetcher throws exception for class with wrong namespace/interface.
	 */
	public function test_get_fetcher_for_wrong_interface_throws_exception() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['wrong-fetcher-interface'] = array(
					'name'    => 'Wrong Fetcher Interface Platform',
					'fetcher' => 'stdClass', // Exists but wrong interface.
					'mapper'  => 'TestMapper',
				);
				return $platforms;
			}
		);

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid fetcher class for platform wrong-fetcher-interface. Class stdClass does not implement Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformFetcherInterface.' );

		$registry = new PlatformRegistry();
		$registry->get_fetcher( 'wrong-fetcher-interface' );
	}

	/**
	 * Test that get_mapper throws exception for class with wrong namespace/interface.
	 */
	public function test_get_mapper_for_wrong_interface_throws_exception() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['wrong-mapper-interface'] = array(
					'name'    => 'Wrong Mapper Interface Platform',
					'fetcher' => 'TestFetcher',
					'mapper'  => 'stdClass', // Exists but wrong interface.
				);
				return $platforms;
			}
		);

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid mapper class for platform wrong-mapper-interface. Class stdClass does not implement Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformMapperInterface.' );

		$registry = new PlatformRegistry();
		$registry->get_mapper( 'wrong-mapper-interface' );
	}

	/**
	 * Test that platforms with null values are not registered.
	 */
	public function test_platform_with_null_values_not_registered() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['null-values-platform'] = array(
					'name'    => 'Null Values Platform',
					'fetcher' => null,
					'mapper'  => null,
				);
				return $platforms;
			}
		);

		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'null-values-platform' );

		$this->assertNull( $platform );
	}

	/**
	 * Test that platforms with malformed class names are handled gracefully.
	 */
	public function test_platform_with_malformed_class_names() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['malformed-class-names'] = array(
					'name'    => 'Malformed Class Names Platform',
					'fetcher' => '\\\\InvalidNamespace\\\\Class',
					'mapper'  => '123InvalidClassName',
				);
				return $platforms;
			}
		);

		$registry = new PlatformRegistry();

		// Test fetcher with malformed namespace.
		$this->expectException( \InvalidArgumentException::class );
		$registry->get_fetcher( 'malformed-class-names' );
	}

	/**
	 * Test that platforms with array values instead of strings are not registered.
	 */
	public function test_platform_with_array_values_not_registered() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['array-values-platform'] = array(
					'name'    => 'Array Values Platform',
					'fetcher' => array( 'TestFetcher' ), // Array instead of string.
					'mapper'  => array( 'TestMapper' ),  // Array instead of string.
				);
				return $platforms;
			}
		);

		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'array-values-platform' );

		// Platform should not be registered due to enhanced validation.
		$this->assertNull( $platform );
	}

	/**
	 * Test that get_fetcher throws exception with proper message for array values.
	 */
	public function test_get_fetcher_with_array_value_throws_exception() {
		// Manually add a platform with array values to bypass load_platforms validation for testing.
		$registry           = new PlatformRegistry();
		$reflection         = new \ReflectionClass( $registry );
		$platforms_property = $reflection->getProperty( 'platforms' );
		$platforms_property->setAccessible( true );
		$platforms_property->setValue(
			$registry,
			array(
				'array-fetcher-platform' => array(
					'name'    => 'Array Fetcher Platform',
					'fetcher' => array( 'TestFetcher' ), // Array instead of string.
					'mapper'  => 'TestMapper',
				),
			)
		);

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid fetcher class for platform array-fetcher-platform. Fetcher must be a non-empty string.' );

		$registry->get_fetcher( 'array-fetcher-platform' );
	}

	/**
	 * Test resolve_platform with empty string platform argument.
	 */
	public function test_resolve_platform_with_empty_string() {
		$registry = new PlatformRegistry();

		// Mock WP_CLI::log to avoid output during tests.
		if ( ! class_exists( 'WP_CLI' ) ) {
			$this->markTestSkipped( 'WP_CLI not available in test environment.' );
		}

		$result = $registry->resolve_platform( array( 'platform' => '' ) );

		// Should fall back to default.
		$this->assertEquals( 'shopify', $result );
	}

	/**
	 * Test that platform IDs with special characters are handled correctly.
	 */
	public function test_platform_with_special_characters_in_id() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				// Test various special characters in platform ID.
				$platforms['platform@with#special$chars'] = array(
					'name'    => 'Special Characters Platform',
					'fetcher' => 'TestFetcher',
					'mapper'  => 'TestMapper',
				);
				return $platforms;
			}
		);

		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'platform@with#special$chars' );

		$this->assertNotNull( $platform );
		$this->assertEquals( 'Special Characters Platform', $platform['name'] );
	}
}
