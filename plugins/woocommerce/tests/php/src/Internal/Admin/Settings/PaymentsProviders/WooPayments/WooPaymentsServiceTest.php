<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders\WooPayments;

use Automattic\Jetpack\Connection\Manager as WPCOM_Connection_Manager;
use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiException;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\PaymentGateway;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * WooPayments settings provider service test.
 *
 * @class WooPaymentsService
 */
class WooPaymentsServiceTest extends WC_Unit_Test_Case {

	/**
	 * @var WooPaymentsService
	 */
	protected WooPaymentsService $sut;

	/**
	 * @var PaymentsProviders|MockObject
	 */
	protected $mock_providers;

	/**
	 * @var PaymentGateway|MockObject
	 */
	protected $mock_provider;

	/**
	 * @var MockableLegacyProxy|MockObject
	 */
	protected $mockable_proxy;

	/**
	 * @var WPCOM_Connection_Manager|MockObject
	 */
	protected $mock_wpcom_connection_manager;

	/**
	 * @var MockObject
	 */
	protected $mock_account_service;

	/**
	 * The ID of the store admin user.
	 *
	 * @var int
	 */
	protected $store_admin_id;

	/**
	 * The current time in seconds.
	 *
	 * Use it instead of time() to avoid using the real time in tests.
	 *
	 * @var int
	 */
	protected int $current_time;

	private const TEST_EPOCH = 1234567890;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->store_admin_id );

		$this->current_time = self::TEST_EPOCH;

		$this->mock_providers = $this->getMockBuilder( PaymentsProviders::class )
									->disableOriginalConstructor()
									->onlyMethods(
										array(
											'get_payment_gateway_provider_instance',
										)
									)
									->getMock();

		$this->mock_provider = $this->getMockBuilder( PaymentGateway::class )
									->disableOriginalConstructor()
									->getMock();

		$this->mock_providers
			->expects( $this->any() )
			->method( 'get_payment_gateway_provider_instance' )
			->willReturn( $this->mock_provider );

		$this->mock_wpcom_connection_manager = $this->getMockBuilder( WPCOM_Connection_Manager::class )
			->disableOriginalConstructor()
			->onlyMethods(
				array(
					'is_connected',
					'has_connected_owner',
					'is_connection_owner',
					'try_registration',
				)
			)
			->getMock();

		$this->mockable_proxy = wc_get_container()->get( LegacyProxy::class );

		$this->mockable_proxy->register_class_mocks(
			array(
				WPCOM_Connection_Manager::class => $this->mock_wpcom_connection_manager,
			)
		);
		$this->mockable_proxy->register_function_mocks(
			array(
				// Mock the current time.
				'time'         => function () {
					return $this->current_time;
				},
				// Everything is callable by default.
				'is_callable'  => function () {
					return true;
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

		// Arrange the version constant to meet the minimum requirements for the native in-context onboarding.
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', WooPaymentsService::EXTENSION_MINIMUM_VERSION );

		$this->mock_account_service = $this->getMockBuilder( \stdClass::class )
			->addMethods( array( 'is_stripe_account_valid', 'get_account_status_data' ) )
			->getMock();

		$this->mockable_proxy->register_static_mocks(
			array(
				'\WC_Payments'         => array(
					'get_gateway'         => function () {
						return new FakePaymentGateway();
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
					// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
					'wc_payments_settings_url'           => function ( ?string $path = null, array $query = array() ) {
						unset( $path, $query );
						return 'https://example.com/payments-settings';
					},
					// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
					'get_wpcom_connection_authorization' => function ( string $return_url ) {
						unset( $return_url );
						return array(
							'success'      => true,
							'errors'       => array(),
							'color_scheme' => 'fresh',
							'url'          => 'https://wordpress.com/auth?query=some_query',
						);
					},
					// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
					'rest_endpoint_get_request'          => function ( string $endpoint, array $params = array() ) {
						unset( $params );
						if ( '/wc/v3/payments/onboarding/fields' === $endpoint ) {
							return array(
								'data' => array(),
							);
						}

						throw new \Exception( esc_html( 'GET endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			),
		);

		$this->sut = new WooPaymentsService();
		$this->sut->init( $this->mock_providers, $this->mockable_proxy );
	}

	/**
	 * Test get onboarding details when the extension is NOT active.
	 */
	public function test_get_onboarding_details_throws_when_extension_not_active(): void {
		$location = 'US';

		// Arrange.
		// Mock the extension as not active.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_to_check ) {
					if ( '\WC_Payments' === $class_to_check ) {
						return false;
					}

					return true;
				},
			)
		);

		// Act.
		try {
			$this->sut->get_onboarding_details( $location, '/some/path' );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertSame( 'woocommerce_woopayments_onboarding_extension_not_active', $e->getErrorCode() );
		}
	}

	/**
	 * Test get onboarding details when the extension is NOT at the right version.
	 */
	public function test_get_onboarding_details_throws_when_extension_at_wrong_version(): void {
		// Arrange.
		$location = 'US';

		// Arrange the version constant to NOT meet the minimum requirements for the native in-context onboarding.
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', '1.0.0' );

		// Act.
		try {
			$this->sut->get_onboarding_details( $location, '/some/path' );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertSame( 'woocommerce_woopayments_onboarding_extension_version', $e->getErrorCode() );
		}
	}

	/**
	 * Test get onboarding details when the extension is active but the onboarding is locked.
	 *
	 * @return void
	 * @throws \Exception If a request is not mocked.
	 */
	public function test_get_onboarding_details_throws_with_onboarding_locked(): void {
		$location = 'US';

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						// Return a current timestamp to simulate locked state.
						return $this->current_time;
					}

					return $default_value;
				},
			)
		);

		// Act.
		try {
			$this->sut->get_onboarding_details( $location, '/some/path' );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertSame( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}
	}

	/**
	 * Test get onboarding details works when onboarding lock has expired.
	 */
	public function test_get_onboarding_details_with_expired_onboarding_lock(): void {
		$location = 'US';

		// Arrange an expired onboarding lock (older than TTL).
		$expired_timestamp = $this->current_time - WooPaymentsService::NOX_ONBOARDING_LOCKED_TTL_SECONDS - 10;
		$clears            = 0;
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $expired_timestamp ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return $expired_timestamp;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( &$clears ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name && 0 === $value ) {
						$clears++;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->get_onboarding_details( $location, '/some/path' );

		// Assert that the method works (doesn't throw exception) because the lock is expired.
		$this->assertIsArray( $result );
		$this->assertSame( 1, $clears, 'Expired onboarding lock should be cleared (self-healed).' );
	}

	/**
	 * Test get onboarding details with non-numeric lock value.
	 */
	public function test_get_onboarding_details_with_invalid_lock_value(): void {
		$location = 'US';

		// Arrange an invalid (non-numeric) onboarding lock value.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return 'invalid_value';
					}

					return $default_value;
				},
			)
		);

		// Act.
		$result = $this->sut->get_onboarding_details( $location, '/some/path' );

		// Assert that the method works (doesn't throw exception) because invalid lock is treated as unlocked.
		$this->assertIsArray( $result );
	}

	/**
	 * Test get onboarding details with lock at the TTL boundary.
	 */
	public function test_get_onboarding_details_with_lock_at_ttl_boundary(): void {
		$location    = 'US';
		$boundary_ts = $this->current_time - WooPaymentsService::NOX_ONBOARDING_LOCKED_TTL_SECONDS;

		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) use ( $boundary_ts ) {
					return WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ? $boundary_ts : $default_value;
				},
			)
		);

		try {
			$this->sut->get_onboarding_details( $location, '/some/path' );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertSame( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}
	}

	/**
	 * Test get onboarding details with a future lock timestamp.
	 */
	public function test_get_onboarding_details_with_future_lock_timestamp(): void {
		$location  = 'US';
		$future_ts = $this->current_time + 10;

		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) use ( $future_ts ) {
					return WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ? $future_ts : $default_value;
				},
			)
		);

		try {
			$this->sut->get_onboarding_details( $location, '/some/path' );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertSame( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}
	}

	/**
	 * Test get onboarding details with zero string lock value.
	 */
	public function test_get_onboarding_details_with_zero_string_lock_value(): void {
		$location = 'US';

		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					return WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ? '0' : $default_value;
				},
			)
		);

		$result = $this->sut->get_onboarding_details( $location, '/some/path' );

		$this->assertIsArray( $result );
	}

	/**
	 * Test get onboarding details with legacy `no` string lock value.
	 *
	 * In this case, the onboarding is considered unlocked.
	 */
	public function test_get_onboarding_details_with_no_string_lock_value(): void {
		$location = 'US';

		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					return WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ? 'no' : $default_value;
				},
			)
		);

		$result = $this->sut->get_onboarding_details( $location, '/some/path' );

		$this->assertIsArray( $result );
	}

	/**
	 * Test get onboarding details with legacy `yes` string lock value.
	 *
	 * In this case, the onboarding is considered unlocked.
	 */
	public function test_get_onboarding_details_with_yes_string_lock_value(): void {
		$location = 'US';

		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					return WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ? 'yes' : $default_value;
				},
			)
		);

		$result = $this->sut->get_onboarding_details( $location, '/some/path' );

		$this->assertIsArray( $result );
	}

	/**
	 * Test get onboarding details - general state.
	 */
	public function test_get_onboarding_details_general_state(): void {
		$location = 'US';

		// Arrange.
		$expected_state = array(
			'supported' => true,
			'started'   => true,
			'completed' => false,
			'test_mode' => true,
			'dev_mode'  => false,
		);
		$this->mock_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_onboarding_supported' )
			->with( $this->anything(), $location )
			->willReturn( $expected_state['supported'] );
		$this->mock_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_onboarding_started' )
			->willReturn( $expected_state['started'] );
		$this->mock_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_onboarding_completed' )
			->willReturn( $expected_state['completed'] );
		$this->mock_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_in_test_mode_onboarding' )
			->willReturn( $expected_state['test_mode'] );
		$this->mock_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_in_dev_mode' )
			->willReturn( $expected_state['dev_mode'] );
		$this->mock_provider
			->expects( $this->never() )
			->method( 'get_onboarding_not_supported_message' );

		// Act.
		$result = $this->sut->get_onboarding_details( $location, '/some/path' );

		// Assert.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'state', $result );
		$this->assertSame( $expected_state, $result['state'] );
		$this->assertArrayHasKey( 'messages', $result );
		$this->assertArrayHasKey( 'not_supported', $result['messages'] );
		$this->assertNull( $result['messages']['not_supported'] );
		$this->assertArrayHasKey( 'context', $result );
		$this->assertSame(
			array(
				'urls' => array(
					'overview_page' => 'https://example.com/overview_page?from=' . WooPaymentsService::FROM_NOX_IN_CONTEXT,
				),
			),
			$result['context']
		);
	}

	/**
	 * Test get onboarding details - general state when not supported.
	 */
	public function test_get_onboarding_details_general_state_not_supported(): void {
		$location                       = 'XX';
		$expected_not_supported_message = 'WooPayments is not supported in this location.';

		// Arrange.
		$expected_state = array(
			'supported' => false,
			'started'   => false,
			'completed' => false,
			'test_mode' => false,
			'dev_mode'  => false,
		);
		$this->mock_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_onboarding_supported' )
			->with( $this->anything(), $location )
			->willReturn( $expected_state['supported'] );
		$this->mock_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_onboarding_started' )
			->willReturn( $expected_state['started'] );
		$this->mock_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_onboarding_completed' )
			->willReturn( $expected_state['completed'] );
		$this->mock_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_in_test_mode_onboarding' )
			->willReturn( $expected_state['test_mode'] );
		$this->mock_provider
			->expects( $this->atLeastOnce() )
			->method( 'is_in_dev_mode' )
			->willReturn( $expected_state['dev_mode'] );
		$this->mock_provider
			->expects( $this->once() )
			->method( 'get_onboarding_not_supported_message' )
			->willReturn( $expected_not_supported_message );

		// Act.
		$result = $this->sut->get_onboarding_details( $location, '/some/path' );

		// Assert.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'state', $result );
		$this->assertSame( $expected_state, $result['state'] );
		$this->assertArrayHasKey( 'messages', $result );
		$this->assertSame(
			array(
				'not_supported' => $expected_not_supported_message,
			),
			$result['messages']
		);
		$this->assertArrayHasKey( 'context', $result );
		$this->assertSame(
			array(
				'urls' => array(
					'overview_page' => 'https://example.com/overview_page?from=' . WooPaymentsService::FROM_NOX_IN_CONTEXT,
				),
			),
			$result['context']
		);
	}

	/**
	 * Test get onboarding details - steps.
	 *
	 * @dataProvider provider_get_onboarding_details_steps
	 *
	 * @param array $expected_step_statuses          The expected step statuses.
	 * @param array $steps_stored_profile            The data stored in the profile for the steps.
	 * @param array $recommended_pms                 The recommended payment methods.
	 * @param array $expected_pms_state              The expected payment methods state.
	 * @param array $wpcom_connection                The WPCOM connection state.
	 * @param array $expected_wpcom_connection_state The expected WPCOM connection state.
	 * @param array $account_state                   The account state.
	 *
	 * @return void
	 * @throws \Exception If a request is not mocked.
	 */
	public function test_get_onboarding_details_steps(
		array $expected_step_statuses,
		array $steps_stored_profile,
		array $recommended_pms,
		array $expected_pms_state,
		array $wpcom_connection,
		array $expected_wpcom_connection_state,
		array $account_state
	): void {
		$location = 'US';

		// Arrange.
		$rest_path        = '/rest/path/to/onboarding/';
		$kyc_fallback_url = 'https://example.com/kyc_fallback';

		$wpcom_connection_return_url = 'https://example.com/payments-settings/return?wpcom_connection_return=1';

		$stored_profile          = array(
			'onboarding' => array(
				$location => array(
					'steps' => $steps_stored_profile,
				),
			),
		);
		$updated_stored_profiles = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile, &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						// Chain the responses to simulate the sequence of DB updates.
						return ! empty( $updated_stored_profiles ) ? end( $updated_stored_profiles ) : $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profiles[] = $value;

						// Mimic the behavior of the original function.
						$previous = empty( $updated_stored_profiles ) ? $stored_profile : end( $updated_stored_profiles );
						if ( $value === $previous || maybe_serialize( $value ) === maybe_serialize( $previous ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		$expected_steps = array(
			// The payment methods step.
			array(
				'id'             => WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
				'path'           => trailingslashit( WooPaymentsService::ONBOARDING_PATH_BASE ) . WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
				'required_steps' => array(),
				'status'         => $expected_step_statuses[ WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS ] ?? WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				'errors'         => array(),
				'actions'        => array(
					'start'  => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS . '/start' ),
					),
					'save'   => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS . '/save' ),
					),
					'finish' => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS . '/finish' ),
					),
					'check'  => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS . '/check' ),
					),
					'clean'  => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS . '/clean' ),
					),
				),
				'context'        => array(
					'recommended_pms' => $recommended_pms,
					'pms_state'       => $expected_pms_state,
				),
			),
			// The WPCOM connection step.
			array(
				'id'             => WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				'path'           => trailingslashit( WooPaymentsService::ONBOARDING_PATH_BASE ) . WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				'required_steps' => array(),
				'status'         => $expected_step_statuses[ WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION ] ?? WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				'errors'         => array(),
				'context'        => array(
					'connection_state' => $expected_wpcom_connection_state,
				),
				'actions'        => array(
					'start' => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION . '/start' ),
					),
					'auth'  => array(
						'type' => WooPaymentsService::ACTION_TYPE_REDIRECT,
						'href' => 'https://wordpress.com/auth?query=some_query',
					),
					'check' => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION . '/check' ),
					),
					'clean' => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION . '/clean' ),
					),
				),
			),
			// The test account step.
			array(
				'id'             => WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				'path'           => trailingslashit( WooPaymentsService::ONBOARDING_PATH_BASE ) . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				'required_steps' => array( WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION ),
				'status'         => $expected_step_statuses[ WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT ] ?? WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				'errors'         => array(),
				'context'        => array(),
				'actions'        => array(
					'start'  => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/start' ),
					),
					'init'   => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/init' ),
					),
					'finish' => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/finish' ),
					),
					'check'  => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/check' ),
					),
					'clean'  => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/clean' ),
					),
					'reset'  => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/reset' ),
					),
				),
			),
			// The business verification step.
			array(
				'id'             => WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				'path'           => trailingslashit( WooPaymentsService::ONBOARDING_PATH_BASE ) . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				'required_steps' => array( WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION ),
				'status'         => $expected_step_statuses[ WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION ] ?? WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				'errors'         => array(),
				'context'        => array(
					// Only with a working WPCOM connection do we include the fields.
					'fields'              => ( $wpcom_connection['is_store_connected'] && $wpcom_connection['has_connected_owner'] ) ? array(
						'business_types'      => $this->get_mock_onboarding_fields_business_types(),
						'mccs_display_tree'   => array(
							array(
								'id'    => 'most_popular',
								'type'  => 'group',
								'title' => 'Most popular',
								'items' => array(
									array(
										'id'       => 'most_popular__software_services',
										'type'     => 'mcc',
										'title'    => 'Software',
										'mcc'      => 7372,
										'keywords' => array(),
									),
									array(
										'id'       => 'most_popular__clothing_and_apparel',
										'type'     => 'mcc',
										'title'    => 'Clothing and accessories',
										'mcc'      => 5699,
										'keywords' => array(),
									),
								),
							),
							array(
								'id'    => 'food_and_drink',
								'type'  => 'group',
								'title' => 'Food and drink',
								'items' => array(
									array(
										'id'       => 'food_and_drink__other_food_and_dining',
										'type'     => 'mcc',
										'title'    => 'Other food and dining',
										'mcc'      => 5819,
										'keywords' => array(),
									),
								),
							),
							array(
								'id'    => 'home_furnishings_and_furniture',
								'type'  => 'group',
								'title' => 'Home, furniture and garden',
								'items' => array(
									array(
										'id'       => 'home_furnishings_and_furniture__other_home_furnishings_and_furniture',
										'type'     => 'mcc',
										'title'    => 'Other home furnishings and furniture',
										'mcc'      => 5719,
										'keywords' => array(),
									),
								),
							),
						),
						'industry_to_mcc'     => array(
							'clothing_and_accessories'  => 'retail__clothing_and_apparel',
							'health_and_beauty'         => 'retail__beauty_products',
							'food_and_drink'            => 'food_and_drink__other_food_and_dining',
							'home_furniture_and_garden' => 'retail__home_furnishings_and_furniture',
							'education_and_learning'    => 'education__other_educational_services',
							'electronics_and_computers' => 'digital_products__other_digital_goods',
						),
						'available_countries' => $this->get_woopayments_supported_countries(),
						'location'            => $location,
					) : array(),
					'sub_steps'           => $steps_stored_profile[ WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION ]['sub_steps'] ?? array(),
					'self_assessment'     => $steps_stored_profile[ WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION ]['self_assessment'] ?? array(),
					'has_test_account'    => $account_state['has_account'] && $account_state['test_account'],
					'has_sandbox_account' => $account_state['has_account'] && $account_state['sandbox_account'],
				),
				'actions'        => array(
					'start'                => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/start' ),
					),
					'save'                 => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/save' ),
					),
					'kyc_session'          => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/kyc_session' ),
					),
					'kyc_session_finish'   => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/kyc_session/finish' ),
					),
					'kyc_fallback'         => array(
						'type' => WooPaymentsService::ACTION_TYPE_REDIRECT,
						'href' => $kyc_fallback_url,
					),
					'finish'               => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/finish' ),
					),
					'check'                => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/check' ),
					),
					'clean'                => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/clean' ),
					),
					'test_account_disable' => array(
						'type' => WooPaymentsService::ACTION_TYPE_REST,
						'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/test_account/disable' ),
					),
				),
			),
		);

		// Arrange the payment methods step.
		$this->mock_provider
			->expects( $this->atLeastOnce() )
			->method( 'get_recommended_payment_methods' )
			->willReturn( $recommended_pms );

		// Arrange the WPCOM connection.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( $wpcom_connection['is_store_connected'] );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( $wpcom_connection['has_connected_owner'] );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connection_owner' )
			->willReturn( $wpcom_connection['is_connection_owner'] );
		// If the status is completed, we expect only the general actions.
		if ( WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED === $expected_step_statuses[ WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION ] ) {
			$expected_steps[1]['actions'] = array(
				'check' => array(
					'type' => WooPaymentsService::ACTION_TYPE_REST,
					'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION . '/check' ),
				),
				'clean' => array(
					'type' => WooPaymentsService::ACTION_TYPE_REST,
					'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION . '/clean' ),
				),
			);
		}

		// Arrange the account.
		$this->mock_provider
			->expects( $this->any() )
			->method( 'is_account_connected' )
			->willReturn( $account_state['has_account'] );

		$this->mock_account_service
			->expects( $this->any() )
			->method( 'is_stripe_account_valid' )
			->willReturn( $account_state['has_valid_account'] );
		$this->mock_account_service
			->expects( $this->any() )
			->method( 'get_account_status_data' )
			->willReturn(
				array(
					'status'           => 'complete',
					'testDrive'        => $account_state['test_account'] ?? false,
					'isLive'           => ! ( ( $account_state['test_account'] ?? false ) || ( $account_state['sandbox_account'] ?? false ) ),
					'paymentsEnabled'  => true,
					'detailsSubmitted' => true,
				)
			);

		// Arrange the test account step.
		// If the status is completed, we expect only the general actions.
		if ( WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED === $expected_step_statuses[ WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT ] ) {
			$expected_steps[2]['actions'] = array(
				'check' => array(
					'type' => WooPaymentsService::ACTION_TYPE_REST,
					'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/check' ),
				),
				'clean' => array(
					'type' => WooPaymentsService::ACTION_TYPE_REST,
					'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/clean' ),
				),
				'reset' => array(
					'type' => WooPaymentsService::ACTION_TYPE_REST,
					'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/reset' ),
				),
			);
		}

		// Arrange the business verification step.
		// If the status is completed, we expect only the general actions.
		if ( WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED === $expected_step_statuses[ WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION ] ) {
			$expected_steps[3]['actions'] = array(
				'check' => array(
					'type' => WooPaymentsService::ACTION_TYPE_REST,
					'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/check' ),
				),
				'clean' => array(
					'type' => WooPaymentsService::ACTION_TYPE_REST,
					'href' => rest_url( $rest_path . 'step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/clean' ),
				),
			);
		}

		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class           => array(
					'wc_payments_settings_url'           => function ( ?string $path = null, array $query = array() ) use ( $wpcom_connection_return_url ) {
						if ( WooPaymentsService::ONBOARDING_PATH_BASE === $path && ! empty( $query['wpcom_connection_return'] ) ) {
							return $wpcom_connection_return_url;
						}

						return 'https://example.com/payments-settings';
					},
					'get_wpcom_connection_authorization' => function ( string $return_url ) use ( $expected_steps, $wpcom_connection_return_url ) {
						$result = array(
							'success'      => true,
							'errors'       => array(),
							'color_scheme' => 'fresh',
							'url'          => 'https://wordpress.com/auth?query=some_query',
						);

						if ( $wpcom_connection_return_url === $return_url && ! empty( $expected_steps[1]['actions']['auth']['href'] ) ) {
							$result['url'] = $expected_steps[1]['actions']['auth']['href'];
						}

						return $result;
					},
					'rest_endpoint_get_request'          => function ( string $endpoint ) use ( $expected_steps ) {
						if ( '/wc/v3/payments/onboarding/fields' === $endpoint ) {
							return array(
								'data' => $expected_steps[3]['context']['fields'],
							);
						}

						throw new \Exception( esc_html( 'GET endpoint response is not mocked: ' . $endpoint ) );
					},
				),
				'\WC_Payments_Account' => array(
					'get_connect_url' => function () use ( $kyc_fallback_url ) {
						return $kyc_fallback_url;
					},
				),
			)
		);

		// Act.
		$result = $this->sut->get_onboarding_details( $location, $rest_path );

		// Assert.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'steps', $result );
		$this->assertCount( count( $expected_steps ), $result['steps'] );
		$this->assertEquals( $expected_steps, $result['steps'] );
	}

	/**
	 * Data provider for test_get_onboarding_details_steps.
	 *
	 * @return array[]
	 */
	public function provider_get_onboarding_details_steps(): array {
		// Can't use the $this->current_time because providers are run before setUp.
		// Use the same value as in setUp().
		$current_time = self::TEST_EPOCH;

		$default_recommended_pms = array(
			array(
				'id'       => 'card',
				'enabled'  => false,
				'required' => true,
			),
			array(
				'id'       => 'apple_pay',
				'enabled'  => true,
				'required' => false,
			),
			array(
				'id'       => 'google_pay',
				'enabled'  => false,
				'required' => false,
			),
		);
		$expected_pms_state      = array(
			'card'         => true, // Force enabled because it is required.
			'apple_google' => true,
		);

		$default_wpcom_connection        = array(
			'is_store_connected'  => false,
			'has_connected_owner' => false,
			'is_connection_owner' => false,
		);
		$expected_wpcom_connection_state = array(
			'has_working_connection' => false,
			'is_store_connected'     => false,
			'has_connected_owner'    => false,
			'is_connection_owner'    => false,
		);

		$default_account_state = array(
			'has_account'       => false,
			'has_valid_account' => false,
			'test_account'      => false,
			'sandbox_account'   => false,
		);

		return array(
			'clean slate'             => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				),
				// No profile steps stored details.
				array(),
				$default_recommended_pms,
				$expected_pms_state,
				$default_wpcom_connection,
				$expected_wpcom_connection_state,
				$default_account_state,
			),
			'stored statuses (started) - no WPCOM connection, no account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				$default_wpcom_connection,
				$expected_wpcom_connection_state,
				$default_account_state,
			),
			'stored statuses (completed) - no WPCOM connection, no account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					// The completed stored status is ignored due to missing dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					// The completed stored status is ignored due to missing dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				$default_wpcom_connection,
				$expected_wpcom_connection_state,
				$default_account_state,
			),
			'stored statuses (failed) - no WPCOM connection, no account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
					// The failed stored status is ignored due to missing dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					// The failed stored status is ignored due to missing dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED  => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				$default_wpcom_connection,
				$expected_wpcom_connection_state,
				$default_account_state,
			),
			'stored statuses (failed) - working WPCOM connection, no account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED  => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				$default_account_state,
			),
			'stored statuses (failed) - working WPCOM connection, test account' => array(
				array(
					// The PMs step is force-completed on valid accounts.
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED  => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => true,
					)
				),
			),
			'stored statuses (failed) - working WPCOM connection, live account' => array(
				array(
					// The PMs step is force-completed on valid accounts.
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED  => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => false,
					)
				),
			),
			'stored statuses (blocked) - no WPCOM connection, no account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
					// The blocked stored status is ignored due to missing dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					// The blocked stored status is ignored due to missing dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED  => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				$default_wpcom_connection,
				$expected_wpcom_connection_state,
				$default_account_state,
			),
			'stored statuses (blocked) - working WPCOM connection, no account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED  => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				$default_account_state,
			),
			'stored statuses (blocked) - working WPCOM connection, test account' => array(
				array(
					// The PMs step is force-completed on valid accounts.
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS       => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION      => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT          => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED  => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => true,
					)
				),
			),
			'stored statuses (blocked) - working WPCOM connection, live account' => array(
				array(
					// The PMs step is force-completed on valid accounts.
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED  => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => false,
					)
				),
			),
			'stored statuses (completed) - no WPCOM connection, test account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					// The completed stored status is ignored due to unmet dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					// The completed stored status is ignored due to unmet dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				$default_wpcom_connection,
				$expected_wpcom_connection_state,
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => true,
					)
				),
			),
			'stored statuses (completed) - no WPCOM connection, live account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					// The completed stored status is ignored due to unmet dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					// The completed stored status is ignored due to unmet dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				$default_wpcom_connection,
				$expected_wpcom_connection_state,
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => false,
					)
				),
			),
			'stored statuses (completed with failed) - no WPCOM connection, test account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
					// The completed and failed stored statuses are ignored due to unmet dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					// The completed and failed stored statuses are ignored due to unmet dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				$default_wpcom_connection,
				$expected_wpcom_connection_state,
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => true,
					)
				),
			),
			'stored statuses (completed with blocked) - no WPCOM connection, live account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
					// The completed and blocked stored statuses are ignored due to unmet dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					// The completed stored and blocked statuses are ignored due to unmet dependency (completed WPCOM connection).
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				$default_wpcom_connection,
				$expected_wpcom_connection_state,
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => false,
					)
				),
			),
			'stored statuses (mixed)' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					// Forced due to the bad WPCOM connection status (requirements).
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					// Forced due to the bad WPCOM connection status (requirements).
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
						),
					),
					// Nothing about the WPCOM connection.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION       => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				$default_wpcom_connection,
				$expected_wpcom_connection_state,
				$default_account_state,
			),
			'no stored statuses - WPCOM connection: store_connected, no connected owner' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				),
				array(),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge( $default_wpcom_connection, array( 'is_store_connected' => true ) ),
				array_merge( $expected_wpcom_connection_state, array( 'is_store_connected' => true ) ),
				$default_account_state,
			),
			'no stored statuses - WPCOM connection: store_connected, connected owner' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					// A working connection with auto-complete the step if no declarative status was stored.
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				),
				array(),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				$default_account_state,
			),
			'stored statuses ignored - WPCOM connection: store connected, connected owner' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // A working connection will overwrite the stored status.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				$default_account_state,
			),
			'stored statuses respected - WPCOM connection missing' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED, // The stored status is respected.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				$default_wpcom_connection,
				$expected_wpcom_connection_state,
				$default_account_state,
			),
			'stored statuses ignored - WPCOM connection broken' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED, // The stored status is ignored.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => false,
					)
				),
				array(
					'has_working_connection' => false,
					'is_store_connected'     => true,
					'has_connected_owner'    => false,
					'is_connection_owner'    => false,
				),
				$default_account_state,
			),
			'stored statuses ignored - Test account completed with requirements met' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // Since we have an account, this step is completed.
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // The connection is required to be completed.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // The stored status is ignored.
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT          => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => true,
					)
				),
			),
			'stored statuses ignored - Test account not completed due to unmet requirements' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // Since we have an account, this step is completed.
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					// The completed stored status is ignored due to unmet requirements.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => false,
					)
				),
				array(
					'has_working_connection' => false,
					'is_store_connected'     => true,
					'has_connected_owner'    => false,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => true,
					)
				),
			),
			'stored statuses respected - Test account started with no account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // The connection is required to be completed.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED, // The stored status is respected.
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => false,
						'has_valid_account' => false,
						'test_account'      => false,
					)
				),
			),
			'stored statuses respected - Test account step with live, invalid account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // The connection is required to be completed.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // The stored status is respected.
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => false,
						'test_account'      => false,
					)
				),
			),
			'stored statuses respected - Test account with live, valid account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // Since we have an account, this step is completed.
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // The connection is required to be completed.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // The stored status is respected.
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => false,
					)
				),
			),
			'stored statuses ignored - Business verification completed with requirements met' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // Since we have an account, this step is completed.
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // The connection is required to be completed.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => false,
					)
				),
			),
			'stored statuses ignored - Business verification not completed due to unmet requirements' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // Since we have an account, this step is completed.
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED, // The connection is required to be completed.
					// The completed stored status is ignored due to unmet requirements.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
					// The completed stored status is ignored due to unmet requirements.
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => false,
					)
				),
				array(
					'has_working_connection' => false,
					'is_store_connected'     => true,
					'has_connected_owner'    => false,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => false,
					)
				),
			),
			'stored statuses respected - Business verification started with no account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // The connection is required to be completed.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED, // The stored status is respected.
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => false,
						'has_valid_account' => false,
						'test_account'      => false,
					)
				),
			),
			'stored statuses ignored - Business verification with live, valid account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // Since we have an account, this step is completed.
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION   => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // The connection is required to be completed.
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT       => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // The stored status is ignored.
				),
				array(
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
							WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
						),
					),
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION   => array(
						'statuses' => array(
							WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time,
						),
					),
				),
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => false,
					)
				),
			),
			'no stored statuses - working WPCOM connection, sandbox account' => array(
				array(
					WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS       => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION      => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT          => WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
					WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				),
				array(), // no stored profile steps.
				$default_recommended_pms,
				$expected_pms_state,
				array_merge(
					$default_wpcom_connection,
					array(
						'is_store_connected'  => true,
						'has_connected_owner' => true,
					)
				),
				array(
					'has_working_connection' => true,
					'is_store_connected'     => true,
					'has_connected_owner'    => true,
					'is_connection_owner'    => false,
				),
				array_merge(
					$default_account_state,
					array(
						'has_account'       => true,
						'has_valid_account' => true,
						'test_account'      => false,
						'sandbox_account'   => true,
					)
				),
			),
		);
	}

	/**
	 * Test is_valid_onboarding_step_id.
	 *
	 * @return void
	 */
	public function test_is_valid_onboarding_step_id() {
		$valid_steps = array(
			WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
			WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
			WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
			WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
		);

		foreach ( $valid_steps as $step ) {
			$this->assertTrue( $this->sut->is_valid_onboarding_step_id( $step ) );
		}

		$this->assertFalse( $this->sut->is_valid_onboarding_step_id( 'invalid_step' ) );
	}

	/**
	 * Test get_onboarding_step_status.
	 *
	 * @dataProvider provider_get_onboarding_step_status
	 *
	 * @param string $step_id              The step ID.
	 * @param string $expected_status      The expected status.
	 * @param array  $step_stored_statuses The stored statuses for the step.
	 * @param array  $wpcom_connection     The WPCOM connection state.
	 * @param array  $account_state        The account state.
	 *
	 * @return void
	 * @throws \Exception On invalid mocking.
	 */
	public function test_get_onboarding_step_status( string $step_id, string $expected_status, array $step_stored_statuses, array $wpcom_connection, array $account_state ) {
		$location = 'US';

		// Arrange.
		$stored_profile          = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => $step_stored_statuses,
						),
					),
				),
			),
		);
		$updated_stored_profiles = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile, &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						// Chain the responses to simulate the sequence of DB updates.
						return ! empty( $updated_stored_profiles ) ? end( $updated_stored_profiles ) : $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profiles[] = $value;

						// Mimic the behavior of the original function.
						$previous = empty( $updated_stored_profiles ) ? $stored_profile : end( $updated_stored_profiles );
						if ( $value === $previous || maybe_serialize( $value ) === maybe_serialize( $previous ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Arrange the WPCOM connection.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( $wpcom_connection['is_store_connected'] );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( $wpcom_connection['has_connected_owner'] );

		// Arrange the account.
		$this->mock_provider
			->expects( $this->any() )
			->method( 'is_account_connected' )
			->willReturn( $account_state['has_account'] );

		$this->mock_account_service
			->expects( $this->any() )
			->method( 'is_stripe_account_valid' )
			->willReturn( $account_state['has_valid_account'] );
		$this->mock_account_service
			->expects( $this->any() )
			->method( 'get_account_status_data' )
			->willReturn(
				array(
					'status'           => 'complete',
					'testDrive'        => $account_state['test_account'] ?? false,
					'isLive'           => ! ( ( $account_state['test_account'] ?? false ) || ( $account_state['sandbox_account'] ?? false ) ),
					'paymentsEnabled'  => true,
					'detailsSubmitted' => true,
				)
			);

		// Act.
		$result = $this->sut->get_onboarding_step_status( $step_id, $location );

		// Assert.
		$this->assertSame( $expected_status, $result );
	}

	/**
	 * Data provider for test_get_onboarding_step_status.
	 *
	 * @return array[]
	 */
	public function provider_get_onboarding_step_status(): array {
		$current_time = 1234567890;

		return array(
			'payment_methods - clean slate'                => array(
				WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'payment_methods - stored started'             => array(
				WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'payment_methods - stored completed'           => array(
				WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'payment_methods - stored failed'              => array(
				WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'payment_methods - stored blocked'             => array(
				WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
				WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'payment_methods - stored started and completed' => array(
				WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'payment_methods - stored started and failed'  => array(
				WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'payment_methods - stored started and blocked' => array(
				WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
				WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - clean slate'               => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - nothing stored with working connection' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - nothing stored with partial connection' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored started with no connection data' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored failed with no connection data' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored started with working connection' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored failed with working connection' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored blocked with working connection' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored completed with no connection data' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time, // This will be ignored.
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored completed with partial connection' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored completed with working connection' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored started and completed with no connection data' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored started and failed with no connection data' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored started and failed with partial connection data' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored started and completed with partial connection data' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored started, failed, and completed with partial connection data' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					// This is ignored.
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored started, blocked, and completed with partial connection data' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					// This is ignored.
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored started and failed with working connection' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored started and blocked with working connection' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored started and completed with working connection' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'wpcom_connection - stored started, failed, and completed with working connection' => array(
				WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - clean slate'                   => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - nothing stored with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - nothing stored with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - nothing stored with invalid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'test_account - nothing stored with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - nothing stored with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'test_account - stored started with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored started with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored started with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored started with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored failed with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored failed with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored failed with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored started and failed with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored failed with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored started and failed with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored blocked with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored blocked with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored blocked with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored started and blocked with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored blocked with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored started and blocked with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored completed with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored completed with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // We trust the stored status since we can be in progress with switch to live.
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored failed and completed with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // We trust the stored status since we can be in progress with switch to live.
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored blocked and completed with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // We trust the stored status since we can be in progress with switch to live.
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored completed with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored failed and completed with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored blocked and completed with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored completed with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored failed and completed with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored blocked and completed with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored completed with invalid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'test_account - stored failed, blocked and completed with invalid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'test_account - stored completed with invalid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored failed, blocked, and completed with invalid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored completed with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored failed, blocked, and completed with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored completed with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'test_account - stored failed and completed with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'test_account - stored blocked and completed with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'test_account - stored completed with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'test_account - stored failed and completed with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'test_account - stored blocked and completed with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'test_account - stored started and completed with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored started, failed, and completed with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored started and completed with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // We trust the completed stored status since we can be in progress with switch to live.
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored started, failed, and completed with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED, // We trust the completed stored status since we can be in progress with switch to live.
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored started and completed with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored started, failed, and completed with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored started and completed with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored started, failed, and completed with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'test_account - stored started and completed with invalid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'test_account - stored started, failed, and completed with invalid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'test_account - stored started and completed with invalid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'test_account - stored started, failed, and completed with invalid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'test_account - stored started, blocked, and completed with invalid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'test_account - stored started and completed with invalid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored started, failed, and completed with invalid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored started and completed with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored started, failed, and completed with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored started, blocked, and completed with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'test_account - stored started and completed with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'test_account - stored started, failed, and completed with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'test_account - stored started and completed with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'test_account - stored started, failed, and completed with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'test_account - stored started with valid sandbox account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'test_account - stored started, failed, and completed with valid sandbox account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - clean slate'          => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - nothing stored with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'business_verification - nothing stored with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'business_verification - nothing stored with valid sandbox account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - nothing stored with invalid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'business_verification - nothing stored with invalid sandbox account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - nothing stored with invalid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - nothing stored with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - nothing stored with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - nothing stored with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored started with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'business_verification - stored started with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'business_verification - stored started with valid sandbox account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - stored started with valid sandbox account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - stored started with invalid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'business_verification - stored started with invalid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'business_verification - stored started with invalid sandbox account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - stored started with invalid sandbox account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - stored started with invalid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored started with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored failed with invalid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored failed with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored failed with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored failed with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored blocked with invalid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored blocked with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored blocked with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored blocked with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 10,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored failed with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored failed with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored blocked with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored blocked with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored completed with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored completed with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored completed with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'business_verification - stored completed with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'business_verification - stored completed with valid sandbox account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - stored completed with valid sandbox account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - stored completed with invalid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'business_verification - stored completed with invalid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'business_verification - stored completed with invalid sandbox account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - stored completed with invalid sandbox account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - stored completed with invalid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored completed with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored completed with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored completed with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored started and completed with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started and completed with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started and completed with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'business_verification - stored started and completed with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'business_verification - stored started and completed with valid sandbox account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - stored started and completed with valid sandbox account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - stored started and completed with invalid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'business_verification - stored started and completed with invalid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'business_verification - stored started and completed with invalid sandbox account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - stored started and completed with invalid sandbox account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
					'sandbox_account'   => true,
				),
			),
			'business_verification - stored started and completed with invalid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started and completed with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started and completed with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored started and completed with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored started, failed, and completed with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started, failed, and completed with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started, failed, and completed with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'business_verification - stored started, failed, and completed with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'business_verification - stored started, failed, and completed with invalid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'business_verification - stored started, failed, and completed with invalid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'business_verification - stored started, failed, and completed with invalid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started, failed, and completed with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started, failed, and completed with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored started, failed, and completed with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored started, blocked, and completed with no account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started, blocked, and completed with no account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => false,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started, blocked, and completed with valid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'business_verification - stored started, blocked, and completed with valid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => true,
				),
			),
			'business_verification - stored started, blocked, and completed with invalid test account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'business_verification - stored started, blocked, and completed with invalid test account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => true,
				),
			),
			'business_verification - stored started, blocked, and completed with invalid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => false,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started, blocked, and completed with invalid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => false,
					'test_account'      => false,
				),
			),
			'business_verification - stored started, blocked, and completed with valid live account, unmet requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => false,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
			'business_verification - stored started, blocked, and completed with valid live account, met requirements' => array(
				WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
				array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $current_time - 10,
					WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED => $current_time - 5,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $current_time,
				),
				array(
					'is_store_connected'  => true,
					'has_connected_owner' => true,
				),
				array(
					'has_account'       => true,
					'has_valid_account' => true,
					'test_account'      => false,
				),
			),
		);
	}

	/**
	 * Test mark_onboarding_step_started throws exception when extension is not active.
	 */
	public function test_mark_onboarding_step_started_throws_when_extension_not_active() {
		$location = 'US';

		// Arrange.
		// Mock the extension as not active.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_to_check ) {
					if ( '\WC_Payments' === $class_to_check ) {
						return false;
					}

					return true;
				},
			)
		);

		try {
			$this->sut->mark_onboarding_step_started( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_extension_not_active', $e->getErrorCode() );
		}
	}

	/**
	 * Test mark_onboarding_step_started throws exception when onboarding is locked.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_mark_onboarding_step_started_throws_with_onboarding_locked() {
		$location = 'US';

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						// Return a current timestamp to simulate locked state.
						return $this->current_time;
					}

					return $default_value;
				},
			)
		);

		try {
			$this->sut->mark_onboarding_step_started( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}
	}

	/**
	 * Test that mark_onboarding_step_started throws an exception when an invalid step ID is provided.
	 *
	 * @return void
	 */
	public function test_mark_onboarding_step_started_throws_on_invalid_step_id() {
		$location = 'US';

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid onboarding step ID' );

		$this->sut->mark_onboarding_step_started( 'invalid_step_id', $location );
	}

	/**
	 * Test that mark_onboarding_step_started throws an exception when the requirements are not met for the step.
	 *
	 * @return void
	 */
	public function test_mark_onboarding_step_started_throws_on_unmet_requirements() {
		$location = 'US';

		// Arrange the WPCOM connection.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( false );

		try {
			$this->sut->mark_onboarding_step_started( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_step_requirements_not_met', $e->getErrorCode() );
		}
	}

	/**
	 * Test that mark_onboarding_step_started does not overwrite the existing step status if the overwrite flag is false.
	 *
	 * @return void
	 */
	public function test_mark_onboarding_step_started_does_not_overwrite() {
		$location = 'US';

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$timestamp              = $this->current_time - 100;
		$stored_profile         = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $timestamp,
							),
						),
					),
				),
			),
		);
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->mark_onboarding_step_started( WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS, $location, false );

		// Assert.
		$this->assertTrue( $result );
		$this->assertEquals( array(), $updated_stored_profile );
	}

	/**
	 * Test that mark_onboarding_step_started overwrites the existing step status if the overwrite flag is true.
	 *
	 * @return void
	 */
	public function test_mark_onboarding_step_started_overwrites() {
		$location = 'US';

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$timestamp              = $this->current_time - 100;
		$stored_profile         = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $timestamp,
							),
						),
					),
				),
			),
		);
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->mark_onboarding_step_started( WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS, $location, true );

		// Assert.
		$this->assertTrue( $result );
		$this->assertNotEquals( array(), $updated_stored_profile );
		$this->assertNotEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'][ WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED ] );
		$this->assertNotSame( $timestamp, $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'][ WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED ] );
	}

	/**
	 * Test that mark_onboarding_step_started stores the step status to started with the current timestamp.
	 *
	 * @return void
	 */
	public function test_mark_onboarding_step_started() {
		$location = 'US';

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$stored_profile         = array();
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->mark_onboarding_step_started( WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS, $location, true );

		// Assert.
		$this->assertTrue( $result );
		$this->assertNotEquals( array(), $updated_stored_profile );
		$this->assertNotEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'][ WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED ] );
		$this->assertSame( $this->current_time, $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'][ WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED ] );
	}

	/**
	 * Test mark_onboarding_step_completed throws exception when extension is not active.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_mark_onboarding_step_completed_throws_when_extension_not_active() {
		$location = 'US';

		// Arrange.
		// Mock the extension as not active.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_to_check ) {
					if ( '\WC_Payments' === $class_to_check ) {
						return false;
					}

					return true;
				},
			)
		);

		try {
			$this->sut->mark_onboarding_step_completed( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_extension_not_active', $e->getErrorCode() );
		}
	}

	/**
	 * Test mark_onboarding_step_completed throws exception when onboarding is locked.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_mark_onboarding_step_completed_throws_with_onboarding_locked() {
		$location = 'US';

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						// Return a current timestamp to simulate locked state.
						return $this->current_time;
					}

					return $default_value;
				},
			)
		);

		try {
			$this->sut->mark_onboarding_step_completed( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}
	}

	/**
	 * Test that mark_onboarding_step_completed throws an exception when an invalid step ID is provided.
	 *
	 * @return void
	 */
	public function test_mark_onboarding_step_completed_throws_on_invalid_step_id() {
		$location = 'US';

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid onboarding step ID' );

		$this->sut->mark_onboarding_step_completed( 'invalid_step_id', $location );
	}

	/**
	 * Test that mark_onboarding_step_completed throws an exception when the requirements are not met for the step.
	 *
	 * @return void
	 */
	public function test_mark_onboarding_step_completed_throws_on_unmet_requirements() {
		$location = 'US';

		// Arrange the WPCOM connection.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( false );

		try {
			$this->sut->mark_onboarding_step_completed( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_step_requirements_not_met', $e->getErrorCode() );
		}
	}

	/**
	 * Test that mark_onboarding_step_completed throws an exception when the step is blocked.
	 *
	 * @return void
	 */
	public function test_mark_onboarding_step_completed_throws_when_blocked() {
		$location = 'US';

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$stored_profile         = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED   => $this->current_time - 200,
								WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED    => $this->current_time - 150,
								WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED   => $this->current_time - 100,
								WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $this->current_time - 10,
							),
							'data'     => array(
								'error' => array( 'some error' => 'some error' ),
							),
						),
					),
				),
			),
		);
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		try {
			$this->sut->mark_onboarding_step_completed( $step_id, $location );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_step_blocked', $e->getErrorCode() );
		}
	}

	/**
	 * Test that mark_onboarding_step_completed does not overwrite the existing step status if the overwrite flag is false.
	 *
	 * @return void
	 */
	public function test_mark_onboarding_step_completed_does_not_overwrite() {
		$location = 'US';

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$timestamp              = $this->current_time - 100;
		$stored_profile         = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $timestamp,
							),
						),
					),
				),
			),
		);
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->mark_onboarding_step_completed( $step_id, $location, false );

		// Assert.
		$this->assertTrue( $result );
		$this->assertEquals( array(), $updated_stored_profile );
	}

	/**
	 * Test that mark_onboarding_step_completed overwrites the existing step status if the overwrite flag is true.
	 *
	 * @return void
	 */
	public function test_mark_onboarding_step_completed_overwrites() {
		$location = 'US';

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$timestamp              = $this->current_time - 100;
		$stored_profile         = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $timestamp,
							),
						),
					),
				),
			),
		);
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->mark_onboarding_step_completed( $step_id, $location, true );

		// Assert.
		$this->assertTrue( $result );
		$this->assertNotEquals( array(), $updated_stored_profile );
		$this->assertNotEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'][ WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED ] );
		$this->assertNotSame( $timestamp, $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'][ WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED ] );
	}

	/**
	 * Test that mark_onboarding_step_completed stores the step status to completed with the current timestamp.
	 *
	 * @return void
	 */
	public function test_mark_onboarding_step_completed() {
		$location = 'US';

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$stored_profile         = array();
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->mark_onboarding_step_completed( $step_id, $location, true );

		// Assert.
		$this->assertTrue( $result );
		$this->assertNotEquals( array(), $updated_stored_profile );
		$this->assertNotEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'][ WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED ] );
		$this->assertSame( $this->current_time, $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'][ WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED ] );
	}

	/**
	 * Test clean_onboarding_step_progress throws exception when extension is not active.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_clean_onboarding_step_progress_throws_when_extension_not_active() {
		$location = 'US';

		// Arrange.
		// Mock the extension as not active.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_to_check ) {
					if ( '\WC_Payments' === $class_to_check ) {
						return false;
					}

					return true;
				},
			)
		);

		try {
			$this->sut->clean_onboarding_step_progress( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_extension_not_active', $e->getErrorCode() );
		}
	}

	/**
	 * Test clean_onboarding_step_progress throws exception when onboarding is locked.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_clean_onboarding_step_progress_throws_with_onboarding_locked() {
		$location = 'US';

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return $this->current_time;
					}

					return $default_value;
				},
			)
		);

		try {
			$this->sut->clean_onboarding_step_progress( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}
	}

	/**
	 * Test that clean_onboarding_step_progress throws an exception when an invalid step ID is provided.
	 *
	 * @return void
	 */
	public function test_clean_onboarding_step_progress_throws_on_invalid_step_id() {
		$location = 'US';

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid onboarding step ID' );

		$this->sut->clean_onboarding_step_progress( 'invalid_step_id', $location );
	}

	/**
	 * Test clean_onboarding_step_progress when the requirements are not met for the step.
	 *
	 * @return void
	 */
	public function test_clean_onboarding_step_progress_on_unmet_requirements() {
		$location = 'US';

		// Arrange the WPCOM connection.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( false );

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT;
		$stored_profile         = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED   => $this->current_time - 200,
								WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED    => $this->current_time - 150,
								WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED   => $this->current_time - 100,
								WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $this->current_time - 10,
							),
							'data'     => array(
								'error' => array( 'some error' => 'some error' ),
							),
						),
					),
				),
			),
		);
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( &$stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( &$stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						// Update the stored profile to the latest set value.
						$stored_profile = $value;

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->clean_onboarding_step_progress( $step_id, $location );

		// Assert.
		$this->assertTrue( $result );
		$this->assertNotEquals( array(), $updated_stored_profile );
		$this->assertEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'] );
		$this->assertEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['error'] );
	}

	/**
	 * Test clean_onboarding_step_progress when the step is blocked.
	 *
	 * @return void
	 */
	public function test_clean_onboarding_step_progress_when_blocked() {
		$location = 'US';

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$stored_profile         = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED   => $this->current_time - 200,
								WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED    => $this->current_time - 150,
								WooPaymentsService::ONBOARDING_STEP_STATUS_BLOCKED   => $this->current_time - 100,
								WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $this->current_time - 10,
							),
							'data'     => array(
								'error' => array( 'some error' => 'some error' ),
							),
						),
					),
				),
			),
		);
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( &$stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( &$stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						// Update the stored profile to the latest set value.
						$stored_profile = $value;

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->clean_onboarding_step_progress( $step_id, $location );

		// Assert.
		$this->assertTrue( $result );
		$this->assertNotEquals( array(), $updated_stored_profile );
		$this->assertEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'] );
		$this->assertEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['error'] );
	}

	/**
	 * Test that clean_onboarding_step_progress clears the statuses and errors.
	 *
	 * @return void
	 */
	public function test_clean_onboarding_step_progress() {
		$location = 'US';

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$stored_profile         = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $this->current_time - 200,
								WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED => $this->current_time - 150,
								WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $this->current_time - 10,
							),
							'data'     => array(
								'error' => array( 'some error' => 'some error' ),
							),
						),
					),
				),
			),
		);
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( &$stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( &$stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						// Update the stored profile to the latest set value.
						$stored_profile = $value;

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->clean_onboarding_step_progress( $step_id, $location );

		// Assert.
		$this->assertTrue( $result );
		$this->assertNotEquals( array(), $updated_stored_profile );
		$this->assertEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'] );
		$this->assertEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['error'] );
	}

	/**
	 * Test onboarding_step_save throws exception when extension is not active.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_onboarding_step_save_throws_when_extension_not_active() {
		$location = 'US';

		// Arrange.
		// Mock the extension as not active.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_to_check ) {
					if ( '\WC_Payments' === $class_to_check ) {
						return false;
					}

					return true;
				},
			)
		);

		try {
			$this->sut->onboarding_step_save( WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS, $location, array() );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_extension_not_active', $e->getErrorCode() );
		}
	}

	/**
	 * Test onboarding_step_save throws exception when onboarding is locked.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_onboarding_step_save_throws_with_onboarding_locked() {
		$location = 'US';

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return $this->current_time;
					}

					return $default_value;
				},
			)
		);

		try {
			$this->sut->onboarding_step_save( WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS, $location, array() );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}
	}

	/**
	 * Test that onboarding_step_save throws an exception when an invalid step ID is provided.
	 *
	 * @return void
	 */
	public function test_onboarding_step_save_throws_on_invalid_step_id() {
		$location = 'US';

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid onboarding step ID' );

		$this->sut->onboarding_step_save( 'invalid_step_id', $location, array() );
	}

	/**
	 * Test that onboarding_step_save throws an exception when receiving invalid data.
	 *
	 * @return void
	 */
	public function test_onboarding_step_save_throws_on_invalid_data() {
		$location = 'US';

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid onboarding step data.' );

		$this->sut->onboarding_step_save( WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS, $location, array() );
	}

	/**
	 * Test that onboarding_step_save throws an exception when attempting to save for step that doesn't support it.
	 *
	 * @return void
	 */
	public function test_onboarding_step_save_throws_on_step_without_support() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working since it is a dependency for the step.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		try {
			$this->sut->onboarding_step_save( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location, array() );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_step_action_not_supported', $e->getErrorCode() );
		}
	}

	/**
	 * Test that onboarding_step_save stores the step data.
	 *
	 * @return void
	 */
	public function test_onboarding_step_save() {
		$location = 'US';

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$payment_methods        = array(
			'credit_card' => true,
			'paypal'      => false,
		);
		$stored_profile         = array();
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->onboarding_step_save(
			$step_id,
			$location,
			array(
				'payment_methods' => $payment_methods,
			)
		);

		// Assert.
		$this->assertTrue( $result );
		$this->assertNotEquals( array(), $updated_stored_profile );
		$this->assertNotEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['payment_methods'] );
		$this->assertEquals( $payment_methods, $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['payment_methods'] );
	}

	/**
	 * Test that onboarding_step_save stores the step data.
	 *
	 * @return void
	 */
	public function test_onboarding_step_save_overwrites() {
		$location = 'US';

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$payment_methods        = array(
			'credit_card' => true,
			'paypal'      => false,
		);
		$stored_profile         = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'data' => array(
								'payment_methods' => array(
									'credit_card' => false,
									'paypal'      => true,
								),
							),
						),
					),
				),
			),
		);
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->onboarding_step_save(
			$step_id,
			$location,
			array(
				'payment_methods' => $payment_methods,
			)
		);

		// Assert.
		$this->assertTrue( $result );
		$this->assertNotEquals( array(), $updated_stored_profile );
		$this->assertNotEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['payment_methods'] );
		$this->assertEquals( $payment_methods, $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['payment_methods'] );
	}

	/**
	 * Test that onboarding_step_save stores the step data with existing profile data and keeps the existing data.
	 *
	 * @return void
	 */
	public function test_onboarding_step_save_with_existing_profile_data() {
		$location = 'US';

		// Arrange.
		$step_id                = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$payment_methods        = array(
			'credit_card' => true,
			'paypal'      => false,
		);
		$stored_profile         = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $this->current_time - 100,
							),
							'data'     => array(
								'some_data' => 'some_value',
							),
						),
					),
				),
			),
		);
		$expected_profile       = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $this->current_time - 100,
							),
							'data'     => array(
								'some_data'       => 'some_value',
								'payment_methods' => $payment_methods,
							),
						),
					),
				),
			),
		);
		$updated_stored_profile = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profile = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->onboarding_step_save(
			$step_id,
			$location,
			array(
				'payment_methods' => $payment_methods,
			)
		);

		// Assert.
		$this->assertTrue( $result );
		$this->assertNotEquals( array(), $updated_stored_profile );
		$this->assertNotEmpty( $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['payment_methods'] );
		$this->assertEquals( $payment_methods, $updated_stored_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['payment_methods'] );
		$this->assertEquals( $expected_profile, $updated_stored_profile );
	}

	/**
	 * Test onboarding_step_check throws exception when extension is not active.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_onboarding_step_check_throws_when_extension_not_active() {
		$location = 'US';

		// Arrange.
		// Mock the extension as not active.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_to_check ) {
					if ( '\WC_Payments' === $class_to_check ) {
						return false;
					}

					return true;
				},
			)
		);

		try {
			$this->sut->onboarding_step_check( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_extension_not_active', $e->getErrorCode() );
		}
	}

	/**
	 * Test onboarding_step_check throws exception when onboarding is locked.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_onboarding_step_check_throws_with_onboarding_locked() {
		$location = 'US';

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return $this->current_time;
					}

					return $default_value;
				},
			)
		);

		try {
			$this->sut->onboarding_step_check( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location );

			$this->fail( 'Expected ApiException not thrown' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}
	}

	/**
	 * Test that onboarding_step_check throws an exception when an invalid step ID is provided.
	 *
	 * @return void
	 */
	public function test_onboarding_step_check_throws_on_invalid_step_id() {
		$location = 'US';

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid onboarding step ID' );

		$this->sut->onboarding_step_check( 'invalid_step_id', $location );
	}

	/**
	 * Test that onboarding_step_check throws an exception when the requirements are not met for the step.
	 *
	 * @return void
	 */
	public function test_onboarding_step_check_throws_on_unmet_requirements() {
		$location = 'US';

		// Arrange the WPCOM connection.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( false );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Onboarding step requirements are not met.' );

		$this->sut->onboarding_step_check( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location );
	}

	/**
	 * Test that onboarding_step_check returns the correct status for a step.
	 *
	 * @return void
	 */
	public function test_onboarding_step_check() {
		$location = 'US';

		// Arrange.
		$step_id        = WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS;
		$stored_profile = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $this->current_time - 100,
							),
						),
					),
				),
			),
		);
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
			)
		);

		// Act.
		$result = $this->sut->onboarding_step_check( $step_id, $location );

		// Assert.
		$this->assertEquals(
			array(
				'status' => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				'error'  => array(),
			),
			$result
		);
	}

	/**
	 * Test that get_onboarding_recommended_payment_methods returns the correct recommended payment methods.
	 *
	 * @return void
	 */
	public function test_get_onboarding_recommended_payment_methods() {
		// Arrange.
		$country_code = 'US';
		$expected     = array(
			array(
				'id'       => 'credit_card',
				'enabled'  => true,
				'required' => true,
			),
			array(
				'id'       => 'affirm',
				'enabled'  => false,
				'required' => false,
			),
		);
		$this->mock_provider
			->expects( $this->once() )
			->method( 'get_recommended_payment_methods' )
			->with( $this->isInstanceOf( FakePaymentGateway::class ), $country_code )
			->willReturn( $expected );

		// Act.
		$result = $this->sut->get_onboarding_recommended_payment_methods( $country_code );

		// Assert.
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test onboarding_test_account_init with an existing test account.
	 *
	 * @return void
	 * @throws \Exception On POST request not mocked.
	 */
	public function test_onboarding_test_account_init_with_existing_test_account_throws() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working since it is a dependency for the step.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the account.
		$this->mock_provider
			->expects( $this->any() )
			->method( 'is_account_connected' )
			->willReturn( true );
		$this->mock_account_service
			->expects( $this->any() )
			->method( 'is_stripe_account_valid' )
			->willReturn( false ); // Make it invalid, for good measure.
		$this->mock_account_service
			->expects( $this->any() )
			->method( 'get_account_status_data' )
			->willReturn(
				array(
					'status'           => 'complete',
					// These two are mirror images of each other.
					'testDrive'        => true,
					'isLive'           => false,
					'paymentsEnabled'  => true,
					'detailsSubmitted' => true,
				)
			);

		// Arrange the REST API requests.
		$requests_made = array();
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made ) {
						if ( '/wc/v3/payments/onboarding/test_drive_account/init' === $endpoint ) {
							$requests_made[] = $params;
							return array(
								'success' => true,
							);
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		$this->expectException( ApiException::class );
		$this->expectExceptionMessage( 'A test account is already set up.' );

		// Act.
		$result = $this->sut->onboarding_test_account_init( $location );

		$this->assertCount( 0, $requests_made );
	}

	/**
	 * Test onboarding_test_account_init with an existing live account.
	 *
	 * @return void
	 * @throws \Exception On POST request not mocked.
	 */
	public function test_onboarding_test_account_init_with_existing_live_account_throws() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working since it is a dependency for the step.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the account.
		$this->mock_provider
			->expects( $this->any() )
			->method( 'is_account_connected' )
			->willReturn( true );
		$this->mock_account_service
			->expects( $this->any() )
			->method( 'is_stripe_account_valid' )
			->willReturn( false ); // Make it invalid, for good measure.
		$this->mock_account_service
			->expects( $this->any() )
			->method( 'get_account_status_data' )
			->willReturn(
				array(
					'status'           => 'complete',
					// These two are mirror images of each other.
					'testDrive'        => false,
					'isLive'           => true,
					'paymentsEnabled'  => true,
					'detailsSubmitted' => true,
				)
			);

		// Arrange the REST API requests.
		$requests_made = array();
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made ) {
						if ( '/wc/v3/payments/onboarding/test_drive_account/init' === $endpoint ) {
							$requests_made[] = $params;
							return array(
								'success' => true,
							);
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		$this->expectException( ApiException::class );
		$this->expectExceptionMessage( 'An account is already set up. Reset the onboarding first.' );

		// Act.
		$result = $this->sut->onboarding_test_account_init( $location );

		$this->assertCount( 0, $requests_made );
	}

	/**
	 * Test onboarding_test_account_init throws exception when extension is not active.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_onboarding_test_account_init_throws_when_extension_not_active() {
		$location = 'US';

		// Arrange.
		// Mock the extension as not active.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_to_check ) {
					if ( '\WC_Payments' === $class_to_check ) {
						return false;
					}

					return true;
				},
			)
		);

		try {
			$this->sut->onboarding_test_account_init( $location );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_extension_not_active', $e->getErrorCode() );
		}
	}

	/**
	 * Test onboarding_test_account_init throws exception when onboarding is locked.
	 *
	 * @return void
	 * @throws \Exception On POST request not mocked.
	 */
	public function test_onboarding_test_account_init_throws_with_onboarding_locked() {
		$location = 'US';

		// Arrange the account.
		$this->mock_provider
			->expects( $this->any() )
			->method( 'is_account_connected' )
			->willReturn( false );

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return $this->current_time;
					}

					return $default_value;
				},
			)
		);

		// Arrange the REST API requests.
		$requests_made = array();
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made ) {
						if ( '/wc/v3/payments/onboarding/test_drive_account/init' === $endpoint ) {
							$requests_made[] = $params;
							return array(
								'success' => true,
							);
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Act.
		try {
			$this->sut->onboarding_test_account_init( $location );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}

		// Assert.
		$this->assertCount( 0, $requests_made );
	}

	/**
	 * Test onboarding_test_account_init that throws an exception when the step requirements are not met.
	 *
	 * @return void
	 */
	public function test_onboarding_test_account_init_throws_on_unmet_requirements() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it NOT working.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( false );

		try {
			$this->sut->onboarding_test_account_init( $location );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_step_requirements_not_met', $e->getErrorCode() );
		}
	}

	/**
	 * Test onboarding_test_account_init that throws an exception when the REST API call fails.
	 *
	 * @return void
	 */
	public function test_onboarding_test_account_init_throws_on_error_response() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the NOX profile.
		$step_id                 = WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT;
		$stored_profile          = array();
		$updated_stored_profiles = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profiles[] = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Arrange the REST API requests.
		$requests_made  = array();
		$expected_error = array(
			'code'    => 'error',
			'message' => 'Error message',
		);
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_error ) {
						if ( '/wc/v3/payments/onboarding/test_drive_account/init' === $endpoint ) {
							$requests_made[] = $params;
							return new WP_Error( $expected_error['code'], $expected_error['message'] );
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Assert.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( $expected_error['message'] );

		// Act.
		$this->sut->onboarding_test_account_init( $location );
	}

	/**
	 * Test that error sanitization moves extra keys to context when storing a step error.
	 *
	 * When an error has keys beyond 'code', 'message', and 'context', those extra keys
	 * should be moved into the 'context' array.
	 *
	 * @return void
	 */
	public function test_error_sanitization_moves_extra_keys_to_context() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the NOX profile.
		$step_id                 = WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT;
		$stored_profile          = array();
		$updated_stored_profiles = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( &$updated_stored_profiles, $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						// Return the latest updated profile if available.
						return ! empty( $updated_stored_profiles ) ? end( $updated_stored_profiles ) : $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profiles[] = $value;
						return true;
					}

					return true;
				},
			)
		);

		// Arrange the REST API requests to return a WP_Error with extra data.
		$error_data_with_extra_keys = array(
			'details'  => 'Some additional details',
			'trace'    => 'stack trace info',
			'response' => 'raw response data',
		);
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( $error_data_with_extra_keys ) {
						unset( $params ); // Avoid parameter not used PHPCS errors.
						if ( '/wc/v3/payments/onboarding/test_drive_account/init' === $endpoint ) {
							return new WP_Error( 'test_error', 'Test error message', $error_data_with_extra_keys );
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Act - call the method which will trigger mark_onboarding_step_failed.
		try {
			$this->sut->onboarding_test_account_init( $location );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( \Exception $e ) {
			// Expected exception, ignore it.
			unset( $e );
		}

		// Assert - verify the stored error has extra keys moved to context.
		$this->assertNotEmpty( $updated_stored_profiles, 'Profile should have been updated' );
		$final_profile = end( $updated_stored_profiles );

		$this->assertArrayHasKey( 'onboarding', $final_profile );
		$this->assertArrayHasKey( $location, $final_profile['onboarding'] );
		$this->assertArrayHasKey( 'steps', $final_profile['onboarding'][ $location ] );
		$this->assertArrayHasKey( $step_id, $final_profile['onboarding'][ $location ]['steps'] );
		$this->assertArrayHasKey( 'data', $final_profile['onboarding'][ $location ]['steps'][ $step_id ] );
		$this->assertArrayHasKey( 'error', $final_profile['onboarding'][ $location ]['steps'][ $step_id ]['data'] );

		$stored_error = $final_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['error'];

		// Verify the error structure has code, message, and context at the top level.
		$this->assertArrayHasKey( 'code', $stored_error );
		$this->assertArrayHasKey( 'message', $stored_error );
		$this->assertArrayHasKey( 'context', $stored_error );
		$this->assertSame( 'test_error', $stored_error['code'] );
		$this->assertSame( 'Test error message', $stored_error['message'] );

		// Verify the extra keys were moved to context.
		$this->assertIsArray( $stored_error['context'] );
		$this->assertNotEmpty( $stored_error['context'], 'Context should contain the extra keys' );
		$this->assertArrayHasKey( 'details', $stored_error['context'] );
		$this->assertArrayHasKey( 'trace', $stored_error['context'] );
		$this->assertArrayHasKey( 'response', $stored_error['context'] );
		$this->assertSame( 'Some additional details', $stored_error['context']['details'] );
		$this->assertSame( 'stack trace info', $stored_error['context']['trace'] );
		$this->assertSame( 'raw response data', $stored_error['context']['response'] );
	}

	/**
	 * Test that error sanitization merges extra keys with existing context.
	 *
	 * When an error has both a 'context' key and extra keys, the extra keys should be
	 * merged into the context with existing context values taking precedence.
	 *
	 * @return void
	 */
	public function test_error_sanitization_merges_extra_keys_with_existing_context() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the NOX profile.
		$step_id                 = WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT;
		$stored_profile          = array();
		$updated_stored_profiles = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( &$updated_stored_profiles, $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return ! empty( $updated_stored_profiles ) ? end( $updated_stored_profiles ) : $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profiles[] = $value;
						return true;
					}

					return true;
				},
			)
		);

		// Arrange the REST API requests to return a WP_Error with context containing extra keys.
		// The error_data has a 'details' key that should be moved to context,
		// and a nested 'context' with 'existing_key'.
		$error_data = array(
			'details'         => 'Extra details',
			'context'         => array(
				'existing_key'    => 'existing value',
				'conflicting_key' => 'context value',
			),
			'conflicting_key' => 'extra key value', // This should be overwritten by context value.
		);
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( $error_data ) {
						unset( $params ); // Avoid parameter not used PHPCS errors.
						if ( '/wc/v3/payments/onboarding/test_drive_account/init' === $endpoint ) {
							return new WP_Error( 'test_error', 'Test error message', $error_data );
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Act.
		try {
			$this->sut->onboarding_test_account_init( $location );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( \Exception $e ) {
			// Expected exception, ignore it.
			unset( $e );
		}

		// Assert.
		$this->assertNotEmpty( $updated_stored_profiles );
		$final_profile = end( $updated_stored_profiles );
		$stored_error  = $final_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['error'];

		// Verify the error structure has code, message, and context at the top level.
		$this->assertArrayHasKey( 'code', $stored_error );
		$this->assertArrayHasKey( 'message', $stored_error );
		$this->assertArrayHasKey( 'context', $stored_error );
		$this->assertIsArray( $stored_error['context'] );
		$this->assertNotEmpty( $stored_error['context'], 'Context should contain the merged keys' );

		// Verify conflicting_key is NOT at the top level (it should only be in context).
		$this->assertArrayNotHasKey( 'conflicting_key', $stored_error, 'Extra keys should be moved to context, not kept at top level' );

		// Verify extra key was moved to context.
		$this->assertArrayHasKey( 'details', $stored_error['context'] );
		$this->assertSame( 'Extra details', $stored_error['context']['details'] );

		// Verify existing context key is preserved.
		$this->assertArrayHasKey( 'existing_key', $stored_error['context'] );
		$this->assertSame( 'existing value', $stored_error['context']['existing_key'] );

		// Verify that the context value takes precedence over the extra key value for conflicting keys.
		$this->assertArrayHasKey( 'conflicting_key', $stored_error['context'] );
		$this->assertSame( 'context value', $stored_error['context']['conflicting_key'] );
	}

	/**
	 * Test that error sanitization flattens nested context keys.
	 *
	 * When an error's context contains a nested 'context' key (e.g., from WP_Error data),
	 * the nested context should be flattened, with nested context values taking precedence.
	 *
	 * @return void
	 */
	public function test_error_sanitization_flattens_nested_context() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the NOX profile.
		$step_id                 = WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT;
		$stored_profile          = array();
		$updated_stored_profiles = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( &$updated_stored_profiles, $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return ! empty( $updated_stored_profiles ) ? end( $updated_stored_profiles ) : $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profiles[] = $value;
						return true;
					}

					return true;
				},
			)
		);

		// Arrange the REST API requests to return a WP_Error with nested context.
		// The error_data has a nested 'context' key that should be flattened.
		$error_data = array(
			'top_level_key'   => 'top value',
			'conflicting_key' => 'top level value', // Should be overwritten by nested context value.
			'context'         => array(
				'nested_key'      => 'nested value',
				'conflicting_key' => 'nested context value', // Takes precedence.
			),
		);
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( $error_data ) {
						unset( $params ); // Avoid parameter not used PHPCS errors.
						if ( '/wc/v3/payments/onboarding/test_drive_account/init' === $endpoint ) {
							return new WP_Error( 'test_error', 'Test error message', $error_data );
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Act.
		try {
			$this->sut->onboarding_test_account_init( $location );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( \Exception $e ) {
			// Exception expected from the mocked WP_Error response.
			// The actual error sanitization is verified in the assertions below.
			unset( $e );
		}

		// Assert.
		$this->assertNotEmpty( $updated_stored_profiles );
		$final_profile = end( $updated_stored_profiles );
		$stored_error  = $final_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['error'];

		$this->assertArrayHasKey( 'context', $stored_error );
		$this->assertIsArray( $stored_error['context'] );

		// Verify the top-level key was preserved.
		$this->assertArrayHasKey( 'top_level_key', $stored_error['context'] );
		$this->assertSame( 'top value', $stored_error['context']['top_level_key'] );

		// Verify the nested context key was flattened into the main context.
		$this->assertArrayHasKey( 'nested_key', $stored_error['context'] );
		$this->assertSame( 'nested value', $stored_error['context']['nested_key'] );

		// Verify the nested 'context' key itself is no longer present.
		$this->assertArrayNotHasKey( 'context', $stored_error['context'] );

		// Verify that nested context value takes precedence over top-level value for conflicting keys.
		$this->assertArrayHasKey( 'conflicting_key', $stored_error['context'] );
		$this->assertSame( 'nested context value', $stored_error['context']['conflicting_key'] );
	}

	/**
	 * Test that step error standardization treats an error object with reserved keys as a single error.
	 *
	 * When a step's errors field directly contains an error object (with code/message/context keys)
	 * instead of a list of errors, it should be treated as a single error and wrapped in an array.
	 *
	 * @return void
	 */
	public function test_step_error_standardization_wraps_single_error_object() {
		$location = 'US';

		// Create step details with errors as a single error object (not a list).
		// This simulates an edge case where errors might be passed incorrectly.
		$step_details = array(
			'id'     => WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
			'status' => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
			// This is a single error object, NOT a list of errors.
			// It has 'code', 'message', and 'context' keys directly.
			'errors' => array(
				'code'    => 'test_error_code',
				'message' => 'Test error message',
				'context' => array(
					'some_key' => 'some_value',
				),
			),
		);

		// Use reflection to call the private standardize_onboarding_step_details method.
		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'standardize_onboarding_step_details' );
		$method->setAccessible( true );

		// Act.
		$result = $method->invoke( $this->sut, $step_details, $location, '/rest/path/' );

		// Assert.
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertIsArray( $result['errors'] );
		$this->assertCount( 1, $result['errors'] );

		// Verify the single error was properly standardized.
		$standardized_error = $result['errors'][0];
		$this->assertArrayHasKey( 'code', $standardized_error );
		$this->assertSame( 'test_error_code', $standardized_error['code'] );
		$this->assertArrayHasKey( 'message', $standardized_error );
		$this->assertSame( 'Test error message', $standardized_error['message'] );
		$this->assertArrayHasKey( 'context', $standardized_error );
		$this->assertArrayHasKey( 'some_key', $standardized_error['context'] );
		$this->assertSame( 'some_value', $standardized_error['context']['some_key'] );
	}

	/**
	 * Test that step error standardization treats errors with only 'code' key as a single error.
	 *
	 * An error object with just the 'code' key should be treated as a single error.
	 *
	 * @return void
	 */
	public function test_step_error_standardization_wraps_error_with_code_key() {
		$location = 'US';

		// Create step details with errors containing only a 'code' key.
		$step_details = array(
			'id'     => WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
			'status' => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
			// Has 'code' key directly - should be treated as single error.
			'errors' => array(
				'code' => 'error_with_only_code',
			),
		);

		// Use reflection to call the private method.
		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'standardize_onboarding_step_details' );
		$method->setAccessible( true );

		// Act.
		$result = $method->invoke( $this->sut, $step_details, $location, '/rest/path/' );

		// Assert.
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertIsArray( $result['errors'] );
		$this->assertCount( 1, $result['errors'] );

		$standardized_error = $result['errors'][0];
		$this->assertArrayHasKey( 'code', $standardized_error );
		$this->assertSame( 'error_with_only_code', $standardized_error['code'] );
		// Message should default to empty string when not provided.
		$this->assertArrayHasKey( 'message', $standardized_error );
		$this->assertSame( '', $standardized_error['message'], 'Message should default to empty string' );
		// Context should default to empty array when not provided.
		$this->assertArrayHasKey( 'context', $standardized_error );
		$this->assertSame( array(), $standardized_error['context'], 'Context should default to empty array' );
	}

	/**
	 * Test that step error standardization treats errors with only 'message' key as a single error.
	 *
	 * An error object with just the 'message' key should be treated as a single error.
	 *
	 * @return void
	 */
	public function test_step_error_standardization_wraps_error_with_message_key() {
		$location = 'US';

		// Create step details with errors containing only a 'message' key.
		$step_details = array(
			'id'     => WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
			'status' => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
			// Has 'message' key directly - should be treated as single error.
			'errors' => array(
				'message' => 'Error with only message',
			),
		);

		// Use reflection to call the private method.
		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'standardize_onboarding_step_details' );
		$method->setAccessible( true );

		// Act.
		$result = $method->invoke( $this->sut, $step_details, $location, '/rest/path/' );

		// Assert.
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertIsArray( $result['errors'] );
		$this->assertCount( 1, $result['errors'] );

		$standardized_error = $result['errors'][0];
		$this->assertSame( 'Error with only message', $standardized_error['message'] );
		// Code should default to 'general_error'.
		$this->assertArrayHasKey( 'code', $standardized_error );
		$this->assertSame( 'general_error', $standardized_error['code'] );
	}

	/**
	 * Test that step error standardization treats errors with only 'context' key as a single error.
	 *
	 * An error object with just the 'context' key should be treated as a single error.
	 *
	 * @return void
	 */
	public function test_step_error_standardization_wraps_error_with_context_key() {
		$location = 'US';

		// Create step details with errors containing only a 'context' key.
		$step_details = array(
			'id'     => WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
			'status' => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
			// Has 'context' key directly - should be treated as single error.
			'errors' => array(
				'context' => array(
					'detail' => 'Some context detail',
				),
			),
		);

		// Use reflection to call the private method.
		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'standardize_onboarding_step_details' );
		$method->setAccessible( true );

		// Act.
		$result = $method->invoke( $this->sut, $step_details, $location, '/rest/path/' );

		// Assert.
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertIsArray( $result['errors'] );
		$this->assertCount( 1, $result['errors'] );

		$standardized_error = $result['errors'][0];
		// Code should default to 'general_error'.
		$this->assertArrayHasKey( 'code', $standardized_error );
		$this->assertSame( 'general_error', $standardized_error['code'] );
		// Context should be preserved.
		$this->assertArrayHasKey( 'context', $standardized_error );
		$this->assertArrayHasKey( 'detail', $standardized_error['context'] );
		$this->assertSame( 'Some context detail', $standardized_error['context']['detail'] );
	}

	/**
	 * Test that step error standardization handles a proper list of errors correctly.
	 *
	 * When errors is already a list of error objects (without code/message/context at top level),
	 * it should NOT be wrapped again.
	 *
	 * @return void
	 */
	public function test_step_error_standardization_keeps_error_list_as_is() {
		$location = 'US';

		// Create step details with errors as a proper list of error objects.
		$step_details = array(
			'id'     => WooPaymentsService::ONBOARDING_STEP_PAYMENT_METHODS,
			'status' => WooPaymentsService::ONBOARDING_STEP_STATUS_FAILED,
			// This is a proper list of errors - should NOT be wrapped.
			'errors' => array(
				array(
					'code'    => 'first_error',
					'message' => 'First error message',
				),
				array(
					'code'    => 'second_error',
					'message' => 'Second error message',
				),
			),
		);

		// Use reflection to call the private method.
		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'standardize_onboarding_step_details' );
		$method->setAccessible( true );

		// Act.
		$result = $method->invoke( $this->sut, $step_details, $location, '/rest/path/' );

		// Assert.
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertIsArray( $result['errors'] );
		$this->assertCount( 2, $result['errors'] );

		// Verify both errors were preserved.
		$this->assertSame( 'first_error', $result['errors'][0]['code'] );
		$this->assertSame( 'First error message', $result['errors'][0]['message'] );
		$this->assertSame( 'second_error', $result['errors'][1]['code'] );
		$this->assertSame( 'Second error message', $result['errors'][1]['message'] );
	}

	/**
	 * Test onboarding_test_account_init that throws an exception when the REST API call doesn't return success.
	 *
	 * @return void
	 */
	public function test_onboarding_test_account_init_throws_on_not_successful() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the NOX profile.
		$step_id                 = WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT;
		$stored_profile          = array();
		$updated_stored_profiles = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profiles[] = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Arrange the REST API requests.
		$requests_made = array();
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made ) {
						if ( '/wc/v3/payments/onboarding/test_drive_account/init' === $endpoint ) {
							$requests_made[] = $params;
							return array(
								'success' => false,
								'error'   => 'Error message',
							);
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Assert.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( esc_html__( 'Failed to initialize the test account.', 'woocommerce' ) );

		// Act.
		$this->sut->onboarding_test_account_init( $location );
	}

	/**
	 * Test that onboarding lock mechanism works during test account initialization.
	 */
	public function test_onboarding_lock_mechanism_during_test_account_init(): void {
		$location = 'US';

		// Arrange the WPCOM connection.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Track onboarding lock state changes.
		$lock_states  = array();
		$current_time = $this->current_time;

		// Arrange the NOX profile and lock tracking.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( &$lock_states ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return array();
					}
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return end( $lock_states ) !== false ? end( $lock_states ) : $default_value;
					}
					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( &$lock_states, $current_time ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						$lock_states[] = $value;
					}
					return true;
				},
			)
		);

		// Mock successful REST API response.
		$expected_response = array(
			'success' => true,
		);
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint ) use ( $expected_response ) {
						if ( '/wc/v3/payments/onboarding/test_drive_account/init' === $endpoint ) {
							return $expected_response;
						}
						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Act - call the method that should set and clear the lock.
		$result = $this->sut->onboarding_test_account_init( $location );

		// Assert that the result is successful.
		$this->assertEquals( $expected_response, $result );

		// Assert that the lock was set and then cleared.
		$this->assertCount( 2, $lock_states, 'Expected exactly 2 lock state changes: set lock (timestamp) and clear lock (0)' );
		$this->assertSame( $current_time, $lock_states[0], 'First lock state should be current timestamp' );
		$this->assertSame( 0, $lock_states[1], 'Second lock state should be 0 (cleared)' );
	}

	/**
	 * Test that onboarding lock is cleared even when an exception occurs.
	 */
	public function test_onboarding_lock_cleared_on_exception(): void {
		$location = 'US';

		// Arrange the WPCOM connection.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Track onboarding lock state changes.
		$lock_states  = array();
		$current_time = $this->current_time;

		// Arrange the NOX profile and lock tracking.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( &$lock_states ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return array();
					}
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return end( $lock_states ) !== false ? end( $lock_states ) : $default_value;
					}
					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( &$lock_states, $current_time ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						$lock_states[] = $value;
					}
					return true;
				},
			)
		);

		// Mock REST API to throw an exception.
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint ) {
						if ( '/wc/v3/payments/onboarding/test_drive_account/init' === $endpoint ) {
							throw new \Exception( 'Simulated API failure' );
						}
						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Act & Assert - expect the method to handle the exception and still clear the lock.
		try {
			$this->sut->onboarding_test_account_init( $location );

			$this->fail( 'Expected an exception to be re-thrown as WP_Error' );
		} catch ( \Throwable $e ) {
			// The exception handling should have cleared the lock even though an error occurred.
			$this->assertInstanceOf( \Exception::class, $e );
		}

		// Assert that the lock was set and then cleared despite the exception.
		$this->assertCount( 2, $lock_states, 'Expected exactly 2 lock state changes even with exception: set lock (timestamp) and clear lock (0)' );
		$this->assertSame( $current_time, $lock_states[0], 'First lock state should be current timestamp' );
		$this->assertSame( 0, $lock_states[1], 'Second lock state should be 0 (cleared) even after exception' );
	}

	/**
	 * Test onboarding_test_account_init.
	 *
	 * @return void
	 */
	public function test_onboarding_test_account_init() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working since it is a dependency for the step.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the NOX profile.
		$step_id                 = WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT;
		$started_timestamp       = $this->current_time - 100;
		$stored_profile          = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $started_timestamp,
							),
						),
					),
				),
			),
		);
		$updated_stored_profiles = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile, &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						// Chain the responses to simulate the sequence of DB updates.
						return ! empty( $updated_stored_profiles ) ? end( $updated_stored_profiles ) : $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profiles[] = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Arrange the REST API requests.
		$requests_made     = array();
		$expected_response = array(
			'success' => true,
		);
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_response ) {
						if ( '/wc/v3/payments/onboarding/test_drive_account/init' === $endpoint ) {
							$requests_made[] = $params;
							return $expected_response;
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Act.
		$result = $this->sut->onboarding_test_account_init( $location );

		// Assert.
		$this->assertEquals( $expected_response, $result );
		$this->assertCount( 1, $requests_made );
		$this->assertCount( 0, $updated_stored_profiles );
		// There is no automatic completion of the step due to its async nature.
	}

	/**
	 * Test get_onboarding_kyc_session throws exception when extension is not active.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_get_onboarding_kyc_session_throws_when_extension_not_active() {
		$location = 'US';

		// Arrange.
		// Mock the extension as not active.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_to_check ) {
					if ( '\WC_Payments' === $class_to_check ) {
						return false;
					}

					return true;
				},
			)
		);

		try {
			$this->sut->get_onboarding_kyc_session( $location );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_extension_not_active', $e->getErrorCode() );
		}
	}

	/**
	 * Test get_onboarding_kyc_session throws exception when onboarding is locked.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_get_onboarding_kyc_session_throws_with_onboarding_locked() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working since it is a dependency for the step.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return $this->current_time;
					}

					return $default_value;
				},
			)
		);

		try {
			$this->sut->get_onboarding_kyc_session( $location );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}
	}

	/**
	 * Test get_onboarding_kyc_session throws exception when step requirements are not met.
	 *
	 * @return void
	 */
	public function test_get_onboarding_kyc_session_throws_on_unmet_requirements() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it NOT working.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( false );

		try {
			$this->sut->get_onboarding_kyc_session( $location );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_step_requirements_not_met', $e->getErrorCode() );
		}
	}

	/**
	 * Test that get_onboarding_kyc_session throws an exception when the REST API call fails.
	 *
	 * @return void
	 * @throws \Exception On GET request not mocked.
	 */
	public function test_get_onboarding_kyc_session_throws_on_error_response() {
		// Arrange the WPCOM connection.
		// Make it working since it is a dependency for the step.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the REST API requests.
		$requests_made  = array();
		$expected_error = array(
			'code'    => 'error',
			'message' => 'Error message',
		);
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_error ) {
						if ( '/wc/v3/payments/onboarding/kyc/session' === $endpoint ) {
							$requests_made[] = $params;
							return new WP_Error( $expected_error['code'], $expected_error['message'] );
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Assert.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( $expected_error['message'] );

		// Act.
		$this->sut->get_onboarding_kyc_session( 'US' );
	}

	/**
	 * Test that get_onboarding_kyc_session throws an exception when the REST API call doesn't respond properly.
	 *
	 * @return void
	 * @throws \Exception On GET request not mocked.
	 */
	public function test_get_onboarding_kyc_session_throws_on_failure() {
		// Arrange the WPCOM connection.
		// Make it working since it is a dependency for the step.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the REST API requests.
		$requests_made = array();
		// Not an array.
		$expected_response = '';
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_response ) {
						if ( '/wc/v3/payments/onboarding/kyc/session' === $endpoint ) {
							$requests_made[] = $params;
							return $expected_response;
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Act.
		try {
			$this->sut->get_onboarding_kyc_session( 'US' );
		} catch ( ApiException $exception ) {
			$this->assertSame( 'woocommerce_woopayments_onboarding_client_api_error', $exception->getErrorCode() );
			$this->assertSame( 'Failed to get the KYC session data.', $exception->getMessage() );
		}
	}

	/**
	 * Test that get_onboarding_kyc_session uses stored data when no data is provided.
	 *
	 * @return void
	 * @throws \Exception On GET request not mocked.
	 */
	public function test_get_onboarding_kyc_session_uses_stored_data() {
		$step_id  = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the NOX profile.
		$self_assessment = array(
			'business_type' => 'company',
			'mcc'           => 1234,
		);
		$stored_profile  = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'data' => array(
								'self_assessment' => $self_assessment,
							),
						),
					),
				),
			),
		);
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
			)
		);

		// Arrange the REST API requests.
		$requests_made     = array();
		$expected_payload  = array(
			'self_assessment' => $self_assessment,
			'capabilities'    => array(),
		);
		$expected_response = array(
			'clientSecret'   => 'secret',
			'expiresAt'      => $this->current_time + 1000,
			'accountId'      => 'id',
			'isLive'         => false,
			'accountCreated' => false,
			'publishableKey' => 'key',
		);
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_response ) {
						if ( '/wc/v3/payments/onboarding/kyc/session' === $endpoint ) {
							$requests_made[] = $params;
							return $expected_response;
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Act.
		$result = $this->sut->get_onboarding_kyc_session( $location );

		// Assert.
		self::assertEquals( $expected_response + array( 'locale' => 'en_US' ), $result );
		self::assertCount( 1, $requests_made );
		self::assertEquals( $expected_payload, $requests_made[0] );
	}

	/**
	 * Test that get_onboarding_kyc_session uses received data, superseding the stored data.
	 *
	 * @return void
	 * @throws \Exception On GET request not mocked.
	 */
	public function test_get_onboarding_kyc_session_uses_received_data() {
		$step_id         = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;
		$location        = 'US';
		$self_assessment = array(
			'business_type' => 'individual',
			'mcc'           => 4567,
		);

		// Arrange the WPCOM connection.
		// Make it working.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the NOX profile.
		$stored_self_assessment = array(
			'business_type' => 'company',
			'mcc'           => 1234,
		);
		$stored_profile         = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'data' => array(
								'self_assessment' => $stored_self_assessment,
							),
						),
					),
				),
			),
		);
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) use ( $stored_profile ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_profile;
					}

					return $default_value;
				},
			)
		);

		// Arrange the REST API requests.
		$requests_made     = array();
		$expected_payload  = array(
			'self_assessment' => $self_assessment,
			'capabilities'    => array(),
		);
		$expected_response = array(
			'clientSecret'   => 'secret',
			'expiresAt'      => $this->current_time + 1000,
			'accountId'      => 'id',
			'isLive'         => false,
			'accountCreated' => false,
			'publishableKey' => 'key',
		);
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_response ) {
						if ( '/wc/v3/payments/onboarding/kyc/session' === $endpoint ) {
							$requests_made[] = $params;
							return $expected_response;
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Act.
		$result = $this->sut->get_onboarding_kyc_session(
			$location,
			$self_assessment
		);

		// Assert.
		self::assertEquals( $expected_response + array( 'locale' => 'en_US' ), $result );
		self::assertCount( 1, $requests_made );
		self::assertEquals( $expected_payload, $requests_made[0] );
	}

	/**
	 * Test finish_onboarding_kyc_session throws exception when extension is not active.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_finish_onboarding_kyc_session_throws_when_extension_not_active() {
		$location = 'US';

		// Arrange.
		// Mock the extension as not active.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_to_check ) {
					if ( '\WC_Payments' === $class_to_check ) {
						return false;
					}

					return true;
				},
			)
		);

		try {
			$this->sut->finish_onboarding_kyc_session( $location );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			self::assertEquals( 'woocommerce_woopayments_onboarding_extension_not_active', $e->getErrorCode() );
		}
	}

	/**
	 * Test finish_onboarding_kyc_session throws exception when onboarding is locked.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_finish_onboarding_kyc_session_throws_with_onboarding_locked() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working since it is a dependency for the step.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return $this->current_time;
					}

					return $default_value;
				},
			)
		);

		try {
			$this->sut->finish_onboarding_kyc_session( $location );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			self::assertEquals( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}
	}

	/**
	 * Test finish_onboarding_kyc_session throws exception when step requirements are not met.
	 *
	 * @return void
	 */
	public function test_finish_onboarding_kyc_session_throws_on_unmet_requirements() {
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it NOT working.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( false );

		try {
			$this->sut->finish_onboarding_kyc_session( $location );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_step_requirements_not_met', $e->getErrorCode() );
		}
	}

	/**
	 * Test that finish_onboarding_kyc_session throws an exception when the REST API call fails.
	 *
	 * @return void
	 * @throws \Exception On POST request not mocked.
	 */
	public function test_finish_onboarding_kyc_session_throws_on_error_response() {
		// Arrange the WPCOM connection.
		// Make it working since it is a dependency for the step.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the REST API requests.
		$requests_made  = array();
		$expected_error = array(
			'code'    => 'error',
			'message' => 'Error message',
		);
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_error ) {
						if ( '/wc/v3/payments/onboarding/kyc/finalize' === $endpoint ) {
							$requests_made[] = $params;
							return new WP_Error( $expected_error['code'], $expected_error['message'] );
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Assert.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( $expected_error['message'] );

		// Act.
		$this->sut->finish_onboarding_kyc_session( 'US' );
	}

	/**
	 * Test that finish_onboarding_kyc_session throws an exception when the REST API call doesn't respond properly.
	 *
	 * @return void
	 * @throws \Exception On POST request not mocked.
	 */
	public function test_finish_onboarding_kyc_session_throws_on_failure() {
		// Arrange the WPCOM connection.
		// Make it working since it is a dependency for the step.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the REST API requests.
		$requests_made = array();
		// Not an array.
		$expected_response = '';
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_response ) {
						if ( '/wc/v3/payments/onboarding/kyc/finalize' === $endpoint ) {
							$requests_made[] = $params;
							return $expected_response;
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Assert.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( esc_html__( 'Failed to finish the KYC session.', 'woocommerce' ) );

		// Act.
		$this->sut->finish_onboarding_kyc_session( 'US' );
	}

	/**
	 * Test finish_onboarding_kyc_session.
	 *
	 * @return void
	 * @throws \Exception On POST request not mocked.
	 */
	public function test_finish_onboarding_kyc_session() {
		$step_id  = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it working.
		// Without this, the step will not be marked as completed due to unmet step requirements.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the NOX profile.
		$started_timestamp       = $this->current_time - 100;
		$stored_profile          = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						$step_id => array(
							'statuses' => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $started_timestamp,
							),
						),
					),
				),
			),
		);
		$updated_stored_profiles = array();
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option'    => function ( $option_name, $default_value = null ) use ( $stored_profile, &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						// Chain the responses to simulate the sequence of DB updates.
						return ! empty( $updated_stored_profiles ) ? end( $updated_stored_profiles ) : $stored_profile;
					}

					return $default_value;
				},
				'update_option' => function ( $option_name, $value ) use ( $stored_profile, &$updated_stored_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$updated_stored_profiles[] = $value;

						// Mimic the behavior of the original function.
						if ( $value === $stored_profile || maybe_serialize( $value ) === maybe_serialize( $stored_profile ) ) {
							return false;
						}

						return true;
					}

					return true;
				},
			)
		);

		// Arrange the REST API requests.
		$requests_made     = array();
		$expected_payload  = array(
			'source' => WooPaymentsService::SESSION_ENTRY_DEFAULT,
			'from'   => WooPaymentsService::FROM_NOX_IN_CONTEXT,
		);
		$expected_response = array(
			'success'           => true,
			'details_submitted' => true,
			'account_id'        => 'id',
			'mode'              => 'test',
		);
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_response ) {
						if ( '/wc/v3/payments/onboarding/kyc/finalize' === $endpoint ) {
							$requests_made[] = $params;
							return $expected_response;
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Act.
		$result = $this->sut->finish_onboarding_kyc_session( $location );

		// Assert.
		self::assertEquals( $expected_response, $result );
		self::assertCount( 1, $requests_made );
		self::assertEquals( $expected_payload, $requests_made[0] );
		// One is from the step status update and one is from the test account being marked as completed.
		$this->assertCount( 2, $updated_stored_profiles );
		// The step status should have been set to `completed`.
		$this->assertEquals(
			array(
				'statuses' => array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED => $started_timestamp,
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $this->current_time,
				),
			),
			$updated_stored_profiles[1]['onboarding'][ $location ]['steps'][ $step_id ]
		);
		$this->assertEquals(
			array(
				'statuses' => array(
					WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED => $this->current_time,
				),
			),
			$updated_stored_profiles[1]['onboarding'][ $location ]['steps'][ WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT ]
		);
	}

	/**
	 * Test onboarding_preload throws exception when onboarding is locked.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_onboarding_preload_throws_with_onboarding_locked() {
		// Arrange.
		$location = 'US';

		// Arrange the WPCOM connection.
		// Make it not connected.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( false );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( false );

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return $this->current_time;
					}

					return $default_value;
				},
			)
		);

		// Act.
		try {
			$this->sut->onboarding_preload( $location );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}
	}

	/**
	 * Test that onboarding_preload throws an exception when the WPCOM connection registration fails.
	 *
	 * @return void
	 * @throws \Exception On POST request not mocked.
	 */
	public function test_onboarding_preload_throws_on_error_response() {
		// Arrange.
		$location = 'US';

		// Arrange the WPCOM connection.
		$expected_error = array(
			'code'    => 'error',
			'message' => 'Error message',
		);
		// Make it not connected.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( false );
		$this->mock_wpcom_connection_manager
			->expects( $this->once() )
			->method( 'try_registration' )
			->willReturn( new WP_Error( $expected_error['code'], $expected_error['message'] ) );

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return 0;
					}

					return $default_value;
				},
			)
		);

		// Assert.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( $expected_error['message'] );

		// Act.
		$this->sut->onboarding_preload( $location );
	}

	/**
	 * Test onboarding_preload.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_onboarding_preload() {
		// Arrange.
		$location                           = 'US';
		$expected_wpcom_registration_result = false;

		// Arrange the WPCOM connection.
		// Make it not connected.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( false );
		$this->mock_wpcom_connection_manager
			->expects( $this->once() )
			->method( 'try_registration' )
			->willReturn( $expected_wpcom_registration_result );

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return 0;
					}

					return $default_value;
				},
			)
		);

		// Act.
		$result = $this->sut->onboarding_preload( $location );

		// Assert.
		self::assertEquals(
			array(
				'success' => $expected_wpcom_registration_result,
			),
			$result
		);
	}

	/**
	 * Test reset_onboarding throws exception when extension is not active.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_reset_onboarding_throws_when_extension_not_active() {
		// Arrange.
		$location = 'US';
		// Mock the extension as not active.
		$this->mockable_proxy->register_function_mocks(
			array(
				'class_exists' => function ( $class_to_check ) {
					if ( '\WC_Payments' === $class_to_check ) {
						return false;
					}

					return true;
				},
			)
		);

		try {
			$this->sut->reset_onboarding( $location );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			self::assertEquals( 'woocommerce_woopayments_onboarding_extension_not_active', $e->getErrorCode() );
		}
	}

	/**
	 * Test reset_onboarding throws exception when onboarding is locked.
	 *
	 * @return void
	 * @throws \Exception When trying to mock uncallable user functions.
	 */
	public function test_reset_onboarding_throws_with_onboarding_locked() {
		// Arrange.
		$location = 'US';
		// Arrange the WPCOM connection.
		// Make it working since it is a dependency for the step.
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( true );
		$this->mock_wpcom_connection_manager
			->expects( $this->any() )
			->method( 'has_connected_owner' )
			->willReturn( true );

		// Arrange the onboarding locked DB option.
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $default_value = null ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name ) {
						return $this->current_time;
					}

					return $default_value;
				},
			)
		);

		try {
			$this->sut->reset_onboarding( $location );

			$this->fail( 'Expected ApiException not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertEquals( 'woocommerce_woopayments_onboarding_locked', $e->getErrorCode() );
		}
	}

	/**
	 * Test that reset_onboarding with a connected account throws an exception when the REST API call fails.
	 *
	 * @return void
	 * @throws \Exception On POST request not mocked.
	 */
	public function test_reset_onboarding_with_account_throws_on_error_response() {
		// Arrange.
		$location = 'US';

		// Arrange the account.
		$this->mock_provider
			->expects( $this->any() )
			->method( 'is_account_connected' )
			->willReturn( true );

		// Arrange the REST API requests.
		$requests_made  = array();
		$expected_error = array(
			'code'    => 'error',
			'message' => 'Error message',
		);
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_error ) {
						if ( '/wc/v3/payments/onboarding/reset' === $endpoint ) {
							$requests_made[] = $params;
							return new WP_Error( $expected_error['code'], $expected_error['message'] );
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Assert.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( $expected_error['message'] );

		// Act.
		$this->sut->reset_onboarding( $location );
	}

	/**
	 * Test that reset_onboarding with a connected account throws an exception when the REST API call doesn't respond properly.
	 *
	 * @return void
	 * @throws \Exception On POST request not mocked.
	 */
	public function test_reset_onboarding_with_account_throws_on_invalid_response() {
		// Arrange.
		$location = 'US';

		// Arrange the account.
		$this->mock_provider
			->expects( $this->any() )
			->method( 'is_account_connected' )
			->willReturn( true );

		// Arrange the REST API requests.
		$requests_made = array();
		// Not an array.
		$expected_response = '';
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_response ) {
						if ( '/wc/v3/payments/onboarding/reset' === $endpoint ) {
							$requests_made[] = $params;
							return $expected_response;
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Assert.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( esc_html__( 'Failed to reset onboarding.', 'woocommerce' ) );

		// Act.
		$this->sut->reset_onboarding( $location );
	}

	/**
	 * Test that reset_onboarding with a connected account throws an exception when the REST API call doesn't succeed.
	 *
	 * @return void
	 * @throws \Exception On POST request not mocked.
	 */
	public function test_reset_onboarding_with_account_throws_on_failure() {
		// Arrange.
		$location = 'US';

		// Arrange the account.
		$this->mock_provider
			->expects( $this->any() )
			->method( 'is_account_connected' )
			->willReturn( true );

		// Arrange the REST API requests.
		$requests_made = array();
		// Not an array.
		$expected_response = array( 'success' => false );
		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_response ) {
						if ( '/wc/v3/payments/onboarding/reset' === $endpoint ) {
							$requests_made[] = $params;
							return $expected_response;
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Assert.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( esc_html__( 'Failed to reset onboarding.', 'woocommerce' ) );

		// Act.
		$this->sut->reset_onboarding( $location );
	}

	/**
	 * Test reset_onboarding with a connected account.
	 *
	 * @return void
	 * @throws \Exception On POST request not mocked.
	 */
	public function test_reset_onboarding_with_account() {
		// Arrange.
		$location = 'US';

		// Arrange the account.
		$this->mock_provider
			->expects( $this->any() )
			->method( 'is_account_connected' )
			->willReturn( true );

		// Arrange the REST API requests.
		$requests_made     = array();
		$expected_payload  = array(
			'from'   => WooPaymentsService::FROM_PAYMENT_SETTINGS,
			'source' => WooPaymentsService::SESSION_ENTRY_DEFAULT,
		);
		$expected_response = array(
			'success' => true,
		);

		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made, $expected_response ) {
						if ( '/wc/v3/payments/onboarding/reset' === $endpoint ) {
							$requests_made[] = $params;

							return $expected_response;
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Arrange the DB options.
		$onboarding_lock_cleared = 0;
		$deleted_profiles        = 0;
		$this->mockable_proxy->register_function_mocks(
			array(
				'update_option' => function ( $option_name, $value ) use ( &$onboarding_lock_cleared ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name && 0 === $value ) {
						$onboarding_lock_cleared++;

						return true;
					}

					return true;
				},
				'delete_option' => function ( $option_name ) use ( &$deleted_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$deleted_profiles++;

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->reset_onboarding( $location );

		// Assert.
		self::assertEquals( $expected_response, $result );
		self::assertCount( 1, $requests_made );
		self::assertEquals( $expected_payload, $requests_made[0] );
		self::assertEquals( 1, $onboarding_lock_cleared ); // The onboarding lock should be cleared.
		self::assertEquals( 1, $deleted_profiles ); // The NOX profile option should be deleted.
	}

	/**
	 * Test reset_onboarding with no connected account.
	 *
	 * @return void
	 * @throws \Exception On POST request not mocked.
	 */
	public function test_reset_onboarding_without_account() {
		// Arrange.
		$location = 'US';

		// Arrange the account.
		$this->mock_provider
			->expects( $this->any() )
			->method( 'is_account_connected' )
			->willReturn( false );

		// Arrange the REST API requests.
		$requests_made     = array();
		$expected_response = array(
			'success' => true,
		);

		$this->mockable_proxy->register_static_mocks(
			array(
				Utils::class => array(
					'rest_endpoint_post_request' => function ( string $endpoint, array $params = array() ) use ( &$requests_made ) {
						if ( '/wc/v3/payments/onboarding/reset' === $endpoint ) {
							$requests_made[] = $params;

							return;
						}

						throw new \Exception( esc_html( 'POST endpoint response is not mocked: ' . $endpoint ) );
					},
				),
			)
		);

		// Arrange the DB options.
		$onboarding_lock_cleared = 0;
		$deleted_profiles        = 0;
		$this->mockable_proxy->register_function_mocks(
			array(
				'update_option' => function ( $option_name, $value ) use ( &$onboarding_lock_cleared ) {
					if ( WooPaymentsService::NOX_ONBOARDING_LOCKED_KEY === $option_name && 0 === $value ) {
						$onboarding_lock_cleared++;

						return true;
					}

					return true;
				},
				'delete_option' => function ( $option_name ) use ( &$deleted_profiles ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						$deleted_profiles++;

						return true;
					}

					return true;
				},
			)
		);

		// Act.
		$result = $this->sut->reset_onboarding( $location );

		// Assert.
		self::assertEquals( $expected_response, $result );
		self::assertCount( 0, $requests_made ); // No request should be made when there is no connected account.
		self::assertEquals( 1, $onboarding_lock_cleared ); // The onboarding lock should be cleared.
		self::assertEquals( 1, $deleted_profiles ); // The NOX profile option should be deleted.
	}

	/**
	 * Get the mock business types for onboarding fields.
	 *
	 * @return array[]
	 */
	private function get_mock_onboarding_fields_business_types(): array {
		return array(
			array(
				'key'   => 'US',
				'name'  => 'United States',
				'types' => array(
					array(
						'key'        => 'individual',
						'name'       => 'Individual',
						'structures' => array(),
					),
					array(
						'key'        => 'company',
						'name'       => 'Company',
						'structures' => array(
							array(
								'key'  => 'sole_proprietorship',
								'name' => 'Sole proprietorship',
							),
							array(
								'key'  => 'single_member_llc',
								'name' => 'Single-member LLC',
							),
							array(
								'key'  => 'multi_member_llc',
								'name' => 'Multi-member LLC',
							),
							array(
								'key'  => 'private_partnership',
								'name' => 'Private partnership',
							),
							array(
								'key'  => 'private_corporation',
								'name' => 'Private corporation',
							),
							array(
								'key'  => 'unincorporated_association',
								'name' => 'Unincorporated association',
							),
							array(
								'key'  => 'public_partnership',
								'name' => 'Public partnership',
							),
							array(
								'key'  => 'public_corporation',
								'name' => 'Public corporation',
							),
						),
					),
					array(
						'key'        => 'non_profit',
						'name'       => 'Non-profit',
						'structures' => array(
							array(
								'key'  => 'incorporated_non_profit',
								'name' => 'Incorporated non-profit',
							),
							array(
								'key'  => 'unincorporated_non_profit',
								'name' => 'Unincorporated non-profit',
							),
						),
					),
					array(
						'key'        => 'government_entity',
						'name'       => 'Government entity',
						'structures' => array(
							array(
								'key'  => 'governmental_unit',
								'name' => 'Governmental unit',
							),
							array(
								'key'  => 'government_instrumentality',
								'name' => 'Government instrumentality',
							),
							array(
								'key'  => 'tax_exempt_government_instrumentality',
								'name' => 'Tax exempt government instrumentality',
							),
						),
					),
				),
			),
		);
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

	/**
	 * Test get_onboarding_payment_methods_state falls back to OR logic when no explicit apple_google stored.
	 *
	 * @return void
	 */
	public function test_get_onboarding_payment_methods_state_fallback_or_logic() {
		$location        = 'US';
		$recommended_pms = array(
			array(
				'id'       => 'apple_pay',
				'enabled'  => true,
				'required' => false,
			),
			array(
				'id'       => 'google_pay',
				'enabled'  => false,
				'required' => false,
			),
		);

		// Create NOX profile data structure with apple_pay and google_pay but no apple_google key.
		$nox_profile_data = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						'payment_methods' => array(
							'data' => array(
								'payment_methods' => array(
									'apple_pay'  => 'false',
									'google_pay' => 'false',
									// No apple_google key - should fall back to OR logic.
								),
							),
						),
					),
				),
			),
		);

		$this->mock_nox_profile_option( $nox_profile_data );

		// Mock the provider to return recommended payment methods.
		$this->mock_provider
			->expects( $this->once() )
			->method( 'get_recommended_payment_methods' )
			->with( $this->isInstanceOf( FakePaymentGateway::class ), $location )
			->willReturn( $recommended_pms );

		$result = $this->invoke_private_method( 'get_onboarding_payment_methods_state', array( $location, null ) );

		$this->assertArrayHasKey( 'apple_google', $result );
		$this->assertTrue( $result['apple_google'], 'apple_google should be true when using OR fallback logic (apple_pay=true || google_pay=false)' );
	}

	/**
	 * Test get_onboarding_payment_methods_state uses recommended values when no stored state.
	 *
	 * @return void
	 */
	public function test_get_onboarding_payment_methods_state_uses_recommended_when_no_stored_state() {
		$location        = 'US';
		$recommended_pms = array(
			array(
				'id'       => 'apple_pay',
				'enabled'  => true,
				'required' => false,
			),
			array(
				'id'       => 'google_pay',
				'enabled'  => false,
				'required' => false,
			),
		);

		// Create empty NOX profile data structure (no stored state).
		$nox_profile_data = array();

		$this->mock_nox_profile_option( $nox_profile_data );

		$this->mock_provider
			->expects( $this->once() )
			->method( 'get_recommended_payment_methods' )
			->with( $this->isInstanceOf( FakePaymentGateway::class ), $location )
			->willReturn( $recommended_pms );

		$result = $this->invoke_private_method( 'get_onboarding_payment_methods_state', array( $location, null ) );

		$this->assertArrayHasKey( 'apple_google', $result );
		$this->assertTrue( $result['apple_google'], 'apple_google should be true when using recommended values with OR logic (true || false = true)' );
	}

	/**
	 * Test get_onboarding_payment_methods_state prefers explicit stored apple_google over OR logic.
	 *
	 * @return void
	 */
	public function test_get_onboarding_payment_methods_state_prefers_explicit_apple_google() {
		$location        = 'US';
		$recommended_pms = array(
			array(
				'id'       => 'apple_pay',
				'enabled'  => true,
				'required' => false,
			),
			array(
				'id'       => 'google_pay',
				'enabled'  => true,
				'required' => false,
			),
		);
		// Create NOX profile data structure with explicit apple_google override.
		$nox_profile_data = array(
			'onboarding' => array(
				$location => array(
					'steps' => array(
						'payment_methods' => array(
							'data' => array(
								'payment_methods' => array(
									'apple_google' => 'false',
								),
							),
						),
					),
				),
			),
		);

		$this->mock_nox_profile_option( $nox_profile_data );

		$result = $this->invoke_private_method( 'get_onboarding_payment_methods_state', array( $location, $recommended_pms ) );
		$this->assertArrayHasKey( 'apple_google', $result );
		$this->assertFalse( $result['apple_google'], 'Explicit stored apple_google should take precedence over OR/recommended.' );
	}

	/**
	 * Helper method to invoke private methods for testing.
	 *
	 * @param string $method_name The name of the private method.
	 * @param array  $args        The arguments to pass to the method.
	 * @return mixed The result of the method call.
	 */
	private function invoke_private_method( string $method_name, array $args = array() ) {
		$reflection = new \ReflectionClass( $this->sut );
		if ( ! $reflection->hasMethod( $method_name ) ) {
			$this->fail( sprintf( 'Method %s not found on %s', $method_name, get_class( $this->sut ) ) );
		}
		$method = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $this->sut, $args );
	}

	/**
	 * Helper method to mock data entry in the NOX profile.
	 *
	 * @param array $stored_data The step data to mock.
	 * @return void
	 */
	private function mock_nox_profile_option( array $stored_data ): void {
		$this->mockable_proxy->register_function_mocks(
			array(
				'get_option' => function ( $option_name, $fallback = false ) use ( $stored_data ) {
					if ( WooPaymentsService::NOX_PROFILE_OPTION_KEY === $option_name ) {
						return $stored_data;
					}
					return $fallback;
				},
			)
		);
	}
}
