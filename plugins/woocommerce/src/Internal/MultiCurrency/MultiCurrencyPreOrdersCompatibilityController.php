<?php
/**
 * MultiCurrencyPreOrdersCompatibilityController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyLocalizationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPreOrdersCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceCalculator;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency Pre-Orders compatibility hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyPreOrdersCompatibilityController implements RegisterHooksInterface {

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
	 * Register Pre-Orders compatibility hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		if ( $this->is_pre_orders_runtime_available() || $this->have_plugins_loaded() ) {
			$this->register_pre_orders_filters();
			return;
		}

		$this->add_action_once( 'plugins_loaded', array( $this, 'register_pre_orders_filters' ), 20 );
	}

	/**
	 * Register Pre-Orders compatibility filters after supported runtimes have loaded.
	 *
	 * @internal
	 */
	public function register_pre_orders_filters(): void {
		if (
			! $this->arbiter->should_core_register()
			|| ! MultiCurrencyPreOrdersCompatibilityProjectionService::should_register(
				$this->is_pre_orders_runtime_available()
			)
		) {
			return;
		}

		$manifest = MultiCurrencyPreOrdersCompatibilityProjectionService::get_hook_manifest();

		foreach ( $manifest['filters'] as $filter ) {
			$callback = array( $this, (string) $filter['callback'] );
			if ( is_callable( $callback ) ) {
				$this->add_filter_once( (string) $filter['hook'], $callback, (int) $filter['priority'], (int) $filter['accepted_args'] );
			}
		}
	}

	/**
	 * Convert Pre-Orders fee args for the selected currency.
	 *
	 * @param array<mixed> $args Pre-Orders fee args.
	 * @return array<mixed>
	 */
	public function convert_pre_orders_fee( array $args ): array {
		if ( ! MultiCurrencyPreOrdersCompatibilityProjectionService::should_convert_fee_amount( $args ) ) {
			return $args;
		}

		$args['amount'] = $this->get_price_projection_service()->get_price( $args['amount'], 'product' );

		return $args;
	}

	/**
	 * Check if Pre-Orders runtime is available.
	 *
	 * @return bool
	 */
	protected function is_pre_orders_runtime_available(): bool {
		return class_exists( 'WC_Pre_Orders' );
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
	 * Get the price projection service.
	 *
	 * @return MultiCurrencyPriceProjectionService
	 */
	private function get_price_projection_service(): MultiCurrencyPriceProjectionService {
		if ( null === $this->price_projection_service ) {
			$localization_service = new MultiCurrencyLocalizationService();

			$this->price_projection_service = new MultiCurrencyPriceProjectionService(
				wc_get_container()->get( MultiCurrencyStateBuilderFactory::class )->create( $localization_service ),
				new MultiCurrencyPriceCalculator( $localization_service )
			);
		}

		return $this->price_projection_service;
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
