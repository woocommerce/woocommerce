<?php
/**
 * MultiCurrencyBookingsCompatibilityController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyBookingsCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFrontendProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency Bookings compatibility hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyBookingsCompatibilityController implements RegisterHooksInterface {

	private const CART_ADD_TO_CART_CALLS = array(
		'WC_Cart->add_to_cart',
	);

	private const BOOKINGS_COST_CALCULATION_CALLS = array(
		'WC_Bookings_Cost_Calculation::calculate_booking_cost',
	);

	private const BOOKING_PRICE_HTML_CALLS = array(
		'WC_Product_Booking->get_price_html',
	);

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Price projection service.
	 *
	 * @var MultiCurrencyPriceProjectionService|null
	 */
	private ?MultiCurrencyPriceProjectionService $price_projection_service = null;

	/**
	 * Frontend projection service.
	 *
	 * @var MultiCurrencyFrontendProjectionService|null
	 */
	private ?MultiCurrencyFrontendProjectionService $frontend_projection_service = null;

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
	 * Register Bookings compatibility hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		if ( $this->is_bookings_runtime_available() || $this->have_plugins_loaded() ) {
			$this->register_bookings_filters();
			return;
		}

		$this->add_action_once( 'plugins_loaded', array( $this, 'register_bookings_filters' ), 20 );
	}

	/**
	 * Register Bookings compatibility filters after supported runtimes have loaded.
	 *
	 * @internal
	 */
	public function register_bookings_filters(): void {
		if (
			! $this->arbiter->should_core_register()
			|| ! MultiCurrencyBookingsCompatibilityProjectionService::should_register(
				$this->is_bookings_runtime_available(),
				$this->is_admin_request(),
				$this->is_ajax_request()
			)
		) {
			return;
		}

		$manifest = MultiCurrencyBookingsCompatibilityProjectionService::get_hook_manifest();

		foreach ( $manifest['filters'] as $filter ) {
			$callback = array( $this, (string) $filter['callback'] );
			if ( is_callable( $callback ) ) {
				$this->add_filter_once( (string) $filter['hook'], $callback, (int) $filter['priority'], (int) $filter['accepted_args'] );
			}
		}

		foreach ( $manifest['actions'] as $action ) {
			$callback = array( $this, (string) $action['callback'] );
			if ( is_callable( $callback ) ) {
				$this->add_action_once( (string) $action['hook'], $callback, (int) $action['priority'], (int) $action['accepted_args'] );
			}
		}
	}

	/**
	 * Convert calculated booking costs for the selected currency.
	 *
	 * @param mixed $costs Calculated booking costs.
	 * @return mixed
	 */
	public function adjust_amount_for_calculated_booking_cost( $costs ) {
		if (
			! MultiCurrencyBookingsCompatibilityProjectionService::should_adjust_calculated_booking_cost(
				$this->is_call_in_backtrace( self::CART_ADD_TO_CART_CALLS )
			)
		) {
			return $costs;
		}

		return $this->get_price_projection_service()->get_price( $costs, 'product' );
	}

	/**
	 * Convert a Bookings price for the selected currency.
	 *
	 * @param mixed $price Bookings price.
	 * @return mixed
	 */
	public function get_price( $price ) {
		if (
			! MultiCurrencyBookingsCompatibilityProjectionService::should_convert_booking_price(
				$price,
				$this->is_call_in_backtrace( self::CART_ADD_TO_CART_CALLS )
					&& $this->is_call_in_backtrace( self::BOOKINGS_COST_CALCULATION_CALLS )
			)
		) {
			return $price;
		}

		return $this->get_price_projection_service()->get_price(
			$price,
			MultiCurrencyBookingsCompatibilityProjectionService::get_booking_price_type(
				$this->is_call_in_backtrace( self::BOOKING_PRICE_HTML_CALLS )
			)
		);
	}

	/**
	 * Convert Bookings resource price arrays for the selected currency.
	 *
	 * @param mixed $prices Resource prices.
	 * @return mixed
	 */
	public function get_resource_prices( $prices ) {
		if ( ! is_array( $prices ) ) {
			return $prices;
		}

		foreach ( $prices as $key => $price ) {
			$prices[ $key ] = $this->get_price( $price );
		}

		return $prices;
	}

	/**
	 * Tell whether default product-price conversion should run for Bookings.
	 *
	 * @param bool  $should_convert Existing product conversion decision.
	 * @param mixed $product        Product object.
	 * @return bool
	 */
	public function should_convert_product_price( bool $should_convert, $product ): bool {
		return MultiCurrencyBookingsCompatibilityProjectionService::should_convert_booking_product_price(
			$should_convert,
			$this->get_product_type( $product ),
			$this->is_call_in_backtrace( self::BOOKING_PRICE_HTML_CALLS )
		);
	}

	/**
	 * Add selected-currency price args while Bookings Ajax calculates costs.
	 */
	public function add_wc_price_args_filter_for_ajax(): void {
		$this->add_filter_once( 'wc_price_args', array( $this, 'filter_wc_price_args' ), 100 );
	}

	/**
	 * Project selected-currency wc_price args for Bookings Ajax.
	 *
	 * @param array<mixed> $args Existing wc_price args.
	 * @return array<mixed>
	 */
	public function filter_wc_price_args( array $args ): array {
		$frontend_projection_service = $this->get_frontend_projection_service();

		return wp_parse_args(
			array(
				'currency'           => $frontend_projection_service->get_woocommerce_currency(),
				'decimal_separator'  => $frontend_projection_service->get_price_decimal_separator( (string) ( $args['decimal_separator'] ?? wc_get_price_decimal_separator() ) ),
				'thousand_separator' => $frontend_projection_service->get_price_thousand_separator( (string) ( $args['thousand_separator'] ?? wc_get_price_thousand_separator() ) ),
				'decimals'           => $frontend_projection_service->get_price_decimals( absint( $args['decimals'] ?? wc_get_price_decimals() ) ),
				'price_format'       => $frontend_projection_service->get_woocommerce_price_format( (string) ( $args['price_format'] ?? get_woocommerce_price_format() ) ),
			),
			$args
		);
	}

	/**
	 * Check if Bookings runtime is available.
	 *
	 * @return bool
	 */
	protected function is_bookings_runtime_available(): bool {
		return class_exists( 'WC_Bookings' );
	}

	/**
	 * Check if this is an admin request.
	 *
	 * @return bool
	 */
	protected function is_admin_request(): bool {
		return is_admin();
	}

	/**
	 * Check if this is an Ajax request.
	 *
	 * @return bool
	 */
	protected function is_ajax_request(): bool {
		return wp_doing_ajax();
	}

	/**
	 * Check if plugins have loaded.
	 *
	 * @return bool
	 */
	protected function have_plugins_loaded(): bool {
		return did_action( 'plugins_loaded' ) > 0;
	}

	/**
	 * Check if any expected call appears in the backtrace summary.
	 *
	 * @param string[] $calls Expected calls.
	 * @return bool
	 */
	protected function is_call_in_backtrace( array $calls ): bool {
		$backtrace = wp_debug_backtrace_summary( null, 0, false ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary -- WooPayments-compatible compatibility guard.

		foreach ( $calls as $call ) {
			if ( in_array( $call, $backtrace, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the product type.
	 *
	 * @param mixed $product Product object.
	 * @return string
	 */
	private function get_product_type( $product ): string {
		return is_object( $product ) && is_callable( array( $product, 'get_type' ) )
			? (string) $product->get_type()
			: '';
	}

	/**
	 * Get the price projection service.
	 *
	 * @return MultiCurrencyPriceProjectionService
	 */
	private function get_price_projection_service(): MultiCurrencyPriceProjectionService {
		if ( null === $this->price_projection_service ) {
			$this->price_projection_service = wc_get_container()
				->get( MultiCurrencyProjectionServiceFactory::class )
				->create_price_projection_service();
		}

		return $this->price_projection_service;
	}

	/**
	 * Get the frontend projection service.
	 *
	 * @return MultiCurrencyFrontendProjectionService
	 */
	private function get_frontend_projection_service(): MultiCurrencyFrontendProjectionService {
		if ( null === $this->frontend_projection_service ) {
			$this->frontend_projection_service = wc_get_container()
				->get( MultiCurrencyProjectionServiceFactory::class )
				->create_frontend_projection_service();
		}

		return $this->frontend_projection_service;
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
