<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutBridge;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsWooPaySessionService;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsCheckoutBridge class.
 */
class WooPaymentsCheckoutBridgeTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'wcpay_payment_fields_js_config' );
		parent::tearDown();
	}

	/**
	 * @testdox Should preserve the card checkout config shape and filter it through wcpay_payment_fields_js_config.
	 */
	public function test_get_payment_fields_js_config_preserves_card_checkout_shape(): void {
		$legacy_runtime  = $this->create_legacy_runtime_for_bridge();
		$account_service = $this->create_account_service_for_bridge( true );
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn(
			array(
				'name'  => 'Test Customer',
				'email' => 'merchant@example.com',
			)
		);
		$legacy_runtime->method( 'can_handle_checkout_bridge_callbacks' )->willReturn( true );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( true ) );

		add_filter(
			'wcpay_payment_fields_js_config',
			static function ( array $config ): array {
				$config['filtered'] = 'yes';
				return $config;
			}
		);

		$config = $bridge->get_payment_fields_js_config();

		$this->assertSame( 'pk_test_123', $config['publishableKey'] );
		$this->assertSame( 'acct_123', $config['accountId'] );
		$this->assertSame( 'woocommerce_payments', $config['gatewayId'] );
		$this->assertTrue( $config['testMode'] );
		$this->assertSame( 'yes', $config['filtered'] );
		$this->assertArrayHasKey( 'paymentMethodsConfig', $config );
		$this->assertSame( 'Card', $config['paymentMethodsConfig']['card']['title'] );
		$this->assertSame( 'Card', $config['paymentMethodsConfig']['card']['label'] );
		$this->assertSame( 'visa', $config['paymentMethodsConfig']['card']['cardBrandIcons'][0]['id'] );
		$this->assertSame( 'Visa', $config['paymentMethodsConfig']['card']['cardBrandIcons'][0]['alt'] );
		$this->assertStringContainsString( '/assets/images/payment-methods/visa.svg', $config['paymentMethodsConfig']['card']['cardBrandIcons'][0]['src'] );
		$this->assertStringContainsString( '4000 0064 2000 0001', $config['paymentMethodsConfig']['card']['testingInstructions'] );
		$this->assertStringContainsString( 'js-woopayments-copy-test-number', $config['paymentMethodsConfig']['card']['testingInstructions'] );
		$this->assertStringContainsString( 'Click to copy the test number to clipboard', $config['paymentMethodsConfig']['card']['testingInstructions'] );
		$this->assertStringContainsString( 'testing guide', $config['paymentMethodsConfig']['card']['testingInstructions'] );
		$this->assertArrayHasKey( 'enabledBillingFields', $config );
		$this->assertArrayHasKey( 'currency', $config );
		$this->assertArrayHasKey( 'cartTotal', $config );
		$this->assertSame( defined( 'WC_VERSION' ) ? WC_VERSION : '', $config['wcpayVersionNumber'] );
		$this->assertArrayHasKey( 'createSetupIntentNonce', $config );
		$this->assertArrayHasKey( 'updateOrderStatusNonce', $config );
		$this->assertSame( 'https://pay.woo.com', $config['woopayHost'] );
		$this->assertSame( '12345', $config['woopayMerchantId'] );
		$this->assertArrayHasKey( 'initWooPayNonce', $config );
		$this->assertArrayNotHasKey( 'woopayInitNonce', $config );
		$this->assertArrayHasKey( 'woopaySessionNonce', $config );
		$this->assertArrayHasKey( 'woopaySignatureNonce', $config );
		$this->assertSame( array( 'encrypted' => 'minimum' ), $config['woopayMinimumSessionData'] );
		$this->assertFalse( $config['usesLegacySetupIntentBridge'] );
		$this->assertFalse( $config['usesLegacyOrderStatusBridge'] );
		$this->assertTrue( $config['usesNativeSetupIntentBridge'] );
		$this->assertTrue( $config['usesNativeOrderStatusBridge'] );
	}

	/**
	 * @testdox Should enqueue core-owned checkout assets when rendering payment fields.
	 */
	public function test_payment_fields_enqueues_core_owned_assets_and_preserves_wcpay_config_filter(): void {
		$legacy_runtime  = $this->create_legacy_runtime_for_bridge();
		$account_service = $this->create_account_service_for_bridge( true );
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn( array() );
		$legacy_runtime->method( 'can_handle_checkout_bridge_callbacks' )->willReturn( true );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( true ) );

		add_filter(
			'wcpay_payment_fields_js_config',
			static function ( array $config ): array {
				$config['filtered'] = 'yes';
				return $config;
			}
		);

		ob_start();
		$bridge->render_payment_fields();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'wcpay-core-checkout-form', $output );
		$this->assertStringContainsString( 'wcpay-core-test-mode-instructions', $output );
		$this->assertStringContainsString( '4000 0064 2000 0001', $output );
		$this->assertStringContainsString( 'js-woopayments-copy-test-number', $output );
		$this->assertStringContainsString( 'data-wcpay-config', $output );
		$this->assertStringContainsString( 'filtered', $output );
		$this->assertTrue( wp_script_is( 'wc-woopayments-checkout', 'enqueued' ) );
		$this->assertStringContainsString(
			'/assets/js/frontend/woopayments-checkout',
			wp_scripts()->registered['wc-woopayments-checkout']->src
		);
	}

	/**
	 * @testdox Should expose native bridge nonces independently from the removed legacy callback bridge.
	 */
	public function test_get_payment_fields_js_config_exposes_native_bridge_nonces_when_checkout_surface_is_available(): void {
		$legacy_runtime  = $this->create_legacy_runtime_for_bridge();
		$account_service = $this->create_account_service_for_bridge( true );
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn( array() );
		$legacy_runtime->method( 'can_handle_checkout_bridge_callbacks' )->willReturn( false );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( true ) );

		$config = $bridge->get_payment_fields_js_config();

		$this->assertArrayHasKey( 'createSetupIntentNonce', $config );
		$this->assertArrayHasKey( 'updateOrderStatusNonce', $config );
		$this->assertFalse( $config['usesLegacySetupIntentBridge'] );
		$this->assertFalse( $config['usesLegacyOrderStatusBridge'] );
		$this->assertTrue( $config['usesNativeSetupIntentBridge'] );
		$this->assertTrue( $config['usesNativeOrderStatusBridge'] );
	}

	/**
	 * @testdox Should hide checkout surface controls when the Core-owned account readiness fails.
	 */
	public function test_get_payment_fields_js_config_hides_native_controls_when_account_is_not_ready(): void {
		$legacy_runtime  = $this->create_legacy_runtime_for_bridge();
		$account_service = $this->create_account_service_for_bridge( false );
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn( array() );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( true ) );

		$config = $bridge->get_payment_fields_js_config();

		$this->assertSame( 'pk_test_123', $config['publishableKey'] );
		$this->assertSame( 'acct_123', $config['accountId'] );
		$this->assertFalse( $config['isCoreNativeCheckoutAvailable'] );
		$this->assertArrayNotHasKey( 'createSetupIntentNonce', $config );
		$this->assertArrayNotHasKey( 'updateOrderStatusNonce', $config );
		$this->assertArrayNotHasKey( 'initWooPayNonce', $config );
		$this->assertArrayNotHasKey( 'woopaySessionNonce', $config );
		$this->assertArrayNotHasKey( 'woopaySignatureNonce', $config );
		$this->assertArrayNotHasKey( 'woopayMerchantId', $config );
		$this->assertArrayNotHasKey( 'woopayMinimumSessionData', $config );
	}

	/**
	 * @testdox Should omit encrypted WooPay payloads when WooPay is disabled.
	 */
	public function test_get_payment_fields_js_config_omits_encrypted_woopay_payload_when_woopay_is_disabled(): void {
		$legacy_runtime  = $this->create_legacy_runtime_for_bridge();
		$account_service = $this->create_account_service_for_bridge( true );
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn( array() );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( false ) );

		$config = $bridge->get_payment_fields_js_config();

		$this->assertArrayHasKey( 'createSetupIntentNonce', $config );
		$this->assertArrayHasKey( 'updateOrderStatusNonce', $config );
		$this->assertArrayNotHasKey( 'initWooPayNonce', $config );
		$this->assertArrayNotHasKey( 'woopaySessionNonce', $config );
		$this->assertArrayNotHasKey( 'woopaySignatureNonce', $config );
		$this->assertArrayNotHasKey( 'woopayMinimumSessionData', $config );
	}

	/**
	 * Create a legacy runtime mock for checkout bridge tests.
	 *
	 * @return WooPaymentsLegacyRuntime|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_legacy_runtime_for_bridge() {
		$legacy_runtime = $this->getMockBuilder( WooPaymentsLegacyRuntime::class )
			->disableOriginalConstructor()
			->onlyMethods(
				array(
					'get_gateway_publishable_key',
					'get_gateway_account_id',
					'get_gateway_prepared_customer_data',
					'get_gateway_upe_enabled_payment_method_ids',
					'can_handle_checkout_bridge_callbacks',
				)
			)
			->getMock();

		$legacy_runtime
			->expects( $this->never() )
			->method( 'get_gateway_publishable_key' );
		$legacy_runtime
			->expects( $this->never() )
			->method( 'get_gateway_account_id' );
		$legacy_runtime
			->method( 'get_gateway_upe_enabled_payment_method_ids' )
			->willReturn( array( 'card' ) );

		return $legacy_runtime;
	}

	/**
	 * Create an account service mock for checkout bridge tests.
	 *
	 * @param bool $can_process_payments Whether the account can process payments.
	 * @return WooPaymentsAccountService|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_account_service_for_bridge( bool $can_process_payments ) {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_publishable_key', 'get_account_id', 'get_cached_account_data', 'can_process_payments', 'is_test_mode_enabled' ) )
			->getMock();

		$account_service
			->method( 'get_publishable_key' )
			->willReturn( 'pk_test_123' );
		$account_service
			->method( 'get_account_id' )
			->willReturn( 'acct_123' );
		$account_service
			->method( 'get_cached_account_data' )
			->willReturn(
				array(
					'country' => 'RO',
				)
			);
		$account_service
			->method( 'can_process_payments' )
			->willReturn( $can_process_payments );
		$account_service
			->method( 'is_test_mode_enabled' )
			->willReturn( true );

		return $account_service;
	}

	/**
	 * Create a WooPay session service mock for checkout bridge tests.
	 *
	 * @param bool $enabled Whether WooPay is enabled.
	 * @return WooPaymentsWooPaySessionService|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_woopay_session_service_for_bridge( bool $enabled ) {
		$service = $this->getMockBuilder( WooPaymentsWooPaySessionService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_woopay_enabled', 'get_woopay_url', 'get_woopay_merchant_id', 'get_encrypted_minimum_session_data' ) )
			->getMock();

		$service->method( 'is_woopay_enabled' )->willReturn( $enabled );
		$service->method( 'get_woopay_url' )->willReturn( 'https://pay.woo.com' );
		$service->method( 'get_woopay_merchant_id' )->willReturn( '12345' );
		$service->method( 'get_encrypted_minimum_session_data' )->willReturn( array( 'encrypted' => 'minimum' ) );

		return $service;
	}
}
