<?php
/**
 * WooPaymentsCheckoutBridge class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;

/**
 * Owns the transitional Core checkout surface for the WooPayments card gateway.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsCheckoutBridge {
	/**
	 * WooPayments checkout support features exposed to Checkout Blocks.
	 *
	 * @var string[]
	 */
	private const BLOCKS_SUPPORTS = array(
		'products',
		'subscriptions',
		'multiple_subscriptions',
		'subscription_cancellation',
		'subscription_suspension',
		'subscription_reactivation',
		'subscription_amount_changes',
		'subscription_date_changes',
		'subscription_payment_method_change',
		'subscription_payment_method_change_customer',
		'subscription_payment_method_change_admin',
	);


	/**
	 * Core-owned classic checkout script handle.
	 */
	private const CLASSIC_SCRIPT_HANDLE = 'wc-woopayments-checkout';

	/**
	 * Core-owned classic checkout style handle.
	 */
	private const CLASSIC_STYLE_HANDLE = 'wc-woopayments-checkout';

	/**
	 * Stripe.js script handle.
	 */
	private const STRIPE_SCRIPT_HANDLE = 'stripe';

	/**
	 * Country-specific Stripe test card numbers used by WooPayments checkout.
	 *
	 * @var array<string,string>
	 */
	private const COUNTRY_TEST_CARDS = array(
		'US' => '4242 4242 4242 4242',
		'AR' => '4000 0003 2000 0021',
		'BR' => '4000 0007 6000 0002',
		'CA' => '4000 0012 4000 0000',
		'CL' => '4000 0015 2000 0001',
		'CO' => '4000 0017 0000 0003',
		'CR' => '4000 0018 8000 0005',
		'EC' => '4000 0021 8000 0000',
		'MX' => '4000 0048 4000 8001',
		'PA' => '4000 0059 1000 0000',
		'PY' => '4000 0060 0000 0066',
		'PE' => '4000 0060 4000 0068',
		'UY' => '4000 0085 8000 0003',
		'AE' => '4000 0078 4000 0001',
		'AT' => '4000 0004 0000 0008',
		'BE' => '4000 0005 6000 0004',
		'BG' => '4000 0010 0000 0000',
		'BY' => '4000 0011 2000 0005',
		'HR' => '4000 0019 1000 0009',
		'CY' => '4000 0019 6000 0008',
		'CZ' => '4000 0020 3000 0002',
		'DK' => '4000 0020 8000 0001',
		'EE' => '4000 0023 3000 0009',
		'FI' => '4000 0024 6000 0001',
		'FR' => '4000 0025 0000 0003',
		'DE' => '4000 0027 6000 0016',
		'GI' => '4000 0029 2000 0005',
		'GR' => '4000 0030 0000 0030',
		'HU' => '4000 0034 8000 0005',
		'IE' => '4000 0037 2000 0005',
		'IT' => '4000 0038 0000 0008',
		'LV' => '4000 0042 8000 0005',
		'LI' => '4000 0043 8000 0004',
		'LT' => '4000 0044 0000 0000',
		'LU' => '4000 0044 2000 0006',
		'MT' => '4000 0047 0000 0007',
		'NL' => '4000 0052 8000 0002',
		'NO' => '4000 0057 8000 0007',
		'PL' => '4000 0061 6000 0005',
		'PT' => '4000 0062 0000 0007',
		'RO' => '4000 0064 2000 0001',
		'SA' => '4000 0068 2000 0007',
		'SI' => '4000 0070 5000 0006',
		'SK' => '4000 0070 3000 0001',
		'ES' => '4000 0072 4000 0007',
		'SE' => '4000 0075 2000 0008',
		'CH' => '4000 0075 6000 0009',
		'GB' => '4000 0082 6000 0000',
		'AU' => '4000 0003 6000 0006',
		'CN' => '4000 0015 6000 0002',
		'HK' => '4000 0034 4000 0004',
		'IN' => '4000 0035 6000 0008',
		'JP' => '4000 0039 2000 0003',
		'MY' => '4000 0045 8000 0002',
		'NZ' => '4000 0055 4000 0008',
		'SG' => '4000 0070 2000 0003',
		'TW' => '4000 0015 8000 0008',
		'TH' => '4000 0076 4000 0003',
	);

	/**
	 * Shopper-facing card brand icons.
	 *
	 * @var array<string,string>
	 */
	private const CARD_BRAND_ICONS = array(
		'visa'       => 'Visa',
		'mastercard' => 'Mastercard',
		'amex'       => 'American Express',
		'discover'   => 'Discover',
		'jcb'        => 'JCB',
		'unionpay'   => 'Union Pay',
	);

	/**
	 * France-only shopper-facing card brand icons.
	 *
	 * @var array<string,string>
	 */
	private const FR_CARD_BRAND_ICONS = array(
		'cartes_bancaires' => 'Cartes Bancaires',
	);

	/**
	 * Shopper-facing card brand icon asset paths.
	 *
	 * @var array<string,string>
	 */
	private const CARD_BRAND_ICON_ASSETS = array(
		'visa'             => 'payment-methods-cards/visa.svg',
		'mastercard'       => 'payment-methods-cards/mastercard.svg',
		'amex'             => 'payment-methods-cards/amex.svg',
		'discover'         => 'payment-methods-cards/discover.svg',
		'jcb'              => 'woopayments-card-brands/jcb.svg',
		'unionpay'         => 'woopayments-card-brands/unionpay.svg',
		'cartes_bancaires' => 'payment-methods-cards/cartes_bancaires.svg',
	);

	/**
	 * WooPayments legacy runtime.
	 *
	 * @var WooPaymentsLegacyRuntime
	 */
	private WooPaymentsLegacyRuntime $legacy_runtime;

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * WooPay session service.
	 *
	 * @var WooPaymentsWooPaySessionService
	 */
	private WooPaymentsWooPaySessionService $woopay_session_service;

	/**
	 * Shared frontend styles service.
	 *
	 * @var WooPaymentsFrontendStylesService
	 */
	private WooPaymentsFrontendStylesService $frontend_styles_service;

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
	 * @param WooPaymentsLegacyRuntime              $legacy_runtime               WooPayments legacy runtime.
	 * @param WooPaymentsAccountService             $account_service              WooPayments account service.
	 * @param WooPaymentsWooPaySessionService       $woopay_session_service       WooPay session service.
	 * @param WooPaymentsFrontendStylesService      $frontend_styles_service      Shared frontend styles service.
	 * @param WooPaymentsFrontendTrackingController $frontend_tracking_controller Frontend tracking controller.
	 */
	final public function init( WooPaymentsLegacyRuntime $legacy_runtime, WooPaymentsAccountService $account_service, WooPaymentsWooPaySessionService $woopay_session_service, WooPaymentsFrontendStylesService $frontend_styles_service, WooPaymentsFrontendTrackingController $frontend_tracking_controller ): void {
		$this->legacy_runtime               = $legacy_runtime;
		$this->account_service              = $account_service;
		$this->woopay_session_service       = $woopay_session_service;
		$this->frontend_styles_service      = $frontend_styles_service;
		$this->frontend_tracking_controller = $frontend_tracking_controller;
	}

	/**
	 * Tell whether the bridge has enough data to expose the shopper checkout UI.
	 *
	 * @return bool
	 */
	public function should_expose_checkout_surface(): bool {
		return $this->get_account_service()->can_process_payments();
	}

	/**
	 * Get the classic checkout JS config.
	 *
	 * @return array<string,mixed>
	 */
	public function get_payment_fields_js_config(): array {
		$force_network_saved_cards = $this->should_force_network_saved_cards();
		$saved_cards_enabled       = $this->is_saved_cards_enabled();
		$config                    = array(
			'publishableKey'                => $this->get_account_service()->get_publishable_key(),
			'accountId'                     => $this->get_account_service()->get_account_id(),
			'locale'                        => $this->get_stripe_locale(),
			'gatewayId'                     => OrderPaymentStore::GATEWAY_ID,
			'ajaxUrl'                       => admin_url( 'admin-ajax.php' ),
			'wcAjaxUrl'                     => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'paymentMethodsConfig'          => $this->get_payment_methods_config( $saved_cards_enabled ),
			'testMode'                      => $this->get_account_service()->is_test_mode_enabled(),
			'enabledBillingFields'          => $this->get_enabled_billing_fields(),
			'currency'                      => get_woocommerce_currency(),
			'cartTotal'                     => $this->get_cart_total(),
			'storeCountry'                  => $this->get_account_country(),
			'cartContainsSubscription'      => $this->cart_contains_subscription(),
			'stylesCacheVersion'            => $this->get_frontend_styles_service()->get_styles_cache_version(),
			'forceNetworkSavedCards'        => $force_network_saved_cards,
			'isSavedCardsEnabled'           => $saved_cards_enabled,
			'customerData'                  => $this->get_legacy_runtime()->get_gateway_prepared_customer_data(),
			'usesLegacySetupIntentBridge'   => false,
			'usesLegacyOrderStatusBridge'   => false,
			'usesNativeSetupIntentBridge'   => true,
			'usesNativeOrderStatusBridge'   => true,
			'isCheckout'                    => function_exists( 'is_checkout' ) && is_checkout(),
			'isCoreNativeCheckoutBridge'    => true,
			'isCoreNativeCheckoutAvailable' => $this->should_expose_checkout_surface(),
			'isWooPayEnabled'               => false,
			'isShopperTrackingEnabled'      => $this->get_frontend_tracking_controller()->is_shopper_tracking_enabled(),
			'confirmationErrorMessage'      => __( 'There was a problem confirming your payment.', 'woocommerce' ),
		);

		if ( $this->should_expose_checkout_surface() ) {
			$config['createSetupIntentNonce'] = wp_create_nonce( 'wcpay_create_setup_intent_nonce' );
			$config['updateOrderStatusNonce'] = wp_create_nonce( 'wcpay_update_order_status_nonce' );

			if ( $this->get_woopay_session_service()->is_woopay_enabled() ) {
				$config                           = array_merge(
					$config,
					$this->get_woopay_session_service()->get_woopay_frontend_config( 'checkout' )
				);
				$config['forceNetworkSavedCards'] = $force_network_saved_cards;
			}
		}

		/**
		 * Allows filtering of the JS config for the WooPayments payment fields.
		 *
		 * @since 11.0.0
		 *
		 * @param array $config The JS config for the payment fields.
		 */
		return apply_filters( 'wcpay_payment_fields_js_config', $config );
	}

	/**
	 * Render the classic checkout payment fields.
	 *
	 * @return void
	 */
	public function render_payment_fields(): void {
		$config      = $this->get_payment_fields_js_config();
		$json_config = wp_json_encode( $config );

		if ( ! is_string( $json_config ) ) {
			$json_config = '{}';
		}

		$this->register_classic_assets();
		wp_localize_script( self::CLASSIC_SCRIPT_HANDLE, 'wcpay_core_checkout_config', $config );
		wp_enqueue_style( self::CLASSIC_STYLE_HANDLE );
		wp_enqueue_script( self::CLASSIC_SCRIPT_HANDLE );

		if ( $this->should_expose_checkout_surface() ) {
			wp_enqueue_script( self::STRIPE_SCRIPT_HANDLE );
		}

		echo '<div id="wcpay-core-checkout-form" class="wcpay-core-checkout-form" data-wcpay-config="' . esc_attr( $json_config ) . '">';

		if ( ! empty( $config['testMode'] ) ) {
			$testing_instructions = $config['paymentMethodsConfig']['card']['testingInstructions'] ?? '';
			if ( is_string( $testing_instructions ) && '' !== $testing_instructions ) {
				echo '<p class="wcpay-core-test-mode-instructions testmode-info">';
				echo wp_kses_post( $testing_instructions );
				echo '</p>';
			}
		}

		echo '<div id="wcpay-core-payment-element" class="wcpay-core-payment-element"></div>';
		echo '<div id="wcpay-core-payment-errors" class="woocommerce-error wcpay-core-payment-errors" role="alert" hidden></div>';

		if ( ! $this->should_expose_checkout_surface() ) {
			echo '<p class="woocommerce-info wcpay-core-checkout-unavailable">';
			echo esc_html__( 'WooPayments checkout is not available right now. Please choose another payment method.', 'woocommerce' );
			echo '</p>';
		}

		echo '</div>';
	}

	/**
	 * Get Blocks payment method data.
	 *
	 * @return array<string,mixed>
	 */
	public function get_blocks_payment_method_data(): array {
		$data = $this->get_payment_fields_js_config();
		if ( ! empty( $data['isWooPayEnabled'] ) ) {
			$data = array_merge(
				$data,
				$this->get_woopay_session_service()->get_save_user_checkout_data()
			);
		}

		return array_merge(
			$data,
			array(
				'title'       => __( 'Card', 'woocommerce' ),
				'description' => __( 'Pay securely using WooPayments.', 'woocommerce' ),
				'supports'    => self::BLOCKS_SUPPORTS,
			)
		);
	}

	/**
	 * Register the classic checkout assets.
	 *
	 * @return void
	 */
	public function register_classic_assets(): void {
		if ( ! wp_script_is( self::STRIPE_SCRIPT_HANDLE, 'registered' ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_register_script( self::STRIPE_SCRIPT_HANDLE, 'https://js.stripe.com/v3/', array(), null, true );
		}

		$suffix = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';

		if ( ! wp_script_is( self::CLASSIC_SCRIPT_HANDLE, 'registered' ) ) {
			wp_register_script(
				self::CLASSIC_SCRIPT_HANDLE,
				WC()->plugin_url() . '/assets/js/frontend/woopayments-checkout' . $suffix . '.js',
				array( 'jquery', 'wc-checkout', self::STRIPE_SCRIPT_HANDLE ),
				WC_VERSION,
				true
			);
		}

		if ( ! wp_style_is( self::CLASSIC_STYLE_HANDLE, 'registered' ) ) {
			wp_register_style(
				self::CLASSIC_STYLE_HANDLE,
				WC()->plugin_url() . '/assets/css/woopayments-checkout.css',
				array(),
				WC_VERSION
			);
			wp_style_add_data( self::CLASSIC_STYLE_HANDLE, 'rtl', 'replace' );
		}
	}

	/**
	 * Get the WooPayments legacy runtime.
	 *
	 * @return WooPaymentsLegacyRuntime
	 */
	private function get_legacy_runtime(): WooPaymentsLegacyRuntime {
		if ( ! isset( $this->legacy_runtime ) ) {
			$this->legacy_runtime = wc_get_container()->get( WooPaymentsLegacyRuntime::class );
		}

		return $this->legacy_runtime;
	}

	/**
	 * Get the WooPayments account service.
	 *
	 * @return WooPaymentsAccountService
	 */
	private function get_account_service(): WooPaymentsAccountService {
		if ( ! isset( $this->account_service ) ) {
			$this->account_service = wc_get_container()->get( WooPaymentsAccountService::class );
		}

		return $this->account_service;
	}

	/**
	 * Get the WooPay session service.
	 *
	 * @return WooPaymentsWooPaySessionService
	 */
	private function get_woopay_session_service(): WooPaymentsWooPaySessionService {
		if ( ! isset( $this->woopay_session_service ) ) {
			$this->woopay_session_service = wc_get_container()->get( WooPaymentsWooPaySessionService::class );
		}

		return $this->woopay_session_service;
	}

	/**
	 * Get the card-only payment method config for this slice.
	 *
	 * @param bool $saved_cards_enabled Whether saved cards are enabled.
	 * @return array<string,array<string,mixed>>
	 */
	private function get_payment_methods_config( bool $saved_cards_enabled ): array {
		$enabled_method_ids = $this->get_legacy_runtime()->get_gateway_upe_enabled_payment_method_ids();
		if ( ! empty( $enabled_method_ids ) && ! in_array( 'card', $enabled_method_ids, true ) ) {
			return array();
		}

		return array(
			'card' => array(
				'id'                     => 'card',
				'title'                  => __( 'Card', 'woocommerce' ),
				'label'                  => __( 'Card', 'woocommerce' ),
				'isReusable'             => true,
				'isBnpl'                 => false,
				'isExpressCheckout'      => false,
				'forceNetworkSavedCards' => $this->should_force_network_saved_cards(),
				'cardBrandIcons'         => $this->get_card_brand_icons(),
				'showSaveOption'         => $this->should_show_card_save_option( $saved_cards_enabled ),
				'supports'               => self::BLOCKS_SUPPORTS,
				'testingInstructions'    => $this->get_card_testing_instructions(),
			),
		);
	}

	/**
	 * Get shopper-facing card test-mode instructions.
	 *
	 * @return string
	 */
	private function get_card_testing_instructions(): string {
		$test_card_number = $this->get_test_card_for_country( $this->get_account_country() );
		$test_card_button = sprintf(
			'<button type="button" class="js-woopayments-copy-test-number" aria-label="%1$s" title="%2$s"><i></i><span>%3$s</span></button>',
			esc_attr__( 'Click to copy the test number to clipboard', 'woocommerce' ),
			esc_attr__( 'Copy to clipboard', 'woocommerce' ),
			esc_html( $test_card_number )
		);
		$testing_guide    = sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_url( 'https://woocommerce.com/document/woopayments/testing-and-troubleshooting/testing/#test-cards' ),
			esc_html__( 'testing guide', 'woocommerce' )
		);

		return sprintf(
			/* translators: 1: Test card copy button, 2: Link to the WooPayments testing guide. */
			__( 'Use test card %1$s or refer to our %2$s.', 'woocommerce' ),
			$test_card_button,
			$testing_guide
		);
	}

	/**
	 * Get shopper-facing card brand icon data.
	 *
	 * @return array<int,array{id:string,alt:string,src:string}>
	 */
	private function get_card_brand_icons(): array {
		$icons = array();

		foreach ( $this->get_card_brand_icon_labels() as $brand => $label ) {
			$asset_path = self::CARD_BRAND_ICON_ASSETS[ $brand ] ?? 'payment-methods/' . $brand . '.svg';
			$icons[]    = array(
				'id'  => $brand,
				'alt' => $label,
				'src' => WC()->plugin_url() . '/assets/images/' . $asset_path,
			);
		}

		return $icons;
	}

	/**
	 * Get shopper-facing card brand labels for the connected account country.
	 *
	 * @return array<string,string>
	 */
	private function get_card_brand_icon_labels(): array {
		if ( 'FR' === $this->get_account_country() ) {
			return array_merge( self::CARD_BRAND_ICONS, self::FR_CARD_BRAND_ICONS );
		}

		return self::CARD_BRAND_ICONS;
	}

	/**
	 * Get the connected account country, falling back to the store base country.
	 *
	 * @return string
	 */
	private function get_account_country(): string {
		$account_data = $this->get_account_service()->get_cached_account_data();
		$country      = isset( $account_data['country'] ) && is_scalar( $account_data['country'] )
			? strtoupper( (string) $account_data['country'] )
			: '';

		if ( '' === $country && function_exists( 'WC' ) && WC() && WC()->countries ) {
			$country = strtoupper( (string) WC()->countries->get_base_country() );
		}

		if ( false !== strpos( $country, ':' ) ) {
			$base_country = strtok( $country, ':' );
			$country      = is_string( $base_country ) ? $base_country : '';
		}

		return '' !== $country ? $country : 'US';
	}

	/**
	 * Tell whether card checkout should use Stripe platform behavior for saved payment details.
	 *
	 * @return bool
	 */
	private function should_force_network_saved_cards(): bool {
		return $this->is_truthy_gateway_setting( 'force_network_saved_cards' ) || $this->should_use_stripe_platform_for_card_checkout();
	}

	/**
	 * Tell whether saved cards are enabled in the gateway settings.
	 *
	 * @return bool
	 */
	private function is_saved_cards_enabled(): bool {
		$value = $this->get_account_service()->get_gateway_setting( 'saved_cards', 'yes' );

		return true === $value || 'yes' === $value || '1' === $value || 1 === $value;
	}

	/**
	 * Tell whether card checkout should show the WooCommerce save-payment option.
	 *
	 * @param bool $saved_cards_enabled Whether saved cards are enabled.
	 * @return bool
	 */
	private function should_show_card_save_option( bool $saved_cards_enabled ): bool {
		if ( is_user_logged_in() && $this->get_woopay_session_service()->is_woopay_enabled() ) {
			return false;
		}

		return $saved_cards_enabled && ! $this->cart_contains_subscription();
	}

	/**
	 * Tell whether card checkout should initialize Stripe through the platform account.
	 *
	 * @return bool
	 */
	private function should_use_stripe_platform_for_card_checkout(): bool {
		$account_data = $this->get_account_service()->get_cached_account_data();
		if ( empty( $account_data['platform_checkout_eligible'] ) || 'yes' !== $this->get_string_gateway_setting( 'platform_checkout', 'no' ) ) {
			return false;
		}

		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
			return false;
		}

		return function_exists( 'WC' ) &&
			WC() &&
			WC()->cart instanceof \WC_Cart &&
			! WC()->cart->is_empty() &&
			WC()->cart->needs_payment();
	}

	/**
	 * Get a string WooPayments gateway setting.
	 *
	 * @param string $key Setting key.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private function get_string_gateway_setting( string $key, string $fallback ): string {
		$value = $this->get_account_service()->get_gateway_setting( $key, $fallback );

		return is_scalar( $value ) && '' !== (string) $value ? sanitize_text_field( (string) $value ) : $fallback;
	}

	/**
	 * Tell whether a WooPayments gateway setting is truthy.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	private function is_truthy_gateway_setting( string $key ): bool {
		$value = $this->get_account_service()->get_gateway_setting( $key, 'no' );

		return true === $value || 'yes' === $value || '1' === $value || 1 === $value;
	}

	/**
	 * Get the country-specific test card number.
	 *
	 * @param string $country Country code.
	 * @return string
	 */
	private function get_test_card_for_country( string $country ): string {
		$country = strtoupper( $country );

		return self::COUNTRY_TEST_CARDS[ $country ] ?? self::COUNTRY_TEST_CARDS['US'];
	}

	/**
	 * Get enabled billing field requirements.
	 *
	 * @return array<string,array{required:bool}>
	 */
	private function get_enabled_billing_fields(): array {
		if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->checkout() ) {
			return array();
		}

		$enabled_fields = array();
		$billing_fields = WC()->checkout()->get_checkout_fields( 'billing' );
		foreach ( $billing_fields as $field_key => $field_options ) {
			if ( isset( $field_options['enabled'] ) && ! $field_options['enabled'] ) {
				continue;
			}

			$enabled_fields[ (string) $field_key ] = array(
				'required' => ! empty( $field_options['required'] ),
			);
		}

		return $enabled_fields;
	}

	/**
	 * Get cart total in the current WooCommerce minor unit convention.
	 *
	 * @return int
	 */
	private function get_cart_total(): int {
		if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
			return 0;
		}

		$total    = (float) WC()->cart->get_total( '' );
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;

		return (int) round( $total * ( 10 ** $decimals ) );
	}

	/**
	 * Tell whether the current cart contains a subscription.
	 *
	 * @return bool
	 */
	private function cart_contains_subscription(): bool {
		return class_exists( '\WC_Subscriptions_Cart' ) &&
			is_callable( array( '\WC_Subscriptions_Cart', 'cart_contains_subscription' ) ) &&
			\WC_Subscriptions_Cart::cart_contains_subscription();
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

	/**
	 * Get the shared frontend styles service.
	 *
	 * @return WooPaymentsFrontendStylesService
	 */
	private function get_frontend_styles_service(): WooPaymentsFrontendStylesService {
		if ( ! isset( $this->frontend_styles_service ) ) {
			$this->frontend_styles_service = wc_get_container()->get( WooPaymentsFrontendStylesService::class );
		}

		return $this->frontend_styles_service;
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
}
