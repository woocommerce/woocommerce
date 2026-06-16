<?php
/**
 * MultiCurrencyDepositsCompatibilityController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyDepositsCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency Deposits compatibility hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyDepositsCompatibilityController implements RegisterHooksInterface {

	private const DEPOSITS_FORM_OUTPUT_CALLS = array(
		'WC_Deposits_Cart_Manager->deposits_form_output',
	);

	private const CART_TOTALS_CALLS = array(
		'WC_Cart->calculate_totals',
	);

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
	 * @param MultiCurrencyRuntimeArbiter           $arbiter                    Runtime owner arbiter.
	 * @param MultiCurrencyProjectionServiceFactory $projection_service_factory Projection service factory.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter, MultiCurrencyProjectionServiceFactory $projection_service_factory ): void {
		$this->arbiter                    = $arbiter;
		$this->projection_service_factory = $projection_service_factory;
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
	 * Register Deposits compatibility hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		if ( $this->is_deposits_runtime_available() || $this->have_plugins_loaded() ) {
			$this->register_deposits_hooks();
			return;
		}

		$this->add_action_once( 'plugins_loaded', array( $this, 'register_deposits_hooks' ), 20 );
	}

	/**
	 * Register Deposits compatibility hooks after supported runtimes have loaded.
	 *
	 * @internal
	 */
	public function register_deposits_hooks(): void {
		if (
			! $this->arbiter->should_core_register()
			|| ! MultiCurrencyDepositsCompatibilityProjectionService::should_register(
				$this->is_deposits_runtime_available(),
				$this->get_deposits_version()
			)
		) {
			return;
		}

		$manifest = MultiCurrencyDepositsCompatibilityProjectionService::get_hook_manifest();

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
	 * Convert deposit amounts on cart items for the selected currency.
	 *
	 * @param array<mixed> $cart_contents Cart contents.
	 * @return array<mixed>
	 */
	public function modify_cart_item_deposit_amounts( array $cart_contents ): array {
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if (
				is_array( $cart_item )
				&& MultiCurrencyDepositsCompatibilityProjectionService::should_convert_cart_item_deposit_amount( $cart_item )
			) {
				$cart_contents[ $cart_item_key ]['deposit_amount'] = $this->get_price_projection_service()->get_price(
					(float) $cart_item['deposit_amount'],
					'product'
				);
			}
		}

		return $cart_contents;
	}

	/**
	 * Convert percent-based deposit amount meta while Deposits renders its form.
	 *
	 * @param mixed $amount  Amount.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public function modify_cart_item_deposit_amount_meta( $amount, $product ) {
		if (
			! MultiCurrencyDepositsCompatibilityProjectionService::should_convert_deposit_amount_meta(
				$this->get_product_deposit_type( $product ),
				$this->is_call_in_backtrace( self::DEPOSITS_FORM_OUTPUT_CALLS )
			)
		) {
			return $amount;
		}

		return $this->get_price_projection_service()->get_price( (float) $amount, 'product' );
	}

	/**
	 * Tell whether native product-price conversion should run during Deposits calculations.
	 *
	 * @param bool  $result  Existing product conversion decision.
	 * @param mixed $product Product.
	 * @return bool
	 */
	public function maybe_convert_product_prices_for_deposits( bool $result, $product ): bool {
		return MultiCurrencyDepositsCompatibilityProjectionService::should_convert_product_price(
			$result,
			$this->get_product_deposit_type( $product ),
			$this->is_call_in_backtrace( self::CART_TOTALS_CALLS )
		);
	}

	/**
	 * Align remaining-payment order currency to the original deposited order currency.
	 *
	 * @param int $order_id Created order ID.
	 */
	public function modify_order_currency( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$order_items = $order->get_items();
		if ( empty( $order_items ) ) {
			return;
		}

		$first_order_item = reset( $order_items );
		if ( ! $first_order_item instanceof \WC_Order_Item ) {
			return;
		}

		$original_order_id = wc_get_order_item_meta( $first_order_item->get_id(), '_original_order_id', true );
		if ( ! $original_order_id ) {
			return;
		}

		$original_order = wc_get_order( (int) $original_order_id );
		if ( ! $original_order instanceof \WC_Order ) {
			return;
		}

		$saved_currency    = $order->get_currency( 'view' );
		$original_currency = $original_order->get_currency( 'view' );

		if ( MultiCurrencyDepositsCompatibilityProjectionService::should_align_order_currency( $saved_currency, $original_currency ) ) {
			$order->set_currency( $original_currency );
			$order->save();
		}
	}

	/**
	 * Check if Deposits runtime is available.
	 *
	 * @return bool
	 */
	protected function is_deposits_runtime_available(): bool {
		return class_exists( 'WC_Deposits' );
	}

	/**
	 * Get Deposits version.
	 *
	 * @return string|null
	 */
	protected function get_deposits_version(): ?string {
		return defined( 'WC_DEPOSITS_VERSION' ) ? (string) constant( 'WC_DEPOSITS_VERSION' ) : null;
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
	 * Gets the deposit type of a product if deposits are enabled for the product.
	 *
	 * @param mixed $product Product.
	 * @return string|false
	 */
	protected function get_product_deposit_type( $product ) {
		$deposits_enabled = array( 'WC_Deposits_Product_Manager', 'deposits_enabled' );
		$get_deposit_type = array( 'WC_Deposits_Product_Manager', 'get_deposit_type' );

		if (
			! class_exists( 'WC_Deposits_Product_Manager' )
			|| ! is_callable( $deposits_enabled )
			|| ! is_callable( $get_deposit_type )
		) {
			return false;
		}

		if ( ! call_user_func( $deposits_enabled, $product ) ) {
			return false;
		}

		return (string) call_user_func( $get_deposit_type, $product );
	}

	/**
	 * Get the price projection service.
	 *
	 * @return MultiCurrencyPriceProjectionService
	 */
	private function get_price_projection_service(): MultiCurrencyPriceProjectionService {
		if ( null === $this->price_projection_service ) {
			$this->price_projection_service = $this->projection_service_factory->create_price_projection_service();
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
