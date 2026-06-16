<?php
/**
 * MultiCurrencySwitcherWidgetController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRuntimeServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySwitcherProjectionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency switcher widget hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySwitcherWidgetController implements RegisterHooksInterface {

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Compatibility controller.
	 *
	 * @var MultiCurrencyCompatibilityController
	 */
	private MultiCurrencyCompatibilityController $compatibility_controller;

	/**
	 * Switcher projection service.
	 *
	 * @var MultiCurrencySwitcherProjectionService|null
	 */
	private ?MultiCurrencySwitcherProjectionService $switcher_projection_service = null;

	/**
	 * Registered widget instance.
	 *
	 * @var MultiCurrencySwitcherWidget|null
	 */
	private ?MultiCurrencySwitcherWidget $widget = null;

	/**
	 * Runtime service factory.
	 *
	 * @var MultiCurrencyRuntimeServiceFactory
	 */
	private MultiCurrencyRuntimeServiceFactory $runtime_service_factory;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter          $arbiter                  Runtime owner arbiter.
	 * @param MultiCurrencyCompatibilityController $compatibility_controller Compatibility controller.
	 * @param MultiCurrencyRuntimeServiceFactory   $runtime_service_factory  Runtime service factory.
	 */
	final public function init(
		MultiCurrencyRuntimeArbiter $arbiter,
		MultiCurrencyCompatibilityController $compatibility_controller,
		MultiCurrencyRuntimeServiceFactory $runtime_service_factory
	): void {
		$this->arbiter                  = $arbiter;
		$this->compatibility_controller = $compatibility_controller;
		$this->runtime_service_factory  = $runtime_service_factory;
	}

	/**
	 * Set the switcher projection service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencySwitcherProjectionService $switcher_projection_service Switcher projection service.
	 */
	public function set_switcher_projection_service( MultiCurrencySwitcherProjectionService $switcher_projection_service ): void {
		$this->switcher_projection_service = $switcher_projection_service;
	}

	/**
	 * Register switcher widget hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		$this->add_action_once( 'widgets_init', array( $this, 'handle_widgets_init' ) );
	}

	/**
	 * Register the native switcher widget.
	 *
	 * @internal
	 */
	public function handle_widgets_init(): void {
		if ( null !== $this->widget ) {
			return;
		}

		$this->widget = new MultiCurrencySwitcherWidget(
			$this->get_switcher_projection_service(),
			$this->compatibility_controller
		);

		register_widget( $this->widget );
	}

	/**
	 * Get the registered widget instance.
	 *
	 * @return MultiCurrencySwitcherWidget|null
	 */
	public function get_registered_widget(): ?MultiCurrencySwitcherWidget {
		return $this->widget;
	}

	/**
	 * Get the switcher projection service.
	 *
	 * @return MultiCurrencySwitcherProjectionService
	 */
	private function get_switcher_projection_service(): MultiCurrencySwitcherProjectionService {
		if ( null === $this->switcher_projection_service ) {
			$this->switcher_projection_service = $this->runtime_service_factory->create_switcher_projection_service();
		}

		return $this->switcher_projection_service;
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
