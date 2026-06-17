<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsWooPaySessionController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsWooPaySessionService;
use WC_REST_Unit_Test_Case;
use WPAjaxDieContinueException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Tests for the WooPaymentsWooPaySessionController class.
 */
class WooPaymentsWooPaySessionControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsWooPaySessionController
	 */
	private $sut;

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( $this->sut instanceof WooPaymentsWooPaySessionController ) {
			remove_action( 'rest_api_init', array( $this->sut, 'register_routes' ) );
			foreach ( $this->get_expected_ajax_hooks() as $hook => $method ) {
				remove_action( $hook, array( $this->sut, $method ) );
			}
			foreach ( $this->get_expected_frontend_hooks() as $hook => $method ) {
				remove_action( $hook, array( $this->sut, $method ) );
			}
			remove_filter( 'wcpay_metadata_from_order', array( $this->sut, 'maybe_add_woopay_user_metadata' ) );
		}

		wc_clear_notices();
		remove_all_filters( 'wcpay_woopay_is_signed_with_blog_token' );
		remove_all_filters( 'woocommerce_is_checkout' );
		remove_all_filters( 'woocommerce_is_product' );
		remove_all_filters( 'wp_die_ajax_handler' );
		remove_all_filters( 'wp_doing_ajax' );
		if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
			WC()->cart->empty_cart();
		}
		wp_dequeue_script( 'wc-woopayments-woopay' );
		wp_dequeue_style( 'wc-woopayments-woopay' );
		wp_deregister_script( 'wc-woopayments-woopay' );
		wp_deregister_style( 'wc-woopayments-woopay' );
		wp_reset_postdata();
		$_POST    = array();
		$_REQUEST = array();
		parent::tearDown();
	}

	/**
	 * @testdox Should register WooPay route and AJAX hooks when native owns runtime and WooPay is enabled.
	 */
	public function test_registers_woopay_route_and_ajax_hooks_when_native_owns_runtime_and_woopay_is_enabled(): void {
		$this->sut = $this->create_controller( true, true );

		$this->sut->register();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		$this->assertArrayHasKey( '/payments/woopay/session', $this->server->get_routes() );
		$this->assertRouteHasMethod( $this->server->get_routes()['/payments/woopay/session'], WP_REST_Server::READABLE );

		foreach ( $this->get_expected_ajax_hooks() as $hook => $method ) {
			$this->assertNotFalse( has_action( $hook, array( $this->sut, $method ) ), "{$hook} should be registered." );
		}
	}

	/**
	 * @testdox Should register WooPay frontend hooks when native owns runtime and WooPay is enabled.
	 */
	public function test_registers_woopay_frontend_hooks_when_native_owns_runtime_and_woopay_is_enabled(): void {
		$this->sut = $this->create_controller( true, true );

		$this->sut->register();

		foreach ( $this->get_expected_frontend_hooks() as $hook => $method ) {
			$this->assertNotFalse( has_action( $hook, array( $this->sut, $method ) ), "{$hook} should be registered." );
		}
		$this->assertNotFalse( has_filter( 'wcpay_metadata_from_order', array( $this->sut, 'maybe_add_woopay_user_metadata' ) ) );
	}

	/**
	 * @testdox Should register no hooks when native does not own runtime.
	 */
	public function test_registers_no_hooks_when_native_does_not_own_runtime(): void {
		$this->sut = $this->create_controller( false, true );

		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
		foreach ( $this->get_expected_ajax_hooks() as $hook => $method ) {
			$this->assertFalse( has_action( $hook, array( $this->sut, $method ) ) );
		}
		foreach ( $this->get_expected_frontend_hooks() as $hook => $method ) {
			$this->assertFalse( has_action( $hook, array( $this->sut, $method ) ) );
		}
		$this->assertFalse( has_filter( 'wcpay_metadata_from_order', array( $this->sut, 'maybe_add_woopay_user_metadata' ) ) );
	}

	/**
	 * @testdox Should register no hooks when WooPay is disabled.
	 */
	public function test_registers_no_hooks_when_platform_checkout_is_disabled(): void {
		$this->sut = $this->create_controller( true, false );

		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
		foreach ( $this->get_expected_ajax_hooks() as $hook => $method ) {
			$this->assertFalse( has_action( $hook, array( $this->sut, $method ) ) );
		}
		foreach ( $this->get_expected_frontend_hooks() as $hook => $method ) {
			$this->assertFalse( has_action( $hook, array( $this->sut, $method ) ) );
		}
		$this->assertFalse( has_filter( 'wcpay_metadata_from_order', array( $this->sut, 'maybe_add_woopay_user_metadata' ) ) );
	}

	/**
	 * @testdox Should enqueue classic WooPay save-user assets on checkout even when the express button is hidden.
	 */
	public function test_enqueue_frontend_assets_preserves_classic_save_user_when_checkout_button_is_hidden(): void {
		$service                            = new RecordingWooPaySessionService();
		$service->woopay_enabled            = true;
		$service->should_show_woopay_button = false;
		$this->sut                          = $this->create_controller( true, true, $service );

		add_filter( 'woocommerce_is_checkout', '__return_true' );

		$this->sut->enqueue_frontend_assets();

		$this->assertTrue( wp_script_is( 'wc-woopayments-woopay', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'wc-woopayments-woopay', 'enqueued' ) );
		$localized_data = wp_scripts()->get_data( 'wc-woopayments-woopay', 'data' );
		$this->assertIsString( $localized_data );
		$this->assertStringContainsString( '"shouldShowWooPayButton":""', $localized_data );
		$this->assertStringContainsString( '"PRE_CHECK_SAVE_MY_INFO":"1"', $localized_data );
	}

	/**
	 * @testdox Should not enqueue classic WooPay assets on checkout block pages.
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

		$this->assertFalse( wp_script_is( 'wc-woopayments-woopay', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'wc-woopayments-woopay', 'enqueued' ) );
	}

	/**
	 * @testdox Should render the WooPay separator on checkout.
	 */
	public function test_display_express_checkout_buttons_renders_separator_on_checkout(): void {
		$this->sut = $this->create_controller( true, true );
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		ob_start();
		$this->sut->display_express_checkout_buttons();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'id="wcpay-woopay-button"', $output );
		$this->assertStringContainsString( 'wcpay-express-checkout-button-separator', $output );
	}

	/**
	 * @testdox Should not render the WooPay separator on product pages.
	 */
	public function test_display_express_checkout_buttons_omits_separator_on_product_page(): void {
		$this->sut = $this->create_controller( true, true );
		$this->set_current_product();

		ob_start();
		$this->sut->display_express_checkout_buttons();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'id="wcpay-woopay-button"', $output );
		$this->assertStringNotContainsString( 'wcpay-express-checkout-button-separator', $output );
	}

	/**
	 * @testdox Should require WooPay user agent and a signed request.
	 */
	public function test_woopay_rest_route_requires_woopay_user_agent_and_signed_request(): void {
		$this->sut = $this->create_controller( true, true );
		$this->sut->register_routes();

		$missing_agent = new WP_REST_Request( 'GET', '/payments/woopay/session' );
		$missing_agent->set_param( 'email', 'shopper@example.com' );
		$this->assertSame( rest_authorization_required_code(), $this->server->dispatch( $missing_agent )->get_status() );

		$unsigned = new WP_REST_Request( 'GET', '/payments/woopay/session' );
		$unsigned->set_header( 'User-Agent', 'WooPay' );
		$unsigned->set_param( 'email', 'shopper@example.com' );
		$this->assertSame( rest_authorization_required_code(), $this->server->dispatch( $unsigned )->get_status() );

		add_filter( 'wcpay_woopay_is_signed_with_blog_token', '__return_true' );
		$signed = new WP_REST_Request( 'GET', '/payments/woopay/session' );
		$signed->set_header( 'User-Agent', 'WooPay' );
		$signed->set_param( 'email', 'shopper@example.com' );

		$this->assertSame( 200, $this->server->dispatch( $signed )->get_status() );
	}

	/**
	 * @testdox Should return session data for signed WooPay requests.
	 */
	public function test_rest_session_route_returns_session_data_for_signed_woopay_request(): void {
		$service   = new RecordingWooPaySessionService();
		$this->sut = $this->create_controller( true, true, $service );
		$this->sut->register_routes();
		add_filter( 'wcpay_woopay_is_signed_with_blog_token', '__return_true' );

		$request = new WP_REST_Request( 'GET', '/payments/woopay/session' );
		$request->set_header( 'User-Agent', 'WooPay' );
		$request->set_param( 'email', 'shopper@example.com' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'session' => 'native' ), $response->get_data() );
		$this->assertSame( 'shopper@example.com', $service->last_session_email );
	}

	/**
	 * @testdox Should build WooPay AJAX-compatible response payloads.
	 */
	public function test_builds_ajax_response_payloads(): void {
		$service   = new RecordingWooPaySessionService();
		$this->sut = $this->create_controller( true, true, $service );

		$this->assertSame( array( 'result' => 'success' ), $this->sut->get_init_woopay_response( array( 'email' => 'shopper@example.com' ) ) );
		$this->assertSame( array( 'encrypted' => 'session' ), $this->sut->get_encrypted_session_response( array( 'email' => 'shopper@example.com' ) ) );
		$this->assertSame( array( 'result' => 'success' ), $this->sut->get_phone_session_response( array( 'phone_number' => '+15555550123' ) ) );
		$this->assertSame( array( 'signature' => 'signed' ), $this->sut->get_signature_response( array() ) );
		$this->assertSame( array( 'encrypted' => 'minimum' ), $this->sut->get_minimum_session_response( array() ) );
		$this->assertSame( array( 'result' => 'success' ), $this->sut->get_admin_appearance_response( array( 'appearance' => $this->get_valid_appearance() ) ) );
		$this->assertSame( array( 'stored' => true ), $this->sut->get_shopper_appearance_response( array( 'appearance' => $this->get_valid_appearance() ) ) );
		$this->assertSame( '+15555550123', $service->last_phone_request['phone_number'] );
		$this->assertSame( $this->get_valid_appearance(), $service->last_appearance );
	}

	/**
	 * @testdox Should return the preserved WooPay signature AJAX success envelope.
	 */
	public function test_signature_ajax_handler_returns_success_envelope(): void {
		$service   = new RecordingWooPaySessionService();
		$this->sut = $this->create_controller( true, true, $service );
		$_POST     = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'_ajax_nonce' => wp_create_nonce( 'woopay_signature_nonce' ),
		);
		$_REQUEST  = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$response = $this->dispatch_signature_ajax();

		$this->assertTrue( $response['success'] );
		$this->assertSame( array( 'signature' => 'signed' ), $response['data'] );
	}

	/**
	 * @testdox Should require the WooPay button nonce before rendering frontend error notices.
	 */
	public function test_show_error_notice_ajax_handler_requires_woopay_button_nonce(): void {
		$this->sut = $this->create_controller( true, true );
		$_POST     = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'message' => 'WooPay is unavailable.',
		);
		$_REQUEST  = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$unauthorized = $this->dispatch_show_error_notice_ajax();

		$this->assertFalse( $unauthorized['success'] );
		$this->assertSame( 'You aren’t authorized to do that.', $unauthorized['data'] );

		$_POST    = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'_ajax_nonce' => wp_create_nonce( 'woopay_button_nonce' ),
			'message'     => 'WooPay is unavailable.',
		);
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$authorized = $this->dispatch_show_error_notice_ajax();

		$this->assertTrue( $authorized['success'] );
		$this->assertStringContainsString( 'WooPay is unavailable.', $authorized['data']['notice'] );
	}

	/**
	 * @testdox Should report false when shopper appearance was already stored.
	 */
	public function test_shopper_appearance_response_reports_when_appearance_slot_is_filled(): void {
		$service                    = new RecordingWooPaySessionService();
		$service->appearance_stored = false;
		$this->sut                  = $this->create_controller( true, true, $service );

		$response = $this->sut->get_shopper_appearance_response( array( 'appearance' => $this->get_valid_appearance() ) );

		$this->assertSame( array( 'stored' => false ), $response );
	}

	/**
	 * @testdox Product-page add-to-cart should preserve selected variable product attributes.
	 */
	public function test_add_to_cart_preserves_top_level_variation_attributes(): void {
		if ( ! function_exists( 'wc_load_cart' ) ) {
			$this->markTestSkipped( 'Cart bootstrap is unavailable.' );
		}
		wc_load_cart();

		$product      = \WC_Helper_Product::create_variation_product();
		$variation_id = 0;
		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( $variation && 'huge' === $variation->get_attribute( 'pa_size' ) && 'blue' === $variation->get_attribute( 'pa_colour' ) && '' === $variation->get_attribute( 'pa_number' ) ) {
				$variation_id = $child_id;
				break;
			}
		}
		$this->assertGreaterThan( 0, $variation_id );

		$this->sut = $this->create_controller( true, true );
		$_POST     = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'security'            => wp_create_nonce( 'wcpay-add-to-cart' ),
			'product_id'          => (string) $product->get_id(),
			'variation_id'        => (string) $variation_id,
			'quantity'            => '1',
			'attribute_pa_size'   => 'huge',
			'attribute_pa_colour' => 'blue',
			'attribute_pa_number' => '2',
		);
		$_REQUEST  = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$response = $this->dispatch_add_to_cart_ajax();
		$cart     = WC()->cart->get_cart();
		$item     = reset( $cart );

		$this->assertSame( 'success', $response['result'] );
		$this->assertSame( $variation_id, $item['variation_id'] );
		$this->assertSame(
			array(
				'attribute_pa_size'   => 'huge',
				'attribute_pa_colour' => 'blue',
				'attribute_pa_number' => '2',
			),
			$item['variation']
		);
	}

	/**
	 * Create the System Under Test.
	 *
	 * @param bool                                 $native_register Whether native should register hooks.
	 * @param bool                                 $woopay_enabled  Whether WooPay should be enabled.
	 * @param WooPaymentsWooPaySessionService|null $service         Optional service double.
	 * @return WooPaymentsWooPaySessionController
	 */
	private function create_controller( bool $native_register, bool $woopay_enabled, ?WooPaymentsWooPaySessionService $service = null ): WooPaymentsWooPaySessionController {
		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$service                 = $service ?? new RecordingWooPaySessionService();
		$service->woopay_enabled = $woopay_enabled;

		$controller = new WooPaymentsWooPaySessionController();
		$controller->init( $arbiter, $service );

		return $controller;
	}

	/**
	 * Dispatch the WooPay signature AJAX handler and decode the JSON response.
	 *
	 * @return array{success:bool,data:mixed}
	 */
	private function dispatch_signature_ajax(): array {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wp_die_ajax_handler',
			static function () {
				return static function (): void {
					throw new WPAjaxDieContinueException();
				};
			}
		);

		ob_start();
		try {
			$this->sut->handle_get_woopay_signature();
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$body = (string) ob_get_clean();

		$decoded = json_decode( $body, true );
		$this->assertIsArray( $decoded, 'WooPay signature AJAX should emit a JSON object.' );

		return $decoded;
	}

	/**
	 * Dispatch the WooPay error notice AJAX handler and decode the JSON response.
	 *
	 * @return array{success:bool,data:mixed}
	 */
	private function dispatch_show_error_notice_ajax(): array {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wp_die_ajax_handler',
			static function () {
				return static function (): void {
					throw new WPAjaxDieContinueException();
				};
			}
		);

		ob_start();
		try {
			$this->sut->handle_show_error_notice();
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$body = (string) ob_get_clean();

		$decoded = json_decode( $body, true );
		$this->assertIsArray( $decoded, 'WooPay notice AJAX should emit a JSON object.' );

		return $decoded;
	}

	/**
	 * Dispatch the WooPay product add-to-cart AJAX handler and decode the JSON response.
	 *
	 * @return array<string,mixed>
	 */
	private function dispatch_add_to_cart_ajax(): array {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wp_die_ajax_handler',
			static function () {
				return static function (): void {
					throw new WPAjaxDieContinueException();
				};
			}
		);

		ob_start();
		try {
			$this->sut->handle_add_to_cart();
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$body = (string) ob_get_clean();

		$decoded = json_decode( $body, true );
		$this->assertIsArray( $decoded, 'WooPay add-to-cart AJAX should emit a JSON object.' );

		return $decoded;
	}

	/**
	 * Get a valid WooPay appearance payload.
	 *
	 * @return array<string,mixed>
	 */
	private function get_valid_appearance(): array {
		return array(
			'theme'     => 'stripe',
			'variables' => array(
				'colorText' => '#111111',
			),
		);
	}

	/**
	 * Get expected AJAX hooks and callbacks.
	 *
	 * @return array<string,string>
	 */
	private function get_expected_ajax_hooks(): array {
		return array(
			'wc_ajax_wcpay_init_woopay'                   => 'handle_init_woopay',
			'wc_ajax_wcpay_get_woopay_session'            => 'handle_get_woopay_session',
			'wc_ajax_wcpay_set_woopay_phone_number'       => 'handle_set_woopay_phone_number',
			'wc_ajax_wcpay_get_woopay_signature'          => 'handle_get_woopay_signature',
			'wc_ajax_wcpay_get_woopay_minimum_session_data' => 'handle_get_woopay_minimum_session_data',
			'wp_ajax_wcpay_admin_set_woopay_appearance'   => 'handle_set_admin_woopay_appearance',
			'wc_ajax_wcpay_shopper_set_woopay_appearance' => 'handle_set_shopper_woopay_appearance',
			'wc_ajax_wcpay_add_to_cart'                   => 'handle_add_to_cart',
			'wp_ajax_woopay_express_checkout_button_show_error_notice' => 'handle_show_error_notice',
			'wp_ajax_nopriv_woopay_express_checkout_button_show_error_notice' => 'handle_show_error_notice',
		);
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
			'woocommerce_after_add_to_cart_form'           => 'display_express_checkout_buttons',
			'woocommerce_pay_order_before_payment'         => 'display_express_checkout_buttons',
			'woocommerce_payment_complete'                 => 'handle_woocommerce_payment_complete',
		);
	}

	/**
	 * Set the current request to a product page.
	 */
	private function set_current_product(): void {
		$product = \WC_Helper_Product::create_simple_product( true );
		$this->go_to( get_permalink( $product->get_id() ) );
		$GLOBALS['product'] = $product;
	}

	/**
	 * Assert a route handler accepts a method.
	 *
	 * @param array<int,array<string,mixed>> $route_handlers Route handlers.
	 * @param string                         $method         HTTP method.
	 */
	private function assertRouteHasMethod( array $route_handlers, string $method ): void {
		foreach ( $route_handlers as $handler ) {
			if ( isset( $handler['methods'][ $method ] ) ) {
				$this->assertTrue( true );
				return;
			}
		}

		$this->fail( "Route did not register {$method}." );
	}
}
