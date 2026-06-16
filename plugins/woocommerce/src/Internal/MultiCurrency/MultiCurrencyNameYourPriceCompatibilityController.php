<?php
/**
 * MultiCurrencyNameYourPriceCompatibilityController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyLocalizationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyNameYourPriceCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency Name Your Price compatibility hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyNameYourPriceCompatibilityController implements RegisterHooksInterface {

	private const NYP_CURRENCY_META_KEY = '_wcpay_multi_currency_nyp_currency';

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
	 * State builder.
	 *
	 * @var MultiCurrencyStateBuilder|null
	 */
	private ?MultiCurrencyStateBuilder $state_builder = null;

	/**
	 * State builder factory.
	 *
	 * @var MultiCurrencyStateBuilderFactory
	 */
	private MultiCurrencyStateBuilderFactory $state_builder_factory;

	/**
	 * Projection service factory.
	 *
	 * @var MultiCurrencyProjectionServiceFactory
	 */
	private MultiCurrencyProjectionServiceFactory $projection_service_factory;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter           $arbiter                    Runtime owner arbiter.
	 * @param MultiCurrencyStateBuilderFactory      $state_builder_factory      State builder factory.
	 * @param MultiCurrencyProjectionServiceFactory $projection_service_factory Projection service factory.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter, MultiCurrencyStateBuilderFactory $state_builder_factory, MultiCurrencyProjectionServiceFactory $projection_service_factory ): void {
		$this->arbiter                    = $arbiter;
		$this->state_builder_factory      = $state_builder_factory;
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
	 * Set the state builder.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyStateBuilder $state_builder State builder.
	 */
	public function set_state_builder( MultiCurrencyStateBuilder $state_builder ): void {
		$this->state_builder = $state_builder;
	}

	/**
	 * Register Name Your Price compatibility hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		if ( $this->is_name_your_price_runtime_available() || $this->have_plugins_loaded() ) {
			$this->register_name_your_price_hooks();
			return;
		}

		$this->add_action_once( 'plugins_loaded', array( $this, 'register_name_your_price_hooks' ), 20 );
	}

	/**
	 * Register Name Your Price compatibility hooks after supported runtimes have loaded.
	 *
	 * @internal
	 */
	public function register_name_your_price_hooks(): void {
		if (
			! $this->arbiter->should_core_register()
			|| ! MultiCurrencyNameYourPriceCompatibilityProjectionService::should_register(
				$this->is_name_your_price_runtime_available()
			)
		) {
			return;
		}

		$manifest = MultiCurrencyNameYourPriceCompatibilityProjectionService::get_hook_manifest();

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
	 * Convert raw NYP prices for the selected currency.
	 *
	 * @param mixed $price Raw price.
	 * @return mixed
	 */
	public function get_nyp_prices( $price ) {
		if ( ! MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_raw_price( $price ) ) {
			return $price;
		}

		return $this->get_price_projection_service()->get_price( $price, 'product' );
	}

	/**
	 * Store the selected currency and original NYP value when a cart item is added.
	 *
	 * @param array<mixed> $cart_item    Cart item data.
	 * @param int          $product_id   Product ID.
	 * @param int          $variation_id Variation ID.
	 * @return array<mixed>
	 */
	public function add_initial_currency( array $cart_item, int $product_id, int $variation_id ): array {
		$nyp_id = $variation_id ? $variation_id : $product_id;

		if (
			! MultiCurrencyNameYourPriceCompatibilityProjectionService::should_store_initial_currency(
				$this->is_name_your_price_product( $nyp_id ),
				isset( $cart_item['nyp'] )
			)
		) {
			return $cart_item;
		}

		$cart_item['nyp_currency'] = $this->get_selected_currency_code();
		$cart_item['nyp_original'] = $cart_item['nyp'];

		return $cart_item;
	}

	/**
	 * Convert the stored NYP cart value when the selected currency changes.
	 *
	 * @param array<mixed> $cart_item Cart item data.
	 * @param array<mixed> $values    Session values.
	 * @return array<mixed>
	 */
	public function convert_cart_currency( array $cart_item, array $values ): array {
		unset( $values );

		if (
			! MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_cart_currency(
				$this->is_name_your_price_function_available(),
				isset( $cart_item['nyp_original'] ),
				isset( $cart_item['nyp_currency'] )
			)
		) {
			return $cart_item;
		}

		if ( isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) && is_callable( array( $cart_item['data'], 'update_meta_data' ) ) ) {
			$cart_item['data']->update_meta_data( self::NYP_CURRENCY_META_KEY, $cart_item['nyp_currency'] );
		}

		$selected_currency_code = $this->get_selected_currency_code();

		if ( $cart_item['nyp_currency'] === $selected_currency_code ) {
			$cart_item['nyp'] = $cart_item['nyp_original'];
		} else {
			$cart_item['nyp'] = $this->get_price_projection_service()->get_raw_conversion(
				(float) $cart_item['nyp_original'],
				$selected_currency_code,
				(string) $cart_item['nyp_currency']
			);
		}

		return $this->set_name_your_price_cart_item( $cart_item );
	}

	/**
	 * Tell whether native product-price conversion should run for NYP products.
	 *
	 * @param bool  $should_convert Existing product conversion decision.
	 * @param mixed $product        Product.
	 * @return bool
	 */
	public function should_convert_product_price( bool $should_convert, $product ): bool {
		$product_currency = is_object( $product ) && is_callable( array( $product, 'get_meta' ) )
			? (string) $product->get_meta( self::NYP_CURRENCY_META_KEY )
			: '';

		return MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_product_price(
			$should_convert,
			'' !== $product_currency && $product_currency === $this->get_selected_currency_code(),
			$this->is_name_your_price_product( $product )
		);
	}

	/**
	 * Add selected currency to cart edit args.
	 *
	 * @param array<mixed> $args      Cart edit args.
	 * @param array<mixed> $cart_item Cart item.
	 * @return array<mixed>
	 */
	public function edit_in_cart_args( array $args, array $cart_item ): array {
		unset( $cart_item );

		$args['nyp_currency'] = $this->get_selected_currency_code();

		return $args;
	}

	/**
	 * Convert initial cart-edit price from its request currency to selected currency.
	 *
	 * @param mixed  $initial_price Initial price.
	 * @param mixed  $product       Product.
	 * @param string $suffix        Request key suffix.
	 * @return mixed
	 */
	public function get_initial_price( $initial_price, $product, string $suffix ) {
		unset( $product );

		$raw_price_key       = 'nyp_raw' . $suffix;
		$has_raw_price       = $this->has_request_value( $raw_price_key );
		$has_source_currency = $this->has_request_value( 'nyp_currency' );
		$source_currency     = $has_source_currency ? $this->get_sanitized_request_value( 'nyp_currency' ) : '';
		$selected_currency   = $this->get_selected_currency_code();

		if (
			! MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_initial_price(
				$has_raw_price,
				$has_source_currency,
				$source_currency,
				$selected_currency
			)
		) {
			return $initial_price;
		}

		$raw_price = (float) $this->get_sanitized_request_value( $raw_price_key );

		return $this->get_price_projection_service()->get_raw_conversion( $raw_price, $selected_currency, $source_currency );
	}

	/**
	 * Check if Name Your Price runtime is available.
	 *
	 * @return bool
	 */
	protected function is_name_your_price_runtime_available(): bool {
		return class_exists( 'WC_Name_Your_Price' );
	}

	/**
	 * Check if the Name Your Price function is available.
	 *
	 * @return bool
	 */
	protected function is_name_your_price_function_available(): bool {
		return function_exists( 'WC_Name_Your_Price' );
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
	 * Tell whether a product is NYP-enabled.
	 *
	 * @param mixed $product Product or product ID.
	 * @return bool
	 */
	protected function is_name_your_price_product( $product ): bool {
		$is_nyp = array( 'WC_Name_Your_Price_Helpers', 'is_nyp' );

		if ( ! class_exists( 'WC_Name_Your_Price_Helpers' ) || ! is_callable( $is_nyp ) ) {
			return false;
		}

		return (bool) call_user_func( $is_nyp, $product );
	}

	/**
	 * Run the Name Your Price cart item normalizer.
	 *
	 * @param array<mixed> $cart_item Cart item.
	 * @return array<mixed>
	 */
	protected function set_name_your_price_cart_item( array $cart_item ): array {
		if ( ! function_exists( 'WC_Name_Your_Price' ) ) {
			return $cart_item;
		}

		$name_your_price = \WC_Name_Your_Price();
		if (
			! is_object( $name_your_price )
			|| ! isset( $name_your_price->cart )
			|| ! is_object( $name_your_price->cart )
			|| ! is_callable( array( $name_your_price->cart, 'set_cart_item' ) )
		) {
			return $cart_item;
		}

		$normalized_cart_item = call_user_func( array( $name_your_price->cart, 'set_cart_item' ), $cart_item );

		return is_array( $normalized_cart_item ) ? $normalized_cart_item : $cart_item;
	}

	/**
	 * Check if a request value exists.
	 *
	 * @param string $key Request key.
	 * @return bool
	 */
	private function has_request_value( string $key ): bool {
		return isset( $_REQUEST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooPayments-compatible cart edit request.
	}

	/**
	 * Get a sanitized scalar request value.
	 *
	 * @param string $key Request key.
	 * @return string
	 */
	private function get_sanitized_request_value( string $key ): string {
		if ( ! $this->has_request_value( $key ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- WooPayments-compatible cart edit request.
		$request_value = wp_unslash( $_REQUEST[ $key ] );

		if ( ! is_scalar( $request_value ) ) {
			return '';
		}

		$clean_value = wc_clean( (string) $request_value );

		return is_string( $clean_value ) ? $clean_value : '';
	}

	/**
	 * Get selected currency code.
	 *
	 * @return string
	 */
	private function get_selected_currency_code(): string {
		return $this->get_state_builder()->build()->get_selected_currency()->get_code();
	}

	/**
	 * Get the price projection service.
	 *
	 * @return MultiCurrencyPriceProjectionService
	 */
	private function get_price_projection_service(): MultiCurrencyPriceProjectionService {
		if ( null === $this->price_projection_service ) {
			$this->price_projection_service = $this->projection_service_factory->create_price_projection_service( null, $this->get_state_builder() );
		}

		return $this->price_projection_service;
	}

	/**
	 * Get the state builder.
	 *
	 * @return MultiCurrencyStateBuilder
	 */
	private function get_state_builder(): MultiCurrencyStateBuilder {
		if ( null === $this->state_builder ) {
			$this->state_builder = $this->state_builder_factory->create( new MultiCurrencyLocalizationService() );
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
