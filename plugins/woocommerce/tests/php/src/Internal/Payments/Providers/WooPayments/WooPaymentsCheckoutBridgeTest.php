<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutBridge;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
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
		$legacy_runtime = $this->createMock( WooPaymentsLegacyRuntime::class );
		$legacy_runtime->method( 'get_gateway_publishable_key' )->willReturn( 'pk_test_123' );
		$legacy_runtime->method( 'get_gateway_account_id' )->willReturn( 'acct_123' );
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn(
			array(
				'name'  => 'Test Customer',
				'email' => 'merchant@example.com',
			)
		);
		$legacy_runtime->method( 'can_handle_checkout_bridge_callbacks' )->willReturn( true );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime );

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
		$this->assertSame( 'yes', $config['filtered'] );
		$this->assertArrayHasKey( 'paymentMethodsConfig', $config );
		$this->assertArrayHasKey( 'enabledBillingFields', $config );
		$this->assertArrayHasKey( 'currency', $config );
		$this->assertArrayHasKey( 'cartTotal', $config );
		$this->assertArrayHasKey( 'createSetupIntentNonce', $config );
		$this->assertArrayHasKey( 'updateOrderStatusNonce', $config );
	}

	/**
	 * @testdox Should enqueue core-owned checkout assets when rendering payment fields.
	 */
	public function test_payment_fields_enqueues_core_owned_assets_and_preserves_wcpay_config_filter(): void {
		$legacy_runtime = $this->createMock( WooPaymentsLegacyRuntime::class );
		$legacy_runtime->method( 'get_gateway_publishable_key' )->willReturn( 'pk_test_123' );
		$legacy_runtime->method( 'get_gateway_account_id' )->willReturn( 'acct_123' );
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn( array() );
		$legacy_runtime->method( 'can_handle_checkout_bridge_callbacks' )->willReturn( true );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime );

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
		$this->assertStringContainsString( 'data-wcpay-config', $output );
		$this->assertStringContainsString( 'filtered', $output );
		$this->assertTrue( wp_script_is( 'wc-woopayments-checkout', 'enqueued' ) );
		$this->assertStringContainsString(
			'/assets/js/frontend/woopayments-checkout',
			wp_scripts()->registered['wc-woopayments-checkout']->src
		);
	}

	/**
	 * @testdox Should only expose legacy bridge nonces when the legacy runtime can serve the callbacks.
	 */
	public function test_get_payment_fields_js_config_exposes_legacy_bridge_nonces_only_when_runtime_is_loaded(): void {
		$legacy_runtime = $this->createMock( WooPaymentsLegacyRuntime::class );
		$legacy_runtime->method( 'get_gateway_publishable_key' )->willReturn( 'pk_test_123' );
		$legacy_runtime->method( 'get_gateway_account_id' )->willReturn( 'acct_123' );
		$legacy_runtime->method( 'get_gateway_prepared_customer_data' )->willReturn( array() );
		$legacy_runtime->method( 'can_handle_checkout_bridge_callbacks' )->willReturn( false );

		$bridge = new WooPaymentsCheckoutBridge();
		$bridge->init( $legacy_runtime );

		$config = $bridge->get_payment_fields_js_config();

		$this->assertArrayNotHasKey( 'createSetupIntentNonce', $config );
		$this->assertArrayNotHasKey( 'updateOrderStatusNonce', $config );
		$this->assertFalse( $config['usesLegacySetupIntentBridge'] );
		$this->assertFalse( $config['usesLegacyOrderStatusBridge'] );
	}
}
