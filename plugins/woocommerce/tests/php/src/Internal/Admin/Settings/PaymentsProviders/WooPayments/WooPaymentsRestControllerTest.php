<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiException;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsOverviewService;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsRestController;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPmPromotionsService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsSettingsService;
use PHPUnit\Framework\MockObject\MockObject;
use WC_REST_Unit_Test_Case;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Server;

/**
 * WooPaymentsRestController API controller test.
 *
 * @class WooPaymentsRestController
 */
class WooPaymentsRestControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/settings/payments/woopayments';

	/**
	 * @var WooPaymentsRestController
	 */
	protected WooPaymentsRestController $sut;

	/**
	 * @var MockObject|Payments
	 */
	private $mock_payments_service;

	/**
	 * @var MockObject|WooPaymentsService
	 */
	private $mock_woopayments_service;

	/**
	 * @var MockObject|WooPaymentsSettingsService
	 */
	private $mock_settings_service;

	/**
	 * @var MockObject|WooPaymentsPmPromotionsService
	 */
	private $mock_pm_promotions_service;

	/**
	 * @var MockObject|WooPaymentsOverviewService
	 */
	private $mock_overview_service;

	/**
	 * @var MockObject|NativePaymentsRuntimeArbiter
	 */
	private $mock_runtime_arbiter;

	/**
	 * The ID of the store admin user.
	 *
	 * @var int
	 */
	protected $store_admin_id;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->store_admin_id );

		$this->mock_payments_service      = $this->getMockBuilder( Payments::class )->getMock();
		$this->mock_woopayments_service   = $this->getMockBuilder( WooPaymentsService::class )->getMock();
		$this->mock_settings_service      = $this->getMockBuilder( WooPaymentsSettingsService::class )->getMock();
		$this->mock_pm_promotions_service = $this->getMockBuilder( WooPaymentsPmPromotionsService::class )
			->onlyMethods( array( 'get_visible_promotions', 'activate_promotion', 'dismiss_promotion' ) )
			->getMock();
		$this->mock_overview_service      = $this->getMockBuilder( WooPaymentsOverviewService::class )
			->onlyMethods( array( 'get_overview' ) )
			->getMock();
		$this->mock_runtime_arbiter       = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$this->mock_runtime_arbiter
			->method( 'should_native_register' )
			->willReturn( true );

		$this->sut = new WooPaymentsRestController();
		$this->sut->init( $this->mock_payments_service, $this->mock_woopayments_service, $this->mock_settings_service, $this->mock_runtime_arbiter, $this->mock_pm_promotions_service, $this->mock_overview_service );
		$this->sut->register_routes( true );
	}

	/**
	 * Test getting onboarding details by a user without the needed capabilities.
	 */
	public function test_get_onboarding_details_by_user_without_caps() {
		// Arrange.
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce'          => false,
			// This is needed.
						'install_plugins' => true,
// This is not needed.
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
			'manage_woocommerce'          => true,
			// This is needed.
						'install_plugins' => false,
// This is not needed.
		);
		add_filter( 'user_has_cap', $filter_callback );

		$this->mock_onboarding_details( $country_code );

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

		// Check that the step has all the fields.
		$step = $data['steps'][0];
		$this->assertArrayHasKey( 'id', $step );
		$this->assertArrayHasKey( 'path', $step );
		$this->assertArrayHasKey( 'required_steps', $step );
		$this->assertArrayHasKey( 'status', $step );
		$this->assertArrayHasKey( 'errors', $step );
		$this->assertArrayHasKey( 'actions', $step );
		$this->assertArrayHasKey( 'context', $step );
		// Check that we have all the actions.
		$this->assertArrayHasKey( 'start', $step['actions'] );
		$this->assertArrayHasKey( 'save', $step['actions'] );
		$this->assertArrayHasKey( 'check', $step['actions'] );
		$this->assertArrayHasKey( 'finish', $step['actions'] );
		$this->assertArrayHasKey( 'auth', $step['actions'] );
		$this->assertArrayHasKey( 'init', $step['actions'] );
		$this->assertArrayHasKey( 'kyc_session', $step['actions'] );
		$this->assertArrayHasKey( 'kyc_session_finish', $step['actions'] );
		$this->assertArrayHasKey( 'kyc_fallback', $step['actions'] );
		$this->assertArrayHasKey( 'clean', $step['actions'] );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * @testdox Should return the native WooPayments account summary for users with permission.
	 */
	public function test_get_account_summary_by_manager() {
		$summary = array(
			'account' => array(
				'id'                   => 'acct_native_test',
				'mode'                 => 'test',
				'default_currency'     => 'usd',
				'connected'            => true,
				'working'              => true,
				'can_process_payments' => true,
				'test_mode'            => true,
				'test_drive'           => true,
				'sandbox'              => false,
				'live'                 => false,
			),
			'urls'    => array(
				'overview_page' => 'https://example.com/overview',
			),
		);

		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'get_account_summary' )
			->willReturn( $summary );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/account' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $summary, $response->get_data() );
	}

	/**
	 * @testdox Should block the native WooPayments account summary for users without permission.
	 */
	public function test_get_account_summary_by_user_without_caps() {
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => false,
			'install_plugins'    => true,
		);
		add_filter( 'user_has_cap', $filter_callback );

		$this->mock_woopayments_service
			->expects( $this->never() )
			->method( 'get_account_summary' );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/account' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );

		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * @testdox Should return an account summary error when the service throws.
	 */
	public function test_get_account_summary_with_exception() {
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'get_account_summary' )
			->willThrowException( new \Exception( 'Account summary unavailable.' ) );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/account' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_woopayments_account_error', $response->get_data()['code'] );
		$this->assertSame( 'Account summary unavailable.', $response->get_data()['message'] );
	}

	/**
	 * @testdox Should return the native WooPayments Overview projection for users with permission.
	 */
	public function test_get_overview_by_manager(): void {
		$overview = array(
			'account'                               => array(
				'id'        => 'acct_native_test',
				'connected' => true,
			),
			'account_status'                        => array(
				'status' => 'restricted_soon',
			),
			'show_update_details_task'              => true,
			'overview_tasks_visibility'             => array(
				'dismissed_todo_tasks'       => array( 'old-task' ),
				'deleted_todo_tasks'         => array(),
				'remind_me_later_todo_tasks' => array(),
			),
			'is_connection_success_modal_dismissed' => false,
			'wpcom_reconnect_url'                   => '',
			'urls'                                  => array(
				'overview_page' => 'https://example.com/overview',
			),
		);

		$this->mock_overview_service
			->expects( $this->once() )
			->method( 'get_overview' )
			->willReturn( $overview );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/overview' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $overview, $response->get_data() );
	}

	/**
	 * @testdox Should block the native WooPayments Overview projection for users without permission.
	 */
	public function test_get_overview_by_user_without_caps(): void {
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => false,
			'install_plugins'    => true,
		);
		add_filter( 'user_has_cap', $filter_callback );

		$this->mock_overview_service
			->expects( $this->never() )
			->method( 'get_overview' );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/overview' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );

		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * @testdox Should return an Overview projection error when the service throws.
	 */
	public function test_get_overview_with_exception(): void {
		$this->mock_overview_service
			->expects( $this->once() )
			->method( 'get_overview' )
			->willThrowException( new \Exception( 'Overview unavailable.' ) );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/overview' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_woopayments_overview_error', $response->get_data()['code'] );
		$this->assertSame( 'Overview unavailable.', $response->get_data()['message'] );
	}

	/**
	 * @testdox Should expose the legacy-compatible native WooPayments settings GET route.
	 */
	public function test_get_native_settings_contract_by_manager(): void {
		$settings = array(
			'is_wcpay_enabled'           => true,
			'enabled_payment_method_ids' => array( 'card' ),
		);

		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'get_settings' )
			->willReturn( $settings );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/settings' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $settings, $response->get_data() );
	}

	/**
	 * @testdox Should persist the native WooPayments settings contract through the legacy-compatible POST route.
	 */
	public function test_update_native_settings_contract_by_manager(): void {
		$settings = array(
			'is_wcpay_enabled'           => true,
			'enabled_payment_method_ids' => array( 'card', 'link' ),
		);

		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'update_settings' )
			->with(
				$this->callback(
					static function ( array $params ): bool {
						return true === $params['is_wcpay_enabled']
							&& array( 'card', 'link' ) === $params['enabled_payment_method_ids'];
					}
				)
			)
			->willReturn( $settings );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/settings' );
		$request->set_body_params(
			array(
				'is_wcpay_enabled'           => true,
				'enabled_payment_method_ids' => array( 'card', 'link' ),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $settings, $response->get_data() );
	}

	/**
	 * @testdox Should round-trip the fraud settings error sentinel through the native settings POST route.
	 */
	public function test_update_native_settings_accepts_fraud_error_sentinel(): void {
		$settings = array(
			'is_debug_log_enabled'               => true,
			'advanced_fraud_protection_settings' => 'error',
		);

		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'update_settings' )
			->with(
				$this->callback(
					static function ( array $params ): bool {
						return true === $params['is_debug_log_enabled']
							&& 'error' === $params['advanced_fraud_protection_settings'];
					}
				)
			)
			->willReturn( $settings );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/settings' );
		$request->set_body_params(
			array(
				'is_debug_log_enabled'               => true,
				'advanced_fraud_protection_settings' => 'error',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $settings, $response->get_data() );
	}

	/**
	 * @testdox Should reject unknown fraud settings strings before settings persistence.
	 */
	public function test_update_native_settings_rejects_unknown_fraud_settings_string(): void {
		$this->mock_settings_service
			->expects( $this->never() )
			->method( 'update_settings' );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/settings' );
		$request->set_body_params(
			array(
				'advanced_fraud_protection_settings' => 'not_a_ruleset',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertArrayHasKey( 'advanced_fraud_protection_settings', $response->get_data()['data']['params'] );
	}

	/**
	 * @testdox Should reject invalid payment method IDs before settings persistence.
	 */
	public function test_update_native_settings_rejects_invalid_payment_method_ids(): void {
		$this->mock_settings_service
			->expects( $this->never() )
			->method( 'update_settings' );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/settings' );
		$request->set_body_params(
			array(
				'enabled_payment_method_ids' => array( 'card', 'not_a_payment_method' ),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertArrayHasKey( 'enabled_payment_method_ids', $response->get_data()['data']['params'] );
	}

	/**
	 * @testdox Should reject duplicate payment method IDs before settings persistence.
	 */
	public function test_update_native_settings_rejects_duplicate_payment_method_ids(): void {
		$this->mock_settings_service
			->expects( $this->never() )
			->method( 'update_settings' );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/settings' );
		$request->set_body_params(
			array(
				'enabled_payment_method_ids' => array( 'card', 'link', 'link' ),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertArrayHasKey( 'enabled_payment_method_ids', $response->get_data()['data']['params'] );
	}

	/**
	 * @testdox Should expose visible native WooPayments payment method promotions.
	 */
	public function test_get_native_pm_promotions_by_manager(): void {
		$promotions = array(
			array(
				'id'             => 'klarna-promo__spotlight',
				'payment_method' => 'klarna',
				'type'           => 'spotlight',
			),
		);

		$this->mock_pm_promotions_service
			->expects( $this->once() )
			->method( 'get_visible_promotions' )
			->willReturn( $promotions );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/pm-promotions' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $promotions, $response->get_data() );
	}

	/**
	 * @testdox Should activate native WooPayments payment method promotions.
	 */
	public function test_activate_native_pm_promotion_by_manager(): void {
		$this->mock_pm_promotions_service
			->expects( $this->once() )
			->method( 'activate_promotion' )
			->with( 'klarna-promo__spotlight' )
			->willReturn( true );

		$request  = new WP_REST_Request( 'POST', '/wc/v3/payments/pm-promotions/klarna-promo__spotlight/activate' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'success' => true ), $response->get_data() );
	}

	/**
	 * @testdox Should dismiss native WooPayments payment method promotions.
	 */
	public function test_dismiss_native_pm_promotion_by_manager(): void {
		$this->mock_pm_promotions_service
			->expects( $this->once() )
			->method( 'dismiss_promotion' )
			->with( 'klarna-promo__spotlight' )
			->willReturn( true );

		$request  = new WP_REST_Request( 'POST', '/wc/v3/payments/pm-promotions/klarna-promo__spotlight/dismiss' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'success' => true ), $response->get_data() );
	}

	/**
	 * @testdox Should reject encoded path separators in promotion action routes.
	 */
	public function test_native_pm_promotion_routes_reject_encoded_path_separators(): void {
		$this->mock_pm_promotions_service
			->expects( $this->never() )
			->method( 'activate_promotion' );

		$request  = new WP_REST_Request( 'POST', '/wc/v3/payments/pm-promotions/klarna%2Fbad/activate' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @testdox Should require payment gateway management permission for native payment method promotions.
	 */
	public function test_get_native_pm_promotions_requires_manager_permission(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
		$this->mock_pm_promotions_service
			->expects( $this->never() )
			->method( 'get_visible_promotions' );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/pm-promotions' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * @testdox Should accept nullable monthly anchor and keyed support address objects.
	 */
	public function test_update_native_settings_accepts_nullable_anchor_and_keyed_address(): void {
		$settings = array( 'deposit_schedule_monthly_anchor' => null );

		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'update_settings' )
			->with(
				$this->callback(
					static function ( array $params ): bool {
						return array_key_exists( 'deposit_schedule_monthly_anchor', $params )
							&& null === $params['deposit_schedule_monthly_anchor']
							&& array(
								'city'        => 'San Francisco',
								'country'     => 'US',
								'line1'       => '60 29th Street',
								'postal_code' => '94110',
							) === $params['account_business_support_address'];
					}
				)
			)
			->willReturn( $settings );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/settings' );
		$request->set_body_params(
			array(
				'deposit_schedule_monthly_anchor'  => null,
				'account_business_support_address' => array(
					'city'        => 'San Francisco',
					'country'     => 'US',
					'line1'       => '60 29th Street',
					'postal_code' => '94110',
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $settings, $response->get_data() );
	}

	/**
	 * @testdox Should not register native WooPayments settings routes while the standalone plugin owns runtime.
	 */
	public function test_native_settings_routes_are_not_registered_when_native_runtime_does_not_own_site(): void {
		global $wp_rest_server;

		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		$runtime_arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$runtime_arbiter
			->method( 'should_native_register' )
			->willReturn( false );

		$sut = new WooPaymentsRestController();
		$sut->init( $this->mock_payments_service, $this->mock_woopayments_service, $this->mock_settings_service, $runtime_arbiter );
		$sut->register_routes( true );

		$routes = $this->server->get_routes();

		$this->assertArrayNotHasKey( '/wc/v3/payments/settings', $routes );
		$this->assertArrayNotHasKey( '/wc/v3/payments/pm-promotions', $routes );
		$this->assertArrayNotHasKey( '/wc/v3/payments/file', $routes );
		$this->assertArrayHasKey( self::ENDPOINT . '/onboarding', $routes );
	}

	/**
	 * @testdox Should update only allowlisted native WooPayments settings options.
	 */
	public function test_update_native_settings_option_by_manager(): void {
		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'update_option' )
			->with( 'wcpay_fraud_protection_welcome_tour_dismissed', true )
			->willReturn( true );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/settings/wcpay_fraud_protection_welcome_tour_dismissed' );
		$request->set_body_params( array( 'value' => true ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'success' => true ), $response->get_data() );
	}

	/**
	 * @testdox Should return option update errors from the native WooPayments settings service.
	 */
	public function test_update_native_settings_option_returns_service_error(): void {
		$error = new WP_Error(
			'woocommerce_woopayments_invalid_settings_option',
			'Invalid option.',
			array( 'status' => 400 )
		);

		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'update_option' )
			->with( 'not_allowed', true )
			->willReturn( $error );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/settings/not_allowed' );
		$request->set_body_params( array( 'value' => true ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_woopayments_invalid_settings_option', $response->get_data()['code'] );
	}

	/**
	 * @testdox Should delegate native WooPayments settings file uploads.
	 */
	public function test_upload_native_settings_file_delegates_to_settings_service(): void {
		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'upload_file' )
			->with( $this->isInstanceOf( WP_REST_Request::class ) )
			->willReturn( array( 'id' => 'file_test_logo' ) );

		$request  = new WP_REST_Request( 'POST', '/wc/v3/payments/file' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'id' => 'file_test_logo' ), $response->get_data() );
	}

	/**
	 * @testdox Should delegate native WooPayments settings file detail and content routes.
	 */
	public function test_get_native_settings_file_detail_and_content_routes_delegate_to_settings_service(): void {
		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'get_file' )
			->with( 'file_test_logo', false )
			->willReturn(
				array(
					'id'      => 'file_test_logo',
					'purpose' => 'business_logo',
				)
			);
		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'get_file_contents' )
			->with( 'file_test_logo', true )
			->willReturn(
				array(
					'content_type' => 'image/png',
					'file_content' => 'TE9HTw==',
				)
			);

		$detail_request  = new WP_REST_Request( 'GET', '/wc/v3/payments/file/file_test_logo/details' );
		$detail_response = $this->server->dispatch( $detail_request );
		$content_request = new WP_REST_Request( 'GET', '/wc/v3/payments/file/file_test_logo/content' );
		$content_request->set_param( 'as_account', true );
		$content_response = $this->server->dispatch( $content_request );

		$this->assertSame( 200, $detail_response->get_status() );
		$this->assertSame( 'business_logo', $detail_response->get_data()['purpose'] );
		$this->assertSame( 200, $content_response->get_status() );
		$this->assertSame( 'image/png', $content_response->get_data()['content_type'] );
		$this->assertSame( 'TE9HTw==', $content_response->get_data()['file_content'] );
	}

	/**
	 * @testdox Should serve public WooPayments business logo files without payment manager permissions.
	 */
	public function test_get_native_public_settings_file_serves_public_logo_without_permissions(): void {
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = static fn( $caps ) => array(
			'manage_woocommerce' => false,
			'install_plugins'    => false,
		);
		add_filter( 'user_has_cap', $filter_callback );

		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'get_file' )
			->with( 'file_test_logo', false )
			->willReturn(
				array(
					'id'      => 'file_test_logo',
					'purpose' => 'business_logo',
				)
			);
		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'get_file_contents' )
			->with( 'file_test_logo', false )
			->willReturn(
				array(
					'content_type' => 'image/png',
					'file_content' => 'TE9HTw==',
				)
			);

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/file/file_test_logo' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'LOGO', $response->get_data() );
		$this->assertSame( 'image/png', $response->get_headers()['Content-Type'] );
		$this->assertSame( 'inline', $response->get_headers()['Content-Disposition'] );

		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * @testdox Should normalize missing public WooPayments files to a not found response.
	 */
	public function test_get_native_public_settings_file_normalizes_missing_file_errors(): void {
		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'get_file' )
			->with( 'file_missing', false )
			->willReturn(
				new WP_Error(
					'resource_missing',
					'No such file.',
					array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
				)
			);
		$this->mock_settings_service
			->expects( $this->never() )
			->method( 'get_file_contents' );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/file/file_missing' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::NOT_FOUND, $response->get_status() );
		$this->assertSame( 'resource_missing', $response->get_data()['code'] );
	}

	/**
	 * @testdox Should normalize public WooPayments file content errors to an internal error response.
	 */
	public function test_get_native_public_settings_file_normalizes_content_errors(): void {
		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'get_file' )
			->with( 'file_test_logo', false )
			->willReturn(
				array(
					'id'      => 'file_test_logo',
					'purpose' => 'business_logo',
				)
			);
		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'get_file_contents' )
			->with( 'file_test_logo', false )
			->willReturn(
				new WP_Error(
					'provider_timeout',
					'Unable to retrieve file contents.',
					array( 'status' => WP_Http::BAD_GATEWAY )
				)
			);

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/file/file_test_logo' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::INTERNAL_SERVER_ERROR, $response->get_status() );
		$this->assertSame( 'provider_timeout', $response->get_data()['code'] );
	}

	/**
	 * @testdox Should block non-public WooPayments files without payment manager permissions.
	 */
	public function test_get_native_public_settings_file_blocks_private_files_without_permissions(): void {
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = static fn( $caps ) => array(
			'manage_woocommerce' => false,
			'install_plugins'    => false,
		);
		add_filter( 'user_has_cap', $filter_callback );

		$this->mock_settings_service
			->expects( $this->once() )
			->method( 'get_file' )
			->with( 'file_private', false )
			->willReturn(
				array(
					'id'      => 'file_private',
					'purpose' => 'dispute_evidence',
				)
			);
		$this->mock_settings_service
			->expects( $this->never() )
			->method( 'get_file_contents' );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/file/file_private' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cannot_view', $response->get_data()['code'] );

		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test getting onboarding details without specifying a location.
	 *
	 * It should default to the providers stored location.
	 */
	public function test_get_onboarding_details_with_no_location() {
		// Arrange.
		$country_code = 'LI';
		// Liechtenstein.
		$this->mock_providers_country( $country_code );
		$this->mock_onboarding_details( $country_code );

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
		$this->mock_woopayments_service
			->expects( $this->never() )
			->method( 'get_onboarding_details' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test getting onboarding details responds with error on exception.
	 */
	public function test_get_onboarding_details_with_exception() {
		// Arrange.
		$country_code = 'US';
		$this->mock_providers_country( $country_code );

		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'get_onboarding_details' )
			->willThrowException( new \Exception( 'Test exception' ) );

		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'Test exception', $response->get_data()['message'] );
	}

	/**
	 * Test handling onboarding step start.
	 */
	public function test_onboarding_step_start() {
		// Arrange.
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$this->mock_woopayments_service
			->expects( $this->exactly( 2 ) )
			->method( 'get_onboarding_step_status' )
			->with( $step_id, $country_code )
			->willReturnOnConsecutiveCalls(
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED
			);

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
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$this->mock_woopayments_service
			->expects( $this->never() )
			->method( 'mark_onboarding_step_started' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/start' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test handling onboarding step start with exception.
	 */
	public function test_onboarding_step_start_with_exception() {
		// Arrange.
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$expected_code      = 'test_exception';
		$expected_message   = 'Test exception message.';
		$expected_http_code = 123;
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'mark_onboarding_step_started' )
			->willThrowException( new ApiException( $expected_code, $expected_message, $expected_http_code ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/start' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( $expected_code, $response->get_data()['code'] );
		$this->assertSame( $expected_message, $response->get_data()['message'] );
		$this->assertSame( $expected_http_code, $response->get_status() );
	}

	/**
	 * Test handling onboarding step save.
	 */
	public function test_onboarding_step_save() {
		// Arrange.
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$request_params = array(
			'key'         => 'value',
			'another_key' => 'another_value',
		);
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'onboarding_step_save' )
			->with(
				$step_id,
				$country_code,
				$this->callback(
					function ( $params ) use ( $request_params ) {
						// Check that the request parameters are passed correctly.
						foreach ( $request_params as $key => $value ) {
							if ( ! isset( $params[ $key ] ) || $params[ $key ] !== $value ) {
								return false;
							}
						}

						return true;
					}
				)
			)
			->willReturn( true );

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
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$this->mock_woopayments_service
			->expects( $this->never() )
			->method( 'onboarding_step_save' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/save' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test handling onboarding step save with exception.
	 */
	public function test_onboarding_step_save_with_exception() {
		// Arrange.
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$expected_code      = 'test_exception';
		$expected_message   = 'Test exception message.';
		$expected_http_code = 123;
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'onboarding_step_save' )
			->willThrowException( new ApiException( $expected_code, $expected_message, $expected_http_code ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/save' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( $expected_code, $response->get_data()['code'] );
		$this->assertSame( $expected_message, $response->get_data()['message'] );
		$this->assertSame( $expected_http_code, $response->get_status() );
	}

	/**
	 * Test handling onboarding step check.
	 */
	public function test_onboarding_step_check() {
		// Arrange.
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'onboarding_step_check' )
			->with( $step_id, $country_code )
			->willReturn(
				array(
					'status' => WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				)
			);

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
		$this->assertSame( WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED, $data['status'] );
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
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$this->mock_woopayments_service
			->expects( $this->never() )
			->method( 'onboarding_step_check' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/check' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test handling onboarding step check with exception.
	 */
	public function test_onboarding_step_check_with_exception() {
		// Arrange.
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$expected_code      = 'test_exception';
		$expected_message   = 'Test exception message.';
		$expected_http_code = 123;
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'onboarding_step_check' )
			->willThrowException( new ApiException( $expected_code, $expected_message, $expected_http_code ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/check' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( $expected_code, $response->get_data()['code'] );
		$this->assertSame( $expected_message, $response->get_data()['message'] );
		$this->assertSame( $expected_http_code, $response->get_status() );
	}

	/**
	 * Test handling onboarding step finish.
	 */
	public function test_onboarding_step_finish() {
		// Arrange.
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$this->mock_woopayments_service
			->expects( $this->exactly( 2 ) )
			->method( 'get_onboarding_step_status' )
			->with( $step_id, $country_code )
			->willReturnOnConsecutiveCalls(
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED
			);

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
		$this->assertSame( WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED, $data['previous_status'] );
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
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$this->mock_woopayments_service
			->expects( $this->never() )
			->method( 'mark_onboarding_step_completed' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/finish' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test handling onboarding step finish with exception.
	 */
	public function test_onboarding_step_finish_with_exception() {
		// Arrange.
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$expected_code      = 'test_exception';
		$expected_message   = 'Test exception message.';
		$expected_http_code = 123;
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'mark_onboarding_step_completed' )
			->willThrowException( new ApiException( $expected_code, $expected_message, $expected_http_code ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/finish' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( $expected_code, $response->get_data()['code'] );
		$this->assertSame( $expected_message, $response->get_data()['message'] );
		$this->assertSame( $expected_http_code, $response->get_status() );
	}

	/**
	 * Test handling onboarding step clean.
	 */
	public function test_onboarding_step_clean() {
		// Arrange.
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$this->mock_woopayments_service
			->expects( $this->exactly( 2 ) )
			->method( 'get_onboarding_step_status' )
			->with( $step_id, $country_code )
			->willReturnOnConsecutiveCalls(
				WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
				WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED
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
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$this->mock_woopayments_service
			->expects( $this->never() )
			->method( 'clean_onboarding_step_progress' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/clean' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test handling onboarding step clean with exception.
	 */
	public function test_onboarding_step_clean_with_exception() {
		// Arrange.
		$step_id      = 'step1';
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$expected_code      = 'test_exception';
		$expected_message   = 'Test exception message.';
		$expected_http_code = 123;
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'clean_onboarding_step_progress' )
			->willThrowException( new ApiException( $expected_code, $expected_message, $expected_http_code ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/clean' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( $expected_code, $response->get_data()['code'] );
		$this->assertSame( $expected_message, $response->get_data()['message'] );
		$this->assertSame( $expected_http_code, $response->get_status() );
	}

	/**
	 * Test onboarding test account init.
	 */
	public function test_onboarding_test_account_init() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT;
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );
		$source = 'test_source';

		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'mark_onboarding_step_started' )
			->with( $step_id, $country_code )
			->willReturn( true );

		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'onboarding_test_account_init' )
			->with( $country_code, $source )
			->willReturn(
				array(
					'some_data' => 'some_value',
				)
			);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/init' );
		$request->set_param( 'location', $country_code );
		$request->set_param( 'source', $source );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'some_data', $data );
		$this->assertSame( 'some_value', $data['some_data'] );
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
		$step_id      = WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT;
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$this->mock_woopayments_service
			->expects( $this->never() )
			->method( 'onboarding_test_account_init' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/init' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test onboarding test account init with exception.
	 */
	public function test_onboarding_test_account_init_with_exception() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT;
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$expected_code      = 'test_exception';
		$expected_message   = 'Test exception message.';
		$expected_http_code = 123;
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'onboarding_test_account_init' )
			->willThrowException( new ApiException( $expected_code, $expected_message, $expected_http_code ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/init' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( $expected_code, $response->get_data()['code'] );
		$this->assertSame( $expected_message, $response->get_data()['message'] );
		$this->assertSame( $expected_http_code, $response->get_status() );
	}

	/**
	 * Test onboarding business verification step KYC session init.
	 */
	public function test_onboarding_business_verification_step_kyc_session_init() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );
		$self_assessment = array(
			'some_data' => 'some_value',
		);
		$session_data    = array(
			'some_session_data' => 'some_session_value',
		);

		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'get_onboarding_kyc_session' )
			->with( $country_code )
			->willReturn( $session_data );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/kyc_session' );
		$request->set_param( 'location', $country_code );
		$request->set_param( 'self_assessment', $self_assessment );
		$response = $this->server->dispatch( $request );

		// Assert.
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
		$step_id      = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$this->mock_woopayments_service
			->expects( $this->never() )
			->method( 'get_onboarding_kyc_session' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/kyc_session' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test onboarding business verification step KYC session init with exception.
	 */
	public function test_onboarding_business_verification_step_kyc_session_init_with_exception() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$expected_code      = 'test_exception';
		$expected_message   = 'Test exception message.';
		$expected_http_code = 123;
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'get_onboarding_kyc_session' )
			->willThrowException( new ApiException( $expected_code, $expected_message, $expected_http_code ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/kyc_session' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( $expected_code, $response->get_data()['code'] );
		$this->assertSame( $expected_message, $response->get_data()['message'] );
		$this->assertSame( $expected_http_code, $response->get_status() );
	}

	/**
	 * Test onboarding business verification step KYC session finish.
	 */
	public function test_onboarding_business_verification_step_kyc_session_finish() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );
		$source          = 'test_source';
		$finish_response = array(
			'some_data' => 'some_value',
		);

		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'finish_onboarding_kyc_session' )
			->with( $country_code, $source )
			->willReturn( $finish_response );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/kyc_session/finish' );
		$request->set_param( 'location', $country_code );
		$request->set_param( 'source', $source );
		$response = $this->server->dispatch( $request );

		// Assert.
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
		$step_id      = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$this->mock_woopayments_service
			->expects( $this->never() )
			->method( 'finish_onboarding_kyc_session' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/kyc_session/finish' );
		$request->set_param( 'location', $location );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Test onboarding business verification step KYC session finish with exception.
	 */
	public function test_onboarding_business_verification_step_kyc_session_finish_with_exception() {
		// Arrange.
		$step_id      = WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION;
		$country_code = 'US';
		$this->mock_onboarding_details( $country_code );

		$expected_code      = 'test_exception';
		$expected_message   = 'Test exception message.';
		$expected_http_code = 123;
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'finish_onboarding_kyc_session' )
			->willThrowException( new ApiException( $expected_code, $expected_message, $expected_http_code ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/step/' . $step_id . '/kyc_session/finish' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( $expected_code, $response->get_data()['code'] );
		$this->assertSame( $expected_message, $response->get_data()['message'] );
		$this->assertSame( $expected_http_code, $response->get_status() );
	}

	/**
	 * Test onboarding preload.
	 */
	public function test_handle_onboarding_preload() {
		// Arrange.
		$country_code = 'US';

		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'onboarding_preload' )
			->with( $country_code )
			->willReturn( array( 'success' => true ) );

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
	 * Test onboarding preload with exception.
	 */
	public function test_handle_onboarding_preload_with_exception() {
		// Arrange.
		$country_code = 'US';

		$expected_code      = 'test_exception';
		$expected_message   = 'Test exception message.';
		$expected_http_code = 123;
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'onboarding_preload' )
			->willThrowException( new ApiException( $expected_code, $expected_message, $expected_http_code ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/preload' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( $expected_code, $response->get_data()['code'] );
		$this->assertSame( $expected_message, $response->get_data()['message'] );
		$this->assertSame( $expected_http_code, $response->get_status() );
	}

	/**
	 * Test onboarding reset.
	 */
	public function test_onboarding_reset() {
		// Arrange.
		$location = 'US';
		$from     = 'test-from';
		$source   = 'test-source';

		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'reset_onboarding' )
			->with( $location, $from, $source )
			->willReturn( array( 'success' => true ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/reset' );
		$request->set_param( 'location', $location );
		$request->set_param( 'from', $from );
		$request->set_param( 'source', $source );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
	}

	/**
	 * Test onboarding reset with exception.
	 */
	public function test_onboarding_reset_with_exception() {
		// Arrange.
		$location = 'US';
		$from     = 'test-from';
		$source   = 'test-source';

		$expected_code      = 'test_exception';
		$expected_message   = 'Test exception message.';
		$expected_http_code = 123;
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'reset_onboarding' )
			->willThrowException( new ApiException( $expected_code, $expected_message, $expected_http_code ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/reset' );
		$request->set_param( 'location', $location );
		$request->set_param( 'from', $from );
		$request->set_param( 'source', $source );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( $expected_code, $response->get_data()['code'] );
		$this->assertSame( $expected_message, $response->get_data()['message'] );
		$this->assertSame( $expected_http_code, $response->get_status() );
	}

	/**
	 * Test disable test account.
	 */
	public function test_disable_test_account() {
		// Arrange.
		$location = 'US';
		$from     = 'test-from';
		$source   = 'test-source';

		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_country' )
			->willReturn( $location );
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'disable_test_account' )
			->with( $location, $from, $source )
			->willReturn( array( 'success' => true ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/test_account/disable' );
		$request->set_param( 'from', $from );
		$request->set_param( 'source', $source );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
	}

	/**
	 * Test disable test account with exception.
	 */
	public function test_disable_test_account_with_exception() {
		// Arrange.
		$location = 'US';
		$from     = 'test-from';
		$source   = 'test-source';

		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_country' )
			->willReturn( $location );

		$expected_code      = 'test_exception';
		$expected_message   = 'Test exception message.';
		$expected_http_code = 123;
		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'disable_test_account' )
			->with( $location, $from, $source )
			->willThrowException( new ApiException( $expected_code, $expected_message, $expected_http_code ) );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/onboarding/test_account/disable' );
		$request->set_param( 'from', $from );
		$request->set_param( 'source', $source );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( $expected_code, $response->get_data()['code'] );
		$this->assertSame( $expected_message, $response->get_data()['message'] );
		$this->assertSame( $expected_http_code, $response->get_status() );
	}

	/**
	 * Mock the onboarding details with the given country code.
	 *
	 * @param string $country_code The country code to mock.
	 */
	private function mock_onboarding_details( string $country_code ) {
		$mock_onboarding_details = array(
			'state'   => array(
				'started'   => false,
				'completed' => false,
				'test_mode' => true,
				'dev_mode'  => true,
			),
			'steps'   => array(
				array(
					'id'             => 'step1',
					'path'           => '/step1',
					'required_steps' => array(),
					'status'         => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					'errors'         => array(
						'error_message_1',
						'error_message_2',
					),
					'actions'        => array(
						'start'              => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/start' ),
						),
						'save'               => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/save' ),
						),
						'check'              => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/check' ),
						),
						'finish'             => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/finish' ),
						),
						'auth'               => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/auth' ),
						),
						'init'               => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/init' ),
						),
						'kyc_session'        => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/kyc_session' ),
						),
						'kyc_session_finish' => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/kyc_session/finish' ),
						),
						'kyc_fallback'       => array(
							'type' => WooPaymentsService::ACTION_TYPE_REDIRECT,
							'href' => 'https://example.com/kyc_fallback',
						),
						'clean'              => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/clean' ),
						),
					),
					'context'        => array(),
				),
				// Add a step that requires the previous step to be completed.
				array(
					'id'             => 'step2',
					'path'           => '/step2',
					'required_steps' => array( 'step1' ),
					'status'         => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					'errors'         => array(),
					'actions'        => array(
						'start'  => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step2/start' ),
						),
						'save'   => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step2/save' ),
						),
						'check'  => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step2/check' ),
						),
						'finish' => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step2/finish' ),
						),
						// No auth step for this step.
						// No init step for this step.
						// No kyc_session step for this step.
						// No kyc_session_finish step for this step.
						// No kyc_fallback step for this step.
						'clean'  => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step2/clean' ),
						),
					),
					'context'        => array(),
				),
			),
			'context' => array(
				'urls' => array(
					'overview_page' => 'https://example.com/overview',
				),
			),
		);

		$this->mock_woopayments_service
			->method( 'get_onboarding_details' )
			->with( $country_code, $this->anything() )
			->willReturn( $mock_onboarding_details );

		// Ensure that only the mocked step IDs are valid.
		$this->mock_woopayments_service
			->method( 'is_valid_onboarding_step_id' )
			->willReturnCallback(
				function ( $step_id ) use ( $mock_onboarding_details ) {
					foreach ( $mock_onboarding_details['steps'] as $step ) {
						if ( $step['id'] === $step_id ) {
							return true;
						}
					}

					return false;
				}
			);
	}

	/**
	 * Mock the providers country to return the given country code.
	 *
	 * @param string $country_code The country code to return.
	 *
	 * @return void
	 */
	private function mock_providers_country( string $country_code ) {
		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_country' )
			->willReturn( $country_code );
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
}
