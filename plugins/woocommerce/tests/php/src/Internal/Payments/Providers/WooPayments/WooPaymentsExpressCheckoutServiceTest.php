<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsExpressCheckoutService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsExpressCheckoutService class.
 */
class WooPaymentsExpressCheckoutServiceTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wc_empty_cart();
		delete_option( 'woocommerce_default_country' );
		delete_option( 'woocommerce_currency' );
		remove_all_filters( 'woocommerce_native_woopayments_express_checkout_enabled_methods' );
		parent::tearDown();
	}

	/**
	 * @testdox Should require native provider readiness before exposing payment-request express checkout.
	 */
	public function test_payment_request_requires_provider_readiness(): void {
		$sut = $this->create_service( array(), false );

		$this->assertFalse( $sut->should_show_payment_request_button( 'checkout' ) );

		$sut = $this->create_service( array(), true );

		$this->assertTrue( $sut->should_show_payment_request_button( 'checkout' ) );
	}

	/**
	 * @testdox Should honor context-specific payment-request express checkout settings.
	 */
	public function test_payment_request_is_context_specific(): void {
		$sut = $this->create_service(
			array(
				'express_checkout_product_methods'  => array( 'payment_request' ),
				'express_checkout_cart_methods'     => array(),
				'express_checkout_checkout_methods' => array( 'woopay' ),
			)
		);

		$this->assertTrue( $sut->should_show_payment_request_button( 'product' ) );
		$this->assertFalse( $sut->should_show_payment_request_button( 'cart' ) );
		$this->assertFalse( $sut->should_show_payment_request_button( 'checkout' ) );
	}

	/**
	 * @testdox Should use location-centric payment-request settings when the migrated legacy switch is absent.
	 */
	public function test_payment_request_uses_location_settings_without_legacy_switch(): void {
		$sut = $this->create_service(
			array(
				'express_checkout_product_methods'  => array(),
				'express_checkout_cart_methods'     => array(),
				'express_checkout_checkout_methods' => array( 'payment_request' ),
			),
			true,
			array(),
			false
		);

		$this->assertTrue( $sut->should_show_payment_request_button( 'checkout' ) );
	}

	/**
	 * @testdox Should fall back to the legacy payment-request switch when per-context settings are absent.
	 */
	public function test_payment_request_uses_legacy_switch_when_context_settings_are_absent(): void {
		$sut = $this->create_service(
			array(
				'payment_request' => 'yes',
			),
			true,
			array(),
			false
		);

		$this->assertTrue( $sut->should_show_payment_request_button( 'checkout' ) );
	}

	/**
	 * @testdox Should build reference-shaped ECE params for Apple Pay and Google Pay.
	 */
	public function test_builds_payment_request_express_checkout_params(): void {
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_currency', 'USD' );

		$sut    = $this->create_service(
			array(
				'manual_capture'                       => 'yes',
				'payment_request_button_type'          => 'buy',
				'payment_request_button_theme'         => 'light',
				'payment_request_button_size'          => 'large',
				'payment_request_button_border_radius' => '6',
			)
		);
		$params = $sut->get_express_checkout_params( 'checkout' );

		$this->assertSame( 'pk_test_123', $params['stripe']['publishableKey'] );
		$this->assertSame( 'acct_123', $params['stripe']['accountId'] );
		$this->assertSame( 'checkout', $params['button_context'] );
		$this->assertSame( array( 'payment_request' ), $params['enabled_methods'] );
		$this->assertSame( get_bloginfo( 'name' ), $params['store_name'] );
		$this->assertSame( admin_url( 'admin-ajax.php' ), $params['ajax_url'] );
		$this->assertSame( \WC_AJAX::get_endpoint( '%%endpoint%%' ), $params['wc_ajax_url'] );
		$this->assertSame( 'usd', $params['checkout']['currency_code'] );
		$this->assertSame( 'US', $params['checkout']['country_code'] );
		$this->assertTrue( $params['is_manual_capture'] );
		$this->assertSame( 'buy', $params['button']['type'] );
		$this->assertSame( 'light', $params['button']['theme'] );
		$this->assertSame( '55', $params['button']['height'] );
		$this->assertSame( '6', $params['button']['radius'] );
		$this->assertSame( 'large', $params['button']['size'] );
		$this->assertArrayHasKey( 'platform_tracker', $params['nonce'] );
		$this->assertArrayHasKey( 'tokenized_cart_nonce', $params['nonce'] );
		$this->assertArrayHasKey( 'tokenized_cart_session_nonce', $params['nonce'] );
		$this->assertArrayHasKey( 'store_api_nonce', $params['nonce'] );
		$this->assertArrayHasKey( 'isEceUsingConfirmationTokens', $params['flags'] );
	}

	/**
	 * @testdox Should allow the enabled platform methods list to be narrowed without coupling it to WooPay.
	 */
	public function test_enabled_methods_can_be_filtered(): void {
		add_filter(
			'woocommerce_native_woopayments_express_checkout_enabled_methods',
			static function ( array $methods, string $context ): array {
				return 'checkout' === $context ? array( 'payment_request', 'klarna' ) : $methods;
			},
			10,
			2
		);

		$params = $this->create_service()->get_express_checkout_params( 'checkout' );

		$this->assertSame( array( 'payment_request', 'klarna' ), $params['enabled_methods'] );
	}

	/**
	 * Create the System Under Test.
	 *
	 * @param array<string,mixed> $settings             Gateway settings.
	 * @param bool                $can_process_payments Whether the provider can process native payments.
	 * @param array<string,mixed> $account_data         Account data.
	 * @param bool                $merge_defaults       Whether to merge default gateway settings.
	 * @return WooPaymentsExpressCheckoutService
	 */
	private function create_service( array $settings = array(), bool $can_process_payments = true, array $account_data = array(), bool $merge_defaults = true ): WooPaymentsExpressCheckoutService {
		$default_settings = array(
			'manual_capture'                       => 'no',
			'payment_request'                      => 'yes',
			'payment_request_button_type'          => 'default',
			'payment_request_button_theme'         => 'dark',
			'payment_request_button_size'          => 'medium',
			'payment_request_button_border_radius' => '',
			'express_checkout_product_methods'     => array( 'payment_request' ),
			'express_checkout_cart_methods'        => array( 'payment_request' ),
			'express_checkout_checkout_methods'    => array( 'payment_request' ),
		);
		$settings         = $merge_defaults ? array_merge( $default_settings, $settings ) : $settings;
		$account_data     = array_merge(
			array(
				'country' => 'US',
			),
			$account_data
		);

		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_account_id', 'get_publishable_key', 'get_cached_account_data', 'is_test_mode_enabled', 'get_gateway_setting' ) )
			->getMock();

		$account_service->method( 'get_account_id' )->willReturn( 'acct_123' );
		$account_service->method( 'get_publishable_key' )->willReturn( 'pk_test_123' );
		$account_service->method( 'get_cached_account_data' )->willReturn( $account_data );
		$account_service->method( 'is_test_mode_enabled' )->willReturn( true );
		$account_service->method( 'get_gateway_setting' )->willReturnCallback(
			static fn( string $key, $fallback = null ) => array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback
		);

		$provider = $this->getMockBuilder( WooPaymentsProvider::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'can_process_payments' ) )
			->getMock();
		$provider->method( 'can_process_payments' )->willReturn( $can_process_payments );

		$sut = new WooPaymentsExpressCheckoutService();
		$sut->init( $account_service, $provider );

		return $sut;
	}
}
