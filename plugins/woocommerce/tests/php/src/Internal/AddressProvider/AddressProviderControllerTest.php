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
		$this->sut->init();

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
		$providers = $this->sut->get_providers();
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

		$this->sut->init();
		$registered_providers = $this->sut->get_providers();

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

		$this->sut = new AddressProviderController();
		$this->sut->init();

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

		$this->sut->init();
		$registered_providers = $this->sut->get_providers();

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

		$this->sut->init();
		$registered_providers = $this->sut->get_providers();

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

		$this->sut->init();
		$registered_providers = $this->sut->get_providers();

		// Only the valid provider should be registered.
		$this->assertCount( 1, $registered_providers );
		$this->assertEquals( 'valid-provider', $registered_providers[0]->id );
		$this->assertEquals( 'Valid Provider', $registered_providers[0]->name );
	}

	/**
	 * Test settings when no providers are registered.
	 */
	public function test_add_address_autocomplete_settings_no_providers() {
		// Do it this way to simulate the actual settings page with the filter.
		$settings_class = new \WC_Settings_General();
		$settings       = $settings_class->get_settings_for_section( '' );

		// Find the autocomplete settings.
		$autocomplete_enabled_setting  = null;
		$autocomplete_provider_setting = null;
		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) && 'woocommerce_address_autocomplete_enabled' === $setting['id'] ) {
				$autocomplete_enabled_setting = $setting;
				break;
			}
			if ( isset( $setting['id'] ) && 'woocommerce_address_autocomplete_provider' === $setting['id'] ) {
				$autocomplete_provider_setting = $setting;
				break;
			}
		}

		// Preferred provider should not be in the settings if no providers are registered.
		$this->assertNull( $autocomplete_provider_setting );

		$this->assertNotNull( $autocomplete_enabled_setting );
		$this->assertEquals( 'checkbox', $autocomplete_enabled_setting['type'] );
		$this->assertTrue( $autocomplete_enabled_setting['disabled'] );
		$this->assertStringContainsString( 'WooPayments', $autocomplete_enabled_setting['desc_tip'] );
	}

	/**
	 * Test settings when one provider is registered.
	 */
	public function test_add_address_autocomplete_settings_single_provider() {
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

		// Getting sut from container because the settings page uses that, not the instance we make in the test.
		$this->sut = wc_get_container()->get( AddressProviderController::class )->init();

		// Do it this way to simulate the actual settings page with the filter.
		$settings_class = new \WC_Settings_General();
		$settings       = $settings_class->get_settings_for_section( '' );

		// Find the autocomplete setting.
		$autocomplete_enabled_setting  = null;
		$autocomplete_provider_setting = null;
		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) && 'woocommerce_address_autocomplete_enabled' === $setting['id'] ) {
				$autocomplete_enabled_setting = $setting;
				break;
			}
			if ( isset( $setting['id'] ) && 'woocommerce_address_autocomplete_provider' === $setting['id'] ) {
				$autocomplete_provider_setting = $setting;
				break;
			}
		}

		// Preferred provider should not be in the settings if only one provider is registered.
		$this->assertNull( $autocomplete_provider_setting );

		$this->assertNotNull( $autocomplete_enabled_setting );
		$this->assertEquals( 'checkbox', $autocomplete_enabled_setting['type'] );
		$this->assertFalse( $autocomplete_enabled_setting['disabled'] );
		$this->assertStringNotContainsString( 'WooPayments', $autocomplete_enabled_setting['desc_tip'] );

		// Verify provider select is not added when only one provider exists.
		$provider_setting = null;
		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) && 'woocommerce_address_autocomplete_provider' === $setting['id'] ) {
				$provider_setting = $setting;
				break;
			}
		}

		$this->assertNull( $provider_setting );
	}

	/**
	 * Test settings when multiple providers are registered.
	 */
	public function test_add_address_autocomplete_settings_multiple_providers() {
		// Define test provider classes.
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

		// Getting sut from container because the settings page uses that, not the instance we make in the test.
		$this->sut = wc_get_container()->get( AddressProviderController::class )->init();

		// Do it this way to simulate the actual settings page with the filter.
		$settings_class = new \WC_Settings_General();
		$settings       = $settings_class->get_settings_for_section( '' );

		// Find the provider select setting.
		$provider_setting = null;
		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) && 'woocommerce_address_autocomplete_provider' === $setting['id'] ) {
				$provider_setting = $setting;
				break;
			}
		}

		$this->assertNotNull( $provider_setting );
		$this->assertEquals( 'select', $provider_setting['type'] );
		$this->assertEquals( 'provider-1', $provider_setting['default'] );
		$this->assertArrayHasKey( 'provider-1', $provider_setting['options'] );
		$this->assertArrayHasKey( 'provider-2', $provider_setting['options'] );
		$this->assertEquals( 'Provider One', $provider_setting['options']['provider-1'] );
		$this->assertEquals( 'Provider Two', $provider_setting['options']['provider-2'] );
	}

	/**
	 * Test that settings are added in the correct position.
	 */
	public function test_add_address_autocomplete_settings_position() {
		$settings_class = new \WC_Settings_General();
		$settings       = $settings_class->get_settings_for_section( '' );

		// Find the position of the default customer address setting and the autocomplete setting.
		$default_address_pos = -1;
		$autocomplete_pos    = -1;
		foreach ( $settings as $index => $setting ) {
			if ( isset( $setting['id'] ) ) {
				if ( 'woocommerce_default_customer_address' === $setting['id'] ) {
					$default_address_pos = $index;
				} elseif ( 'woocommerce_address_autocomplete_enabled' === $setting['id'] ) {
					$autocomplete_pos = $index;
				}
			}
		}

		$this->assertGreaterThan( -1, $default_address_pos );
		$this->assertGreaterThan( -1, $autocomplete_pos );
		$this->assertGreaterThan( $default_address_pos, $autocomplete_pos );
		$this->assertEquals( $default_address_pos + 1, $autocomplete_pos );
	}

	/**
	 * Test getting preferred provider when set in options.
	 */
	public function test_get_preferred_provider_from_options() {
		// Define test provider classes.
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

		update_option( 'woocommerce_address_autocomplete_provider', 'provider-2' );

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$preferred_provider = $this->sut->get_preferred_provider();
		$this->assertEquals( 'provider-2', $preferred_provider );
	}

	/**
	 * Test getting preferred provider when option is not set.
	 */
	public function test_get_preferred_provider_fallback() {
		// Define test provider classes.
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

		delete_option( 'woocommerce_address_autocomplete_provider' );

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$preferred_provider = $this->sut->get_preferred_provider();
		$this->assertEquals( 'provider-1', $preferred_provider );
	}

	/**
	 * Test getting preferred provider when selected provider is no longer available.
	 */
	public function test_get_preferred_provider_unavailable() {
		// Define test provider classes.
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

		update_option( 'woocommerce_address_autocomplete_provider', 'provider-3' );

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$preferred_provider = $this->sut->get_preferred_provider();
		$this->assertEquals( 'provider-1', $preferred_provider );
	}

	/**
	 * Test getting preferred provider with no providers registered.
	 */
	public function test_get_preferred_provider_no_providers() {
		$preferred_provider = $this->sut->get_preferred_provider();
		$this->assertEmpty( $preferred_provider );
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

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$providers = $this->sut->get_providers();
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

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$providers = $this->sut->get_providers();
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

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$providers = $this->sut->get_providers();
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

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$providers = $this->sut->get_providers();
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

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$providers = $this->sut->get_providers();
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

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$providers = $this->sut->get_providers();

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

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$providers = $this->sut->get_providers();

		// Both providers should be registered.
		$this->assertCount( 2, $providers );
		$this->assertEquals( 'unique-id-1', $providers[0]->id );
		$this->assertEquals( 'unique-id-2', $providers[1]->id );
		$this->assertEquals( 'Provider One', $providers[0]->name );
		$this->assertEquals( 'Provider Two', $providers[1]->name );
	}

	/**
	 * Test that providers are returned in registration order when no preferred provider is set.
	 */
	public function test_providers_ordered_by_registration() {
		// Define test provider classes.
		$provider1_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-1';
				$this->name = 'Provider One';
			}
		};

		$provider2_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-2';
				$this->name = 'Provider Two';
			}
		};

		$provider3_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-3';
				$this->name = 'Provider Three';
			}
		};

		$provider1_class_name = get_class( $provider1_class );
		$provider2_class_name = get_class( $provider2_class );
		$provider3_class_name = get_class( $provider3_class );

		// Register providers in specific order.
		add_filter(
			'woocommerce_address_providers',
			function () use ( $provider1_class_name, $provider2_class_name, $provider3_class_name ) {
				return array( $provider1_class_name, $provider2_class_name, $provider3_class_name );
			}
		);

		// Delete any existing preferred provider setting.
		delete_option( 'woocommerce_address_autocomplete_provider' );

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$providers = $this->sut->get_providers();

		// Verify providers are in registration order.
		$this->assertCount( 3, $providers );
		$this->assertEquals( 'provider-1', $providers[0]->id );
		$this->assertEquals( 'provider-2', $providers[1]->id );
		$this->assertEquals( 'provider-3', $providers[2]->id );
	}

	/**
	 * Test that the preferred provider is moved to the first position when set.
	 */
	public function test_preferred_provider_moved_to_first_position() {
		// Define test provider classes.
		$provider1_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-1';
				$this->name = 'Provider One';
			}
		};

		$provider2_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-2';
				$this->name = 'Provider Two';
			}
		};

		$provider3_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-3';
				$this->name = 'Provider Three';
			}
		};

		$provider1_class_name = get_class( $provider1_class );
		$provider2_class_name = get_class( $provider2_class );
		$provider3_class_name = get_class( $provider3_class );

		// Register providers in specific order.
		add_filter(
			'woocommerce_address_providers',
			function () use ( $provider1_class_name, $provider2_class_name, $provider3_class_name ) {
				return array( $provider1_class_name, $provider2_class_name, $provider3_class_name );
			}
		);

		// Set provider-2 as the preferred provider.
		update_option( 'woocommerce_address_autocomplete_provider', 'provider-2' );

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$providers = $this->sut->get_providers();

		// Verify provider-2 is first, and others maintain their relative order.
		$this->assertCount( 3, $providers );
		$this->assertEquals( 'provider-2', $providers[0]->id );
		$this->assertEquals( 'provider-1', $providers[1]->id );
		$this->assertEquals( 'provider-3', $providers[2]->id );
	}

	/**
	 * Test that the preferred provider is moved to the first position when the last provider is preferred.
	 */
	public function test_preferred_provider_moved_from_last_position() {
		// Define test provider classes.
		$provider1_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-1';
				$this->name = 'Provider One';
			}
		};

		$provider2_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-2';
				$this->name = 'Provider Two';
			}
		};

		$provider3_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-3';
				$this->name = 'Provider Three';
			}
		};

		$provider1_class_name = get_class( $provider1_class );
		$provider2_class_name = get_class( $provider2_class );
		$provider3_class_name = get_class( $provider3_class );

		// Register providers in specific order.
		add_filter(
			'woocommerce_address_providers',
			function () use ( $provider1_class_name, $provider2_class_name, $provider3_class_name ) {
				return array( $provider1_class_name, $provider2_class_name, $provider3_class_name );
			}
		);

		// Set provider-3 (the last one) as the preferred provider.
		update_option( 'woocommerce_address_autocomplete_provider', 'provider-3' );

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$providers = $this->sut->get_providers();

		// Verify provider-3 is first, and others maintain their relative order.
		$this->assertCount( 3, $providers );
		$this->assertEquals( 'provider-3', $providers[0]->id );
		$this->assertEquals( 'provider-1', $providers[1]->id );
		$this->assertEquals( 'provider-2', $providers[2]->id );
	}

	/**
	 * Test that the original order is preserved when the preferred provider is not found.
	 */
	public function test_order_preserved_when_preferred_provider_not_found() {
		// Define test provider classes.
		$provider1_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-1';
				$this->name = 'Provider One';
			}
		};

		$provider2_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-2';
				$this->name = 'Provider Two';
			}
		};

		$provider1_class_name = get_class( $provider1_class );
		$provider2_class_name = get_class( $provider2_class );

		// Register providers in specific order.
		add_filter(
			'woocommerce_address_providers',
			function () use ( $provider1_class_name, $provider2_class_name ) {
				return array( $provider1_class_name, $provider2_class_name );
			}
		);

		// Set a non-existent provider as preferred.
		update_option( 'woocommerce_address_autocomplete_provider', 'provider-nonexistent' );

		$this->sut = new AddressProviderController();
		$this->sut->init();

		$providers = $this->sut->get_providers();

		// Verify original order is maintained.
		$this->assertCount( 2, $providers );
		$this->assertEquals( 'provider-1', $providers[0]->id );
		$this->assertEquals( 'provider-2', $providers[1]->id );
	}

	/**
	 * Test the original order is preserved when the first provider is already the preferred one.
	 */
	public function test_order_preserved_when_first_provider_already_preferred() {
		// Define test provider classes.
		$provider1_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-1';
				$this->name = 'Provider One';
			}
		};

		$provider2_class = new class() extends WC_Address_Provider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'provider-2';
				$this->name = 'Provider Two';
			}
		};

		$provider1_class_name = get_class( $provider1_class );
		$provider2_class_name = get_class( $provider2_class );

		// Register providers in specific order.
		add_filter(
			'woocommerce_address_providers',
			function () use ( $provider1_class_name, $provider2_class_name ) {
				return array( $provider1_class_name, $provider2_class_name );
			}
		);

		$this->sut = new AddressProviderController();
		$this->sut->init();

		// Set the first provider as preferred.
		update_option( 'woocommerce_address_autocomplete_provider', 'provider-1' );

		$providers = $this->sut->get_providers();

		// Verify original order is maintained.
		$this->assertCount( 2, $providers );
		$this->assertEquals( 'provider-1', $providers[0]->id );
		$this->assertEquals( 'provider-2', $providers[1]->id );
	}
}
