<?php
/**
 * MultiCurrencyFedExCompatibilityController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFedExCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency FedEx compatibility hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyFedExCompatibilityController implements RegisterHooksInterface {

	private const FEDEX_SHIPPING_CALLS = array(
		'WC_Shipping_Fedex->set_settings',
		'WC_Shipping_Fedex->per_item_shipping',
		'WC_Shipping_Fedex->box_shipping',
		'WC_Shipping_Fedex->get_fedex_api_request',
		'WC_Shipping_Fedex->get_fedex_requests',
		'WC_Shipping_Fedex->process_result',
	);

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

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
	 * Register FedEx compatibility hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		if ( $this->is_fedex_runtime_available() || $this->have_plugins_loaded() ) {
			$this->register_fedex_filters();
			return;
		}

		$this->add_action_once( 'plugins_loaded', array( $this, 'register_fedex_filters' ), 20 );
	}

	/**
	 * Register FedEx compatibility filters after supported runtimes have loaded.
	 *
	 * @internal
	 */
	public function register_fedex_filters(): void {
		if (
			! $this->arbiter->should_core_register()
			|| ! MultiCurrencyFedExCompatibilityProjectionService::should_register(
				$this->is_fedex_runtime_available()
			)
		) {
			return;
		}

		$manifest = MultiCurrencyFedExCompatibilityProjectionService::get_hook_manifest();

		foreach ( $manifest['filters'] as $filter ) {
			$callback = array( $this, (string) $filter['callback'] );
			if ( is_callable( $callback ) ) {
				$this->add_filter_once( (string) $filter['hook'], $callback, (int) $filter['priority'], (int) $filter['accepted_args'] );
			}
		}
	}

	/**
	 * Tell whether product prices should convert during FedEx calculations.
	 *
	 * @param bool $should_convert Existing product conversion decision.
	 * @return bool
	 */
	public function should_convert_product_price( bool $should_convert ): bool {
		return MultiCurrencyFedExCompatibilityProjectionService::should_convert_product_price(
			$should_convert,
			$this->is_call_in_backtrace( self::FEDEX_SHIPPING_CALLS )
		);
	}

	/**
	 * Tell whether native multi-currency should force store currency for FedEx.
	 *
	 * @param bool $should_return Existing store-currency decision.
	 * @return bool
	 */
	public function should_return_store_currency( bool $should_return ): bool {
		return MultiCurrencyFedExCompatibilityProjectionService::should_return_store_currency(
			$should_return,
			$this->is_call_in_backtrace( self::FEDEX_SHIPPING_CALLS )
		);
	}

	/**
	 * Check if FedEx runtime is available.
	 *
	 * @return bool
	 */
	protected function is_fedex_runtime_available(): bool {
		return class_exists( 'WC_Shipping_Fedex_Init' );
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
