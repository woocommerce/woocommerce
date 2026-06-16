<?php
/**
 * MultiCurrencyPointsRewardsCompatibilityController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyLocalizationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPointsRewardsCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency Points and Rewards compatibility hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyPointsRewardsCompatibilityController implements RegisterHooksInterface {

	private const POINTS_REWARDS_DISCOUNT_DATA_CALLS = array(
		'WC_Points_Rewards_Discount->get_discount_data',
	);

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * State builder.
	 *
	 * @var MultiCurrencyStateBuilder|null
	 */
	private ?MultiCurrencyStateBuilder $state_builder = null;

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
	 * Set the state builder.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyStateBuilder $state_builder State builder.
	 */
	public function set_state_builder( MultiCurrencyStateBuilder $state_builder ): void {
		$this->state_builder = $state_builder;
	}

	/**
	 * Register Points and Rewards compatibility hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		if ( $this->is_points_rewards_runtime_available() || $this->have_plugins_loaded() ) {
			$this->register_points_rewards_filters();
			return;
		}

		$this->add_action_once( 'plugins_loaded', array( $this, 'register_points_rewards_filters' ), 20 );
	}

	/**
	 * Register Points and Rewards compatibility filters after supported runtimes have loaded.
	 *
	 * @internal
	 */
	public function register_points_rewards_filters(): void {
		if (
			! $this->arbiter->should_core_register()
			|| ! MultiCurrencyPointsRewardsCompatibilityProjectionService::should_register(
				$this->is_points_rewards_runtime_available(),
				$this->is_admin_request()
			)
		) {
			return;
		}

		$manifest = MultiCurrencyPointsRewardsCompatibilityProjectionService::get_hook_manifest();

		foreach ( $manifest['filters'] as $filter ) {
			$callback = array( $this, (string) $filter['callback'] );
			if ( is_callable( $callback ) ) {
				$this->add_filter_once( (string) $filter['hook'], $callback, (int) $filter['priority'], (int) $filter['accepted_args'] );
			}
		}
	}

	/**
	 * Convert the monetary side of a Points and Rewards ratio to the selected currency.
	 *
	 * @param string $ratio Store currency points ratio.
	 * @return string
	 */
	public function convert_points_ratio( string $ratio = '' ): string {
		$state             = $this->get_state_builder()->build();
		$selected_currency = $state->get_selected_currency();
		$default_currency  = $state->get_default_currency();

		if (
			! MultiCurrencyPointsRewardsCompatibilityProjectionService::should_convert_ratio(
				$selected_currency->get_code() !== $default_currency->get_code(),
				$this->is_call_in_backtrace( self::POINTS_REWARDS_DISCOUNT_DATA_CALLS )
			)
		) {
			return $ratio;
		}

		return MultiCurrencyPointsRewardsCompatibilityProjectionService::convert_ratio_value(
			$ratio,
			$selected_currency->get_rate()
		);
	}

	/**
	 * Check if Points and Rewards runtime is available.
	 *
	 * @return bool
	 */
	protected function is_points_rewards_runtime_available(): bool {
		return class_exists( 'WC_Points_Rewards' );
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
	 * Get the state builder.
	 *
	 * @return MultiCurrencyStateBuilder
	 */
	private function get_state_builder(): MultiCurrencyStateBuilder {
		if ( null === $this->state_builder ) {
			$this->state_builder = wc_get_container()
				->get( MultiCurrencyStateBuilderFactory::class )
				->create( new MultiCurrencyLocalizationService() );
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
