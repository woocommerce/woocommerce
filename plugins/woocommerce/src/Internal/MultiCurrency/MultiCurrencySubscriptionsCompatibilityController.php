<?php
/**
 * MultiCurrencySubscriptionsCompatibilityController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySubscriptionsCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Registers native multi-currency Subscriptions compatibility hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySubscriptionsCompatibilityController implements RegisterHooksInterface {

	private const SUBSCRIPTION_TYPES = array( 'renewal', 'resubscribe', 'switch' );

	private const PRODUCT_PRICE_CALCULATION_CALLS = array(
		'WC_Cart_Totals->calculate_item_totals',
		'WC_Cart->get_product_subtotal',
		'wc_get_price_excluding_tax',
		'wc_get_price_including_tax',
	);

	private const RENEWAL_SETUP_CALLS = array(
		'WCS_Cart_Renewal->setup_cart',
	);

	private const EARLY_RENEWAL_SETUP_CALLS = array(
		'WCS_Cart_Early_Renewal->setup_cart',
	);

	private const RECURRING_ITEM_DATA_CALLS = array(
		'WC_Payments_Subscription_Service->get_recurring_item_data_for_subscription',
	);

	private const PRODUCT_GET_PRICE_CALLS = array(
		'WC_Product->get_price',
	);

	private const APPLY_COUPON_CALLS = array(
		'WC_Discounts->apply_coupon',
	);

	private const SUBSCRIPTION_PRICE_SETUP_CALLS = array(
		'WC_Subscriptions_Cart::set_subscription_prices_for_calculation',
	);

	private const SUBSCRIPTION_PRODUCT_SIGNUP_FEE_CALLS = array(
		'WC_Subscriptions_Product::get_sign_up_fee',
	);

	private const CART_CALCULATE_TOTALS_CALLS = array(
		'WC_Cart->calculate_totals',
	);

	private const SWITCH_APPORTION_SIGNUP_FEE_CALLS = array(
		'WCS_Switch_Totals_Calculator->apportion_sign_up_fees',
	);

	private const MY_ACCOUNT_SUBSCRIPTIONS_TEMPLATE_CALLS = array(
		'WCS_Template_Loader::get_my_subscriptions',
		'WCS_Template_Loader::get_my_subscriptions ',
	);

	private const FORMATTED_SUBSCRIPTION_TOTAL_CALLS = array(
		'WC_Subscription->get_formatted_order_total',
	);

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Legacy proxy for mockable global calls.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * Price projection service.
	 *
	 * @var MultiCurrencyPriceProjectionService|null
	 */
	private ?MultiCurrencyPriceProjectionService $price_projection_service = null;

	/**
	 * State builder.
	 *
	 * @var MultiCurrencyStateBuilder|null
	 */
	private ?MultiCurrencyStateBuilder $state_builder = null;

	/**
	 * Previously observed subscription switch cart item key.
	 *
	 * @var string
	 */
	private string $switch_cart_item = '';

	/**
	 * Current My Account subscription being formatted.
	 *
	 * @var object|null
	 */
	private ?object $current_my_account_subscription = null;

	/**
	 * Whether selected-currency override lookups are already running.
	 *
	 * @var bool
	 */
	private bool $running_override_selected_currency_filters = false;

	/**
	 * State builder factory.
	 *
	 * @var MultiCurrencyStateBuilderFactory
	 */
	private MultiCurrencyStateBuilderFactory $state_builder_factory;

	/**
	 * Projection service factory.
	 *
	 * @var MultiCurrencyProjectionServiceFactory
	 */
	private MultiCurrencyProjectionServiceFactory $projection_service_factory;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter           $arbiter                    Runtime owner arbiter.
	 * @param LegacyProxy                           $legacy_proxy               Legacy proxy.
	 * @param MultiCurrencyStateBuilderFactory      $state_builder_factory      State builder factory.
	 * @param MultiCurrencyProjectionServiceFactory $projection_service_factory Projection service factory.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter, LegacyProxy $legacy_proxy, MultiCurrencyStateBuilderFactory $state_builder_factory, MultiCurrencyProjectionServiceFactory $projection_service_factory ): void {
		$this->arbiter                    = $arbiter;
		$this->legacy_proxy               = $legacy_proxy;
		$this->state_builder_factory      = $state_builder_factory;
		$this->projection_service_factory = $projection_service_factory;
	}

	/**
	 * Set the price projection service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyPriceProjectionService $price_projection_service Price projection service.
	 */
	public function set_price_projection_service( MultiCurrencyPriceProjectionService $price_projection_service ): void {
		$this->price_projection_service = $price_projection_service;
	}

	/**
	 * Set the state builder.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyStateBuilder $state_builder State builder.
	 */
	public function set_state_builder( MultiCurrencyStateBuilder $state_builder ): void {
		$this->state_builder = $state_builder;
	}

	/**
	 * Register Subscriptions compatibility hooks.
	 */
	public function register() {
		if (
			! $this->arbiter->should_core_register()
			|| $this->is_admin_request()
			|| $this->is_cron_request()
		) {
			return;
		}

		if ( $this->is_subscriptions_runtime_available() || $this->have_plugins_loaded() ) {
			$this->register_subscription_filters();
			return;
		}

		$this->add_action_once( 'plugins_loaded', array( $this, 'register_subscription_filters' ), 20 );
	}

	/**
	 * Register Subscriptions compatibility filters after supported runtimes have loaded.
	 *
	 * @internal
	 */
	public function register_subscription_filters(): void {
		if (
			! $this->arbiter->should_core_register()
			|| $this->is_admin_request()
			|| $this->is_cron_request()
			|| ! $this->is_subscriptions_runtime_available()
		) {
			return;
		}

		foreach ( MultiCurrencySubscriptionsCompatibilityProjectionService::get_hook_manifest()['filters'] as $filter ) {
			$callback = array( $this, (string) $filter['callback'] );
			if ( ! is_callable( $callback ) ) {
				continue;
			}

			$this->add_filter_once(
				(string) $filter['hook'],
				$callback,
				(int) $filter['priority'],
				(int) $filter['accepted_args']
			);
		}
	}

	/**
	 * Override selected currency from renewal, resubscribe, or switch subscription context.
	 *
	 * @param mixed $selected_currency Existing selected currency decision.
	 * @return mixed
	 */
	public function override_selected_currency( $selected_currency ) {
		if ( $selected_currency || $this->running_override_selected_currency_filters ) {
			return $selected_currency;
		}

		$currency_code = $this->get_subscription_currency( $this->current_my_account_subscription );
		if ( null !== $currency_code ) {
			return $currency_code;
		}

		foreach ( self::SUBSCRIPTION_TYPES as $type ) {
			$cart_item = $this->get_subscription_type_from_cart( $type );
			if ( null === $cart_item ) {
				continue;
			}

			$subscription                                     = null;
			$this->running_override_selected_currency_filters = true;

			try {
				$subscription = $this->get_subscription_from_cart_item( $cart_item, $type );
			} finally {
				$this->running_override_selected_currency_filters = false;
			}

			$currency_code = $this->get_subscription_currency( $subscription );

			return null === $currency_code ? $selected_currency : $currency_code;
		}

		$currency_code = $this->get_subscription_currency( $this->get_subscription_from_switch_request() );

		return null === $currency_code ? $selected_currency : $currency_code;
	}

	/**
	 * Tell whether currency switching should be disabled for subscription contexts.
	 *
	 * @param bool $should_disable Existing switching-disable decision.
	 * @return bool
	 */
	public function should_disable_currency_switching( bool $should_disable ): bool {
		return MultiCurrencySubscriptionsCompatibilityProjectionService::should_disable_currency_switching(
			$should_disable,
			$this->has_subscription_cart_context(),
			null !== $this->get_subscription_from_switch_request()
		);
	}

	/**
	 * Tell whether product price conversion should run for subscription contexts.
	 *
	 * @param bool  $should_convert Existing product conversion decision.
	 * @param mixed $product        Product object.
	 * @return bool
	 */
	public function should_convert_product_price( bool $should_convert, $product ): bool {
		unset( $product );

		return MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_product_price(
			$should_convert,
			null !== $this->get_subscription_type_from_cart( 'renewal' ),
			null !== $this->get_subscription_type_from_cart( 'resubscribe' ),
			$this->is_call_in_backtrace( self::RENEWAL_SETUP_CALLS ),
			$this->is_call_in_backtrace( self::PRODUCT_PRICE_CALCULATION_CALLS ),
			$this->is_recurring_item_data_price_context()
		);
	}

	/**
	 * Tell whether coupon amount conversion should run for subscription contexts.
	 *
	 * @param bool  $should_convert Existing coupon conversion decision.
	 * @param mixed $coupon         Coupon object.
	 * @return bool
	 */
	public function should_convert_coupon_amount( bool $should_convert, $coupon ): bool {
		return MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_coupon_amount(
			$should_convert,
			$this->get_coupon_discount_type( $coupon ),
			null !== $this->get_subscription_type_from_cart( 'renewal' ),
			$this->is_call_in_backtrace( self::EARLY_RENEWAL_SETUP_CALLS ),
			$this->is_call_in_backtrace( self::APPLY_COUPON_CALLS )
		);
	}

	/**
	 * Convert direct Subscriptions product prices when Subscriptions guards allow it.
	 *
	 * @param mixed $price   Subscription product price.
	 * @param mixed $product Product object.
	 * @return mixed
	 */
	public function get_subscription_product_price( $price, $product ) {
		if (
			! MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_subscription_product_price(
				$price,
				$this->should_convert_product_price( true, $product )
			)
		) {
			return $price;
		}

		return $this->get_price_projection_service()->get_price( $price, 'product' );
	}

	/**
	 * Convert subscription sign-up fees when switch/proration guards allow it.
	 *
	 * @param mixed $price   Subscription sign-up fee.
	 * @param mixed $product Product object.
	 * @return mixed
	 */
	public function get_subscription_product_signup_fee( $price, $product ) {
		$is_switch_product                   = false;
		$is_subscription_price_setup_context = false;
		$is_switch_proration_context         = false;
		$has_changed_signup_fee_meta         = false;

		$cart_item = $this->get_subscription_type_from_cart( 'switch' );
		if ( null !== $cart_item ) {
			$product_id                = $this->get_product_id( $product );
			$switch_product_id         = $this->get_switch_cart_item_product_id( $cart_item );
			$previous_switch_cart_item = $this->switch_cart_item;
			$current_switch_cart_item  = $this->get_switch_cart_item_key( $cart_item );

			$this->switch_cart_item = $current_switch_cart_item;

			$is_switch_product = $product_id === $switch_product_id;

			if ( $is_switch_product ) {
				$is_subscription_price_setup_context = $this->is_call_in_backtrace( self::SUBSCRIPTION_PRICE_SETUP_CALLS );
				$is_switch_proration_context         = $this->is_subscription_switch_proration_context( $current_switch_cart_item, $previous_switch_cart_item );
				$has_changed_signup_fee_meta         = $this->has_changed_subscription_signup_fee_meta( $product, $current_switch_cart_item, $previous_switch_cart_item );
			}
		}

		if (
			! MultiCurrencySubscriptionsCompatibilityProjectionService::should_convert_subscription_signup_fee(
				$price,
				$is_switch_product,
				$is_subscription_price_setup_context,
				$is_switch_proration_context,
				$has_changed_signup_fee_meta
			)
		) {
			return $price;
		}

		return $this->get_price_projection_service()->get_price( $price, 'product' );
	}

	/**
	 * Track the current My Account subscription while Subscriptions formats totals.
	 *
	 * @param array<mixed> $subscription_details Subscription price string details.
	 * @param mixed        $subscription         Subscription object.
	 * @return array<mixed>
	 */
	public function maybe_set_current_my_account_subscription( $subscription_details, $subscription ): array {
		if (
			MultiCurrencySubscriptionsCompatibilityProjectionService::should_set_current_my_account_subscription(
				$this->is_call_in_backtrace( self::MY_ACCOUNT_SUBSCRIPTIONS_TEMPLATE_CALLS ),
				$this->is_call_in_backtrace( self::FORMATTED_SUBSCRIPTION_TOTAL_CALLS )
			)
			&& null !== $this->get_subscription_currency( is_object( $subscription ) ? $subscription : null )
		) {
			$this->current_my_account_subscription = $subscription;
		}

		return $subscription_details;
	}

	/**
	 * Clear the current My Account subscription after the formatted total is done.
	 *
	 * @param mixed $formatted    Formatted subscription total.
	 * @param mixed $subscription Subscription object.
	 * @return string
	 */
	public function maybe_clear_current_my_account_subscription( $formatted, $subscription ): string {
		unset( $subscription );

		if ( null !== $this->get_subscription_currency( $this->current_my_account_subscription ) ) {
			$this->current_my_account_subscription = null;
		}

		return (string) $formatted;
	}

	/**
	 * Append explicit currency code to My Account subscription totals when needed.
	 *
	 * @param mixed $html_price Price HTML.
	 * @return string
	 */
	public function maybe_get_explicit_format_for_subscription_total( $html_price ): string {
		$currency_code = $this->get_subscription_currency( $this->current_my_account_subscription );
		if ( null === $currency_code ) {
			return (string) $html_price;
		}

		return MultiCurrencySubscriptionsCompatibilityProjectionService::get_explicit_subscription_total_price_html(
			(string) $html_price,
			$currency_code,
			$this->get_state_builder()->build()->has_additional_currencies_enabled()
		);
	}

	/**
	 * Disable mixed subscription purchases while a switch cart item is present.
	 *
	 * @param mixed $value Option value.
	 * @return mixed
	 */
	public function maybe_disable_mixed_cart( $value ) {
		return MultiCurrencySubscriptionsCompatibilityProjectionService::maybe_disable_mixed_cart(
			$value,
			null !== $this->get_subscription_type_from_cart( 'switch' )
		);
	}

	/**
	 * Tell whether the current request is an admin request.
	 *
	 * @return bool
	 */
	protected function is_admin_request(): bool {
		return is_admin();
	}

	/**
	 * Tell whether the current request is a cron request.
	 *
	 * @return bool
	 */
	protected function is_cron_request(): bool {
		return defined( 'DOING_CRON' );
	}

	/**
	 * Tell whether WooCommerce Subscriptions or WooPayments Subscriptions is available.
	 *
	 * @return bool
	 */
	protected function is_subscriptions_runtime_available(): bool {
		return class_exists( 'WC_Subscriptions' ) || class_exists( 'WC_Payments_Subscriptions' );
	}

	/**
	 * Tell whether WordPress has finished loading active plugins.
	 *
	 * @return bool
	 */
	protected function have_plugins_loaded(): bool {
		return 0 < did_action( 'plugins_loaded' );
	}

	/**
	 * Get a subscription cart item for a specific Subscriptions cart type.
	 *
	 * @param string $type Subscription cart type.
	 * @return array<string,mixed>|null
	 */
	protected function get_subscription_type_from_cart( string $type ): ?array {
		if ( ! in_array( $type, self::SUBSCRIPTION_TYPES, true ) || ! function_exists( 'WC' ) ) {
			return null;
		}

		$woocommerce      = WC();
		$subscription_key = 'subscription_' . $type;
		$cart             = $woocommerce->cart;

		if ( $cart instanceof \WC_Cart && is_array( $cart->cart_contents ) ) {
			foreach ( $cart->cart_contents as $cart_item ) {
				if ( is_array( $cart_item ) && isset( $cart_item[ $subscription_key ] ) ) {
					return $cart_item;
				}
			}
		}

		$session = $woocommerce->session;
		if ( $session instanceof \WC_Session ) {
			$session_cart = $session->get( 'cart' );
			if ( is_array( $session_cart ) ) {
				foreach ( $session_cart as $cart_item ) {
					if ( is_array( $cart_item ) && isset( $cart_item[ $subscription_key ] ) ) {
						return $cart_item;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Get a subscription object from a subscription cart item.
	 *
	 * @param array<string,mixed> $cart_item Subscription cart item.
	 * @param string              $type      Subscription cart type.
	 * @return object|null
	 */
	protected function get_subscription_from_cart_item( array $cart_item, string $type ): ?object {
		$subscription_data = $cart_item[ 'subscription_' . $type ] ?? null;

		if ( ! is_array( $subscription_data ) || ! isset( $subscription_data['subscription_id'] ) ) {
			return null;
		}

		return $this->get_subscription_by_id( $subscription_data['subscription_id'] );
	}

	/**
	 * Get a verified switch request subscription.
	 *
	 * @return object|null
	 */
	protected function get_subscription_from_switch_request(): ?object {
		if ( ! isset( $_GET['_wcsnonce'] ) || ! isset( $_GET['switch-subscription'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return null;
		}

		$nonce = wp_unslash( $_GET['_wcsnonce'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		if ( ! is_scalar( $nonce ) || ! $this->legacy_proxy->call_function( 'wp_verify_nonce', sanitize_key( (string) $nonce ), 'wcs_switch_request' ) ) {
			return null;
		}

		$switch_subscription_id = wp_unslash( $_GET['switch-subscription'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		if ( ! is_numeric( $switch_subscription_id ) ) {
			return null;
		}

		$subscription                                     = null;
		$this->running_override_selected_currency_filters = true;

		try {
			$subscription = $this->get_subscription_by_id( absint( $switch_subscription_id ) );
		} finally {
			$this->running_override_selected_currency_filters = false;
		}

		if ( null === $subscription ) {
			return null;
		}

		return $this->get_subscription_customer_id( $subscription ) === $this->get_current_user_id() ? $subscription : null;
	}

	/**
	 * Tell whether any expected calls are present in the backtrace.
	 *
	 * @param string[] $expected_calls Expected call strings.
	 * @return bool
	 */
	protected function is_call_in_backtrace( array $expected_calls ): bool {
		$expected_lookup = array_fill_keys( $expected_calls, true );

		foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) as $call ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
			if ( empty( $call['function'] ) ) {
				continue;
			}

			$call_string = isset( $call['class'] )
				? (string) $call['class'] . (string) ( $call['type'] ?? '::' ) . (string) $call['function']
				: (string) $call['function'];

			if ( isset( $expected_lookup[ $call_string ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Tell whether recurring item data is reading the product price.
	 *
	 * @return bool
	 */
	private function is_recurring_item_data_price_context(): bool {
		return $this->is_call_in_backtrace( self::RECURRING_ITEM_DATA_CALLS )
			&& $this->is_call_in_backtrace( self::PRODUCT_GET_PRICE_CALLS );
	}

	/**
	 * Tell whether switch proration totals are reading a repeated sign-up fee.
	 *
	 * @param string $current_switch_cart_item  Current switch cart item key.
	 * @param string $previous_switch_cart_item Previously observed switch cart item key.
	 * @return bool
	 */
	private function is_subscription_switch_proration_context( string $current_switch_cart_item, string $previous_switch_cart_item ): bool {
		return '' !== $current_switch_cart_item
			&& $current_switch_cart_item === $previous_switch_cart_item
			&& $this->is_call_in_backtrace( self::SUBSCRIPTION_PRODUCT_SIGNUP_FEE_CALLS )
			&& $this->is_call_in_backtrace( self::CART_CALCULATE_TOTALS_CALLS )
			&& ! $this->is_call_in_backtrace( self::SWITCH_APPORTION_SIGNUP_FEE_CALLS );
	}

	/**
	 * Tell whether a repeated switch sign-up fee was already written to product meta.
	 *
	 * @param mixed  $product                   Product object.
	 * @param string $current_switch_cart_item  Current switch cart item key.
	 * @param string $previous_switch_cart_item Previously observed switch cart item key.
	 * @return bool
	 */
	private function has_changed_subscription_signup_fee_meta( $product, string $current_switch_cart_item, string $previous_switch_cart_item ): bool {
		if (
			'' === $current_switch_cart_item ||
			$current_switch_cart_item !== $previous_switch_cart_item ||
			! is_object( $product ) ||
			! is_callable( array( $product, 'get_meta_data' ) )
		) {
			return false;
		}

		$meta_data = call_user_func( array( $product, 'get_meta_data' ) );
		if ( ! is_iterable( $meta_data ) ) {
			return false;
		}

		foreach ( $meta_data as $meta ) {
			if (
				! is_object( $meta ) ||
				! is_callable( array( $meta, 'get_data' ) ) ||
				! is_callable( array( $meta, 'get_changes' ) )
			) {
				continue;
			}

			$data = call_user_func( array( $meta, 'get_data' ) );
			if ( ! is_array( $data ) || '_subscription_sign_up_fee' !== ( $data['key'] ?? null ) ) {
				continue;
			}

			if ( ! empty( call_user_func( array( $meta, 'get_changes' ) ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Tell whether any subscription cart item is present.
	 *
	 * @return bool
	 */
	private function has_subscription_cart_context(): bool {
		foreach ( self::SUBSCRIPTION_TYPES as $type ) {
			if ( null !== $this->get_subscription_type_from_cart( $type ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a subscription object by ID when Subscriptions functions are available.
	 *
	 * @param mixed $subscription_id Subscription ID.
	 * @return object|null
	 */
	private function get_subscription_by_id( $subscription_id ): ?object {
		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return null;
		}

		$subscription = $this->legacy_proxy->call_function( 'wcs_get_subscription', $subscription_id );

		return is_object( $subscription ) ? $subscription : null;
	}

	/**
	 * Get a subscription currency code.
	 *
	 * @param object|null $subscription Subscription object.
	 * @return string|null
	 */
	private function get_subscription_currency( ?object $subscription ): ?string {
		if ( null === $subscription || ! is_callable( array( $subscription, 'get_currency' ) ) ) {
			return null;
		}

		$currency_code = call_user_func( array( $subscription, 'get_currency' ) );

		return is_scalar( $currency_code ) && '' !== trim( (string) $currency_code ) ? strtoupper( trim( (string) $currency_code ) ) : null;
	}

	/**
	 * Get a subscription customer ID.
	 *
	 * @param object $subscription Subscription object.
	 * @return int
	 */
	private function get_subscription_customer_id( object $subscription ): int {
		if ( is_callable( array( $subscription, 'get_customer_id' ) ) ) {
			return absint( call_user_func( array( $subscription, 'get_customer_id' ) ) );
		}

		if ( is_callable( array( $subscription, 'get_user_id' ) ) ) {
			return absint( call_user_func( array( $subscription, 'get_user_id' ) ) );
		}

		return 0;
	}

	/**
	 * Get the current user ID.
	 *
	 * @return int
	 */
	private function get_current_user_id(): int {
		return get_current_user_id();
	}

	/**
	 * Get a coupon discount type.
	 *
	 * @param mixed $coupon Coupon object.
	 * @return string
	 */
	private function get_coupon_discount_type( $coupon ): string {
		if ( ! is_object( $coupon ) || ! is_callable( array( $coupon, 'get_discount_type' ) ) ) {
			return '';
		}

		$discount_type = call_user_func( array( $coupon, 'get_discount_type' ) );

		return is_scalar( $discount_type ) ? (string) $discount_type : '';
	}

	/**
	 * Get the product ID from a product-like object.
	 *
	 * @param mixed $product Product object.
	 * @return int
	 */
	private function get_product_id( $product ): int {
		if ( ! is_object( $product ) || ! is_callable( array( $product, 'get_id' ) ) ) {
			return 0;
		}

		return absint( call_user_func( array( $product, 'get_id' ) ) );
	}

	/**
	 * Get the product or variation ID from a switch cart item.
	 *
	 * @param array<string,mixed> $cart_item Switch cart item.
	 * @return int
	 */
	private function get_switch_cart_item_product_id( array $cart_item ): int {
		$variation_id = absint( $cart_item['variation_id'] ?? 0 );

		return 0 < $variation_id ? $variation_id : absint( $cart_item['product_id'] ?? 0 );
	}

	/**
	 * Get the switch cart item key.
	 *
	 * @param array<string,mixed> $cart_item Switch cart item.
	 * @return string
	 */
	private function get_switch_cart_item_key( array $cart_item ): string {
		return is_scalar( $cart_item['key'] ?? null ) ? (string) $cart_item['key'] : '';
	}

	/**
	 * Get the price projection service.
	 *
	 * @return MultiCurrencyPriceProjectionService
	 */
	private function get_price_projection_service(): MultiCurrencyPriceProjectionService {
		if ( null === $this->price_projection_service ) {
			$this->price_projection_service = $this->projection_service_factory->create_price_projection_service();
		}

		return $this->price_projection_service;
	}

	/**
	 * Get the state builder.
	 *
	 * @return MultiCurrencyStateBuilder
	 */
	private function get_state_builder(): MultiCurrencyStateBuilder {
		if ( null === $this->state_builder ) {
			$this->state_builder = $this->state_builder_factory->create();
		}

		return $this->state_builder;
	}

	/**
	 * Register a filter only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_filter_once( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( false === has_filter( $hook, $callback ) ) {
			add_filter( $hook, $callback, $priority, $accepted_args );
		}
	}

	/**
	 * Register an action only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_action_once( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( false === has_action( $hook, $callback ) ) {
			add_action( $hook, $callback, $priority, $accepted_args );
		}
	}
}
