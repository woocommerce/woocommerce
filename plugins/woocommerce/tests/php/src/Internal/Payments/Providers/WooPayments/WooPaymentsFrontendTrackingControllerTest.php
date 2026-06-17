<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsFrontendTrackingController;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsFrontendTrackingController class.
 */
class WooPaymentsFrontendTrackingControllerTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_actions( 'wp_ajax_platform_tracks' );
		remove_all_actions( 'wp_ajax_nopriv_platform_tracks' );
		remove_all_actions( 'wp_ajax_get_identity' );
		remove_all_actions( 'wp_ajax_nopriv_get_identity' );
		remove_all_filters( 'wcpay_tracks_event_properties' );
		remove_all_filters( 'wcpay_shopper_tracking_enabled' );
		remove_all_filters( 'wp_doing_ajax' );
		remove_all_filters( 'pre_http_request' );
		unset( $_COOKIE['tk_opt-out'] );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @testdox Should register platform Tracks AJAX hooks when native owns runtime.
	 */
	public function test_registers_platform_tracks_ajax_hooks_when_native_owns_runtime(): void {
		$sut = $this->create_controller( true );

		$sut->register();

		$this->assertSame( 10, has_action( 'wp_ajax_platform_tracks', array( $sut, 'handle_tracks' ) ) );
		$this->assertSame( 10, has_action( 'wp_ajax_nopriv_platform_tracks', array( $sut, 'handle_tracks' ) ) );
		$this->assertSame( 10, has_action( 'wp_ajax_get_identity', array( $sut, 'handle_tracks_identity' ) ) );
		$this->assertSame( 10, has_action( 'wp_ajax_nopriv_get_identity', array( $sut, 'handle_tracks_identity' ) ) );
	}

	/**
	 * @testdox Should not register platform Tracks AJAX hooks when native runtime is inactive.
	 */
	public function test_does_not_register_ajax_hooks_when_native_runtime_is_inactive(): void {
		$sut = $this->create_controller( false );

		$sut->register();

		$this->assertFalse( has_action( 'wp_ajax_platform_tracks', array( $sut, 'handle_tracks' ) ) );
		$this->assertFalse( has_action( 'wp_ajax_nopriv_platform_tracks', array( $sut, 'handle_tracks' ) ) );
		$this->assertFalse( has_action( 'wp_ajax_get_identity', array( $sut, 'handle_tracks_identity' ) ) );
		$this->assertFalse( has_action( 'wp_ajax_nopriv_get_identity', array( $sut, 'handle_tracks_identity' ) ) );
	}

	/**
	 * @testdox Should reject platform Tracks requests with invalid nonces.
	 */
	public function test_tracks_response_rejects_invalid_nonce(): void {
		$sut = $this->create_controller( true );

		$response = $sut->get_tracks_response(
			array(
				'tracksNonce'     => 'bad',
				'tracksEventName' => 'woopay_button_click',
			)
		);

		$this->assertFalse( $response['success'] );
		$this->assertSame( 403, $response['status_code'] );
		$this->assertSame( 'You aren’t authorized to do that.', $response['data'] );
	}

	/**
	 * @testdox Should require a platform Tracks event name.
	 */
	public function test_tracks_response_requires_event_name(): void {
		$sut = $this->create_controller( true );

		$response = $sut->get_tracks_response(
			array(
				'tracksNonce' => wp_create_nonce( 'platform_tracks_nonce' ),
			)
		);

		$this->assertFalse( $response['success'] );
		$this->assertSame( 403, $response['status_code'] );
		$this->assertSame( 'No valid event name or type.', $response['data'] );
	}

	/**
	 * @testdox Should record prefixed WooPayments shopper events and apply the reference filter.
	 */
	public function test_records_prefixed_wcpay_event_and_applies_reference_filter(): void {
		$captured_url = '';
		$user_id      = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		update_option( 'woocommerce_allow_tracking', 'yes' );
		update_option( 'woocommerce_default_country', 'US:CA' );
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wcpay_tracks_event_properties',
			static function ( array $properties, string $event_name ): array {
				$properties['filtered_prop']  = 'yes';
				$properties['filtered_event'] = $event_name;
				return $properties;
			},
			10,
			2
		);
		add_filter(
			'pre_http_request',
			static function ( $preempt, $parsed_args, $url ) use ( &$captured_url ) {
				$captured_url = $url;

				return array(
					'headers'  => array(),
					'body'     => '',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			},
			10,
			3
		);
		$sut = $this->create_controller( true, $this->create_account_service( true ) );

		$response = $sut->get_tracks_response(
			array(
				'tracksNonce'     => wp_create_nonce( 'platform_tracks_nonce' ),
				'tracksEventName' => 'woopay_button_click',
				'tracksEventProp' => wp_json_encode( array( 'source' => 'checkout' ) ),
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 200, $response['status_code'] );
		$this->assertNotSame( '', $captured_url );
		parse_str( (string) wp_parse_url( $captured_url, PHP_URL_QUERY ), $pixel_args );
		$this->assertSame( 'wcpay_woopay_button_click', $pixel_args['_en'] );
		$this->assertSame( 'anon', $pixel_args['_ut'] );
		$this->assertStringStartsWith( 'jetpack:', $pixel_args['_ui'] );
		$this->assertSame( 'checkout', $pixel_args['source'] );
		$this->assertSame( 'yes', $pixel_args['filtered_prop'] );
		$this->assertSame( 'wcpay_woopay_button_click', $pixel_args['filtered_event'] );
		$this->assertSame( '1', $pixel_args['test_mode'] );
	}

	/**
	 * @testdox Should preserve WooPayments Jetpack identity meta continuity.
	 */
	public function test_tracks_identity_prefers_jetpack_identity_meta(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		update_user_meta( $user_id, 'jetpack_tracks_anon_id', 'jetpack:anon-id' );
		wp_set_current_user( $user_id );

		$sut = $this->create_controller( true );

		$response = $sut->get_tracks_identity_response();

		$this->assertTrue( $response['success'] );
		$this->assertSame( 200, $response['status_code'] );
		$this->assertSame( 'anon', $response['data']['_ut'] );
		$this->assertSame( 'jetpack:anon-id', $response['data']['_ui'] );
		$this->assertSame( '', get_user_meta( $user_id, '_woocommerce_tracks_anon_id', true ) );
	}

	/**
	 * Create the controller under test.
	 *
	 * @param bool                           $native_register Whether native runtime owns WooPayments.
	 * @param WooPaymentsAccountService|null $account_service Account service double.
	 * @return WooPaymentsFrontendTrackingController
	 */
	private function create_controller( bool $native_register, ?WooPaymentsAccountService $account_service = null ): WooPaymentsFrontendTrackingController {
		$arbiter = $this->createMock( NativePaymentsRuntimeArbiter::class );
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$sut = new WooPaymentsFrontendTrackingController();
		$sut->init( $arbiter, $account_service ?? $this->create_account_service( true ) );

		return $sut;
	}

	/**
	 * Create an account service double.
	 *
	 * @param bool $test_mode Whether the account is in test mode.
	 * @return WooPaymentsAccountService
	 */
	private function create_account_service( bool $test_mode ): WooPaymentsAccountService {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'can_process_payments', 'get_cached_account_data', 'get_gateway_setting', 'is_test_mode_enabled' ) )
			->getMock();

		$account_service->method( 'can_process_payments' )->willReturn( true );
		$account_service->method( 'get_cached_account_data' )->willReturn(
			array(
				'country'                    => 'US',
				'platform_checkout_eligible' => true,
			)
		);
		$account_service->method( 'get_gateway_setting' )->willReturnMap(
			array(
				array( 'platform_checkout', 'no', 'yes' ),
			)
		);
		$account_service->method( 'is_test_mode_enabled' )->willReturn( $test_mode );

		return $account_service;
	}
}
