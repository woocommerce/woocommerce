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
		delete_option( 'woocommerce_tax_based_on' );
		delete_option( 'woocommerce_calc_taxes' );
		unset( $_GET['pay_for_order'], $_GET['key'] );
		remove_all_filters( 'woocommerce_native_woopayments_express_checkout_enabled_methods' );
		$this->set_order_pay_query_var( 0 );
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
	 * @testdox Should expose server-authoritative Stripe payment method types for enabled express methods.
	 */
	public function test_allowed_payment_method_types_include_eligible_amazon_pay(): void {
		$sut = $this->create_service(
			array(
				'express_checkout_checkout_methods' => array( 'payment_request', 'amazon_pay' ),
				'upe_enabled_payment_method_ids'    => array( 'card', 'amazon_pay' ),
			),
			true,
			array(
				'ece_confirmation_tokens_disabled' => false,
			)
		);

		$this->assertSame( array( 'card', 'amazon_pay' ), $sut->get_allowed_payment_method_types_for_context( 'checkout' ) );
		$this->assertSame( array( 'payment_request', 'amazon_pay' ), $sut->get_enabled_methods_for_context( 'checkout' ) );
		$this->assertSame( array( 'card', 'amazon_pay' ), $sut->get_express_checkout_params( 'checkout' )['payment_method_types'] );
		$this->assertSame( array( 'payment_request', 'amazon_pay' ), $sut->get_express_checkout_params( 'checkout' )['enabled_methods'] );
	}

	/**
	 * @testdox Should fail closed when Amazon Pay is configured but not available on the connected account.
	 */
	public function test_allowed_payment_method_types_exclude_unavailable_amazon_pay(): void {
		$sut = $this->create_service(
			array(
				'express_checkout_checkout_methods' => array( 'payment_request', 'amazon_pay' ),
				'upe_available_payment_methods'     => array( 'card' ),
				'upe_enabled_payment_method_ids'    => array( 'card', 'amazon_pay' ),
			),
			true,
			array(
				'ece_confirmation_tokens_disabled' => false,
			)
		);

		$this->assertSame( array( 'card' ), $sut->get_allowed_payment_method_types_for_context( 'checkout' ) );
		$this->assertSame( array( 'payment_request' ), $sut->get_enabled_methods_for_context( 'checkout' ) );
		$this->assertSame( array( 'payment_request' ), $sut->get_express_checkout_params( 'checkout' )['enabled_methods'] );
	}

	/**
	 * @testdox Should fail closed when account data disables ECE confirmation tokens.
	 */
	public function test_allowed_payment_method_types_exclude_amazon_pay_when_confirmation_tokens_are_disabled(): void {
		$sut = $this->create_service(
			array(
				'express_checkout_checkout_methods' => array( 'payment_request', 'amazon_pay' ),
				'upe_available_payment_methods'     => array( 'card', 'amazon_pay' ),
				'upe_enabled_payment_method_ids'    => array( 'card', 'amazon_pay' ),
			),
			true,
			array(
				'ece_confirmation_tokens_disabled' => true,
			)
		);

		$this->assertSame( array( 'card' ), $sut->get_allowed_payment_method_types_for_context( 'checkout' ) );
	}

	/**
	 * @testdox Should block Amazon Pay when taxes are based on billing address.
	 */
	public function test_allowed_payment_method_types_exclude_amazon_pay_for_billing_address_taxes(): void {
		update_option( 'woocommerce_tax_based_on', 'billing' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$sut = $this->create_service(
			array(
				'express_checkout_checkout_methods' => array( 'payment_request', 'amazon_pay' ),
				'upe_available_payment_methods'     => array( 'card', 'amazon_pay' ),
				'upe_enabled_payment_method_ids'    => array( 'card', 'amazon_pay' ),
			),
			true,
			array(
				'ece_confirmation_tokens_disabled' => false,
			)
		);

		$this->assertSame( array( 'card' ), $sut->get_allowed_payment_method_types_for_context( 'checkout' ) );
	}

	/**
	 * @testdox Should allow Amazon Pay with billing-address tax settings when taxes are disabled.
	 */
	public function test_allowed_payment_method_types_include_amazon_pay_for_billing_address_when_taxes_disabled(): void {
		update_option( 'woocommerce_tax_based_on', 'billing' );
		update_option( 'woocommerce_calc_taxes', 'no' );

		$sut = $this->create_service(
			array(
				'express_checkout_checkout_methods' => array( 'payment_request', 'amazon_pay' ),
				'upe_available_payment_methods'     => array( 'card', 'amazon_pay' ),
				'upe_enabled_payment_method_ids'    => array( 'card', 'amazon_pay' ),
			),
			true,
			array(
				'ece_confirmation_tokens_disabled' => false,
			)
		);

		$this->assertSame( array( 'card', 'amazon_pay' ), $sut->get_allowed_payment_method_types_for_context( 'checkout' ) );
	}

	/**
	 * @testdox Should validate pay-for-order express method types against the order currency.
	 */
	public function test_pay_for_order_payment_method_types_use_order_currency(): void {
		update_option( 'woocommerce_currency', 'USD' );

		$order = wc_create_order();
		$order->set_total( '24.00' );
		$order->set_currency( 'EUR' );
		$order->set_billing_email( 'shopper@example.test' );
		$order->save();
		$_GET['pay_for_order'] = 'true';
		$_GET['key']           = $order->get_order_key();
		$this->set_order_pay_query_var( $order->get_id() );

		$params = $this->create_service(
			array(
				'express_checkout_checkout_methods' => array( 'payment_request', 'amazon_pay' ),
				'upe_available_payment_methods'     => array( 'card', 'amazon_pay' ),
				'upe_enabled_payment_method_ids'    => array( 'card', 'amazon_pay' ),
			),
			true,
			array(
				'ece_confirmation_tokens_disabled' => false,
			)
		)->get_express_checkout_params( 'pay_for_order' );

		$this->assertSame( 'eur', $params['checkout']['currency_code'] );
		$this->assertSame( array( 'payment_request' ), $params['enabled_methods'] );
		$this->assertSame( array( 'card' ), $params['payment_method_types'] );
	}

	/**
	 * @testdox Should keep Amazon Pay available for pay-for-order when billing-address taxes are enabled.
	 */
	public function test_pay_for_order_allows_amazon_pay_with_billing_address_taxes(): void {
		update_option( 'woocommerce_tax_based_on', 'billing' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$order = wc_create_order();
		$order->set_total( '24.00' );
		$order->set_currency( 'USD' );
		$order->set_billing_email( 'shopper@example.test' );
		$order->save();
		$_GET['pay_for_order'] = 'true';
		$_GET['key']           = $order->get_order_key();
		$this->set_order_pay_query_var( $order->get_id() );

		$params = $this->create_service(
			array(
				'express_checkout_checkout_methods' => array( 'payment_request', 'amazon_pay' ),
				'upe_available_payment_methods'     => array( 'card', 'amazon_pay' ),
				'upe_enabled_payment_method_ids'    => array( 'card', 'amazon_pay' ),
			),
			true,
			array(
				'ece_confirmation_tokens_disabled' => false,
			)
		)->get_express_checkout_params( 'pay_for_order' );

		$this->assertSame( array( 'payment_request', 'amazon_pay' ), $params['enabled_methods'] );
		$this->assertSame( array( 'card', 'amazon_pay' ), $params['payment_method_types'] );
	}

	/**
	 * @testdox Should build pay-for-order express checkout params from the current order.
	 */
	public function test_builds_pay_for_order_express_checkout_params(): void {
		$order = wc_create_order();
		$order->set_total( '24.00' );
		$order->set_billing_email( 'shopper@example.test' );
		$order->save();
		$_GET['pay_for_order'] = 'true';
		$_GET['key']           = $order->get_order_key();
		$this->set_order_pay_query_var( $order->get_id() );

		$params = $this->create_service()->get_express_checkout_params( 'pay_for_order' );

		$this->assertSame( 'pay_for_order', $params['button_context'] );
		$this->assertSame( $order->get_id(), $params['order_id'] );
		$this->assertSame( 'true', $params['pay_for_order'] );
		$this->assertSame( $order->get_order_key(), $params['key'] );
		$this->assertSame( 'shopper@example.test', $params['billing_email'] );
		$this->assertSame( array( 'payment_request' ), $params['enabled_methods'] );
		$this->assertSame( array( 'card' ), $params['payment_method_types'] );
	}

	/**
	 * @testdox Should fail closed for pay-for-order when billing email is missing.
	 */
	public function test_payment_request_fails_closed_for_pay_for_order_without_billing_email(): void {
		$order = wc_create_order();
		$order->set_total( '24.00' );
		$order->save();
		$_GET['pay_for_order'] = 'true';
		$_GET['key']           = $order->get_order_key();
		$this->set_order_pay_query_var( $order->get_id() );

		$this->assertFalse( $this->create_service()->should_show_payment_request_button( 'pay_for_order' ) );
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
				'country'          => 'US',
				'payments_enabled' => true,
				'capabilities'     => array(
					'amazon_pay_payments' => 'active',
				),
				'fees'             => array(
					'amazon_pay' => array(
						'base' => array(
							'currency' => 'usd',
						),
					),
				),
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

	/**
	 * Set the current order-pay query var.
	 *
	 * @param int $order_id Order ID.
	 */
	private function set_order_pay_query_var( int $order_id ): void {
		global $wp;

		if ( ! is_object( $wp ) ) {
			$wp = new \WP(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( $order_id > 0 ) {
			$wp->query_vars['order-pay'] = $order_id;
			return;
		}

		unset( $wp->query_vars['order-pay'] );
	}
}
