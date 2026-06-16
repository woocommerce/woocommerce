<?php
/**
 * MultiCurrencyFrontendCurrenciesController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistry;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyDatabaseCache;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFrontendProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyGeolocationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyLocalizationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRateService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native frontend currency hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyFrontendCurrenciesController implements RegisterHooksInterface {

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Frontend projection service.
	 *
	 * @var MultiCurrencyFrontendProjectionService|null
	 */
	private ?MultiCurrencyFrontendProjectionService $frontend_projection_service = null;

	/**
	 * Request context.
	 *
	 * @var MultiCurrencyRequestContext|null
	 */
	private ?MultiCurrencyRequestContext $request_context = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter $arbiter Runtime owner arbiter.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter ): void {
		$this->arbiter = $arbiter;
	}

	/**
	 * Set the frontend projection service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyFrontendProjectionService $frontend_projection_service Frontend projection service.
	 */
	public function set_frontend_projection_service( MultiCurrencyFrontendProjectionService $frontend_projection_service ): void {
		$this->frontend_projection_service = $frontend_projection_service;
	}

	/**
	 * Set the request context.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyRequestContext $request_context Request context.
	 */
	public function set_request_context( MultiCurrencyRequestContext $request_context ): void {
		$this->request_context = $request_context;
	}

	/**
	 * Register frontend currency hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		if ( $this->get_request_context()->should_register_frontend_hooks() ) {
			$this->add_filter_once( 'woocommerce_currency', array( $this, 'get_woocommerce_currency' ), 900 );
			$this->add_filter_once( 'wc_get_price_decimals', array( $this, 'get_price_decimals' ), 900 );
			$this->add_filter_once( 'wc_get_price_decimal_separator', array( $this, 'get_price_decimal_separator' ), 900 );
			$this->add_filter_once( 'wc_get_price_thousand_separator', array( $this, 'get_price_thousand_separator' ), 900 );
			$this->add_filter_once( 'woocommerce_price_format', array( $this, 'get_woocommerce_price_format' ), 900 );
			$this->add_filter_once( 'option_woocommerce_currency_pos', array( $this, 'get_woocommerce_currency_pos' ), 900 );
			$this->add_filter_once( 'woocommerce_order_get_total', array( $this, 'maybe_init_order_currency_from_order_total_prop' ), 900, 2 );
			$this->add_filter_once( 'woocommerce_get_formatted_order_total', array( $this, 'maybe_clear_order_currency_after_formatted_order_total' ), 900, 4 );

			$this->add_action_once( 'before_woocommerce_pay', array( $this, 'init_order_currency_from_query_vars' ) );
		}

		$this->add_filter_once( 'woocommerce_thankyou_order_id', array( $this, 'init_order_currency' ) );
		$this->add_action_once( 'woocommerce_account_view-order_endpoint', array( $this, 'init_order_currency' ), 9 );
		$this->add_filter_once( 'woocommerce_cart_hash', array( $this, 'add_currency_to_cart_hash' ), 900 );
		$this->add_filter_once( 'woocommerce_shipping_method_add_rate_args', array( $this, 'fix_price_decimals_for_shipping_rates' ), 900, 2 );
	}

	/**
	 * Project the WooCommerce currency code.
	 *
	 * @param mixed $currency Existing currency.
	 * @return string
	 */
	public function get_woocommerce_currency( $currency = null ): string {
		unset( $currency );

		return $this->get_frontend_projection_service()->get_woocommerce_currency();
	}

	/**
	 * Project the price decimal count.
	 *
	 * @param mixed $decimals Existing decimal count.
	 * @return int
	 */
	public function get_price_decimals( $decimals ): int {
		return $this->get_frontend_projection_service()->get_price_decimals( absint( $decimals ) );
	}

	/**
	 * Project the price decimal separator.
	 *
	 * @param mixed $separator Existing decimal separator.
	 * @return string
	 */
	public function get_price_decimal_separator( $separator ): string {
		return $this->get_frontend_projection_service()->get_price_decimal_separator( (string) $separator );
	}

	/**
	 * Project the price thousand separator.
	 *
	 * @param mixed $separator Existing thousand separator.
	 * @return string
	 */
	public function get_price_thousand_separator( $separator ): string {
		return $this->get_frontend_projection_service()->get_price_thousand_separator( (string) $separator );
	}

	/**
	 * Project the WooCommerce price format.
	 *
	 * @param mixed $format Existing price format.
	 * @return string
	 */
	public function get_woocommerce_price_format( $format ): string {
		return $this->get_frontend_projection_service()->get_woocommerce_price_format( (string) $format );
	}

	/**
	 * Project the WooCommerce currency position option.
	 *
	 * @param mixed $position Existing currency position.
	 * @return string
	 */
	public function get_woocommerce_currency_pos( $position ): string {
		return $this->get_frontend_projection_service()->get_woocommerce_currency_pos( (string) $position );
	}

	/**
	 * Project a cart hash varied by selected currency and rate.
	 *
	 * @param mixed $hash Existing cart hash.
	 * @return string
	 */
	public function add_currency_to_cart_hash( $hash ): string {
		return $this->get_frontend_projection_service()->add_currency_to_cart_hash( (string) $hash );
	}

	/**
	 * Placeholder for pay-for-order query-var currency initialization.
	 */
	public function init_order_currency_from_query_vars(): void {}

	/**
	 * Preserve order total until native order-context currency handling is added.
	 *
	 * @param mixed $total Order total.
	 * @param mixed $order Order object.
	 * @return mixed
	 */
	public function maybe_init_order_currency_from_order_total_prop( $total, $order ) {
		unset( $order );

		return $total;
	}

	/**
	 * Preserve formatted order total until native order-context currency handling is added.
	 *
	 * @param mixed $formatted_total  Formatted total.
	 * @param mixed $order            Order object.
	 * @param mixed $tax_display      Tax display.
	 * @param mixed $display_refunded Whether refunded values are displayed.
	 * @return mixed
	 */
	public function maybe_clear_order_currency_after_formatted_order_total( $formatted_total, $order, $tax_display, $display_refunded ) {
		unset( $order, $tax_display, $display_refunded );

		return $formatted_total;
	}

	/**
	 * Preserve order ID until native order-context currency handling is added.
	 *
	 * @param mixed $order_id Order ID.
	 * @return mixed
	 */
	public function init_order_currency( $order_id ) {
		return $order_id;
	}

	/**
	 * Preserve shipping rate args until native shipping decimal handling is added.
	 *
	 * @param mixed $args   Shipping rate args.
	 * @param mixed $method Shipping method.
	 * @return mixed
	 */
	public function fix_price_decimals_for_shipping_rates( $args, $method ) {
		unset( $method );

		if ( ! is_array( $args ) ) {
			return $args;
		}

		$args['price_decimals'] = $this->get_frontend_projection_service()->get_store_currency_decimals();

		return $args;
	}

	/**
	 * Get the frontend projection service.
	 *
	 * @return MultiCurrencyFrontendProjectionService
	 */
	private function get_frontend_projection_service(): MultiCurrencyFrontendProjectionService {
		if ( null === $this->frontend_projection_service ) {
			$localization_service = new MultiCurrencyLocalizationService();
			$state_builder        = new MultiCurrencyStateBuilder(
				$localization_service,
				new MultiCurrencyRateService( new CurrencyRateProviderRegistry() ),
				new MultiCurrencyDatabaseCache()
			);

			$this->frontend_projection_service = new MultiCurrencyFrontendProjectionService(
				$state_builder,
				$localization_service,
				new MultiCurrencyGeolocationService( $localization_service )
			);
		}

		return $this->frontend_projection_service;
	}

	/**
	 * Get the request context.
	 *
	 * @return MultiCurrencyRequestContext
	 */
	private function get_request_context(): MultiCurrencyRequestContext {
		if ( null === $this->request_context ) {
			$this->request_context = new MultiCurrencyRequestContext();
		}

		return $this->request_context;
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
