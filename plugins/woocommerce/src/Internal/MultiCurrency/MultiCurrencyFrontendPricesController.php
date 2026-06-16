<?php
/**
 * MultiCurrencyFrontendPricesController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native frontend price hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyFrontendPricesController implements RegisterHooksInterface {

	private const FILTER_SHOULD_CONVERT_PRODUCT_PRICE = 'wcpay_multi_currency_should_convert_product_price';
	private const FILTER_SHOULD_CONVERT_COUPON_AMOUNT = 'wcpay_multi_currency_should_convert_coupon_amount';
	private const STORE_API_COLLECTION_DATA_ROUTE     = '/wc/store/v1/products/collection-data';

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
	 * Request context.
	 *
	 * @var MultiCurrencyRequestContext|null
	 */
	private ?MultiCurrencyRequestContext $request_context = null;

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
	 * Register frontend price hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() || ! $this->get_request_context()->should_register_frontend_hooks() ) {
			return;
		}

		$this->add_filter_once( 'woocommerce_product_get_price', array( $this, 'get_product_price_string' ), 99, 2 );
		$this->add_filter_once( 'woocommerce_product_get_regular_price', array( $this, 'get_product_price_string' ), 99, 2 );
		$this->add_filter_once( 'woocommerce_product_get_sale_price', array( $this, 'get_product_price_string' ), 99, 2 );
		$this->add_filter_once( 'woocommerce_product_variation_get_price', array( $this, 'get_product_price_string' ), 99, 2 );
		$this->add_filter_once( 'woocommerce_product_variation_get_regular_price', array( $this, 'get_product_price_string' ), 99, 2 );
		$this->add_filter_once( 'woocommerce_product_variation_get_sale_price', array( $this, 'get_product_price_string' ), 99, 2 );
		$this->add_filter_once( 'woocommerce_variation_prices', array( $this, 'get_variation_price_range' ), 99 );
		$this->add_filter_once( 'woocommerce_get_variation_prices_hash', array( $this, 'add_exchange_rate_to_variation_prices_hash' ), 99 );
		$this->add_filter_once( 'woocommerce_shipping_zone_shipping_methods', array( $this, 'convert_free_shipping_method_min_amount' ), 99 );
		$this->add_filter_once( 'woocommerce_shipping_method_add_rate_args', array( $this, 'convert_shipping_method_rate_cost' ), 99 );
		$this->add_filter_once( 'woocommerce_coupon_get_amount', array( $this, 'get_coupon_amount' ), 99, 2 );
		$this->add_filter_once( 'woocommerce_coupon_get_minimum_amount', array( $this, 'get_coupon_min_max_amount' ), 99 );
		$this->add_filter_once( 'woocommerce_coupon_get_maximum_amount', array( $this, 'get_coupon_min_max_amount' ), 99 );
		$this->add_filter_once( 'woocommerce_new_order', array( $this, 'add_order_meta' ), 99, 2 );
		$this->add_action_once( 'woocommerce_order_refunded', array( $this, 'add_refund_meta' ), 99, 2 );
		$this->add_filter_once( 'rest_post_dispatch', array( $this, 'maybe_modify_price_ranges_rest_response' ), 10, 3 );
		$this->add_filter_once( 'query_loop_block_query_vars', array( $this, 'maybe_modify_price_ranges_query_var' ), 10, 3 );
	}

	/**
	 * Project the product price when conversion is allowed.
	 *
	 * @param mixed $price   Product price.
	 * @param mixed $product Product object.
	 * @return mixed
	 */
	public function get_product_price( $price, $product = null ) {
		if ( ! $price || ! $this->should_convert_product_price( $product ) ) {
			return $price;
		}

		return $this->get_price_projection_service()->get_price( $price, 'product' );
	}

	/**
	 * Project the product price as a string.
	 *
	 * @param mixed $price   Product price.
	 * @param mixed $product Product object.
	 * @return string
	 */
	public function get_product_price_string( $price, $product = null ): string {
		return (string) $this->get_product_price( $price, $product );
	}

	/**
	 * Project variation price ranges.
	 *
	 * @param mixed $variation_prices Variation price range arrays.
	 * @return mixed
	 */
	public function get_variation_price_range( $variation_prices ) {
		if ( ! is_array( $variation_prices ) ) {
			return $variation_prices;
		}

		foreach ( $variation_prices as $price_type => $prices ) {
			if ( ! is_array( $prices ) ) {
				continue;
			}

			foreach ( $prices as $variation_id => $price ) {
				$variation_prices[ $price_type ][ $variation_id ] = $this->get_product_price_string( $price );
			}
		}

		return $variation_prices;
	}

	/**
	 * Add the selected-currency exchange rate to the variation price hash.
	 *
	 * @param mixed $prices_hash Variation prices hash.
	 * @return mixed
	 */
	public function add_exchange_rate_to_variation_prices_hash( $prices_hash ) {
		if ( ! is_array( $prices_hash ) ) {
			return $prices_hash;
		}

		$prices_hash[] = $this->get_product_price( 1 );

		return $prices_hash;
	}

	/**
	 * Project shipping add-rate cost args.
	 *
	 * @param mixed $args Shipping rate args.
	 * @return mixed
	 */
	public function convert_shipping_method_rate_cost( $args ) {
		if ( ! is_array( $args ) || ! isset( $args['cost'] ) ) {
			return $args;
		}

		if ( is_array( $args['cost'] ) ) {
			$args['cost'] = array_map(
				function ( $cost ) {
					return $this->get_price_projection_service()->get_price( $cost, 'shipping' );
				},
				$args['cost']
			);
		} else {
			$args['cost'] = $this->get_price_projection_service()->get_price( $args['cost'], 'shipping' );
		}

		return $args;
	}

	/**
	 * Project fixed coupon amounts when conversion is allowed.
	 *
	 * @param mixed $amount Coupon amount.
	 * @param mixed $coupon Coupon object.
	 * @return mixed
	 */
	public function get_coupon_amount( $amount, $coupon ) {
		if ( ! $amount || $this->is_percentage_coupon( $coupon ) || ! $this->should_convert_coupon_amount( $coupon ) ) {
			return $amount;
		}

		return $this->get_price_projection_service()->get_price( $amount, 'coupon' );
	}

	/**
	 * Project coupon minimum and maximum amounts.
	 *
	 * @param mixed $amount Coupon minimum or maximum amount.
	 * @return mixed
	 */
	public function get_coupon_min_max_amount( $amount ) {
		if ( ! $amount ) {
			return $amount;
		}

		return $this->get_price_projection_service()->get_price( $amount, 'product' );
	}

	/**
	 * Project free shipping minimum amounts.
	 *
	 * @param mixed $methods Shipping methods.
	 * @return mixed
	 */
	public function convert_free_shipping_method_min_amount( $methods ) {
		if ( ! is_array( $methods ) ) {
			return $methods;
		}

		foreach ( $methods as $method ) {
			if (
				is_object( $method ) &&
				isset( $method->id, $method->min_amount ) &&
				'free_shipping' === $method->id &&
				! empty( $method->min_amount )
			) {
				$method->min_amount = $this->get_price_projection_service()->get_price( $method->min_amount, 'product' );
			}
		}

		return $methods;
	}

	/**
	 * Persist projected multi-currency order meta.
	 *
	 * @param mixed $order_id Order ID.
	 * @param mixed $order    Order object.
	 */
	public function add_order_meta( $order_id, $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( absint( $order_id ) );
		}

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$meta_candidates = $this->get_price_projection_service()->get_order_meta_candidates( $order->get_currency() );
		if ( empty( $meta_candidates ) ) {
			return;
		}

		foreach ( $meta_candidates as $meta_key => $meta_value ) {
			$order->update_meta_data( $meta_key, (string) $meta_value );
		}

		$order->save_meta_data();
	}

	/**
	 * Persist projected multi-currency refund meta.
	 *
	 * @param mixed $order_id  Order ID.
	 * @param mixed $refund_id Refund ID.
	 */
	public function add_refund_meta( $order_id, $refund_id ): void {
		$order  = wc_get_order( absint( $order_id ) );
		$refund = wc_get_order( absint( $refund_id ) );

		if ( ! $order instanceof \WC_Order || ! $refund instanceof \WC_Order_Refund ) {
			return;
		}

		$meta_candidates = $this->get_price_projection_service()->get_refund_meta_candidates( $order );
		if ( empty( $meta_candidates ) ) {
			return;
		}

		foreach ( $meta_candidates as $meta_key => $meta_value ) {
			$refund->update_meta_data( $meta_key, (string) $meta_value );
		}

		$refund->save_meta_data();
	}

	/**
	 * Project Store API collection-data price ranges.
	 *
	 * @param mixed $response REST response.
	 * @param mixed $server   REST server.
	 * @param mixed $request  REST request.
	 * @return mixed
	 */
	public function maybe_modify_price_ranges_rest_response( $response, $server, $request ) {
		unset( $server );

		if (
			! $response instanceof \WP_REST_Response ||
			! $request instanceof \WP_REST_Request ||
			self::STORE_API_COLLECTION_DATA_ROUTE !== $request->get_route()
		) {
			return $response;
		}

		$data = $response->get_data();
		if ( ! is_array( $data ) || empty( $data['price_range'] ) || ! is_object( $data['price_range'] ) ) {
			return $response;
		}

		foreach ( array( 'min_price', 'max_price' ) as $field ) {
			if ( property_exists( $data['price_range'], $field ) && is_numeric( $data['price_range']->$field ) ) {
				$data['price_range']->$field = (string) $this->get_price_projection_service()->get_price( $data['price_range']->$field, 'product' );
			}
		}

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Project query-loop price-range query vars.
	 *
	 * @param mixed $query Query vars.
	 * @param mixed $block Block instance.
	 * @param mixed $page  Page number.
	 * @return mixed
	 */
	public function maybe_modify_price_ranges_query_var( $query, $block, $page ) {
		unset( $block, $page );

		if (
			! is_array( $query ) ||
			'product' !== ( $query['post_type'] ?? null ) ||
			empty( $query['meta_query'] ) ||
			! is_array( $query['meta_query'] ) ||
			! $this->get_price_projection_service()->should_project_between_selected_and_default_currency()
		) {
			return $query;
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$query['meta_query'] = $this->convert_meta_query_price_filters( $query['meta_query'] );

		return $query;
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
	 * Get the request context.
	 *
	 * @return MultiCurrencyRequestContext
	 */
	private function get_request_context(): MultiCurrencyRequestContext {
		if ( null === $this->request_context ) {
			$this->request_context = new MultiCurrencyRequestContext();
		}

		return $this->request_context;
	}

	/**
	 * Recursively project price meta-query filters.
	 *
	 * @param array<mixed> $meta_query Meta query.
	 * @param int          $depth      Current recursion depth.
	 * @return array<mixed>
	 */
	private function convert_meta_query_price_filters( array $meta_query, int $depth = 0 ): array {
		if ( 4 < $depth ) {
			return $meta_query;
		}

		foreach ( $meta_query as $key => $query ) {
			if ( ! is_array( $query ) ) {
				continue;
			}

			if (
				'_price' === ( $query['key'] ?? null ) &&
				isset( $query['value'], $query['compare'] ) &&
				is_numeric( $query['value'] ) &&
				in_array( $query['compare'], array( '<=', '>=' ), true )
			) {
				$meta_query[ $key ]['value'] = $this->get_price_projection_service()->get_price_filter_query_value(
					$query['value'],
					(string) $query['compare']
				);
				continue;
			}

			$meta_query[ $key ] = $this->convert_meta_query_price_filters( $query, $depth + 1 );
		}

		return $meta_query;
	}

	/**
	 * Tell whether product price conversion should run.
	 *
	 * @param mixed $product Product object.
	 * @return bool
	 */
	private function should_convert_product_price( $product = null ): bool {
		if ( ! $product ) {
			return true;
		}

		/**
		 * Filters whether a product price should be converted.
		 *
		 * @param bool  $should_convert Whether the product price should be converted.
		 * @param mixed $product        Product object.
		 *
		 * @since 11.0.0
		 */
		return (bool) apply_filters( self::FILTER_SHOULD_CONVERT_PRODUCT_PRICE, true, $product );
	}

	/**
	 * Tell whether coupon amount conversion should run.
	 *
	 * @param mixed $coupon Coupon object.
	 * @return bool
	 */
	private function should_convert_coupon_amount( $coupon = null ): bool {
		if ( ! $coupon ) {
			return true;
		}

		/**
		 * Filters whether a coupon amount should be converted.
		 *
		 * @param bool  $should_convert Whether the coupon amount should be converted.
		 * @param mixed $coupon         Coupon object.
		 *
		 * @since 11.0.0
		 */
		return (bool) apply_filters( self::FILTER_SHOULD_CONVERT_COUPON_AMOUNT, true, $coupon );
	}

	/**
	 * Tell whether the coupon is percentage-based.
	 *
	 * @param mixed $coupon Coupon object.
	 * @return bool
	 */
	private function is_percentage_coupon( $coupon ): bool {
		return is_object( $coupon ) &&
			is_callable( array( $coupon, 'is_type' ) ) &&
			(bool) call_user_func( array( $coupon, 'is_type' ), array( 'percent' ) );
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
