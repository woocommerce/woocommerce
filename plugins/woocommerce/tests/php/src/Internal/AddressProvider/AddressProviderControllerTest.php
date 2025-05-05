<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AddressProvider;

use Automattic\WooCommerce\Internal\AddressProvider\AddressProviderController;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use WC_Address_Provider;

/**
 * Tests for Address Provider Service functionality
 */
class AddressProviderControllerTest extends MockeryTestCase {

	/**
	 * System under test.
	 *
	 * @var AddressProviderController
	 */
	private $sut;

	/**
	 * The mock logger.
	 *
	 * @var \WC_Logger_Interface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_logger;

	/**
	 * Setup test case.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->sut = new AddressProviderController();

		// Setup mock logger.
		$this->mock_logger = $this->getMockBuilder( 'WC_Logger_Interface' )->getMock();
		add_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );
	}

	/**
	 * Tear down test case.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'woocommerce_address_providers' );
		remove_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );
	}

	/**
	 * Overrides the WC logger.
	 *
	 * @return mixed
	 */
	public function override_wc_logger() {
		return $this->mock_logger;
	}

	/**
	 * Test getting providers when none are registered.
	 */
	public function test_get_providers_empty() {
		$providers = $this->sut->get_registered_providers();
		$this->assertEmpty( $providers );
	}

	/**
	 * Test getting registered providers.
	 */
	public function test_get_providers() {
		// Define test provider class for provider-1.
		$provider1_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 * Sets up the provider with an ID and name.
			 */
			public function __construct() {
				$this->id   = 'provider-1';
				$this->name = 'Provider One';
			}
		};

		// Define test provider class for provider-2.
		$provider2_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 * Sets up the provider with an ID and name.
			 */
			public function __construct() {
				$this->id   = 'provider-2';
				$this->name = 'Provider Two';
			}
		};

		// Get class names for filter.
		$provider1_class_name = get_class( $provider1_class );
		$provider2_class_name = get_class( $provider2_class );

		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) use ( $provider1_class_name, $provider2_class_name ) {
				$providers[] = $provider1_class_name;
				$providers[] = $provider2_class_name;
				return $providers;
			}
		);

		$registered_providers = $this->sut->get_registered_providers();

		// Test that we have two provider instances registered.
		$this->assertCount( 2, $registered_providers );

		// Test that the registered providers are instances with correct properties.
		$this->assertInstanceOf( WC_Address_Provider::class, $registered_providers[0] );
		$this->assertInstanceOf( WC_Address_Provider::class, $registered_providers[1] );
		$this->assertEquals( 'provider-1', $registered_providers[0]->id );
		$this->assertEquals( 'provider-2', $registered_providers[1]->id );
		$this->assertEquals( 'Provider One', $registered_providers[0]->name );
		$this->assertEquals( 'Provider Two', $registered_providers[1]->name );
	}

	/**
	 * Test checking if a provider is available.
	 */
	public function test_is_provider_available() {
		// Define test provider class.
		$provider_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 * Sets up the provider with an ID and name.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
			}
		};

		// Get class name for filter.
		$provider_class_name = get_class( $provider_class );

		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) use ( $provider_class_name ) {
				$providers[] = $provider_class_name;
				return $providers;
			}
		);

		// Check if the provider is available.
		$this->assertTrue( $this->sut->is_provider_available( 'test-provider' ) );
		$this->assertFalse( $this->sut->is_provider_available( 'non-existent-provider' ) );
	}

	/**
	 * Test that multiple filters can add providers.
	 */
	public function test_multiple_provider_filters() {
		// Define test provider class for provider-1.
		$provider1_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 * Sets up the provider with an ID and name.
			 */
			public function __construct() {
				$this->id   = 'provider-1';
				$this->name = 'Provider One';
			}
		};

		// Define test provider class for provider-2.
		$provider2_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 * Sets up the provider with an ID and name.
			 */
			public function __construct() {
				$this->id   = 'provider-2';
				$this->name = 'Provider Two';
			}
		};

		// Get class names for filters.
		$provider1_class_name = get_class( $provider1_class );
		$provider2_class_name = get_class( $provider2_class );

		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) use ( $provider1_class_name ) {
				$providers[] = $provider1_class_name;
				return $providers;
			},
			10
		);

		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) use ( $provider2_class_name ) {
				$providers[] = $provider2_class_name;
				return $providers;
			},
			20
		);

		$registered_providers = $this->sut->get_registered_providers();

		// Test that we have two provider instances registered.
		$this->assertCount( 2, $registered_providers );

		// Test that both providers are properly instantiated.
		$this->assertInstanceOf( WC_Address_Provider::class, $registered_providers[0] );
		$this->assertInstanceOf( WC_Address_Provider::class, $registered_providers[1] );
		$this->assertEquals( 'provider-1', $registered_providers[0]->id );
		$this->assertEquals( 'provider-2', $registered_providers[1]->id );
		$this->assertEquals( 'Provider One', $registered_providers[0]->name );
		$this->assertEquals( 'Provider Two', $registered_providers[1]->name );
	}

	/**
	 * Test that invalid provider classes are filtered out.
	 */
	public function test_invalid_provider_classes() {
		// Create classes in the filter to ensure they're unique.
		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) {
				// Add an invalid provider class without required properties.
				$providers[] = get_class( new class() extends WC_Address_Provider {} );

				// Add a valid provider class.
				$providers[] = get_class(
					new class() extends WC_Address_Provider {
						/**
						 * Constructor for valid test provider.
						 */
						public function __construct() {
							$this->id   = 'valid-provider';
							$this->name = 'Valid Provider';
						}
					}
				);

				// Add a non-existent class.
				$providers[] = 'NonExistentClass';

				return $providers;
			}
		);

		$registered_providers = $this->sut->get_registered_providers();

		// Only the valid provider should be registered.
		$this->assertCount( 1, $registered_providers );
		$this->assertEquals( 'valid-provider', $registered_providers[0]->id );
		$this->assertEquals( 'Valid Provider', $registered_providers[0]->name );
	}

	/**
	 * Test that non-WC_Address_Provider classes are filtered out.
	 */
	public function test_non_provider_classes() {
		// Create classes in the filter to ensure they're unique.
		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) {
				// Add a class that's not a WC_Address_Provider.
				$providers[] = get_class(
					new class() {
						/**
						 * @var string ID of the provider.
						 */
						public $id = 'non-provider';
						/**
						 * @var string Name of the provider.
						 */
						public $name = 'Non Provider';
					}
				);

				// Add a valid provider class.
				$providers[] = get_class(
					new class() extends WC_Address_Provider {
						/**
						 * Constructor for valid test provider.
						 */
						public function __construct() {
							$this->id   = 'valid-provider';
							$this->name = 'Valid Provider';
						}
					}
				);

				return $providers;
			}
		);

		$registered_providers = $this->sut->get_registered_providers();

		// Only the valid provider should be registered.
		$this->assertCount( 1, $registered_providers );
		$this->assertEquals( 'valid-provider', $registered_providers[0]->id );
		$this->assertEquals( 'Valid Provider', $registered_providers[0]->name );
	}

	/**
	 * Test that providers are cached and not reinstantiated when the filter returns the same classes.
	 */
	public function test_provider_caching() {
		$instantiation_count = 0;

		// Define a test provider class that tracks instantiation.
		$provider_class = new class() extends WC_Address_Provider {
			/**
			 * @var int Count of instances created.
			 */
			public static $instance_count = 0;

			/**
			 * Constructor for test provider.
			 */
			public function __construct() {
				++self::$instance_count;
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
			}
		};

		$provider_class_name = get_class( $provider_class );

		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) use ( $provider_class_name ) {
				$providers[] = $provider_class_name;
				return $providers;
			}
		);

		// First call should instantiate the provider.
		$providers1    = $this->sut->get_registered_providers();
		$initial_count = $provider_class::$instance_count;

		// Second call should use cached instance.
		$providers2 = $this->sut->get_registered_providers();

		// Verify the instance count hasn't increased.
		$this->assertEquals( $initial_count, $provider_class::$instance_count );

		// Verify we got the same instance both times.
		$this->assertSame( $providers1[0], $providers2[0] );
	}

	/**
	 * Test that providers are reinstantiated when the filter returns different classes.
	 */
	public function test_provider_cache_invalidation() {
		// Define first test provider class.
		$provider1_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor for test provider 1.
			 */
			public function __construct() {
				$this->id   = 'provider-1';
				$this->name = 'Provider One';
			}
		};

		// Define second test provider class.
		$provider2_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor for test provider 2.
			 */
			public function __construct() {
				$this->id   = 'provider-2';
				$this->name = 'Provider Two';
			}
		};

		$provider1_class_name = get_class( $provider1_class );
		$provider2_class_name = get_class( $provider2_class );

		// First, register provider1.
		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) use ( $provider1_class_name ) {
				$providers[] = $provider1_class_name;
				return $providers;
			}
		);

		$first_result = $this->sut->get_registered_providers();
		$this->assertCount( 1, $first_result );
		$this->assertEquals( 'provider-1', $first_result[0]->id );

		// Now change the filter to return provider2.
		remove_all_filters( 'woocommerce_address_providers' );
		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) use ( $provider2_class_name ) {
				$providers[] = $provider2_class_name;
				return $providers;
			}
		);

		$second_result = $this->sut->get_registered_providers();
		$this->assertCount( 1, $second_result );
		$this->assertEquals( 'provider-2', $second_result[0]->id );
	}

	/**
	 * Test that providers are not reinstantiated when the filter returns the same class names in a new array.
	 */
	public function test_provider_caching_with_new_array() {
		// Create a provider class that tracks instantiations.
		$tracking_provider = new class() extends WC_Address_Provider {
			/**
			 * @var int Count of instances created.
			 */
			public static $instance_count = 0;

			/**
			 * Constructor for test provider.
			 */
			public function __construct() {
				++self::$instance_count;
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
			}
		};

		$provider_class_name = get_class( $tracking_provider );

		// Reset the static counter before starting the test.
		$provider_class_name::$instance_count = 0;

		// First filter call.
		add_filter(
			'woocommerce_address_providers',
			function () use ( $provider_class_name ) {
				// Return a new array each time.
				return array( $provider_class_name );
			}
		);

		// First call should instantiate the provider.
		$providers1 = $this->sut->get_registered_providers();
		$this->assertEquals( 1, $provider_class_name::$instance_count, 'Provider should be instantiated once' );

		// Second call should use cached instance even though filter returns a new array.
		$providers2 = $this->sut->get_registered_providers();
		$this->assertEquals( 1, $provider_class_name::$instance_count, 'Provider should not be instantiated again' );

		// Verify we got the same instance both times.
		$this->assertSame( $providers1[0], $providers2[0] );
	}

	/**
	 * Test that errors are logged when filter returns non-array.
	 */
	public function test_logs_error_for_non_array_filter_return() {
		add_filter(
			'woocommerce_address_providers',
			function () {
				return 'not an array';
			}
		);

		$this->mock_logger
			->expects( $this->once() )
			->method( 'error' )
			->with(
				'Invalid return value for woocommerce_address_providers, expected an array of class names.',
				array( 'context' => 'address_provider_service' )
			);

		$providers = $this->sut->get_registered_providers();
		$this->assertEmpty( $providers );
	}

	/**
	 * Test that errors are logged for invalid class names.
	 */
	public function test_logs_error_for_invalid_class_name() {
		add_filter(
			'woocommerce_address_providers',
			function () {
				return array( 123 ); // Non-string value.
			}
		);

		$this->mock_logger
			->expects( $this->once() )
			->method( 'error' )
			->with(
				'Invalid class name for address provider, expected a string.',
				array( 'context' => 'address_provider_service' )
			);

		$providers = $this->sut->get_registered_providers();
		$this->assertEmpty( $providers );
	}

	/**
	 * Test that errors are logged for non-existent classes.
	 */
	public function test_logs_error_for_non_existent_class() {
		add_filter(
			'woocommerce_address_providers',
			function () {
				return array( 'NonExistentClass' );
			}
		);

		$this->mock_logger
			->expects( $this->once() )
			->method( 'error' )
			->with(
				'Invalid address provider class, class does not exist or is not a subclass of WC_Address_Provider: NonExistentClass',
				array( 'context' => 'address_provider_service' )
			);

		$providers = $this->sut->get_registered_providers();
		$this->assertEmpty( $providers );
	}

	/**
	 * Test that errors are logged for invalid provider instances.
	 */
	public function test_logs_error_for_invalid_provider_instance() {
		// Create a provider class without required properties.
		$invalid_provider    = new class() extends WC_Address_Provider {};
		$provider_class_name = get_class( $invalid_provider );

		add_filter(
			'woocommerce_address_providers',
			function () use ( $provider_class_name ) {
				return array( $provider_class_name );
			}
		);

		$this->mock_logger
			->expects( $this->once() )
			->method( 'error' )
			->with(
				'Invalid address provider instance, id or name property is missing or empty: ' . $provider_class_name,
				array( 'context' => 'address_provider_service' )
			);

		$providers = $this->sut->get_registered_providers();
		$this->assertEmpty( $providers );
	}

	/**
	 * Test that multiple errors are logged when multiple issues exist.
	 */
	public function test_logs_multiple_errors() {
		// Create a provider class without required properties.
		$invalid_provider    = new class() extends WC_Address_Provider {};
		$provider_class_name = get_class( $invalid_provider );

		add_filter(
			'woocommerce_address_providers',
			function () use ( $provider_class_name ) {
				return array(
					123, // Invalid type.
					'NonExistentClass', // Non-existent class.
					$provider_class_name, // Missing properties.
				);
			}
		);

		$this->mock_logger
			->expects( $this->exactly( 3 ) )
			->method( 'error' )
			->withConsecutive(
				array(
					'Invalid class name for address provider, expected a string.',
					array( 'context' => 'address_provider_service' ),
				),
				array(
					'Invalid address provider class, class does not exist or is not a subclass of WC_Address_Provider: NonExistentClass',
					array( 'context' => 'address_provider_service' ),
				),
				array(
					'Invalid address provider instance, id or name property is missing or empty: ' . $provider_class_name,
					array( 'context' => 'address_provider_service' ),
				)
			);

		$providers = $this->sut->get_registered_providers();
		$this->assertEmpty( $providers );
	}

	/**
	 * Test that duplicate provider IDs are detected and logged.
	 */
	public function test_logs_error_for_duplicate_provider_ids() {
		// Create two provider classes with the same ID.
		$provider1_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'duplicate-id';
				$this->name = 'Provider One';
			}
		};

		$provider2_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'duplicate-id';
				$this->name = 'Provider Two';
			}
		};

		$provider1_class_name = get_class( $provider1_class );
		$provider2_class_name = get_class( $provider2_class );

		add_filter(
			'woocommerce_address_providers',
			function () use ( $provider1_class_name, $provider2_class_name ) {
				return array( $provider1_class_name, $provider2_class_name );
			}
		);

		$this->mock_logger
			->expects( $this->once() )
			->method( 'error' )
			->with(
				sprintf(
					'Duplicate provider ID found. ID "%s" is used by both %s and %s.',
					'duplicate-id',
					$provider1_class_name,
					$provider2_class_name
				),
				array( 'context' => 'address_provider_service' )
			);

		$providers = $this->sut->get_registered_providers();

		// Only the first provider should be registered.
		$this->assertCount( 1, $providers );
		$this->assertEquals( 'duplicate-id', $providers[0]->id );
		$this->assertEquals( 'Provider One', $providers[0]->name );
	}

	/**
	 * Test that providers with unique IDs are all registered.
	 */
	public function test_accepts_unique_provider_ids() {
		// Create two provider classes with different IDs.
		$provider1_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'unique-id-1';
				$this->name = 'Provider One';
			}
		};

		$provider2_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'unique-id-2';
				$this->name = 'Provider Two';
			}
		};

		$provider1_class_name = get_class( $provider1_class );
		$provider2_class_name = get_class( $provider2_class );

		add_filter(
			'woocommerce_address_providers',
			function () use ( $provider1_class_name, $provider2_class_name ) {
				return array( $provider1_class_name, $provider2_class_name );
			}
		);

		// The logger should not receive any error calls.
		$this->mock_logger
			->expects( $this->never() )
			->method( 'error' );

		$providers = $this->sut->get_registered_providers();

		// Both providers should be registered.
		$this->assertCount( 2, $providers );
		$this->assertEquals( 'unique-id-1', $providers[0]->id );
		$this->assertEquals( 'unique-id-2', $providers[1]->id );
		$this->assertEquals( 'Provider One', $providers[0]->name );
		$this->assertEquals( 'Provider Two', $providers[1]->name );
	}
}
