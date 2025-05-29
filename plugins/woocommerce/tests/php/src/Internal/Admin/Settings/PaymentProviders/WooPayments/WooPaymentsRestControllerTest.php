<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentProviders\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiException;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders\WooPayments\WooPaymentsRestController;
use PHPUnit\Framework\MockObject\MockObject;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

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

		$this->mock_payments_service    = $this->getMockBuilder( Payments::class )->getMock();
		$this->mock_woopayments_service = $this->getMockBuilder( WooPaymentsService::class )->getMock();

		$this->sut = new WooPaymentsRestController();
		$this->sut->init( $this->mock_payments_service, $this->mock_woopayments_service );
		$this->sut->register_routes( true );
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
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/onboarding' );
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

		$this->mock_onboarding_details( $country_code );

		// Act.
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/onboarding' );
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

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test getting onboarding details without specifying a location.
	 *
	 * It should default to the providers stored location.
	 */
	public function test_get_onboarding_details_with_no_location() {
		// Arrange.
		$country_code = 'LI'; // Liechtenstein.
		$this->mock_providers_country( $country_code );
		$this->mock_onboarding_details( $country_code );

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/onboarding' );
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
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/onboarding' );
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
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/onboarding' );
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
