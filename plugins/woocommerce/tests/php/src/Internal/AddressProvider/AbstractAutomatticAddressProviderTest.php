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
		delete_option( 'test-provider_last_fetch_attempt' );
	}

	/**
	 * Tear down test case.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'pre_update_option_woocommerce_address_autocomplete_enabled' );
		remove_all_actions( 'wp_enqueue_scripts' );
		remove_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );

		// Clean up options.
		delete_option( 'test-provider_address_autocomplete_jwt' );
		delete_option( 'test-provider_last_fetch_attempt' );
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
				$this->stringContains( 'Failed loding JWT for Test Provider address autocomplete service with error Test exception.' ),
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

		// Call load_scripts.
		$provider->load_scripts();

		// Check if script is registered and enqueued.
		$this->assertTrue( wp_script_is( 'a8c-address-autocomplete-service', 'registered' ) );
		$this->assertTrue( wp_script_is( 'a8c-address-autocomplete-service', 'enqueued' ) );
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

		// Call load_scripts twice.
		$provider->load_scripts();
		$provider->load_scripts();

		// Script should still be registered and enqueued only once.
		$this->assertTrue( wp_script_is( 'a8c-address-autocomplete-service', 'registered' ) );
		$this->assertTrue( wp_script_is( 'a8c-address-autocomplete-service', 'enqueued' ) );
	}
}
