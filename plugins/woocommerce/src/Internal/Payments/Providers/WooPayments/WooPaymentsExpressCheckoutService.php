<?php
/**
 * WooPaymentsExpressCheckoutService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

/**
 * Native WooPayments express checkout helpers for platform payment methods.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsExpressCheckoutService {

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * WooPayments provider.
	 *
	 * @var WooPaymentsProvider
	 */
	private WooPaymentsProvider $provider;

	/**
	 * Frontend tracking controller.
	 *
	 * @var WooPaymentsFrontendTrackingController
	 */
	private WooPaymentsFrontendTrackingController $frontend_tracking_controller;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsAccountService             $account_service              WooPayments account service.
	 * @param WooPaymentsProvider                   $provider                     WooPayments provider.
	 * @param WooPaymentsFrontendTrackingController $frontend_tracking_controller Frontend tracking controller.
	 */
	final public function init( WooPaymentsAccountService $account_service, WooPaymentsProvider $provider, WooPaymentsFrontendTrackingController $frontend_tracking_controller ): void {
		$this->account_service              = $account_service;
		$this->provider                     = $provider;
		$this->frontend_tracking_controller = $frontend_tracking_controller;
	}

	/**
	 * Tell whether the payment-request express checkout button should be shown.
	 *
	 * @param string $context Express checkout context.
	 * @return bool
	 */
	public function should_show_payment_request_button( string $context = 'checkout' ): bool {
		$context = $this->normalize_button_context( $context );

		if ( ! $this->provider->can_process_payments() ) {
			return false;
		}

		if ( 'pay_for_order' === $context && ! $this->is_pay_for_order_supported() ) {
			return false;
		}

		if ( 'product' === $context && ! $this->is_product_supported() ) {
			return false;
		}

		return ! empty( $this->get_allowed_payment_method_types_for_context( $context, $this->get_context_currency( $context ) ) );
	}

	/**
	 * Get express checkout params for Apple Pay and Google Pay.
	 *
	 * @param string $context Express checkout context.
	 * @return array<string,mixed>
	 */
	public function get_express_checkout_params( string $context = 'checkout' ): array {
		$context          = $this->normalize_button_context( $context );
		$context_currency = $this->get_context_currency( $context );
		$currency         = strtolower( '' === $context_currency ? get_woocommerce_currency() : $context_currency );
		$decimals         = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
		$tracking_enabled = $this->get_frontend_tracking_controller()->is_shopper_tracking_enabled();

		$params = array(
			'ajax_url'                    => admin_url( 'admin-ajax.php' ),
			'wc_ajax_url'                 => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'nonce'                       => array(
				'platform_tracker'             => wp_create_nonce( 'platform_tracks_nonce' ),
				'tokenized_cart_nonce'         => wp_create_nonce( 'woopayments_tokenized_cart_nonce' ),
				'tokenized_cart_session_nonce' => wp_create_nonce( 'woopayments_tokenized_cart_session_nonce' ),
				'store_api_nonce'              => wp_create_nonce( 'wc_store_api' ),
			),
			'checkout'                    => array(
				'currency_code'              => $currency,
				'currency_decimals'          => $decimals,
				'stripe_minor_unit'          => WooPaymentsCurrencyUtils::get_stripe_minor_unit_for_currency( $currency ),
				'country_code'               => $this->get_store_base_country(),
				'needs_shipping'             => function_exists( 'WC' ) && WC() && WC()->cart ? WC()->cart->needs_shipping() : false,
				'needs_payer_phone'          => 'required' === get_option( 'woocommerce_checkout_phone_field', 'required' ),
				'allowed_shipping_countries' => function_exists( 'WC' ) && WC() && WC()->countries ? array_keys( WC()->countries->get_shipping_countries() ?? array() ) : array(),
				'display_prices_with_tax'    => 'incl' === get_option( 'woocommerce_tax_display_cart' ),
			),
			'has_subscription'            => class_exists( '\WC_Subscriptions_Cart' ) && is_callable( array( '\WC_Subscriptions_Cart', 'cart_contains_subscription' ) ) && \WC_Subscriptions_Cart::cart_contains_subscription(),
			'is_manual_capture'           => $this->is_truthy_gateway_setting( 'manual_capture' ),
			'isShopperTrackingEnabled'    => $tracking_enabled,
			'is_shopper_tracking_enabled' => $tracking_enabled,
			'button'                      => $this->get_button_settings( $context ),
			'login_confirmation'          => false,
			'button_context'              => $context,
			'has_block'                   => has_block( 'woocommerce/cart' ) || has_block( 'woocommerce/checkout' ),
			'product'                     => 'product' === $context ? $this->get_product_data() : array(),
			'store_name'                  => get_bloginfo( 'name' ),
			'enabled_methods'             => $this->get_enabled_methods_for_context( $context, $context_currency ),
			'payment_method_types'        => $this->get_allowed_payment_method_types_for_context( $context, $context_currency ),
			'stripe'                      => array(
				'publishableKey' => $this->account_service->get_publishable_key(),
				'accountId'      => $this->account_service->get_account_id(),
				'locale'         => $this->get_stripe_locale(),
			),
			'flags'                       => array(
				'isEceUsingConfirmationTokens' => true,
			),
		);

		return 'pay_for_order' === $context ? array_merge( $params, $this->get_pay_for_order_params() ) : $params;
	}

	/**
	 * Get the frontend tracking controller.
	 *
	 * @return WooPaymentsFrontendTrackingController
	 */
	private function get_frontend_tracking_controller(): WooPaymentsFrontendTrackingController {
		if ( ! isset( $this->frontend_tracking_controller ) ) {
			$this->frontend_tracking_controller = wc_get_container()->get( WooPaymentsFrontendTrackingController::class );
		}

		return $this->frontend_tracking_controller;
	}

	/**
	 * Get enabled express checkout platform methods for a context.
	 *
	 * @param string $context Express checkout context.
	 * @param string $currency Optional order/cart currency.
	 * @return array<int,string>
	 */
	public function get_enabled_methods_for_context( string $context = 'checkout', string $currency = '' ): array {
		$context = $this->normalize_button_context( $context );
		$methods = $this->get_configured_methods_for_context( $context );

		/**
		 * Filters native WooPayments platform express checkout methods for a context.
		 *
		 * @param array<int,string>                 $methods Enabled method IDs.
		 * @param string                            $context Express checkout context.
		 * @param WooPaymentsExpressCheckoutService $service Native express checkout service.
		 *
		 * @since 11.0.0
		 */
		$filtered_methods = apply_filters( 'woocommerce_native_woopayments_express_checkout_enabled_methods', $methods, $context, $this );
		$filtered_methods = is_array( $filtered_methods ) ? $this->normalize_method_list( $filtered_methods ) : $methods;

		return array_values(
			array_filter(
				$filtered_methods,
				function ( string $method ) use ( $context, $currency ): bool {
					if ( WooPaymentsExpressPaymentMethodTypes::EXPRESS_METHOD_AMAZON_PAY !== $method ) {
						return true;
					}

					return in_array(
						WooPaymentsExpressPaymentMethodTypes::STRIPE_TYPE_AMAZON_PAY,
						WooPaymentsExpressPaymentMethodTypes::get_allowed_payment_method_types_for_methods( $this->account_service, array( $method ), $context, $currency ),
						true
					);
				}
			)
		);
	}

	/**
	 * Get server-allowed Stripe payment method types for a context.
	 *
	 * @param string $context Express checkout context.
	 * @param string $currency Optional order/cart currency.
	 * @return array<int,string>
	 */
	public function get_allowed_payment_method_types_for_context( string $context = 'checkout', string $currency = '' ): array {
		$context = $this->normalize_button_context( $context );

		return WooPaymentsExpressPaymentMethodTypes::get_allowed_payment_method_types_for_methods(
			$this->account_service,
			$this->get_enabled_methods_for_context( $context, $currency ),
			$context,
			$currency
		);
	}

	/**
	 * Tell whether the gateway-level payment-request switch is enabled.
	 *
	 * @return bool
	 */
	private function is_payment_request_enabled(): bool {
		return $this->is_truthy_gateway_setting( WooPaymentsExpressPaymentMethodTypes::EXPRESS_METHOD_PAYMENT_REQUEST );
	}

	/**
	 * Get configured express checkout methods for a context.
	 *
	 * @param string $context Express checkout context.
	 * @return array<int,string>
	 */
	private function get_configured_methods_for_context( string $context ): array {
		$setting_context = 'pay_for_order' === $context ? 'checkout' : $context;
		$setting_key     = 'express_checkout_' . $setting_context . '_methods';
		$methods         = $this->account_service->get_gateway_setting( $setting_key, null );

		if ( is_array( $methods ) ) {
			return $this->normalize_method_list( $methods );
		}

		return $this->is_payment_request_enabled() ? array( WooPaymentsExpressPaymentMethodTypes::EXPRESS_METHOD_PAYMENT_REQUEST ) : array();
	}

	/**
	 * Normalize express checkout method IDs.
	 *
	 * @param array<int,mixed> $methods Method IDs.
	 * @return array<int,string>
	 */
	private function normalize_method_list( array $methods ): array {
		$normalized = array();

		foreach ( $methods as $method ) {
			if ( ! is_scalar( $method ) ) {
				continue;
			}

			$method = sanitize_key( (string) $method );
			if ( '' !== $method ) {
				$normalized[] = $method;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Get button settings shared by Apple Pay and Google Pay.
	 *
	 * @param string $context Express checkout context.
	 * @return array<string,string>
	 */
	private function get_button_settings( string $context ): array {
		$type = $this->get_string_gateway_setting( 'payment_request_button_type', 'default' );

		return array(
			'type'         => $type,
			'theme'        => $this->get_string_gateway_setting( 'payment_request_button_theme', 'dark' ),
			'height'       => $this->get_button_height(),
			'radius'       => $this->get_string_gateway_setting_allow_empty( 'payment_request_button_border_radius', '' ),
			'size'         => $this->get_string_gateway_setting( 'payment_request_button_size', 'default' ),
			'context'      => $this->normalize_button_context( $context ),
			'locale'       => substr( get_locale(), 0, 2 ),
			'branded_type' => 'default' === $type ? 'short' : 'long',
		);
	}

	/**
	 * Get the button height from the express checkout size setting.
	 *
	 * @return string
	 */
	private function get_button_height(): string {
		$size = $this->get_string_gateway_setting( 'payment_request_button_size', 'medium' );

		if ( 'medium' === $size ) {
			return '48';
		}

		if ( 'large' === $size ) {
			return '55';
		}

		return '40';
	}

	/**
	 * Normalize an express checkout context.
	 *
	 * @param string $context Context.
	 * @return string
	 */
	private function normalize_button_context( string $context ): string {
		$context = sanitize_key( $context );

		return in_array( $context, array( 'product', 'cart', 'checkout', 'pay_for_order' ), true ) ? $context : 'checkout';
	}

	/**
	 * Tell whether the current order-pay surface can show ECE.
	 *
	 * @return bool
	 */
	private function is_pay_for_order_supported(): bool {
		$order = $this->get_pay_for_order_order();
		if ( ! $order instanceof \WC_Order || ! $order->needs_payment() ) {
			return false;
		}

		if ( '' === $order->get_billing_email() ) {
			return false;
		}

		$key = $this->get_pay_for_order_key();
		if ( '' === $key || ! hash_equals( $order->get_order_key(), $key ) ) {
			return false;
		}

		return current_user_can( 'pay_for_order', $order->get_id() );
	}

	/**
	 * Tell whether the current product page can show ECE.
	 *
	 * @return bool
	 */
	private function is_product_supported(): bool {
		$product = $this->get_product_for_product_page();
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		$supported_types = array(
			'simple',
			'variable',
			'variation',
			'subscription',
			'variable-subscription',
			'subscription_variation',
			'booking',
			'bundle',
			'composite',
			'mix-and-match',
		);

		/**
		 * Filters WooPayments product types that can render product-page express checkout.
		 *
		 * @param array<int,string> $supported_types Product type IDs.
		 *
		 * @since 11.0.0
		 */
		$supported_types = apply_filters( 'wcpay_payment_request_supported_types', $supported_types );

		/**
		 * Filters native WooPayments product types that can render product-page express checkout.
		 *
		 * @param array<int,string>                 $supported_types Product type IDs.
		 * @param \WC_Product                       $product         Product object.
		 * @param WooPaymentsExpressCheckoutService $service         Native express checkout service.
		 *
		 * @since 11.0.0
		 */
		$supported_types = apply_filters( 'woocommerce_native_woopayments_express_checkout_product_types', $supported_types, $product, $this );
		if ( ! is_array( $supported_types ) || ! in_array( $product->get_type(), $supported_types, true ) ) {
			return false;
		}

		$supported = ! $this->is_reference_blocked_product( $product ) && $this->get_product_price( $product ) > 0;

		/**
		 * Filters whether a product can show WooPayments product-page express checkout.
		 *
		 * @param bool        $supported Whether the product is supported.
		 * @param \WC_Product $product   Product object.
		 *
		 * @since 11.0.0
		 */
		$supported = (bool) apply_filters( 'wcpay_payment_request_is_product_supported', $supported, $product );

		/**
		 * Filters whether a product can show native WooPayments product-page express checkout.
		 *
		 * @param bool                              $supported Whether the product is supported.
		 * @param \WC_Product                       $product   Product object.
		 * @param WooPaymentsExpressCheckoutService $service   Native express checkout service.
		 *
		 * @since 11.0.0
		 */
		return (bool) apply_filters( 'woocommerce_native_woopayments_express_checkout_is_product_supported', $supported, $product, $this );
	}

	/**
	 * Get reference-shaped product data for product-page ECE.
	 *
	 * @return array<string,mixed>
	 */
	private function get_product_data(): array {
		$product = $this->get_product_for_product_page();
		if ( ! $product instanceof \WC_Product ) {
			return array();
		}

		$currency  = get_woocommerce_currency();
		$price     = $this->get_product_price( $product );
		$total_tax = 0.0;
		$items     = array(
			array(
				'label'  => $product->get_name(),
				'amount' => $this->prepare_amount( $price ),
			),
		);

		foreach ( $this->get_taxes_like_cart( $product, $price ) as $tax ) {
			$tax        = (float) $tax;
			$total_tax += $tax;
			$items[]    = array(
				'label'   => __( 'Tax', 'woocommerce' ),
				'amount'  => $this->prepare_amount( $tax ),
				'pending' => 0.0 === $tax,
			);
		}

		/**
		 * Filters WooPayments product-page express checkout total label.
		 *
		 * @param string $label Total label.
		 *
		 * @since 11.0.0
		 */
		$total_label = apply_filters( 'wcpay_payment_request_total_label', get_bloginfo( 'name' ) );

		$data = array(
			'displayItems'   => $items,
			'total'          => array(
				'label'   => $total_label,
				'amount'  => $this->prepare_amount( $price + $total_tax ),
				'pending' => true,
			),
			'needs_shipping' => $this->product_needs_shipping( $product ),
			'currency'       => strtolower( $currency ),
			'country_code'   => $this->get_store_base_country(),
			'product_type'   => $product->get_type(),
		);

		if ( $data['needs_shipping'] ) {
			$data['shippingOptions'] = array(
				'id'     => 'pending',
				'label'  => __( 'Pending', 'woocommerce' ),
				'detail' => '',
				'amount' => 0,
			);
		}

		/**
		 * Filters WooPayments product-page express checkout product data.
		 *
		 * @param array<string,mixed> $data    Product data.
		 * @param \WC_Product        $product Product object.
		 *
		 * @since 11.0.0
		 */
		$data = apply_filters( 'wcpay_payment_request_product_data', $data, $product );

		/**
		 * Filters native WooPayments product-page express checkout product data.
		 *
		 * @param array<string,mixed>                $data    Product data.
		 * @param \WC_Product                       $product Product object.
		 * @param WooPaymentsExpressCheckoutService $service Native express checkout service.
		 *
		 * @since 11.0.0
		 */
		$filtered_data = apply_filters( 'woocommerce_native_woopayments_express_checkout_product_data', $data, $product, $this );

		return is_array( $filtered_data ) ? $filtered_data : $data;
	}

	/**
	 * Tell whether the product hits a reference WooPayments product-page ECE fail-closed guardrail.
	 *
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	private function is_reference_blocked_product( \WC_Product $product ): bool {
		if ( class_exists( '\WC_Pre_Orders_Product' ) && \WC_Pre_Orders_Product::product_is_charged_upon_release( $product ) ) {
			return true;
		}

		if ( class_exists( '\WC_Composite_Products' ) && $product->is_type( 'composite' ) ) {
			return true;
		}

		if ( class_exists( '\WC_Mix_and_Match' ) && $product->is_type( 'mix-and-match' ) ) {
			return true;
		}

		if (
			class_exists( '\WC_Subscriptions_Product' ) &&
			\WC_Subscriptions_Product::is_subscription( $product ) &&
			\WC_Subscriptions_Product::get_trial_length( $product ) > 0 &&
			0.0 >= (float) \WC_Subscriptions_Product::get_sign_up_fee( $product )
		) {
			return true;
		}

		if ( class_exists( '\WC_Product_Addons_Helper' ) ) {
			$product_addons = \WC_Product_Addons_Helper::get_product_addons( $product->get_id() );
			foreach ( $product_addons as $addon ) {
				if ( is_array( $addon ) && 'file_upload' === ( $addon['type'] ?? '' ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get the current product-page product.
	 *
	 * @return \WC_Product|null
	 */
	private function get_product_for_product_page(): ?\WC_Product {
		$current_product = $GLOBALS['product'] ?? null;
		if ( $current_product instanceof \WC_Product ) {
			return $current_product;
		}

		$shortcode_product = $this->get_product_from_product_page_shortcode();
		if ( $shortcode_product instanceof \WC_Product ) {
			return $shortcode_product;
		}

		$post_id = function_exists( 'get_queried_object_id' ) ? get_queried_object_id() : 0;
		if ( $post_id <= 0 ) {
			$post_id = get_the_ID();
		}

		$queried_product = $post_id ? wc_get_product( $post_id ) : null;

		return $queried_product instanceof \WC_Product ? $queried_product : null;
	}

	/**
	 * Get the product from a product_page shortcode in the current post.
	 *
	 * @return \WC_Product|null
	 */
	private function get_product_from_product_page_shortcode(): ?\WC_Product {
		$post = get_post();
		if ( ! $post instanceof \WP_Post || ! has_shortcode( $post->post_content, 'product_page' ) ) {
			return null;
		}

		if ( ! preg_match_all( '/' . get_shortcode_regex( array( 'product_page' ) ) . '/', $post->post_content, $matches, PREG_SET_ORDER ) ) {
			return null;
		}

		foreach ( $matches as $shortcode ) {
			if ( 'product_page' !== $shortcode[2] ) {
				continue;
			}

			$atts = shortcode_parse_atts( $shortcode[3] );
			if ( ! is_array( $atts ) ) {
				continue;
			}

			$product_id = isset( $atts['id'] ) ? absint( $atts['id'] ) : 0;
			if ( ! $product_id && isset( $atts['sku'] ) && is_scalar( $atts['sku'] ) ) {
				$sku        = wc_clean( wp_unslash( (string) $atts['sku'] ) );
				$sku        = is_scalar( $sku ) ? (string) $sku : '';
				$product_id = '' !== $sku ? wc_get_product_id_by_sku( $sku ) : 0;
			}

			$product = $product_id ? wc_get_product( $product_id ) : null;
			if ( $product instanceof \WC_Product ) {
				return $product;
			}
		}

		return null;
	}

	/**
	 * Get the product display price for ECE product data.
	 *
	 * @param \WC_Product $product Product object.
	 * @return float
	 */
	private function get_product_price( \WC_Product $product ): float {
		$price = $product->get_price();
		if ( '' === $price ) {
			return 0.0;
		}

		return (float) ( $this->cart_prices_include_tax() ? wc_get_price_including_tax(
			$product,
			array(
				'qty'   => 1,
				'price' => (float) $price,
			)
		) : wc_get_price_excluding_tax(
			$product,
			array(
				'qty'   => 1,
				'price' => (float) $price,
			)
		) );
	}

	/**
	 * Tell whether product prices are displayed as tax-inclusive in cart-like contexts.
	 *
	 * @return bool
	 */
	private function cart_prices_include_tax(): bool {
		return ! wc_tax_enabled() || 'incl' === get_option( 'woocommerce_tax_display_cart' );
	}

	/**
	 * Calculate product taxes like cart totals for product-page ECE display.
	 *
	 * @param \WC_Product $product Product object.
	 * @param float       $price   Display price.
	 * @return array<int|float>
	 */
	private function get_taxes_like_cart( \WC_Product $product, float $price ): array {
		if ( ! wc_tax_enabled() || $this->cart_prices_include_tax() ) {
			return array();
		}

		return \WC_Tax::calc_tax( $price, \WC_Tax::get_rates( $product->get_tax_class() ), false );
	}

	/**
	 * Convert a decimal WooCommerce amount to a Stripe minor-unit amount.
	 *
	 * @param float $amount Decimal amount.
	 * @return int
	 */
	private function prepare_amount( float $amount ): int {
		return (int) round( $amount * ( 10 ** wc_get_price_decimals() ) );
	}

	/**
	 * Tell whether product-page ECE should ask Stripe for shipping.
	 *
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	private function product_needs_shipping( \WC_Product $product ): bool {
		return wc_shipping_enabled() && 0 !== wc_get_shipping_method_count( true ) && $product->needs_shipping();
	}

	/**
	 * Get the currency that should drive context-specific Stripe Elements eligibility.
	 *
	 * @param string $context Express checkout context.
	 * @return string
	 */
	private function get_context_currency( string $context ): string {
		if ( 'pay_for_order' !== $context ) {
			return '';
		}

		$order = $this->get_pay_for_order_order();

		return $order instanceof \WC_Order ? (string) $order->get_currency() : '';
	}

	/**
	 * Get pay-for-order params for the frontend.
	 *
	 * @return array<string,mixed>
	 */
	private function get_pay_for_order_params(): array {
		$order = $this->get_pay_for_order_order();
		if ( ! $order instanceof \WC_Order ) {
			return array();
		}

		return array(
			'order_id'      => $order->get_id(),
			'pay_for_order' => $this->get_pay_for_order_flag(),
			'key'           => $this->get_pay_for_order_key(),
			'billing_email' => $order->get_billing_email(),
		);
	}

	/**
	 * Get the current order-pay order.
	 *
	 * @return \WC_Order|null
	 */
	private function get_pay_for_order_order(): ?\WC_Order {
		$order_id = $this->get_pay_for_order_id();
		if ( $order_id <= 0 ) {
			return null;
		}

		$order = wc_get_order( $order_id );

		return $order instanceof \WC_Order ? $order : null;
	}

	/**
	 * Get the current order-pay order ID.
	 *
	 * @return int
	 */
	private function get_pay_for_order_id(): int {
		global $wp;

		if ( is_object( $wp ) && isset( $wp->query_vars['order-pay'] ) ) {
			return absint( $wp->query_vars['order-pay'] );
		}

		return 0;
	}

	/**
	 * Get the current order-pay key.
	 *
	 * @return string
	 */
	private function get_pay_for_order_key(): string {
		if ( ! isset( $_GET['key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '';
		}

		return sanitize_text_field( wp_unslash( $_GET['key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get the current pay-for-order flag value.
	 *
	 * @return string
	 */
	private function get_pay_for_order_flag(): string {
		if ( ! isset( $_GET['pay_for_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '';
		}

		return sanitize_text_field( wp_unslash( $_GET['pay_for_order'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get a string gateway setting.
	 *
	 * @param string $key      Setting key.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private function get_string_gateway_setting( string $key, string $fallback ): string {
		$value = $this->account_service->get_gateway_setting( $key, $fallback );

		return is_scalar( $value ) && '' !== (string) $value ? sanitize_text_field( (string) $value ) : $fallback;
	}

	/**
	 * Get a string gateway setting while preserving empty string values.
	 *
	 * @param string $key      Setting key.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private function get_string_gateway_setting_allow_empty( string $key, string $fallback ): string {
		$value = $this->account_service->get_gateway_setting( $key, $fallback );

		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : $fallback;
	}

	/**
	 * Tell whether a gateway setting is truthy.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	private function is_truthy_gateway_setting( string $key ): bool {
		$value = $this->account_service->get_gateway_setting( $key, 'no' );

		return true === $value || 'yes' === $value || '1' === $value || 1 === $value;
	}

	/**
	 * Get the store base country.
	 *
	 * @return string
	 */
	private function get_store_base_country(): string {
		$country = (string) get_option( 'woocommerce_default_country', 'US' );
		if ( function_exists( 'WC' ) && WC() && WC()->countries ) {
			$country = (string) WC()->countries->get_base_country();
		}

		if ( false !== strpos( $country, ':' ) ) {
			$base_country = strtok( $country, ':' );
			$country      = is_string( $base_country ) ? $base_country : '';
		}

		$country = strtoupper( $country );

		return '' !== $country ? $country : 'US';
	}

	/**
	 * Get a Stripe-compatible locale.
	 *
	 * @return string
	 */
	private function get_stripe_locale(): string {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$locale = strtolower( str_replace( '_', '-', (string) $locale ) );

		return '' !== $locale ? $locale : 'auto';
	}
}
