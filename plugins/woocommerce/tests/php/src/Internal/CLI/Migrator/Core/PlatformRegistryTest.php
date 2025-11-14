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
	 * Test get_platform_credential_fields returns expected fields for shopify.
	 */
	public function test_get_platform_credential_fields_shopify() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['shopify'] = array(
					'name'        => 'Shopify',
					'fetcher'     => 'TestFetcher',
					'mapper'      => 'TestMapper',
					'credentials' => array(
						'shop_url'     => 'Enter shop URL (e.g., mystore.myshopify.com):',
						'access_token' => 'Enter access token:',
					),
				);
				return $platforms;
			}
		);

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
	 * Test that platforms with invalid data types are not registered.
	 */
	public function test_platform_with_invalid_data_types_not_registered() {
		add_filter(
			'woocommerce_migrator_platforms',
			function ( $platforms ) {
				$platforms['invalid-types-platform'] = array(
					'name'    => 'Invalid Types Platform',
					'fetcher' => array( 'TestFetcher' ), // Array instead of string.
					'mapper'  => 123, // Number instead of string.
				);
				return $platforms;
			}
		);

		$registry = new PlatformRegistry();
		$platform = $registry->get_platform( 'invalid-types-platform' );

		// Platform should not be registered due to validation.
		$this->assertNull( $platform );
	}
}
