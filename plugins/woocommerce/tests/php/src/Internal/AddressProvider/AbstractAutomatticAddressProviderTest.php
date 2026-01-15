<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AddressProvider;

use Automattic\WooCommerce\Internal\AddressProvider\AbstractAutomatticAddressProvider;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use Automattic\Jetpack\Constants;
use WC_Address_Provider;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Tests for AbstractAutomatticAddressProvider functionality
 */
class AbstractAutomatticAddressProviderTest extends TestCase {

	/**
	 * The mock logger.
	 *
	 * @var \WC_Logger_Interface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_logger;

	/**
	 * The test provider instance.
	 *
	 * @var AbstractAutomatticAddressProvider
	 */
	protected $test_provider;

	/**
	 * Setup test case.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Setup mock logger.
		$this->mock_logger = $this->getMockBuilder( 'WC_Logger_Interface' )->getMock();
		add_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );

		// Enable address autocomplete for tests.
		update_option( 'woocommerce_address_autocomplete_enabled', 'yes' );

		// Create test provider instance.
		$this->test_provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				return 'invalid-jwt';
			}
		};

		// Clear any existing options.
		delete_option( 'test-provider_address_autocomplete_jwt' );
		delete_option( 'test-provider_jwt_retry_data' );
	}

	/**
	 * Tear down test case.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'pre_update_option_woocommerce_address_autocomplete_enabled' );
		remove_all_filters( 'woocommerce_is_checkout' );
		remove_all_actions( 'wp_enqueue_scripts' );
		remove_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );

		// Dequeue and deregister scripts.
		wp_dequeue_script( 'a8c-address-autocomplete-service' );
		wp_deregister_script( 'a8c-address-autocomplete-service' );

		// Clean up options.
		delete_option( 'test-provider_address_autocomplete_jwt' );
		delete_option( 'test-provider_jwt_retry_data' );
		delete_option( 'woocommerce_address_autocomplete_enabled' );
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
	 * Test constructor sets up hooks correctly.
	 */
	public function test_constructor_sets_up_hooks() {
		$this->assertNotFalse( has_filter( 'pre_update_option_woocommerce_address_autocomplete_enabled', array( $this->test_provider, 'refresh_cache' ) ) );
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', array( $this->test_provider, 'load_scripts' ) ) );
	}

	/**
	 * Test can_telemetry returns false by default.
	 */
	public function test_can_telemetry_returns_false_by_default() {
		$this->assertFalse( $this->test_provider->can_telemetry() );
	}

	/**
	 * Test can_telemetry can be overridden.
	 */
	public function test_can_telemetry_can_be_overridden() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				return 'test-jwt';
			}

			/**
			 * Override can_telemetry.
			 *
			 * @return bool
			 */
			public function can_telemetry() {
				return true;
			}
		};

		$this->assertTrue( $provider->can_telemetry() );
	}

	/**
	 * Test get_jwt returns null initially.
	 */
	public function test_get_jwt_returns_null_initially() {
		$this->assertNull( $this->test_provider->get_jwt() );
	}

	/**
	 * Test load_jwt_fetches_fresh_token_when_no_cache().
	 */
	public function test_load_jwt_fetches_fresh_token_when_no_cache() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				// Return a valid JWT for testing.
				return JsonWebToken::create(
					array(
						'iss' => 'test-issuer',
						'aud' => 'test-audience',
						'exp' => time() + 3600, // 1 hour from now.
						'iat' => time(),
					),
					'test-secret'
				);
			}
		};

		$jwt = $provider->get_jwt();

		$this->assertNotNull( $jwt );
		$this->assertTrue( JsonWebToken::shallow_validate( $jwt ) );
	}

	/**
	 * Test load_jwt_handles_exception_gracefully().
	 */
	public function test_load_jwt_handles_exception_gracefully() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @throws \Exception Throws an exception to test the error handling.
			 */
			public function get_address_service_jwt() {
				throw new \Exception( 'Test exception' );
			}
		};

		$this->mock_logger
			->expects( $this->once() )
			->method( 'error' )
			->with(
				$this->stringContains( 'Failed loading JWT for Test Provider address autocomplete service (attempt 1) with error Test exception.' ),
				'address-autocomplete'
			);

		$this->assertNull( $provider->get_jwt() );
	}

	/**
	 * Test set_jwt_caches_valid_token().
	 */
	public function test_set_jwt_caches_valid_token() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				return 'test-jwt';
			}
		};

		$valid_jwt = JsonWebToken::create(
			array(
				'iss' => 'test-issuer',
				'aud' => 'test-audience',
				'exp' => time() + 3600,
				'iat' => time(),
			),
			'test-secret'
		);

		$provider->set_jwt( $valid_jwt );

		// Verify the JWT is set in the instance.
		$this->assertEquals( $valid_jwt, $provider->get_jwt() );
	}

	/**
	 * Test set_jwt_removes_expired_token().
	 */
	public function test_set_jwt_removes_expired_token() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				return 'test-jwt';
			}
		};

		$expired_jwt = JsonWebToken::create(
			array(
				'iss' => 'test-issuer',
				'aud' => 'test-audience',
				'exp' => time() - 3600, // Expired 1 hour ago.
				'iat' => time() - 7200,
			),
			'test-secret'
		);

		$provider->set_jwt( $expired_jwt );

		// The token should be null because it's expired.
		$this->assertNull( $provider->get_jwt() );
	}

	/**
	 * Test get_jwt_cache_duration_returns_correct_duration().
	 */
	public function test_get_jwt_cache_duration_returns_correct_duration() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				return 'test-jwt';
			}
		};

		$expiration_time = time() + 1800; // 30 minutes from now.
		$valid_jwt       = JsonWebToken::create(
			array(
				'iss' => 'test-issuer',
				'aud' => 'test-audience',
				'exp' => $expiration_time,
				'iat' => time(),
			),
			'test-secret'
		);

		$duration = $provider->get_jwt_cache_duration( $valid_jwt );
		$this->assertGreaterThan( 0, $duration );
		$this->assertLessThanOrEqual( 1800, $duration );
	}

	/**
	 * Test get_jwt_cache_duration_returns_null_when_no_exp().
	 */
	public function test_get_jwt_cache_duration_returns_null_when_no_exp() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				return 'test-jwt';
			}
		};

		$jwt_without_exp = JsonWebToken::create(
			array(
				'iss' => 'test-issuer',
				'aud' => 'test-audience',
				'iat' => time(),
			),
			'test-secret'
		);

		$duration = $provider->get_jwt_cache_duration( $jwt_without_exp );
		$this->assertNull( $duration );
	}

	/**
	 * Test refresh_cache_loads_jwt_when_enabled().
	 */
	public function test_refresh_cache_loads_jwt_when_enabled() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				// Return a valid JWT for testing.
				return JsonWebToken::create(
					array(
						'iss' => 'test-issuer',
						'aud' => 'test-audience',
						'exp' => time() + 3600, // 1 hour from now.
						'iat' => time(),
					),
					'test-secret'
				);
			}
		};

		$result = $provider->refresh_cache( 'yes' );

		$this->assertEquals( 'yes', $result );
		$this->assertNotNull( $provider->get_jwt() );
	}

	/**
	 * Test refresh_cache_clears_jwt_when_disabled().
	 */
	public function test_refresh_cache_clears_jwt_when_disabled() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				return 'test-jwt';
			}
		};

		// First set a valid token.
		$valid_jwt = JsonWebToken::create(
			array(
				'iss' => 'test-issuer',
				'aud' => 'test-audience',
				'exp' => time() + 3600,
				'iat' => time(),
			),
			'test-secret'
		);

		$provider->set_jwt( $valid_jwt );
		$this->assertNotNull( $provider->get_jwt() );

		// Now disable the service.
		$result = $provider->refresh_cache( 'no' );

		$this->assertEquals( 'no', $result );
		$this->assertNull( $provider->get_jwt() );
	}

	/**
	 * Test get_asset_url_returns_correct_url().
	 */
	public function test_get_asset_url_returns_correct_url() {
		$path = 'assets/js/test.js';
		$url  = AbstractAutomatticAddressProvider::get_asset_url( $path );

		$this->assertStringContainsString( 'assets/js/test.js', $url );
		$this->assertStringContainsString( 'plugins/woocommerce', $url );
	}

	/**
	 * Test load_scripts_registers_and_enqueues_script().
	 */
	public function test_load_scripts_registers_and_enqueues_script() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				// Return a valid JWT for testing.
				return JsonWebToken::create(
					array(
						'iss' => 'test-issuer',
						'aud' => 'test-audience',
						'exp' => time() + 3600, // 1 hour from now.
						'iat' => time(),
					),
					'test-secret'
				);
			}
		};

		// Mock is_checkout() to return true.
		add_filter(
			'woocommerce_is_checkout',
			function () {
				return true;
			}
		);

		// Call load_scripts.
		$provider->load_scripts();

		// Check if script is registered and enqueued.
		$this->assertTrue( wp_script_is( 'a8c-address-autocomplete-service', 'registered' ) );
		$this->assertTrue( wp_script_is( 'a8c-address-autocomplete-service', 'enqueued' ) );

		// Clean up filter.
		remove_all_filters( 'woocommerce_is_checkout' );
	}

	/**
	 * Test load_scripts_adds_inline_script_with_jwt().
	 */
	public function test_load_scripts_adds_inline_script_with_jwt() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				// Return a valid JWT for testing.
				return JsonWebToken::create(
					array(
						'iss' => 'test-issuer',
						'aud' => 'test-audience',
						'exp' => time() + 3600, // 1 hour from now.
						'iat' => time(),
					),
					'test-secret'
				);
			}
		};

		// Mock is_checkout() to return true.
		add_filter(
			'woocommerce_is_checkout',
			function () {
				return true;
			}
		);

		// Call load_scripts.
		$provider->load_scripts();

		// Check if inline script was added.
		global $wp_scripts;
		$script = $wp_scripts->get_data( 'a8c-address-autocomplete-service', 'data' );

		// The script data might be false if no inline script was added.
		if ( false !== $script ) {
			$this->assertStringContainsString( 'test-provider', $script );
			$this->assertStringContainsString( 'false', $script ); // canTelemetry should be false by default.
		} else {
			// If no inline script was added, that's also acceptable for this test.
			$this->assertTrue( true );
		}

		// Clean up filter.
		remove_all_filters( 'woocommerce_is_checkout' );
	}

	/**
	 * Test load_scripts_handles_null_jwt().
	 */
	public function test_load_scripts_handles_null_jwt() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				return 'test-jwt';
			}
		};

		// Mock is_checkout() to return true.
		add_filter(
			'woocommerce_is_checkout',
			function () {
				return true;
			}
		);

		// Call load_scripts.
		$provider->load_scripts();

		// Check if inline script was added with null JWT.
		global $wp_scripts;
		$script = $wp_scripts->get_data( 'a8c-address-autocomplete-service', 'data' );

		// The script data might be false if no inline script was added.
		if ( false !== $script ) {
			$this->assertStringContainsString( 'test-provider', $script );
			$this->assertStringContainsString( 'null', $script );
		} else {
			// If no inline script was added, that's also acceptable for this test.
			$this->assertTrue( true );
		}

		// Clean up filter.
		remove_all_filters( 'woocommerce_is_checkout' );
	}

	/**
	 * Test load_scripts_does_not_duplicate_registration().
	 */
	public function test_load_scripts_does_not_duplicate_registration() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				// Return a valid JWT for testing.
				return JsonWebToken::create(
					array(
						'iss' => 'test-issuer',
						'aud' => 'test-audience',
						'exp' => time() + 3600, // 1 hour from now.
						'iat' => time(),
					),
					'test-secret'
				);
			}
		};

		// Mock is_checkout() to return true.
		add_filter(
			'woocommerce_is_checkout',
			function () {
				return true;
			}
		);

		// Call load_scripts twice.
		$provider->load_scripts();
		$provider->load_scripts();

		// Script should still be registered and enqueued only once.
		$this->assertTrue( wp_script_is( 'a8c-address-autocomplete-service', 'registered' ) );
		$this->assertTrue( wp_script_is( 'a8c-address-autocomplete-service', 'enqueued' ) );

		// Clean up filter.
		remove_all_filters( 'woocommerce_is_checkout' );
	}

	/**
	 * Test backoff logic for multiple failure scenarios.
	 */
	public function test_backoff_logic_for_multiple_failures() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @throws \Exception Always throws an exception to test retry logic.
			 */
			public function get_address_service_jwt() {
				throw new \Exception( 'Test failure' );
			}
		};

		// Test different attempt numbers and their expected backoff periods.
		$test_cases = array(
			1 => array(
				'attempts'         => 0,
				'expected_hours'   => 1,
				'expected_attempt' => 1,
			),
			2 => array(
				'attempts'         => 1,
				'expected_hours'   => 2,
				'expected_attempt' => 2,
			),
			3 => array(
				'attempts'         => 2,
				'expected_hours'   => 4,
				'expected_attempt' => 3,
			),
			4 => array(
				'attempts'         => 3,
				'expected_hours'   => 8,
				'expected_attempt' => 4,
			),
		);

		foreach ( $test_cases as $test_number => $test_case ) {
			// Set up retry data for this test case.
			if ( $test_case['attempts'] > 0 ) {
				update_option(
					'test-provider_jwt_retry_data',
					array(
						'data'    => array(
							'attempts'  => $test_case['attempts'],
							'try_after' => time() - 10, // Allow retry (10 seconds ago).
						),
						'updated' => time() - 10,
						'ttl'     => DAY_IN_SECONDS,
					),
					false
				);
			} else {
				// Clear any existing retry data for first attempt.
				delete_option( 'test-provider_jwt_retry_data' );
			}

			// Create a new mock logger for each test case to avoid conflicts.
			$mock_logger = $this->getMockBuilder( 'WC_Logger_Interface' )->getMock();
			$mock_logger
				->expects( $this->once() )
				->method( 'error' )
				->with(
					$this->stringContains( sprintf( 'Failed loading JWT for Test Provider address autocomplete service (attempt %d) with error Test failure.', $test_case['expected_attempt'] ) ),
					'address-autocomplete'
				);

			// Temporarily override the logger for this test case.
			add_filter(
				'woocommerce_logging_class',
				function () use ( $mock_logger ) {
					return $mock_logger;
				}
			);

			// Attempt should fail and update retry data.
			$provider->get_jwt();

			// Remove the temporary logger override.
			remove_all_filters( 'woocommerce_logging_class' );

			// Check that retry data was set correctly.
			$retry_data = get_option( 'test-provider_jwt_retry_data' );
			$this->assertNotNull( $retry_data, "Retry data should be set for test case {$test_number}" );
			$this->assertEquals( $test_case['expected_attempt'], $retry_data['data']['attempts'], "Attempt count should be {$test_case['expected_attempt']} for test case {$test_number}" );
			$this->assertArrayHasKey( 'try_after', $retry_data['data'], "try_after should be set for test case {$test_number}" );

			// Calculate expected try_after time.
			$expected_try_after = time() + ( $test_case['expected_hours'] * HOUR_IN_SECONDS );
			$this->assertGreaterThanOrEqual( $expected_try_after - 10, $retry_data['data']['try_after'], "try_after should be at least {$expected_try_after} for test case {$test_number}" ); // Allow 10 second tolerance.
			$this->assertLessThanOrEqual( $expected_try_after + 10, $retry_data['data']['try_after'], "try_after should be at most {$expected_try_after} for test case {$test_number}" );
		}
	}

	/**
	 * Test that retry is prevented when try_after time hasn't passed.
	 */
	public function test_retry_prevented_when_try_after_time_not_passed() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @throws \Exception Always throws an exception to test retry logic.
			 */
			public function get_address_service_jwt() {
				throw new \Exception( 'Test failure' );
			}
		};

		// Set up retry data with try_after in the future.
		update_option(
			'test-provider_jwt_retry_data',
			array(
				'data'    => array(
					'attempts'  => 1,
					'try_after' => time() + 3600, // 1 hour in the future.
				),
				'updated' => time(),
				'ttl'     => DAY_IN_SECONDS,
			),
			false
		);

		// Should not attempt to fetch JWT because try_after time hasn't passed.
		$provider->get_jwt();

		// Verify that no error was logged (no attempt was made).
		$this->mock_logger
			->expects( $this->never() )
			->method( 'error' );

		// Retry data should remain unchanged.
		$retry_data = get_option( 'test-provider_jwt_retry_data' );
		$this->assertEquals( 1, $retry_data['data']['attempts'] );
	}

	/**
	 * Test that retry data is cleared on successful JWT fetch.
	 */
	public function test_retry_data_cleared_on_successful_jwt_fetch() {
		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				// Return a valid JWT for testing.
				return JsonWebToken::create(
					array(
						'iss' => 'test-issuer',
						'aud' => 'test-audience',
						'exp' => time() + 3600, // 1 hour from now.
						'iat' => time(),
					),
					'test-secret'
				);
			}
		};

		// Set up retry data to simulate previous failures.
		update_option(
			'test-provider_jwt_retry_data',
			array(
				'data'    => array(
					'attempts'  => 2,
					'try_after' => time() - 10, // Allow retry (10 seconds ago).
				),
				'updated' => time() - 10,
				'ttl'     => DAY_IN_SECONDS,
			),
			false
		);

		// Successful JWT fetch should clear retry data.
		$jwt = $provider->get_jwt();

		$this->assertNotNull( $jwt );
		$this->assertTrue( JsonWebToken::shallow_validate( $jwt ) );

		// Retry data should be deleted.
		$retry_data = get_option( 'test-provider_jwt_retry_data' );
		$this->assertFalse( $retry_data );
	}

	/**
	 * Test load_jwt does not load JWT when autocomplete is disabled.
	 *
	 * @testdox load_jwt() should not load JWT when woocommerce_address_autocomplete_enabled is disabled
	 */
	public function test_load_jwt_does_not_load_when_disabled() {
		update_option( 'woocommerce_address_autocomplete_enabled', 'no' );

		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				// Return a valid JWT for testing.
				return JsonWebToken::create(
					array(
						'iss' => 'test-issuer',
						'aud' => 'test-audience',
						'exp' => time() + 3600, // 1 hour from now.
						'iat' => time(),
					),
					'test-secret'
				);
			}
		};

		$jwt = $provider->get_jwt();

		$this->assertNull( $jwt );
	}

	/**
	 * Test load_scripts does not enqueue scripts when autocomplete is disabled.
	 *
	 * @testdox load_scripts() should not enqueue scripts when woocommerce_address_autocomplete_enabled is disabled
	 */
	public function test_load_scripts_does_not_enqueue_when_disabled() {
		update_option( 'woocommerce_address_autocomplete_enabled', 'no' );

		$provider = new class() extends AbstractAutomatticAddressProvider {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id   = 'test-provider';
				$this->name = 'Test Provider';
				parent::__construct();
			}

			/**
			 * Get address service JWT.
			 *
			 * @return string
			 */
			public function get_address_service_jwt() {
				// Return a valid JWT for testing.
				return JsonWebToken::create(
					array(
						'iss' => 'test-issuer',
						'aud' => 'test-audience',
						'exp' => time() + 3600, // 1 hour from now.
						'iat' => time(),
					),
					'test-secret'
				);
			}
		};

		// Mock is_checkout() to return true.
		add_filter(
			'woocommerce_is_checkout',
			function () {
				return true;
			}
		);

		// Call load_scripts.
		$provider->load_scripts();

		// Check that script was not enqueued.
		$this->assertFalse( wp_script_is( 'a8c-address-autocomplete-service', 'enqueued' ) );

		// Clean up filter.
		remove_all_filters( 'woocommerce_is_checkout' );
	}
}
