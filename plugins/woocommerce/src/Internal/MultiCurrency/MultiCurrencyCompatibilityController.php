<?php
/**
 * MultiCurrencyCompatibilityController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency compatibility hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyCompatibilityController implements RegisterHooksInterface {

	private const FILTER_PREFIX = 'wcpay_multi_currency_';

	private const NEW_SALES_RECORD_BACKTRACE_CALLS = array(
		'Automattic\WooCommerce\Admin\Notes\NewSalesRecord::sum_sales_for_date',
		'Automattic\WooCommerce\Admin\Notes\NewSalesRecord::possibly_add_note',
	);

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * State builder factory.
	 *
	 * @var MultiCurrencyStateBuilderFactory
	 */
	private MultiCurrencyStateBuilderFactory $state_builder_factory;

	/**
	 * State builder.
	 *
	 * @var MultiCurrencyStateBuilder|null
	 */
	private ?MultiCurrencyStateBuilder $state_builder = null;

	/**
	 * Projected compatibility integration names.
	 *
	 * @var string[]
	 */
	private array $compatibility_integrations = array();

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter      $arbiter               Runtime owner arbiter.
	 * @param MultiCurrencyStateBuilderFactory $state_builder_factory State builder factory.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter, MultiCurrencyStateBuilderFactory $state_builder_factory ): void {
		$this->arbiter               = $arbiter;
		$this->state_builder_factory = $state_builder_factory;
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
	 * Register compatibility hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		$this->add_action_once( 'init', array( $this, 'init_compatibility_classes' ), 11 );

		if ( $this->is_cron_request() ) {
			$this->add_filter_once( 'woocommerce_admin_sales_record_milestone_enabled', array( $this, 'attach_order_modifier' ) );
		}
	}

	/**
	 * Initialize projected compatibility integrations.
	 *
	 * @internal
	 */
	public function init_compatibility_classes(): void {
		$this->compatibility_integrations = MultiCurrencyCompatibilityProjectionService::get_compatibility_integrations(
			$this->get_state_builder()->build()->has_additional_currencies_enabled()
		);
	}

	/**
	 * Get projected compatibility integration names.
	 *
	 * @return string[]
	 */
	public function get_compatibility_integrations(): array {
		return $this->compatibility_integrations;
	}

	/**
	 * Check whether the selected currency should be overridden.
	 *
	 * @return mixed Three-letter currency code or false.
	 */
	public function override_selected_currency() {
		/**
		 * Filters the native multi-currency selected currency override.
		 *
		 * @param mixed $currency Currency code or false.
		 *
		 * @since 11.0.0
		 */
		return apply_filters( self::filter_name( 'override_selected_currency' ), false );
	}

	/**
	 * Deprecated widget-hiding compatibility method.
	 *
	 * @return bool Whether currency switchers should be hidden.
	 */
	public function should_hide_widgets(): bool {
		wc_deprecated_function( __FUNCTION__, '6.5.0', 'MultiCurrencyCompatibilityController::should_disable_currency_switching' );

		return $this->should_disable_currency_switching();
	}

	/**
	 * Tell whether currency switching should be disabled.
	 *
	 * @return bool
	 */
	public function should_disable_currency_switching(): bool {
		$should_disable = array_key_exists( 'pay_for_order', $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only compatibility flag matching WooPayments behavior.

		if ( has_filter( self::filter_name( 'should_hide_widgets' ) ) ) {
			wc_deprecated_hook( self::filter_name( 'should_hide_widgets' ), '6.5.0', self::filter_name( 'should_disable_currency_switching' ) );
			/**
			 * Filters whether native multi-currency widgets should be hidden.
			 *
			 * @param bool $should_disable Whether currency switching should be disabled.
			 *
			 * @since 11.0.0
			 */
			$should_disable = (bool) apply_filters( self::filter_name( 'should_hide_widgets' ), $should_disable );
		}

		/**
		 * Filters whether native multi-currency switching should be disabled.
		 *
		 * @param bool $should_disable Whether currency switching should be disabled.
		 *
		 * @since 11.0.0
		 */
		return (bool) apply_filters( self::filter_name( 'should_disable_currency_switching' ), $should_disable );
	}

	/**
	 * Tell whether coupon amounts should be converted.
	 *
	 * @param mixed $coupon Coupon object.
	 * @return bool
	 */
	public function should_convert_coupon_amount( $coupon = null ): bool {
		if ( ! $coupon ) {
			return true;
		}

		/**
		 * Filters whether a coupon amount should be converted.
		 *
		 * @param bool  $should_convert Whether the coupon amount should be converted.
		 * @param mixed $coupon         Coupon object.
		 *
		 * @since 11.0.0
		 */
		return (bool) apply_filters( self::filter_name( 'should_convert_coupon_amount' ), true, $coupon );
	}

	/**
	 * Tell whether product prices should be converted.
	 *
	 * @param mixed $product Product object.
	 * @return bool
	 */
	public function should_convert_product_price( $product = null ): bool {
		if ( ! $product ) {
			return true;
		}

		/**
		 * Filters whether a product price should be converted.
		 *
		 * @param bool  $should_convert Whether the product price should be converted.
		 * @param mixed $product        Product object.
		 *
		 * @since 11.0.0
		 */
		return (bool) apply_filters( self::filter_name( 'should_convert_product_price' ), true, $product );
	}

	/**
	 * Tell whether the store currency should be returned.
	 *
	 * @return bool
	 */
	public function should_return_store_currency(): bool {
		/**
		 * Filters whether native multi-currency should return the store currency.
		 *
		 * @param bool $should_return Whether to return the store currency.
		 *
		 * @since 11.0.0
		 */
		return (bool) apply_filters( self::filter_name( 'should_return_store_currency' ), false );
	}

	/**
	 * Attach the order query modifier for sales-record calculations.
	 *
	 * @param mixed $enabled Existing enabled value.
	 * @return mixed
	 */
	public function attach_order_modifier( $enabled ) {
		$this->add_filter_once( 'woocommerce_order_query', array( $this, 'convert_order_prices' ) );

		return $enabled;
	}

	/**
	 * Convert sales-record order totals back to the default store currency.
	 *
	 * @param mixed $results Order query results.
	 * @return mixed
	 */
	public function convert_order_prices( $results ) {
		if ( ! is_array( $results ) || ! $this->is_call_in_backtrace( self::NEW_SALES_RECORD_BACKTRACE_CALLS ) ) {
			return $results;
		}

		$default_currency_code = $this->get_state_builder()->build()->get_default_currency()->get_code();

		foreach ( $results as $order ) {
			if ( ! $order instanceof \WC_Order && ! $order instanceof \WC_Order_Refund ) {
				continue;
			}

			$exchange_rate = $order->get_meta( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, true );
			if (
				$default_currency_code === $order->get_currency() ||
				! is_numeric( $exchange_rate ) ||
				0 >= (float) $exchange_rate ||
				$order->get_meta( MultiCurrencyPriceProjectionService::META_KEY_ORDER_DEFAULT_CURRENCY, true ) !== $default_currency_code
			) {
				continue;
			}

			$order->set_total( wc_format_decimal( (float) $order->get_total() * ( 1 / (float) $exchange_rate ), wc_get_price_decimals() ) );
		}

		remove_filter( 'woocommerce_order_query', array( $this, 'convert_order_prices' ) );

		return $results;
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
	 * Tell whether the current request is a cron request.
	 *
	 * @return bool
	 */
	protected function is_cron_request(): bool {
		return defined( 'DOING_CRON' );
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
				? (string) $call['class'] . '::' . (string) $call['function']
				: (string) $call['function'];

			if ( isset( $expected_lookup[ $call_string ] ) ) {
				return true;
			}
		}

		return false;
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
	 * Build a multi-currency filter name.
	 *
	 * @param string $suffix Filter suffix.
	 * @return string
	 */
	private static function filter_name( string $suffix ): string {
		return self::FILTER_PREFIX . $suffix;
	}
}
