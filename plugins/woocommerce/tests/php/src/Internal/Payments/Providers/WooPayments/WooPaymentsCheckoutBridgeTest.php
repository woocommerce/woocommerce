<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutBridge;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsFrontendStylesService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsFrontendTrackingController;
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
		wp_set_current_user( 0 );
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
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( true ), $this->create_frontend_styles_service_for_bridge(), $this->create_frontend_tracking_controller_for_bridge() );

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
		$this->assertFalse( $config['paymentMethodsConfig']['card']['forceNetworkSavedCards'] );
		$this->assertSame( 'visa', $config['paymentMethodsConfig']['card']['cardBrandIcons'][0]['id'] );
		$this->assertSame( 'Visa', $config['paymentMethodsConfig']['card']['cardBrandIcons'][0]['alt'] );
		$this->assertStringContainsString( '/assets/images/payment-methods-cards/visa.svg', $config['paymentMethodsConfig']['card']['cardBrandIcons'][0]['src'] );
		$this->assertStringContainsString( '4000 0064 2000 0001', $config['paymentMethodsConfig']['card']['testingInstructions'] );
		$this->assertStringContainsString( 'js-woopayments-copy-test-number', $config['paymentMethodsConfig']['card']['testingInstructions'] );
		$this->assertStringContainsString( 'Click to copy the test number to clipboard', $config['paymentMethodsConfig']['card']['testingInstructions'] );
		$this->assertStringContainsString( 'testing guide', $config['paymentMethodsConfig']['card']['testingInstructions'] );
		$this->assertArrayHasKey( 'enabledBillingFields', $config );
		$this->assertArrayHasKey( 'currency', $config );
		$this->assertArrayHasKey( 'cartTotal', $config );
		$this->assertFalse( $config['cartContainsSubscription'] );
		$this->assertSame( 'styles-v1', $config['stylesCacheVersion'] );
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
		$this->assertTrue( $config['isWooPayEnabled'] );
		$this->assertTrue( $config['isWoopayExpressCheckoutEnabled'] );
		$this->assertTrue( $config['isWooPayEmailInputEnabled'] );
		$this->assertFalse( $config['isWooPayDirectCheckoutEnabled'] );
		$this->assertFalse( $config['isWooPayGlobalThemeSupportEnabled'] );
		$this->assertFalse( $config['forceNetworkSavedCards'] );
		$this->assertArrayHasKey( 'platformTrackerNonce', $config );
		$this->assertSame(
			array(
				'type'    => 'default',
				'theme'   => 'dark',
				'height'  => '48',
				'radius'  => '4',
				'size'    => 'default',
				'context' => 'checkout',
			),
			$config['woopayButton']
		);
		$this->assertArrayHasKey( 'woopayButtonNonce', $config );
		$this->assertArrayHasKey( 'addToCartNonce', $config );
		$this->assertTrue( $config['shouldShowWooPayButton'] );
		$this->assertSame( 'shopper@example.com', $config['woopaySessionEmail'] );
		$this->assertTrue( $config['woopayIsCountryAvailable'] );
		$this->assertNull( $config['woopayAppearance'] );
		$this->assertSame( array(), $config['woopayFontRules'] );
		$this->assertSame( 'Securely save my information for 1-click checkout', $config['woopaySaveUserLabel'] );
		$this->assertSame( 'Mobile phone number', $config['woopayPhoneLabel'] );
		$this->assertArrayHasKey( 'isShopperTrackingEnabled', $config );
		$this->assertFalse( $config['usesLegacySetupIntentBridge'] );
		$this->assertFalse( $config['usesLegacyOrderStatusBridge'] );
		$this->assertTrue( $config['usesNativeSetupIntentBridge'] );
		$this->assertTrue( $config['usesNativeOrderStatusBridge'] );
	}

	/**
	 * @testdox Should expose card platform checkout config independently from WooPay frontend config.
	 */
	public function test_card_platform_checkout_config_is_independent_from_woopay_frontend_config(): void {
		$legacy_runtime  = $this->create_legacy_runtime_for_bridge();
		$account_service = $this->create_account_service_for_bridge(
			true,
			array(
				'country'                    => 'US',
				'platform_checkout_eligible' => true,
			),
			array(
				'force_network_saved_cards' => 'yes',
			)
		);
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn( array() );
		$legacy_runtime->method( 'can_handle_checkout_bridge_callbacks' )->willReturn( true );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( false ), $this->create_frontend_styles_service_for_bridge(), $this->create_frontend_tracking_controller_for_bridge() );

		$config = $bridge->get_payment_fields_js_config();

		$this->assertTrue( $config['forceNetworkSavedCards'] );
		$this->assertTrue( $config['paymentMethodsConfig']['card']['forceNetworkSavedCards'] );
	}

	/**
	 * @testdox Should hide the card save-payment checkbox for logged-in WooPay shoppers.
	 */
	public function test_get_payment_fields_js_config_hides_card_save_option_for_logged_in_woopay_shoppers(): void {
		wp_set_current_user( self::factory()->user->create() );

		$legacy_runtime  = $this->create_legacy_runtime_for_bridge();
		$account_service = $this->create_account_service_for_bridge( true );
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn( array() );
		$legacy_runtime->method( 'can_handle_checkout_bridge_callbacks' )->willReturn( true );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( true ), $this->create_frontend_styles_service_for_bridge(), $this->create_frontend_tracking_controller_for_bridge() );

		$config = $bridge->get_payment_fields_js_config();

		$this->assertTrue( $config['isSavedCardsEnabled'] );
		$this->assertFalse( $config['paymentMethodsConfig']['card']['showSaveOption'] );
	}

	/**
	 * @testdox Should hide saved-card controls when saved cards are disabled.
	 */
	public function test_get_payment_fields_js_config_hides_saved_card_controls_when_saved_cards_are_disabled(): void {
		$legacy_runtime  = $this->create_legacy_runtime_for_bridge();
		$account_service = $this->create_account_service_for_bridge(
			true,
			array( 'country' => 'RO' ),
			array( 'saved_cards' => 'no' )
		);
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn( array() );
		$legacy_runtime->method( 'can_handle_checkout_bridge_callbacks' )->willReturn( true );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( false ), $this->create_frontend_styles_service_for_bridge(), $this->create_frontend_tracking_controller_for_bridge() );

		$config = $bridge->get_payment_fields_js_config();

		$this->assertFalse( $config['isSavedCardsEnabled'] );
		$this->assertFalse( $config['paymentMethodsConfig']['card']['showSaveOption'] );
	}

	/**
	 * @testdox Should expose Cartes Bancaires card branding for France merchants.
	 */
	public function test_get_payment_fields_js_config_includes_cartes_bancaires_for_france_merchants(): void {
		$legacy_runtime  = $this->create_legacy_runtime_for_bridge();
		$account_service = $this->create_account_service_for_bridge(
			true,
			array(
				'country' => 'FR',
			)
		);
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn( array() );
		$legacy_runtime->method( 'can_handle_checkout_bridge_callbacks' )->willReturn( true );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( false ), $this->create_frontend_styles_service_for_bridge(), $this->create_frontend_tracking_controller_for_bridge() );

		$config = $bridge->get_payment_fields_js_config();
		$icons  = $config['paymentMethodsConfig']['card']['cardBrandIcons'];

		$this->assertSame( 'FR', $config['storeCountry'] );
		$this->assertContains( 'cartes_bancaires', array_column( $icons, 'id' ) );
		$this->assertContains( 'Cartes Bancaires', array_column( $icons, 'alt' ) );
		$this->assertStringContainsString( '/assets/images/woopayments-card-brands/jcb.svg', $icons[4]['src'] );
		$this->assertStringContainsString( '/assets/images/woopayments-card-brands/unionpay.svg', $icons[5]['src'] );
		$this->assertStringContainsString( '/assets/images/payment-methods-cards/cartes_bancaires.svg', $icons[6]['src'] );
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
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( true ), $this->create_frontend_styles_service_for_bridge(), $this->create_frontend_tracking_controller_for_bridge() );

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
		$this->assertTrue( wp_style_is( 'wc-woopayments-checkout', 'enqueued' ) );
		$this->assertStringContainsString(
			'/assets/css/woopayments-checkout.css',
			wp_styles()->registered['wc-woopayments-checkout']->src
		);
	}

	/**
	 * @testdox Should include WooPay save-user data in Blocks payment method data.
	 */
	public function test_get_blocks_payment_method_data_includes_woopay_save_user_data(): void {
		$legacy_runtime  = $this->create_legacy_runtime_for_bridge();
		$account_service = $this->create_account_service_for_bridge( true );
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn( array() );
		$legacy_runtime->method( 'can_handle_checkout_bridge_callbacks' )->willReturn( true );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( true ), $this->create_frontend_styles_service_for_bridge(), $this->create_frontend_tracking_controller_for_bridge() );

		$data = $bridge->get_blocks_payment_method_data();

		$this->assertTrue( $data['isWooPayEnabled'] );
		$this->assertTrue( $data['PRE_CHECK_SAVE_MY_INFO'] );
		$this->assertContains( 'subscriptions', $data['supports'] );
		$this->assertContains( 'multiple_subscriptions', $data['paymentMethodsConfig']['card']['supports'] );
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
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( true ), $this->create_frontend_styles_service_for_bridge(), $this->create_frontend_tracking_controller_for_bridge() );

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
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( true ), $this->create_frontend_styles_service_for_bridge(), $this->create_frontend_tracking_controller_for_bridge() );

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
		$bridge->init( $legacy_runtime, $account_service, $this->create_woopay_session_service_for_bridge( false ), $this->create_frontend_styles_service_for_bridge(), $this->create_frontend_tracking_controller_for_bridge() );

		$config = $bridge->get_payment_fields_js_config();

		$this->assertArrayHasKey( 'createSetupIntentNonce', $config );
		$this->assertArrayHasKey( 'updateOrderStatusNonce', $config );
		$this->assertArrayNotHasKey( 'initWooPayNonce', $config );
		$this->assertArrayNotHasKey( 'woopaySessionNonce', $config );
		$this->assertArrayNotHasKey( 'woopaySignatureNonce', $config );
		$this->assertArrayNotHasKey( 'woopayMinimumSessionData', $config );
		$this->assertFalse( $config['isWooPayEnabled'] );
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
	 * @param bool  $can_process_payments Whether the account can process payments.
	 * @param array $account_data         Account cache data.
	 * @param array $gateway_settings     Gateway settings.
	 * @return WooPaymentsAccountService|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_account_service_for_bridge( bool $can_process_payments, array $account_data = array( 'country' => 'RO' ), array $gateway_settings = array() ) {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_publishable_key', 'get_account_id', 'get_cached_account_data', 'get_gateway_setting', 'can_process_payments', 'is_test_mode_enabled' ) )
			->getMock();

		$account_service
			->method( 'get_publishable_key' )
			->willReturn( 'pk_test_123' );
		$account_service
			->method( 'get_account_id' )
			->willReturn( 'acct_123' );
		$account_service
			->method( 'get_cached_account_data' )
			->willReturn( $account_data );
		$account_service
			->method( 'get_gateway_setting' )
			->willReturnCallback(
				static function ( string $key, $fallback = null ) use ( $gateway_settings ) {
					return array_key_exists( $key, $gateway_settings ) ? $gateway_settings[ $key ] : $fallback;
				}
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
			->onlyMethods( array( 'is_woopay_enabled', 'get_woopay_frontend_config', 'get_save_user_checkout_data' ) )
			->getMock();

		$service->method( 'is_woopay_enabled' )->willReturn( $enabled );
		$service->method( 'get_woopay_frontend_config' )->willReturn(
			array(
				'isWooPayEnabled'                   => true,
				'isWoopayExpressCheckoutEnabled'    => true,
				'isWooPayEmailInputEnabled'         => true,
				'isWooPayDirectCheckoutEnabled'     => false,
				'isWooPayGlobalThemeSupportEnabled' => false,
				'forceNetworkSavedCards'            => false,
				'platformTrackerNonce'              => 'platform-tracks-nonce',
				'woopayHost'                        => 'https://pay.woo.com',
				'wcpayVersionNumber'                => defined( 'WC_VERSION' ) ? WC_VERSION : '',
				'woopayMerchantId'                  => '12345',
				'initWooPayNonce'                   => 'init-woopay-nonce',
				'woopaySessionNonce'                => 'woopay-session-nonce',
				'woopaySignatureNonce'              => 'woopay-signature-nonce',
				'woopayMinimumSessionData'          => array( 'encrypted' => 'minimum' ),
				'woopayButton'                      => array(
					'type'    => 'default',
					'theme'   => 'dark',
					'height'  => '48',
					'radius'  => '4',
					'size'    => 'default',
					'context' => 'checkout',
				),
				'woopayButtonNonce'                 => 'woopay-button-nonce',
				'addToCartNonce'                    => 'add-to-cart-nonce',
				'shouldShowWooPayButton'            => true,
				'woopaySessionEmail'                => 'shopper@example.com',
				'woopayIsCountryAvailable'          => true,
				'woopayAppearance'                  => null,
				'woopayFontRules'                   => array(),
				'woopaySaveUserLabel'               => 'Securely save my information for 1-click checkout',
				'woopayPhoneLabel'                  => 'Mobile phone number',
			)
		);
		$service->method( 'get_save_user_checkout_data' )->willReturn( array( 'PRE_CHECK_SAVE_MY_INFO' => true ) );

		return $service;
	}

	/**
	 * Create a frontend styles service mock for checkout bridge tests.
	 *
	 * @return WooPaymentsFrontendStylesService|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_frontend_styles_service_for_bridge() {
		$service = $this->getMockBuilder( WooPaymentsFrontendStylesService::class )
			->onlyMethods( array( 'get_styles_cache_version' ) )
			->getMock();

		$service->method( 'get_styles_cache_version' )->willReturn( 'styles-v1' );

		return $service;
	}

	/**
	 * Create a frontend tracking controller mock for checkout bridge tests.
	 *
	 * @return WooPaymentsFrontendTrackingController|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_frontend_tracking_controller_for_bridge() {
		$controller = $this->getMockBuilder( WooPaymentsFrontendTrackingController::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_shopper_tracking_enabled' ) )
			->getMock();

		$controller->method( 'is_shopper_tracking_enabled' )->willReturn( true );

		return $controller;
	}
}
