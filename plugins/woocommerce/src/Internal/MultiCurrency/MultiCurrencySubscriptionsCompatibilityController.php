<?php
/**
 * MultiCurrencySubscriptionsCompatibilityController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySubscriptionsCompatibilityProjectionService;
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
	 * Whether selected-currency override lookups are already running.
	 *
	 * @var bool
	 */
	private bool $running_override_selected_currency_filters = false;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter $arbiter      Runtime owner arbiter.
	 * @param LegacyProxy                 $legacy_proxy Legacy proxy.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter, LegacyProxy $legacy_proxy ): void {
		$this->arbiter      = $arbiter;
		$this->legacy_proxy = $legacy_proxy;
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
