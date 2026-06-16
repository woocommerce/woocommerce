<?php
/**
 * MultiCurrencyFrontendCurrenciesController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFrontendProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyOrderContextService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRuntimeServiceFactory;
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
	 * Projection service factory.
	 *
	 * @var MultiCurrencyProjectionServiceFactory
	 */
	private MultiCurrencyProjectionServiceFactory $projection_service_factory;

	/**
	 * Runtime service factory.
	 *
	 * @var MultiCurrencyRuntimeServiceFactory
	 */
	private MultiCurrencyRuntimeServiceFactory $runtime_service_factory;

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
	 * Order context service.
	 *
	 * @var MultiCurrencyOrderContextService|null
	 */
	private ?MultiCurrencyOrderContextService $order_context_service = null;

	/**
	 * Order currency override for order-context formatting.
	 *
	 * @var string|null
	 */
	private ?string $order_currency = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter           $arbiter                    Runtime owner arbiter.
	 * @param MultiCurrencyProjectionServiceFactory $projection_service_factory Projection service factory.
	 * @param MultiCurrencyRuntimeServiceFactory    $runtime_service_factory    Runtime service factory.
	 */
	final public function init(
		MultiCurrencyRuntimeArbiter $arbiter,
		MultiCurrencyProjectionServiceFactory $projection_service_factory,
		MultiCurrencyRuntimeServiceFactory $runtime_service_factory
	): void {
		$this->arbiter                    = $arbiter;
		$this->projection_service_factory = $projection_service_factory;
		$this->runtime_service_factory    = $runtime_service_factory;
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
	 * Set the order context service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyOrderContextService $order_context_service Order context service.
	 */
	public function set_order_context_service( MultiCurrencyOrderContextService $order_context_service ): void {
		$this->order_context_service = $order_context_service;
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

		return $this->get_frontend_projection_service()->get_woocommerce_currency( $this->order_currency );
	}

	/**
	 * Project the price decimal count.
	 *
	 * @param mixed $decimals Existing decimal count.
	 * @return int
	 */
	public function get_price_decimals( $decimals ): int {
		return $this->get_frontend_projection_service()->get_price_decimals( absint( $decimals ), $this->order_currency );
	}

	/**
	 * Project the price decimal separator.
	 *
	 * @param mixed $separator Existing decimal separator.
	 * @return string
	 */
	public function get_price_decimal_separator( $separator ): string {
		return $this->get_frontend_projection_service()->get_price_decimal_separator( (string) $separator, $this->order_currency );
	}

	/**
	 * Project the price thousand separator.
	 *
	 * @param mixed $separator Existing thousand separator.
	 * @return string
	 */
	public function get_price_thousand_separator( $separator ): string {
		return $this->get_frontend_projection_service()->get_price_thousand_separator( (string) $separator, $this->order_currency );
	}

	/**
	 * Project the WooCommerce price format.
	 *
	 * @param mixed $format Existing price format.
	 * @return string
	 */
	public function get_woocommerce_price_format( $format ): string {
		return $this->get_frontend_projection_service()->get_woocommerce_price_format( (string) $format, $this->order_currency );
	}

	/**
	 * Project the WooCommerce currency position option.
	 *
	 * @param mixed $position Existing currency position.
	 * @return string
	 */
	public function get_woocommerce_currency_pos( $position ): string {
		return $this->get_frontend_projection_service()->get_woocommerce_currency_pos( (string) $position, $this->order_currency );
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
	 * Initialize order-context currency from WooCommerce order query vars.
	 */
	public function init_order_currency_from_query_vars(): void {
		global $wp;

		if ( ! $wp instanceof \WP ) {
			return;
		}

		foreach ( array( 'order-pay', 'order-received', 'view-order' ) as $query_var ) {
			if ( ! empty( $wp->query_vars[ $query_var ] ) ) {
				$this->init_order_currency( $wp->query_vars[ $query_var ] );
				return;
			}
		}
	}

	/**
	 * Preserve order total until native order-context currency handling is added.
	 *
	 * @param mixed $total Order total.
	 * @param mixed $order Order object.
	 * @return mixed
	 */
	public function maybe_init_order_currency_from_order_total_prop( $total, $order ) {
		if ( $this->get_order_context_service()->should_use_order_currency() ) {
			$this->init_order_currency( $order );
		}

		return $total;
	}

	/**
	 * Clear order-context currency after formatted order totals.
	 *
	 * @param mixed $formatted_total  Formatted total.
	 * @param mixed $order            Order object.
	 * @param mixed $tax_display      Tax display.
	 * @param mixed $display_refunded Whether refunded values are displayed.
	 * @return mixed
	 */
	public function maybe_clear_order_currency_after_formatted_order_total( $formatted_total, $order, $tax_display, $display_refunded ) {
		unset( $order, $tax_display, $display_refunded );

		if ( null !== $this->order_currency && $this->get_order_context_service()->should_use_order_currency() ) {
			$this->order_currency = null;
		}

		return $formatted_total;
	}

	/**
	 * Initialize order-context currency from an order object or ID.
	 *
	 * @param mixed $order_id Order object or ID.
	 * @return mixed
	 */
	public function init_order_currency( $order_id ) {
		if ( null !== $this->order_currency ) {
			return $order_id;
		}

		$removed_filters = $this->remove_frontend_currency_format_filters();
		try {
			$order = $order_id instanceof \WC_Order ? $order_id : wc_get_order( $order_id );
		} finally {
			$this->restore_frontend_currency_format_filters( $removed_filters );
		}

		if ( $order ) {
			$this->order_currency = $order->get_currency();

			return $order->get_id();
		}

		$this->order_currency = $this->get_frontend_projection_service()->get_woocommerce_currency();

		return $order_id;
	}

	/**
	 * Get the current order-context currency.
	 *
	 * @return string|null
	 */
	public function get_order_currency(): ?string {
		return $this->order_currency;
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
			$this->frontend_projection_service = $this->projection_service_factory->create_frontend_projection_service();
		}

		return $this->frontend_projection_service;
	}

	/**
	 * Remove frontend currency format filters during order lookup.
	 *
	 * @return array<string,int|false> Removed filter priorities keyed by hook.
	 */
	private function remove_frontend_currency_format_filters(): array {
		$removed_filters = array();

		foreach ( $this->get_frontend_currency_format_filters() as $hook => $callback ) {
			$priority                 = has_filter( $hook, $callback );
			$removed_filters[ $hook ] = $priority;

			if ( false !== $priority ) {
				remove_filter( $hook, $callback, $priority );
			}
		}

		return $removed_filters;
	}

	/**
	 * Restore frontend currency format filters removed during order lookup.
	 *
	 * @param array<string,int|false> $removed_filters Removed filter priorities keyed by hook.
	 */
	private function restore_frontend_currency_format_filters( array $removed_filters ): void {
		foreach ( $this->get_frontend_currency_format_filters() as $hook => $callback ) {
			$priority = $removed_filters[ $hook ] ?? false;

			if ( false !== $priority ) {
				add_filter( $hook, $callback, $priority );
			}
		}
	}

	/**
	 * Get frontend currency format callbacks.
	 *
	 * @return array<string,callable>
	 */
	private function get_frontend_currency_format_filters(): array {
		return array(
			'woocommerce_price_format'        => array( $this, 'get_woocommerce_price_format' ),
			'wc_get_price_thousand_separator' => array( $this, 'get_price_thousand_separator' ),
			'wc_get_price_decimal_separator'  => array( $this, 'get_price_decimal_separator' ),
			'wc_get_price_decimals'           => array( $this, 'get_price_decimals' ),
		);
	}

	/**
	 * Get the request context.
	 *
	 * @return MultiCurrencyRequestContext
	 */
	private function get_request_context(): MultiCurrencyRequestContext {
		if ( null === $this->request_context ) {
			$this->request_context = $this->runtime_service_factory->create_request_context();
		}

		return $this->request_context;
	}

	/**
	 * Get the order context service.
	 *
	 * @return MultiCurrencyOrderContextService
	 */
	private function get_order_context_service(): MultiCurrencyOrderContextService {
		if ( null === $this->order_context_service ) {
			$this->order_context_service = $this->runtime_service_factory->create_order_context_service();
		}

		return $this->order_context_service;
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
