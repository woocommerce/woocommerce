<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsExpressCheckoutController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsExpressCheckoutService;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsExpressCheckoutController class.
 */
class WooPaymentsExpressCheckoutControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsExpressCheckoutController|null
	 */
	private $sut;

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( $this->sut instanceof WooPaymentsExpressCheckoutController ) {
			foreach ( $this->get_expected_frontend_hooks() as $hook => $method ) {
				remove_action( $hook, array( $this->sut, $method ) );
			}
			remove_filter( 'wcpay_tracks_event_properties', array( $this->sut, 'add_tracking_event_properties' ) );
		}

		remove_all_filters( 'woocommerce_is_checkout' );
		remove_all_filters( 'woocommerce_is_cart' );
		$this->set_order_pay_query_var( 0 );
		wp_dequeue_script( 'wc-woopayments-express-checkout' );
		wp_dequeue_style( 'wc-woopayments-express-checkout' );
		wp_deregister_script( 'wc-woopayments-express-checkout' );
		wp_deregister_style( 'wc-woopayments-express-checkout' );
		wp_deregister_script( 'stripe' );
		wp_reset_postdata();
		parent::tearDown();
	}

	/**
	 * @testdox Should register express checkout frontend hooks when native owns runtime and payment request is available.
	 */
	public function test_registers_frontend_hooks_when_native_owns_runtime_and_payment_request_is_available(): void {
		$this->sut = $this->create_controller( true, true );

		$this->sut->register();

		foreach ( $this->get_expected_frontend_hooks() as $hook => $method ) {
			$this->assertNotFalse( has_action( $hook, array( $this->sut, $method ) ), "{$hook} should be registered." );
		}
		$this->assertNotFalse( has_filter( 'wcpay_tracks_event_properties', array( $this->sut, 'add_tracking_event_properties' ) ) );
	}

	/**
	 * @testdox Should register no hooks when native does not own the runtime.
	 */
	public function test_registers_no_hooks_when_native_does_not_own_runtime(): void {
		$this->sut = $this->create_controller( false, true );

		$this->sut->register();

		foreach ( $this->get_expected_frontend_hooks() as $hook => $method ) {
			$this->assertFalse( has_action( $hook, array( $this->sut, $method ) ) );
		}
		$this->assertFalse( has_filter( 'wcpay_tracks_event_properties', array( $this->sut, 'add_tracking_event_properties' ) ) );
	}

	/**
	 * @testdox Should register frontend hooks without eagerly depending on payment request settings.
	 */
	public function test_registers_frontend_hooks_without_eager_payment_request_gate(): void {
		$this->sut = $this->create_controller( true, false );

		$this->sut->register();

		foreach ( $this->get_expected_frontend_hooks() as $hook => $method ) {
			$this->assertNotFalse( has_action( $hook, array( $this->sut, $method ) ), "{$hook} should be registered." );
		}
		$this->assertNotFalse( has_filter( 'wcpay_tracks_event_properties', array( $this->sut, 'add_tracking_event_properties' ) ) );
	}

	/**
	 * @testdox Should enqueue separate express checkout assets and localize ECE params on checkout.
	 */
	public function test_enqueue_frontend_assets_loads_separate_express_checkout_bundle(): void {
		$this->sut = $this->create_controller( true, true );
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		$this->sut->enqueue_frontend_assets();

		$this->assertTrue( wp_script_is( 'wc-woopayments-express-checkout', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'wc-woopayments-express-checkout', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'stripe', 'registered' ) );
		$this->assertSame( 'https://js.stripe.com/v3/', wp_scripts()->registered['stripe']->src );
		$localized_data = wp_scripts()->get_data( 'wc-woopayments-express-checkout', 'data' );
		$this->assertIsString( $localized_data );
		$this->assertStringContainsString( 'var wcpayExpressCheckoutParams', $localized_data );
		$this->assertStringContainsString( '"enabled_methods":["payment_request"]', $localized_data );
	}

	/**
	 * @testdox Should enqueue express checkout assets on custom checkout shortcode pages.
	 */
	public function test_enqueue_frontend_assets_loads_on_checkout_shortcode_page(): void {
		$this->sut = $this->create_controller( true, true );
		$page_id   = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '[woocommerce_checkout]',
			)
		);

		global $post;
		$post = get_post( $page_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );

		$this->sut->enqueue_frontend_assets();

		$this->assertTrue( wp_script_is( 'wc-woopayments-express-checkout', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'wc-woopayments-express-checkout', 'enqueued' ) );
	}

	/**
	 * @testdox Should enqueue express checkout assets with pay-for-order context on order-pay pages.
	 */
	public function test_enqueue_frontend_assets_loads_on_order_pay_page(): void {
		$service   = new RecordingExpressCheckoutService();
		$this->sut = $this->create_controller( true, true, $service );
		$this->set_order_pay_query_var( 123 );
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		$this->sut->enqueue_frontend_assets();

		$this->assertTrue( wp_script_is( 'wc-woopayments-express-checkout', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'wc-woopayments-express-checkout', 'enqueued' ) );
		$this->assertSame( array( 'pay_for_order' ), $service->contexts );
		$localized_data = wp_scripts()->get_data( 'wc-woopayments-express-checkout', 'data' );
		$this->assertIsString( $localized_data );
		$this->assertStringContainsString( '"button_context":"pay_for_order"', $localized_data );
	}

	/**
	 * @testdox Should not enqueue classic ECE assets on checkout block pages.
	 */
	public function test_enqueue_frontend_assets_skips_checkout_block_pages(): void {
		$this->sut = $this->create_controller( true, true );
		$page_id   = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout"></div><!-- /wp:woocommerce/checkout -->',
			)
		);

		global $post;
		$post = get_post( $page_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		$this->sut->enqueue_frontend_assets();

		$this->assertFalse( wp_script_is( 'wc-woopayments-express-checkout', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'wc-woopayments-express-checkout', 'enqueued' ) );
	}

	/**
	 * @testdox Should render the reference ECE container on supported shopper surfaces.
	 */
	public function test_display_express_checkout_buttons_renders_ece_container(): void {
		$this->sut = $this->create_controller( true, true );
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		ob_start();
		$this->sut->display_express_checkout_buttons();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'wcpay-express-checkout-wrapper', $output );
		$this->assertStringContainsString( 'id="wcpay-express-checkout-element"', $output );
		$this->assertStringContainsString( 'wcpay-express-checkout-button-separator', $output );
	}

	/**
	 * @testdox Should render the ECE container with pay-for-order context on order-pay pages.
	 */
	public function test_display_express_checkout_buttons_renders_on_order_pay_page(): void {
		$service   = new RecordingExpressCheckoutService();
		$this->sut = $this->create_controller( true, true, $service );
		$this->set_order_pay_query_var( 123 );
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		ob_start();
		$this->sut->display_express_checkout_buttons();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'wcpay-express-checkout-wrapper', $output );
		$this->assertSame( array( 'pay_for_order' ), $service->contexts );
	}

	/**
	 * @testdox Should render no ECE container or assets when payment request is disabled.
	 */
	public function test_display_express_checkout_buttons_renders_nothing_when_payment_request_is_disabled(): void {
		$this->sut = $this->create_controller( true, false );
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		ob_start();
		$this->sut->display_express_checkout_buttons();
		$output = (string) ob_get_clean();

		$this->assertSame( '', $output );
		$this->assertFalse( wp_script_is( 'wc-woopayments-express-checkout', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'wc-woopayments-express-checkout', 'enqueued' ) );
	}

	/**
	 * @testdox Should mark Apple Pay and Google Pay load/click events as all-store Tracks events.
	 */
	public function test_add_tracking_event_properties_marks_platform_wallet_events_as_all_store_events(): void {
		$this->sut = $this->create_controller( true, true );

		$this->assertSame(
			array( 'record_event_data' => array( 'track_on_all_stores' => true ) ),
			$this->sut->add_tracking_event_properties( array(), 'wcpay_applepay_button_load' )
		);
		$this->assertSame(
			array( 'record_event_data' => array( 'track_on_all_stores' => true ) ),
			$this->sut->add_tracking_event_properties( array(), 'wcpay_gpay_button_click' )
		);
		$this->assertSame( array(), $this->sut->add_tracking_event_properties( array(), 'wcpay_woopay_button_click' ) );
	}

	/**
	 * Create the System Under Test.
	 *
	 * @param bool                                   $native_register     Whether native should register hooks.
	 * @param bool                                   $payment_request_on  Whether payment request should be available.
	 * @param WooPaymentsExpressCheckoutService|null $service            Optional service double.
	 * @return WooPaymentsExpressCheckoutController
	 */
	private function create_controller( bool $native_register, bool $payment_request_on, ?WooPaymentsExpressCheckoutService $service = null ): WooPaymentsExpressCheckoutController {
		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$service                                     = $service ?? new RecordingExpressCheckoutService();
		$service->should_show_payment_request_button = $payment_request_on;

		$controller = new WooPaymentsExpressCheckoutController();
		$controller->init( $arbiter, $service );

		return $controller;
	}

	/**
	 * Get expected frontend hooks and callbacks.
	 *
	 * @return array<string,string>
	 */
	private function get_expected_frontend_hooks(): array {
		return array(
			'wp_enqueue_scripts'                           => 'enqueue_frontend_assets',
			'woocommerce_checkout_before_customer_details' => 'display_express_checkout_buttons',
			'woocommerce_proceed_to_checkout'              => 'display_express_checkout_buttons',
			'woocommerce_pay_order_before_payment'         => 'display_express_checkout_buttons',
		);
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
