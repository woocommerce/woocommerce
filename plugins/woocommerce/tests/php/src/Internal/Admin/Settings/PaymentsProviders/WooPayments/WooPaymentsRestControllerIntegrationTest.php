<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders\WooPayments;

use Automattic\Jetpack\Connection\Manager as WPCOM_Connection_Manager;
use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsRestController;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * WooPaymentsRestController API controller integration test.
 *
 * @class WooPaymentsRestController
 */
class WooPaymentsRestControllerIntegrationTest extends WC_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/settings/payments/woopayments';

	/**
	 * @var WooPaymentsRestController
	 */
	protected WooPaymentsRestController $controller;

	/**
	 * @var PaymentsProviders
	 */
	protected PaymentsProviders $providers_service;

	/**
	 * @var WooPaymentsService
	 */
	protected WooPaymentsService $woopayments_provider_service;

	/**
	 * @var MockableLegacyProxy|MockObject
	 */
	protected $mockable_proxy;

	/**
	 * @var WPCOM_Connection_Manager|MockObject
	 */
	protected $mock_wpcom_connection_manager;

	/**
	 * @var object&MockObject
	 */
	protected $mock_account_service;

	/**
	 * @var FakePaymentGateway
	 */
	protected FakePaymentGateway $mock_gateway;

	/**
	 * The ID of the store admin user.
	 *
	 * @var int
	 */
	protected $store_admin_id;

	/**
	 * REST server used by the controller tests.
	 *
	 * @var \WP_REST_Server
	 */
	protected $server;

	/**
	 * The current time in seconds.
	 *
	 * Use it instead of time() to avoid using the real time in tests.
	 *
	 * @var int
	 */
	protected int $current_time;

	/**
	 * Gateways mock.
	 *
	 * @var callable|null
	 */
	private $gateways_mock_ref = null;

	/**
	 * Raw option states before the test class mutates them.
	 *
	 * @var array<string, array{exists: bool, value: string|null, autoload: string|null}>
	 */
	private static array $initial_option_states = array();

	/** @var bool Whether the WooPayments version override existed before the test. */
	private bool $initial_wcpay_version_override_exists = false;

	/** @var mixed WooPayments version override value before the test. */
	private $initial_wcpay_version_override;

	/**
	 * Saves values of initial country and currency before running test suite.
	 */
	public static function wpSetUpBeforeClass(): void {
		foreach ( self::get_restored_option_names() as $option_name ) {
			self::$initial_option_states[ $option_name ] = self::get_raw_option_state( $option_name );
			self::invalidate_option_cache( $option_name );
		}
	}

	/**
	 * Restores initial values of country and currency after running test suite.
	 */
	public static function wpTearDownAfterClass(): void {
		$failures = array();
		foreach ( self::$initial_option_states as $option_name => $state ) {
			try {
				if ( ! self::restore_raw_option_state( $option_name, $state ) ) {
					$failures[] = "Database write failed for {$option_name}.";
				}
			} catch ( \Throwable $error ) {
				$failures[] = $error->getMessage();
			}
		}

		if ( array() !== $failures ) {
			throw new \RuntimeException( esc_html( implode( ' ', $failures ) ) );
		}
	}

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->initial_wcpay_version_override_exists = array_key_exists( 'WCPAY_VERSION_NUMBER', Constants::$set_constants );
		$this->initial_wcpay_version_override        = Constants::$set_constants['WCPAY_VERSION_NUMBER'] ?? null;

		$gateways                   = \WC_Payment_Gateways::instance();
		$gateways->payment_gateways = array();
		$gateways->init();

		$this->store_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->store_admin_id );

		$this->current_time = 1234567890;

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();

		// Arrange the version constant to meet the minimum requirements for the native in-context onboarding.
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', WooPaymentsService::EXTENSION_MINIMUM_VERSION );

		$this->mock_wpcom_connection_manager = $this->getMockBuilder( WPCOM_Connection_Manager::class )
													->onlyMethods(
														array(
															'is_connected',
															'has_connected_owner',
															'is_connection_owner',
															'try_registration',
														)
													)
													->getMock();

		$this->mockable_proxy = $container->get( LegacyProxy::class );
		$this->mockable_proxy->register_class_mocks(
			array(
				WPCOM_Connection_Manager::class => $this->mock_wpcom_connection_manager,
			)
		);
		// We have no way of knowing if the container has already resolved the mocked classes,
		// so we need to reset all resolved instances.
		$container->reset_all_resolved();

		$this->mock_account_service = $this->getMockBuilder( \stdClass::class )
											->addMethods( array( 'is_stripe_account_valid', 'get_account_status_data' ) )
											->getMock();

		// Use this instance to set different states depending on your specific test needs.
		$this->mock_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'                     => false,
				'account_connected'           => false,
				'needs_setup'                 => true,
				'test_mode'                   => true,
				'dev_mode'                    => true,
				'onboarding_started'          => false,
				'onboarding_completed'        => false,
				'onboarding_test_mode'        => false,
				'plugin_slug'                 => 'woocommerce-payments',
				'plugin_file'                 => 'woocommerce-payments/woocommerce-payments.php',
				'recommended_payment_methods' => array(
					array(
						'id'          => 'card',
						'_order'      => 0,
						'enabled'     => true,
						'required'    => true,
						'title'       => 'Credit/debit card (required)',
						'description' => 'Accepts all major credit and debit cards',
						'icon'        => 'https://example.com/card-icon.png',
					),
					array(
						'id'          => 'woopay',
						'_order'      => 1,
						'enabled'     => false,
						'title'       => 'WooPay',
						'description' => 'WooPay express checkout',
						'icon'        => 'https://example.com/woopay-icon.png',
					),
				),
			)
		);

		$this->mockable_proxy->register_static_mocks(
			array(
				'\WC_Payments'         => array(
					'get_gateway'         => function () {
						return $this->mock_gateway;
					},
					'get_account_service' => function () {
						return $this->mock_account_service;
					},
				),
				'\WC_Payments_Account' => array(
					'get_connect_url'       => function () {
						return 'https://example.com/kyc_fallback';
					},
					'get_overview_page_url' => function () {
						return 'https://example.com/overview_page?from=' . WooPaymentsService::FROM_NOX_IN_CONTEXT;
					},
				),
				'\WC_Payments_Utils'   => array(
					'supported_countries' => function () {
						return $this->get_woopayments_supported_countries();
					},
				),
				Utils::class           => array(
					'get_wpcom_connection_authorization' => function ( string $return_url ) {
						unset( $return_url ); // Avoid parameter not used PHPCS errors.
						return array(
							'success'      => true,
							'errors'       => array(),
							'color_scheme' => 'fresh',
							'url'          => 'https://wordpress.com/auth?query=some_query',
						);
					},
					'rest_endpoint_get_request'          => function ( string $endpoint, array $params = array() ) {
						unset( $params ); // Avoid parameter not used PHPCS errors.
						if ( '/wc/v3/payments/onboarding/fields' === $endpoint ) {
							return array(
								'data' => array(),
							);
						}

						throw new \Exception( esc_html( 'GET endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		$this->mockable_proxy->register_function_mocks(
			array(
				// Mock the current time.
				'time'         => function () {
					return $this->current_time;
				},
				'class_exists' => function ( $class_to_check ) {
					// By default, the WooPayments extension is mocked as active.
					if ( '\WC_Payments' === $class_to_check ) {
						return true;
					}

					return false;
				},
			),
		);

		$this->gateways_mock_ref = function ( \WC_Payment_Gateways $wc_payment_gateways ) {
			$mock_gateways = array(
				'woocommerce_payments' => $this->mock_gateway,
			);
			$order         = 99999;
			foreach ( $mock_gateways as $gateway_id => $fake_gateway ) {
				$wc_payment_gateways->payment_gateways[ $order++ ] = $fake_gateway;
			}
		};

		$this->providers_service            = $container->get( PaymentsProviders::class );
		$this->woopayments_provider_service = $container->get( WooPaymentsService::class );

		// Register the REST controller routes again to make sure the dependency tree is using our mocks.
		$this->controller = new WooPaymentsRestController();
		$this->controller->init( $container->get( Payments::class ), $this->woopayments_provider_service );
		$this->server = $this->create_rest_server_with_routes(
			array(
				function () {
					$this->controller->register_routes( true );
				},
			),
			true
		);
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		$cleanup_errors = array();

		$this->run_cleanup_step( fn() => $this->unmock_payment_gateways(), $cleanup_errors );
		$this->run_cleanup_step(
			function () {
				if ( isset( $this->mockable_proxy ) ) {
					$this->mockable_proxy->reset();
				}
			},
			$cleanup_errors
		);
		$this->run_cleanup_step( fn() => wc_get_container()->reset_all_resolved(), $cleanup_errors );
		$this->run_cleanup_step( fn() => $this->clear_rest_server(), $cleanup_errors );
		$this->run_cleanup_step( fn() => $this->restore_wcpay_version_override(), $cleanup_errors );
		$this->run_cleanup_step( fn() => parent::tearDown(), $cleanup_errors );
		$this->run_cleanup_step( fn() => self::invalidate_test_option_caches(), $cleanup_errors );

		if ( array() !== $cleanup_errors ) {
			throw $cleanup_errors[0];
		}
	}

	/**
	 * Run one teardown phase without preventing later phases.
	 *
	 * @param callable     $callback Cleanup phase.
	 * @param \Throwable[] $errors   Collected cleanup errors.
	 */
	private function run_cleanup_step( callable $callback, array &$errors ): void {
		try {
			$callback();
		} catch ( \Throwable $error ) {
			$errors[] = $error;
		}
	}

	/**
	 * Restore only the Jetpack constant override owned by this fixture.
	 */
	private function restore_wcpay_version_override(): void {
		if ( $this->initial_wcpay_version_override_exists ) {
			Constants::set_constant( 'WCPAY_VERSION_NUMBER', $this->initial_wcpay_version_override );
		} else {
			Constants::clear_single_constant( 'WCPAY_VERSION_NUMBER' );
		}
	}

	/**
	 * Get option rows that must survive the class exactly.
	 *
	 * @return string[]
	 */
	private static function get_restored_option_names(): array {
		return array(
			'woocommerce_default_country',
			'woocommerce_currency',
		);
	}

	/**
	 * Get option caches mutated by the fixture or SUT.
	 *
	 * @return string[]
	 */
	private static function get_test_option_names(): array {
		return array_merge( self::get_restored_option_names(), self::get_transactional_option_names() );
	}

	/**
	 * Get option rows changed within the inherited transaction.
	 *
	 * @return string[]
	 */
	private static function get_transactional_option_names(): array {
		return array(
			'woocommerce_gateway_order',
			WooPaymentsService::NOX_PROFILE_OPTION_KEY,
			WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY,
		);
	}

	/**
	 * Read an option without default filters or value coercion.
	 *
	 * @param string $option_name Option name.
	 * @return array{exists: bool, value: string|null, autoload: string|null}
	 */
	private static function get_raw_option_state( string $option_name ): array {
		global $wpdb;

		$wpdb->last_error = '';
		$row              = $wpdb->get_row(
			$wpdb->prepare( "SELECT option_value, autoload FROM {$wpdb->options} WHERE option_name = %s", $option_name ),
			ARRAY_A
		);
		if ( '' !== $wpdb->last_error ) {
			throw new \RuntimeException( esc_html( "Failed to read option {$option_name}: {$wpdb->last_error}" ) );
		}

		return null === $row
			? array(
				'exists'   => false,
				'value'    => null,
				'autoload' => null,
			)
			: array(
				'exists'   => true,
				'value'    => $row['option_value'],
				'autoload' => $row['autoload'],
			);
	}

	/**
	 * Restore an option without invoking setting sanitizers.
	 *
	 * @param string                                                         $option_name Option name.
	 * @param array{exists: bool, value: string|null, autoload: string|null} $state Raw option state.
	 * @return bool Whether the database operation succeeded.
	 */
	private static function restore_raw_option_state( string $option_name, array $state ): bool {
		global $wpdb;

		try {
			if ( ! $state['exists'] ) {
				$result = $wpdb->delete( $wpdb->options, array( 'option_name' => $option_name ) );
			} elseif ( self::get_raw_option_state( $option_name )['exists'] ) {
				$result = $wpdb->update(
					$wpdb->options,
					array(
						'option_value' => $state['value'],
						'autoload'     => $state['autoload'],
					),
					array( 'option_name' => $option_name )
				);
			} else {
				$result = $wpdb->insert(
					$wpdb->options,
					array(
						'option_name'  => $option_name,
						'option_value' => $state['value'],
						'autoload'     => $state['autoload'],
					)
				);
			}

			return false !== $result;
		} finally {
			self::invalidate_option_cache( $option_name );
		}
	}

	/**
	 * Invalidate all WordPress option cache representations.
	 *
	 * @param string $option_name Option name.
	 */
	private static function invalidate_option_cache( string $option_name ): void {
		wp_cache_delete( $option_name, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Invalidate every option cache representation touched by a test.
	 */
	private static function invalidate_test_option_caches(): void {
		foreach ( self::get_test_option_names() as $option_name ) {
			wp_cache_delete( $option_name, 'options' );
		}
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Test getting onboarding details by a user without the needed capabilities.
	 */
	public function test_get_onboarding_details_by_user_without_caps() {
		// Arrange.
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => false, // This is needed.
			'install_plugins'    => true,  // This is not needed.
		);
		add_filter( 'user_has_cap', $filter_callback );

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( rest_authorization_required_code(), $response->get_status() );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test getting payment providers by a user with the needed permissions.
	 */
	public function test_get_onboarding_details_by_manager() {
		// Arrange.
		$country_code = 'US';
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => true,  // This is needed.
			'install_plugins'    => false, // This is not needed.
		);
		add_filter( 'user_has_cap', $filter_callback );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert all the entries are in the response.
		$this->assertArrayHasKey( 'state', $data );
		$this->assertArrayHasKey( 'steps', $data );
		$this->assertArrayHasKey( 'context', $data );

		// Check that the payment methods step has all the fields.
		$step = $data['steps'][0];
		$this->assertArrayHasKey( 'id', $step );
		$this->assertSame( WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS, $step['id'] );
		$this->assertArrayHasKey( 'path', $step );
		$this->assertSame( '/woopayments/onboarding/' . WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS, $step['path'] );
		$this->assertArrayHasKey( 'required_steps', $step );
		$this->assertSame( array(), $step['required_steps'] );
		$this->assertArrayHasKey( 'status', $step );
		$this->assertSame( WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED, $step['status'] );
		$this->assertArrayHasKey( 'errors', $step );
		$this->assertSame( array(), $step['errors'] );
		$this->assertArrayHasKey( 'actions', $step );
		$this->assertArrayHasKey( 'context', $step );
		// Check that we have all the actions.
		$this->assertArrayHasKey( 'start', $step['actions'] );
		$this->assertArrayHasKey( 'save', $step['actions'] );
		$this->assertArrayHasKey( 'check', $step['actions'] );
		$this->assertArrayHasKey( 'finish', $step['actions'] );
		$this->assertArrayHasKey( 'clean', $step['actions'] );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test getting onboarding details without specifying a location.
	 *
	 * It should default to the store's base location country.
	 */
	public function test_get_onboarding_details_with_no_location() {
		// Arrange.
		$country_code = 'LI'; // Liechtenstein.
		update_option( 'woocommerce_default_country', $country_code ); // Liechtenstein.

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert all the entries are in the response.
		$this->assertArrayHasKey( 'state', $data );
		$this->assertArrayHasKey( 'steps', $data );
		$this->assertArrayHasKey( 'context', $data );
	}

	/**
	 * Test getting onboarding details with invalid location.
	 *
	 * @dataProvider provider_invalid_location_provider
	 *
	 * @param string $location The location to test.
	 */
	public function test_get_onboarding_details_with_invalid_location( string $location ) {
		// Arrange.

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test handling onboarding step start.
	 */
	public function test_onboarding_step_start() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$country_code = 'US';

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/start' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'previous_status', $data );
		$this->assertSame( WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED, $data['previous_status'] );
		$this->assertArrayHasKey( 'current_status', $data );
		$this->assertSame( WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED, $data['current_status'] );
	}

	/**
	 * Test handling onboarding step start with invalid location.
	 *
	 * @dataProvider provider_invalid_location_provider
	 *
	 * @param string $location The location to test.
	 */
	public function test_onboarding_step_start_with_invalid_location( string $location ) {
		// Arrange.
		$step_id = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/start' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test handling onboarding step start with invalid step.
	 */
	public function test_onboarding_step_start_with_invalid_step() {
		// Arrange.
		$step_id      = 'invalid_step';
		$country_code = 'US';

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/start' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test handling onboarding step save.
	 */
	public function test_onboarding_step_save() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$country_code = 'US';

		$request_params = array(
			'payment_methods' => array(
				'card'   => true,
				'woopay' => false,
			),
			'another_key'     => 'another_value', // This should be ignored and not saved.
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/save' );
		$request->set_param( 'location', $country_code );
		foreach ( $request_params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );

		$this->assertSame(
			array(
				'onboarding' => array(
					$country_code => array(
						'steps' => array(
							$step_id => array(
								'data'     => array(
									'payment_methods' => $request_params['payment_methods'],
								),
								'statuses' => array(
									'started' => $this->current_time, // The step is auto-marked as started when saved.
								),
							),
						),
					),
				),
			),
			get_option( WooPaymentsService::NOX_PROFILE_OPTION_KEY )
		);
	}

	/**
	 * Test handling onboarding step save with invalid data.
	 */
	public function test_onboarding_step_save_with_invalid_data() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$country_code = 'US';

		$request_params = array(
			// The `payment_methods` entry is missing.
			'some_key'    => 'some_value',
			'another_key' => 'another_value',
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/save' );
		$request->set_param( 'location', $country_code );
		foreach ( $request_params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );

		$this->assertSame( false, get_option( WooPaymentsService::NOX_PROFILE_OPTION_KEY ) );
	}

	/**
	 * Test handling onboarding step save with invalid location.
	 *
	 * @dataProvider provider_invalid_location_provider
	 *
	 * @param string $location The location to test.
	 */
	public function test_onboarding_step_save_with_invalid_location( string $location ) {
		// Arrange.
		$step_id = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/save' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test handling onboarding step save with invalid step.
	 */
	public function test_onboarding_step_save_with_invalid_step() {
		// Arrange.
		$step_id      = 'invalid_step';
		$country_code = 'US';

		$request_params = array(
			'another_key' => 'another_value',
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/save' );
		$request->set_param( 'location', $country_code );
		foreach ( $request_params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test handling onboarding step check.
	 */
	public function test_onboarding_step_check() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$country_code = 'US';

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/check' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertSame( WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED, $data['status'] );
	}

	/**
	 * Test handling onboarding step check with invalid location.
	 *
	 * @dataProvider provider_invalid_location_provider
	 *
	 * @param string $location The location to test.
	 */
	public function test_onboarding_step_check_with_invalid_location( string $location ) {
		// Arrange.
		$step_id = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/check' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test handling onboarding step check with invalid step.
	 */
	public function test_onboarding_step_check_with_invalid_step() {
		// Arrange.
		$step_id      = 'invalid_step';
		$country_code = 'US';

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/check' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test handling onboarding step finish.
	 */
	public function test_onboarding_step_finish() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$country_code = 'US';

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/finish' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'previous_status', $data );
		$this->assertSame( WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED, $data['previous_status'] );
		$this->assertArrayHasKey( 'current_status', $data );
		$this->assertSame( WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, $data['current_status'] );
	}

	/**
	 * Test handling onboarding step finish with invalid location.
	 *
	 * @dataProvider provider_invalid_location_provider
	 *
	 * @param string $location The location to test.
	 */
	public function test_onboarding_step_finish_with_invalid_location( string $location ) {
		// Arrange.
		$step_id = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/finish' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test handling onboarding step finish with invalid step.
	 */
	public function test_onboarding_step_finish_with_invalid_step() {
		// Arrange.
		$step_id      = 'invalid_step';
		$country_code = 'US';

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/finish' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test handling onboarding step clean.
	 */
	public function test_onboarding_step_clean() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$country_code = 'US';

		// The step is started.
		update_option(
			WooPaymentsService::NOX_PROFILE_OPTION_KEY,
			array(
				'onboarding' => array(
					$country_code => array(
						'steps' => array(
							$step_id => array(
								'statuses' => array(
									WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => 1234,
								),
							),
						),
					),
				),
			),
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/clean' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'previous_status', $data );
		$this->assertSame( WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED, $data['previous_status'] );
		$this->assertArrayHasKey( 'current_status', $data );
		$this->assertSame( WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED, $data['current_status'] );
	}

	/**
	 * Test handling onboarding step clean with invalid location.
	 *
	 * @dataProvider provider_invalid_location_provider
	 *
	 * @param string $location The location to test.
	 */
	public function test_onboarding_step_clean_with_invalid_location( string $location ) {
		// Arrange.
		$step_id = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/clean' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test handling onboarding step clean with invalid step.
	 */
	public function test_onboarding_step_clean_with_invalid_step() {
		// Arrange.
		$step_id      = 'invalid_step';
		$country_code = 'US';

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/clean' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test onboarding test account init.
	 */
	public function test_onboarding_test_account_init() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT;
		$country_code = 'US';
		$source       = 'test_source';

		// Arrange the WPCOM connection.
		// Make it connected to pass the step requirements.
		$this->mock_wpcom_connection_manager
			->expects( $this->atLeastOnce() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->atLeastOnce() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Intercept the request to our platform (proxied by the client).
		$requested_urls = array();
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( $url ) use ( &$requested_urls ) {
						$requested_urls[] = $url;

						return array( 'success' => true );
					},
				),
			)
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/init' );
		$request->set_param( 'location', $country_code );
		$request->set_param( 'source', $source );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertContains( '/wc/v3/payments/onboarding/test_drive_account/init', $requested_urls );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
	}

	/**
	 * Test onboarding test account init with invalid location.
	 *
	 * @dataProvider provider_invalid_location_provider
	 *
	 * @param string $location The location to test.
	 */
	public function test_onboarding_test_account_init_with_invalid_location( string $location ) {
		// Arrange.
		$step_id = WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT;

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/init' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test onboarding business verification step KYC session init.
	 */
	public function test_onboarding_business_verification_step_kyc_session_init() {
		// Arrange.
		$step_id         = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;
		$country_code    = 'US';
		$self_assessment = array(
			'some_data' => 'some_value',
		);
		$session_data    = array(
			'some_session_data' => 'some_session_value',
			'locale'            => 'en_US',
		);

		// Arrange the WPCOM connection.
		// Make it connected to pass the step requirements.
		$this->mock_wpcom_connection_manager
			->expects( $this->atLeastOnce() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->atLeastOnce() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Intercept the request to our platform (proxied by the client).
		$requested_urls = array();
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( $url ) use ( $session_data, &$requested_urls ) {
						$requested_urls[] = $url;

						return $session_data;
					},
				),
			)
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/kyc_session' );
		$request->set_param( 'location', $country_code );
		$request->set_param( 'self_assessment', $self_assessment );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertContains( '/wc/v3/payments/onboarding/kyc/session', $requested_urls );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'session', $data );
		$this->assertSame( $session_data, $data['session'] );
	}

	/**
	 * Test onboarding business verification step KYC session init with invalid location.
	 *
	 * @dataProvider provider_invalid_location_provider
	 *
	 * @param string $location The location to test.
	 */
	public function test_onboarding_business_verification_step_kyc_session_init_with_invalid_location( string $location ) {
		// Arrange.
		$step_id = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/kyc_session' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test onboarding business verification step KYC session finish.
	 */
	public function test_onboarding_business_verification_step_kyc_session_finish() {
		// Arrange.
		$step_id         = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;
		$country_code    = 'US';
		$source          = 'test_source';
		$finish_response = array(
			'some_data' => 'some_value',
		);

		// Arrange the WPCOM connection.
		// Make it connected to pass the step requirements.
		$this->mock_wpcom_connection_manager
			->expects( $this->atLeastOnce() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->atLeastOnce() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Intercept the request to our platform (proxied by the client).
		$requested_urls = array();
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( $url ) use ( $finish_response, &$requested_urls ) {
						$requested_urls[] = $url;

						return $finish_response;
					},
				),
			)
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/kyc_session/finish' );
		$request->set_param( 'location', $country_code );
		$request->set_param( 'source', $source );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertContains( '/wc/v3/payments/onboarding/kyc/finalize', $requested_urls );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		foreach ( $finish_response as $key => $value ) {
			$this->assertArrayHasKey( $key, $data );
			$this->assertSame( $value, $data[ $key ] );
		}
	}

	/**
	 * Test onboarding business verification step KYC session finish with invalid location.
	 *
	 * @dataProvider provider_invalid_location_provider
	 *
	 * @param string $location The location to test.
	 */
	public function test_onboarding_business_verification_step_kyc_session_finish_with_invalid_location( string $location ) {
		// Arrange.
		$step_id = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/kyc_session/finish' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( \WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * Test onboarding preload.
	 */
	public function test_handle_onboarding_preload() {
		// Arrange.
		$country_code = 'US';

		// Arrange the WPCOM connection.
		// Make it not connected.
		$this->mock_wpcom_connection_manager
			->expects( $this->atLeastOnce() )
			->method( 'is_connected' )
			->willReturn( false );
		$this->mock_wpcom_connection_manager
			->expects( $this->once() )
			->method( 'try_registration' )
			->willReturn( true );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/preload' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
	}

	/**
	 * Test onboarding reset.
	 */
	public function test_onboarding_reset() {
		// Arrange.
		$from         = 'test-from';
		$source       = 'test-source';
		$country_code = 'US';

		// Put some data in the option to reset.
		update_option(
			WooPaymentsService::NOX_PROFILE_OPTION_KEY,
			array(
				'onboarding' => array(
					$country_code => array(
						'steps' => array(
							WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
								'statuses' => array(
									WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => 1234,
								),
							),
						),
					),
				),
			),
		);

		// Intercept the request to our platform (proxied by the client).
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function () {
						return array( 'success' => true );
					},
				),
			)
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/reset' );
		$request->set_param( 'from', $from );
		$request->set_param( 'source', $source );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );

		// Assert that the option is reset.
		$this->assertFalse( get_option( WooPaymentsService::NOX_PROFILE_OPTION_KEY ) );
	}

	/**
	 * Test disable test account when a test account is in use.
	 */
	public function test_disable_test_account_with_test_account() {
		// Arrange.
		$location = 'US';
		$from     = 'test-from';
		$source   = 'test-source';

		// Arrange the WPCOM connection.
		// Make it connected to pass the step requirements.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the account.
		$this->mock_gateway->account_connected = true;
		$this->mock_account_service
			->expects( $this->any() )
			->method( 'is_stripe_account_valid' )
			->willReturn( true );
		$this->mock_account_service
			->expects( $this->any() )
			->method( 'get_account_status_data' )
			->willReturn(
				array(
					'status'           => 'complete',
					'testDrive'        => true,
					'isLive'           => false,
					'paymentsEnabled'  => true,
					'detailsSubmitted' => true,
				)
			);

		// Intercept the request to our platform (proxied by the client).
		$requested_urls = array();
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( $url ) use ( &$requested_urls ) {
						$requested_urls[] = $url;

						return array( 'success' => true );
					},
				),
			)
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/test_account/disable' );
		$request->set_param( 'location', $location );
		$request->set_param( 'from', $from );
		$request->set_param( 'source', $source );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertContains( '/wc/v3/payments/onboarding/test_drive_account/disable', $requested_urls );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );

		// Assert that the onboarding is unlocked.
		$this->assertSame( 0, (int) get_option( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY ) );

		// Assert the test account step status.
		$this->assertSame(
			WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
			$this->woopayments_provider_service->get_onboarding_step_status( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location )
		);
	}

	/**
	 * Test disable test account when a sandbox account is in use.
	 */
	public function test_disable_test_account_with_sandbox_account() {
		// Arrange.
		$location = 'US';
		$from     = 'test-from';
		$source   = 'test-source';

		// Arrange the WPCOM connection.
		// Make it connected to pass the step requirements.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the account.
		$this->mock_gateway->account_connected = true;
		$this->mock_account_service
			->expects( $this->any() )
			->method( 'is_stripe_account_valid' )
			->willReturn( true );
		$this->mock_account_service
			->expects( $this->any() )
			->method( 'get_account_status_data' )
			->willReturn(
				array(
					'status'           => 'complete',
					'testDrive'        => false,
					'isLive'           => false, // This is a sandbox account.
					'paymentsEnabled'  => true,
					'detailsSubmitted' => true,
				)
			);

		// Intercept the request to our platform (proxied by the client).
		$requested_urls = array();
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( $url ) use ( &$requested_urls ) {
						$requested_urls[] = $url;

						return array( 'success' => true );
					},
				),
			)
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/test_account/disable' );
		$request->set_param( 'location', $location );
		$request->set_param( 'from', $from );
		$request->set_param( 'source', $source );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertContains( '/wc/v3/payments/onboarding/reset', $requested_urls );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );

		// Assert that the onboarding is unlocked.
		$this->assertSame( 0, (int) get_option( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY ) );

		// Assert the test account step status.
		$this->assertSame(
			WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
			$this->woopayments_provider_service->get_onboarding_step_status( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location )
		);
	}

	/**
	 * Test disable test account when a live account is in use.
	 */
	public function test_disable_test_account_with_live_account() {
		// Arrange.
		$location = 'US';
		$from     = 'test-from';
		$source   = 'test-source';

		// Arrange the WPCOM connection.
		// Make it connected to pass the step requirements.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the account.
		$this->mock_gateway->account_connected = true;
		$this->mock_account_service
			->expects( $this->any() )
			->method( 'is_stripe_account_valid' )
			->willReturn( true );
		$this->mock_account_service
			->expects( $this->any() )
			->method( 'get_account_status_data' )
			->willReturn(
				array(
					'status'           => 'complete',
					'testDrive'        => false,
					'isLive'           => true,
					'paymentsEnabled'  => true,
					'detailsSubmitted' => true,
				)
			);

		// Intercept the request to our platform (proxied by the client).
		$requested_urls = array();
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( $url ) use ( &$requested_urls ) {
						$requested_urls[] = $url;

						return array( 'success' => true );
					},
				),
			)
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/test_account/disable' );
		$request->set_param( 'location', $location );
		$request->set_param( 'from', $from );
		$request->set_param( 'source', $source );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertCount( 0, $requested_urls );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );

		// Assert that the onboarding is unlocked.
		$this->assertSame( 0, (int) get_option( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY ) );

		// Assert the test account step status.
		$this->assertSame(
			WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
			$this->woopayments_provider_service->get_onboarding_step_status( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location )
		);
	}

	/**
	 * Provider for invalid location test cases.
	 *
	 * @return array[]
	 */
	public function provider_invalid_location_provider(): array {
		return array(
			'empty'       => array( '' ),
			'single_char' => array( 'U' ),
			'number'      => array( '12' ),
			'long_string' => array( 'USA' ),
		);
	}

	/**
	 * Mock the WC payment gateways.
	 */
	protected function mock_payment_gateways() {
		// Hook into the payment gateways initialization to mock the gateways.
		add_action( 'wc_payment_gateways_initialized', $this->gateways_mock_ref, 100 );
		// Reinitialize the WC gateways.
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();

		$this->providers_service->reset_memo();
	}

	/**
	 * Unmock the WC payment gateways.
	 */
	private function unmock_payment_gateways() {
		if ( null !== $this->gateways_mock_ref ) {
			remove_action( 'wc_payment_gateways_initialized', $this->gateways_mock_ref, 100 );
		}
		// Reinitialize the WC gateways.
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();

		if ( isset( $this->providers_service ) ) {
			$this->providers_service->reset_memo();
		}
	}

	/**
	 * Get the mock WooPayments supported countries.
	 *
	 * @return string[]
	 */
	private function get_woopayments_supported_countries(): array {
		return array(
			'AE' => 'United Arab Emirates',
			'AT' => 'Austria',
			'AU' => 'Australia',
			'BE' => 'Belgium',
			'BG' => 'Bulgaria',
			'CA' => 'Canada',
			'CH' => 'Switzerland',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DE' => 'Germany',
			'DK' => 'Denmark',
			'EE' => 'Estonia',
			'FI' => 'Finland',
			'ES' => 'Spain',
			'FR' => 'France',
			'HR' => 'Croatia',
			'JP' => 'Japan',
			'LU' => 'Luxembourg',
			'GB' => 'United Kingdom (UK)',
			'GR' => 'Greece',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IE' => 'Ireland',
			'IT' => 'Italy',
			'LT' => 'Lithuania',
			'LV' => 'Latvia',
			'MT' => 'Malta',
			'NL' => 'Netherlands',
			'NO' => 'Norway',
			'NZ' => 'New Zealand',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'RO' => 'Romania',
			'SE' => 'Sweden',
			'SI' => 'Slovenia',
			'SK' => 'Slovakia',
			'SG' => 'Singapore',
			'US' => 'United States (US)',
			'PR' => 'Puerto Rico',
		);
	}
}
