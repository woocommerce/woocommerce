<?php
/**
 * MultiCurrencyStoreCurrencyLifecycleController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyDatabaseCache;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStoreCurrencyLifecycleService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency store-currency lifecycle hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyStoreCurrencyLifecycleController implements RegisterHooksInterface {

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
	 * Store-currency lifecycle service.
	 *
	 * @var MultiCurrencyStoreCurrencyLifecycleService|null
	 */
	private ?MultiCurrencyStoreCurrencyLifecycleService $lifecycle_service = null;

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
	 * Set the lifecycle service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyStoreCurrencyLifecycleService $lifecycle_service Lifecycle service.
	 */
	public function set_lifecycle_service( MultiCurrencyStoreCurrencyLifecycleService $lifecycle_service ): void {
		$this->lifecycle_service = $lifecycle_service;
	}

	/**
	 * Register store-currency lifecycle hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		$this->add_action_once( 'init', array( $this, 'handle_init' ), 10 );
	}

	/**
	 * Handle the init hook.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		$this->get_lifecycle_service()->synchronize_store_currency();
	}

	/**
	 * Get the lifecycle service.
	 *
	 * @return MultiCurrencyStoreCurrencyLifecycleService
	 */
	private function get_lifecycle_service(): MultiCurrencyStoreCurrencyLifecycleService {
		if ( null === $this->lifecycle_service ) {
			$cache                   = new MultiCurrencyDatabaseCache();
			$this->lifecycle_service = new MultiCurrencyStoreCurrencyLifecycleService(
				$cache,
				$this->state_builder_factory->create( null, $cache )
			);
		}

		return $this->lifecycle_service;
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
