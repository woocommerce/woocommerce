<?php
/**
 * MultiCurrencyUpsCompatibilityController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyUpsCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency UPS compatibility hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyUpsCompatibilityController implements RegisterHooksInterface {

	private const UPS_SHIPPING_CALLS = array(
		'WC_Shipping_UPS->per_item_shipping',
		'WC_Shipping_UPS->box_shipping',
		'WC_Shipping_UPS->calculate_shipping',
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
	 * Register UPS compatibility hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		if ( $this->is_ups_runtime_available() || $this->have_plugins_loaded() ) {
			$this->register_ups_filters();
			return;
		}

		$this->add_action_once( 'plugins_loaded', array( $this, 'register_ups_filters' ), 20 );
	}

	/**
	 * Register UPS compatibility filters after supported runtimes have loaded.
	 *
	 * @internal
	 */
	public function register_ups_filters(): void {
		if (
			! $this->arbiter->should_core_register()
			|| ! MultiCurrencyUpsCompatibilityProjectionService::should_register(
				$this->is_ups_runtime_available()
			)
		) {
			return;
		}

		$manifest = MultiCurrencyUpsCompatibilityProjectionService::get_hook_manifest();

		foreach ( $manifest['filters'] as $filter ) {
			$callback = array( $this, (string) $filter['callback'] );
			if ( is_callable( $callback ) ) {
				$this->add_filter_once( (string) $filter['hook'], $callback, (int) $filter['priority'], (int) $filter['accepted_args'] );
			}
		}
	}

	/**
	 * Tell whether native multi-currency should force store currency for UPS.
	 *
	 * @param bool $should_return Existing store-currency decision.
	 * @return bool
	 */
	public function should_return_store_currency( bool $should_return ): bool {
		return MultiCurrencyUpsCompatibilityProjectionService::should_return_store_currency(
			$should_return,
			$this->is_call_in_backtrace( self::UPS_SHIPPING_CALLS )
		);
	}

	/**
	 * Check if UPS runtime is available.
	 *
	 * @return bool
	 */
	protected function is_ups_runtime_available(): bool {
		return class_exists( 'WC_Shipping_UPS_Init' );
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
