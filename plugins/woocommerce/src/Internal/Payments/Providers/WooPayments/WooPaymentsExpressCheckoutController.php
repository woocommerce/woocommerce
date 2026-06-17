<?php
/**
 * WooPaymentsExpressCheckoutController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Native WooPayments Apple Pay and Google Pay express checkout frontend hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsExpressCheckoutController implements RegisterHooksInterface {

	private const CLASSIC_EXPRESS_CHECKOUT_SCRIPT_HANDLE = 'wc-woopayments-express-checkout';

	private const CLASSIC_EXPRESS_CHECKOUT_STYLE_HANDLE = 'wc-woopayments-express-checkout';

	private const STRIPE_SCRIPT_HANDLE = 'stripe';

	private const STRIPE_SCRIPT_URL = 'https://js.stripe.com/v3/';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Express checkout service.
	 *
	 * @var WooPaymentsExpressCheckoutService
	 */
	private WooPaymentsExpressCheckoutService $express_checkout_service;

	/**
	 * Whether express checkout buttons have already rendered in this request.
	 *
	 * @var bool
	 */
	private bool $has_rendered_express_checkout_buttons = false;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter      $arbiter                  Runtime owner arbiter.
	 * @param WooPaymentsExpressCheckoutService $express_checkout_service Express checkout service.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsExpressCheckoutService $express_checkout_service ): void {
		$this->arbiter                  = $arbiter;
		$this->express_checkout_service = $express_checkout_service;
	}

	/**
	 * Register express checkout frontend hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		foreach ( $this->get_frontend_hooks() as $hook => $callback ) {
			if ( false === has_action( $hook, $callback ) ) {
				add_action( $hook, $callback );
			}
		}

		if ( false === has_filter( 'wcpay_tracks_event_properties', array( $this, 'add_tracking_event_properties' ) ) ) {
			add_filter( 'wcpay_tracks_event_properties', array( $this, 'add_tracking_event_properties' ), 10, 2 );
		}
	}

	/**
	 * Enqueue Core-owned express checkout frontend assets on supported shopper surfaces.
	 */
	public function enqueue_frontend_assets(): void {
		if ( ! $this->is_supported_frontend_surface() ) {
			return;
		}

		$context = $this->get_current_button_context();
		if ( ! $this->express_checkout_service->should_show_payment_request_button( $context ) ) {
			return;
		}

		$this->register_classic_express_checkout_assets();
		wp_localize_script( self::CLASSIC_EXPRESS_CHECKOUT_SCRIPT_HANDLE, 'wcpayExpressCheckoutParams', $this->express_checkout_service->get_express_checkout_params( $context ) );
		wp_enqueue_style( self::CLASSIC_EXPRESS_CHECKOUT_STYLE_HANDLE );
		if ( 'product' === $context ) {
			wp_enqueue_script( 'wp-hooks' );
		}
		wp_enqueue_script( self::CLASSIC_EXPRESS_CHECKOUT_SCRIPT_HANDLE );
	}

	/**
	 * Display the Stripe Express Checkout Element placeholder.
	 */
	public function display_express_checkout_buttons(): void {
		if ( $this->has_rendered_express_checkout_buttons || ! $this->is_supported_frontend_surface() ) {
			return;
		}

		$context = $this->get_current_button_context();
		if ( ! $this->express_checkout_service->should_show_payment_request_button( $context ) ) {
			return;
		}

		$this->has_rendered_express_checkout_buttons = true;

		echo '<div class="wcpay-express-checkout-wrapper">';
		echo '<div id="wcpay-express-checkout-element"></div>';
		if ( 'checkout' === $context ) {
			echo '<p id="wcpay-express-checkout-button-separator">&mdash; ' . esc_html__( 'OR', 'woocommerce' ) . ' &mdash;</p>';
		}
		echo '</div>';
	}

	/**
	 * Add Tracks properties for Apple Pay and Google Pay button events.
	 *
	 * @param array<string,mixed> $properties Event properties.
	 * @param string              $event_name Event name.
	 * @return array<string,mixed>
	 */
	public function add_tracking_event_properties( array $properties, string $event_name ): array {
		if (
			in_array(
				$event_name,
				array(
					'wcpay_applepay_button_load',
					'wcpay_applepay_button_click',
					'wcpay_gpay_button_load',
					'wcpay_gpay_button_click',
				),
				true
			)
		) {
			if ( ! isset( $properties['record_event_data'] ) || ! is_array( $properties['record_event_data'] ) ) {
				$properties['record_event_data'] = array();
			}
			$properties['record_event_data']['track_on_all_stores'] = true;
		}

		return $properties;
	}

	/**
	 * Get express checkout frontend hooks and callbacks.
	 *
	 * @return array<string,callable>
	 */
	private function get_frontend_hooks(): array {
		return array(
			'wp_enqueue_scripts'                           => array( $this, 'enqueue_frontend_assets' ),
			'woocommerce_after_add_to_cart_form'           => array( $this, 'display_express_checkout_buttons' ),
			'woocommerce_checkout_before_customer_details' => array( $this, 'display_express_checkout_buttons' ),
			'woocommerce_proceed_to_checkout'              => array( $this, 'display_express_checkout_buttons' ),
			'woocommerce_pay_order_before_payment'         => array( $this, 'display_express_checkout_buttons' ),
		);
	}

	/**
	 * Register classic express checkout assets when WooCommerce has not registered them yet.
	 */
	private function register_classic_express_checkout_assets(): void {
		$this->register_stripe_script();

		if ( ! wp_script_is( self::CLASSIC_EXPRESS_CHECKOUT_SCRIPT_HANDLE, 'registered' ) ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_register_script(
				self::CLASSIC_EXPRESS_CHECKOUT_SCRIPT_HANDLE,
				WC()->plugin_url() . '/assets/js/frontend/woopayments-express-checkout' . $suffix . '.js',
				array( 'jquery', self::STRIPE_SCRIPT_HANDLE, 'wp-api-fetch' ),
				defined( 'WC_VERSION' ) ? WC_VERSION : '',
				true
			);
		}

		if ( ! wp_style_is( self::CLASSIC_EXPRESS_CHECKOUT_STYLE_HANDLE, 'registered' ) ) {
			wp_register_style(
				self::CLASSIC_EXPRESS_CHECKOUT_STYLE_HANDLE,
				WC()->plugin_url() . '/assets/css/woopayments-express-checkout.css',
				array(),
				defined( 'WC_VERSION' ) ? WC_VERSION : ''
			);
			wp_style_add_data( self::CLASSIC_EXPRESS_CHECKOUT_STYLE_HANDLE, 'rtl', 'replace' );
		}
	}

	/**
	 * Register Stripe.js when the card checkout bridge is not active on the current surface.
	 */
	private function register_stripe_script(): void {
		if ( wp_script_is( self::STRIPE_SCRIPT_HANDLE, 'registered' ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_script( self::STRIPE_SCRIPT_HANDLE, self::STRIPE_SCRIPT_URL, array(), null, true );
	}

	/**
	 * Tell whether the current request is a supported shopper frontend surface.
	 *
	 * @return bool
	 */
	private function is_supported_frontend_surface(): bool {
		if ( $this->is_pay_for_order_surface() ) {
			return true;
		}

		if ( $this->is_block_cart_or_checkout_surface() ) {
			return false;
		}

		return $this->is_checkout_surface() ||
			$this->is_product_surface() ||
			( function_exists( 'is_cart' ) && is_cart() );
	}

	/**
	 * Tell whether the current request is a checkout page or checkout content page.
	 *
	 * @return bool
	 */
	private function is_checkout_surface(): bool {
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}

		$post_id = function_exists( 'get_queried_object_id' ) ? get_queried_object_id() : 0;
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post instanceof \WP_Post ) {
			$post = get_queried_object();
		}

		if ( ! $post instanceof \WP_Post ) {
			$post = get_post();
		}

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		if ( function_exists( 'has_block' ) && has_block( 'woocommerce/checkout', $post ) ) {
			return true;
		}

		return has_shortcode( $post->post_content, 'woocommerce_checkout' );
	}

	/**
	 * Tell whether the current request is a product page or product shortcode content page.
	 *
	 * @return bool
	 */
	private function is_product_surface(): bool {
		if ( function_exists( 'is_product' ) && is_product() ) {
			return true;
		}

		$post_id = function_exists( 'get_queried_object_id' ) ? get_queried_object_id() : 0;
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post instanceof \WP_Post ) {
			$post = get_queried_object();
		}

		if ( ! $post instanceof \WP_Post ) {
			$post = get_post();
		}

		return $post instanceof \WP_Post && has_shortcode( $post->post_content, 'product_page' );
	}

	/**
	 * Tell whether the current request renders a Blocks cart or checkout page.
	 *
	 * @return bool
	 */
	private function is_block_cart_or_checkout_surface(): bool {
		$post_id = function_exists( 'get_queried_object_id' ) ? get_queried_object_id() : 0;
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post instanceof \WP_Post ) {
			$post = get_queried_object();
		}

		if ( ! $post instanceof \WP_Post ) {
			$post = get_post();
		}

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		return has_block( 'woocommerce/cart', $post ) || has_block( 'woocommerce/checkout', $post );
	}

	/**
	 * Get the current express checkout button context.
	 *
	 * @return string
	 */
	private function get_current_button_context(): string {
		if ( $this->is_pay_for_order_surface() ) {
			return 'pay_for_order';
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return 'cart';
		}

		if ( $this->is_product_surface() ) {
			return 'product';
		}

		return 'checkout';
	}

	/**
	 * Tell whether the current request renders the classic order-pay surface.
	 *
	 * @return bool
	 */
	private function is_pay_for_order_surface(): bool {
		global $wp;

		return is_object( $wp ) && ! empty( $wp->query_vars['order-pay'] );
	}
}
