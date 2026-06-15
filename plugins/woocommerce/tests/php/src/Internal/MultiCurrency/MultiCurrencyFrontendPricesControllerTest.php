<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyFrontendPricesController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use WC_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Tests for the MultiCurrencyFrontendPricesController class.
 */
class MultiCurrencyFrontendPricesControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the frontend prices controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'woocommerce_product_get_price',
		'woocommerce_product_get_regular_price',
		'woocommerce_product_get_sale_price',
		'woocommerce_product_variation_get_price',
		'woocommerce_product_variation_get_regular_price',
		'woocommerce_product_variation_get_sale_price',
		'woocommerce_variation_prices',
		'woocommerce_get_variation_prices_hash',
		'woocommerce_shipping_zone_shipping_methods',
		'woocommerce_shipping_method_add_rate_args',
		'woocommerce_coupon_get_amount',
		'woocommerce_coupon_get_minimum_amount',
		'woocommerce_coupon_get_maximum_amount',
		'woocommerce_new_order',
		'rest_post_dispatch',
		'query_loop_block_query_vars',
		'wcpay_multi_currency_should_convert_product_price',
		'wcpay_multi_currency_should_convert_coupon_amount',
	);

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->hooks as $hook ) {
			remove_all_filters( $hook );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should not register frontend price hooks while plugin multi-currency owns the runtime.
	 */
	public function test_does_not_register_when_plugin_multi_currency_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_product_get_price', array( $sut, 'get_product_price_string' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_coupon_get_amount', array( $sut, 'get_coupon_amount' ) ) );
	}

	/**
	 * @testdox Should not register frontend price hooks when no multi-currency runtime owns the site.
	 */
	public function test_does_not_register_when_no_multi_currency_runtime_owns_site(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_NONE );

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_product_get_price', array( $sut, 'get_product_price_string' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_coupon_get_amount', array( $sut, 'get_coupon_amount' ) ) );
	}

	/**
	 * @testdox Should register frontend price hooks when core multi-currency owns the runtime.
	 */
	public function test_registers_frontend_price_hooks_when_core_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$sut->register();
		$sut->register();

		$this->assertSame( 99, has_filter( 'woocommerce_product_get_price', array( $sut, 'get_product_price_string' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_product_get_regular_price', array( $sut, 'get_product_price_string' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_product_get_sale_price', array( $sut, 'get_product_price_string' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_product_variation_get_price', array( $sut, 'get_product_price_string' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_product_variation_get_regular_price', array( $sut, 'get_product_price_string' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_product_variation_get_sale_price', array( $sut, 'get_product_price_string' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_variation_prices', array( $sut, 'get_variation_price_range' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_get_variation_prices_hash', array( $sut, 'add_exchange_rate_to_variation_prices_hash' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_shipping_zone_shipping_methods', array( $sut, 'convert_free_shipping_method_min_amount' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_shipping_method_add_rate_args', array( $sut, 'convert_shipping_method_rate_cost' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_coupon_get_amount', array( $sut, 'get_coupon_amount' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_coupon_get_minimum_amount', array( $sut, 'get_coupon_min_max_amount' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_coupon_get_maximum_amount', array( $sut, 'get_coupon_min_max_amount' ) ) );
		$this->assertSame( 99, has_filter( 'woocommerce_new_order', array( $sut, 'add_order_meta' ) ) );
		$this->assertSame( 10, has_filter( 'rest_post_dispatch', array( $sut, 'maybe_modify_price_ranges_rest_response' ) ) );
		$this->assertSame( 10, has_filter( 'query_loop_block_query_vars', array( $sut, 'maybe_modify_price_ranges_query_var' ) ) );
	}

	/**
	 * @testdox Should convert product variation shipping and coupon prices through the projection service.
	 */
	public function test_converts_prices_through_projection_service(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$this->assertSame( '20', $sut->get_product_price_string( '10.00', (object) array() ) );
		$this->assertSame(
			array(
				'price'         => array( 11 => '20' ),
				'regular_price' => array( 11 => '24' ),
			),
			$sut->get_variation_price_range(
				array(
					'price'         => array( 11 => '10.00' ),
					'regular_price' => array( 11 => '12.00' ),
				)
			)
		);
		$this->assertSame( array( 'base', 2.0 ), $sut->add_exchange_rate_to_variation_prices_hash( array( 'base' ) ) );
		$this->assertSame( array( 'cost' => 6.0 ), $sut->convert_shipping_method_rate_cost( array( 'cost' => '2.00' ) ) );
		$this->assertSame(
			array(
				'cost' => array(
					'base' => 6.0,
					'fee'  => 9.0,
				),
			),
			$sut->convert_shipping_method_rate_cost(
				array(
					'cost' => array(
						'base' => '2.00',
						'fee'  => '3.00',
					),
				)
			)
		);
		$this->assertSame( 20.0, $sut->get_coupon_amount( '5.00', $this->create_coupon() ) );
		$this->assertSame( 20.0, $sut->get_coupon_min_max_amount( '10.00' ) );
	}

	/**
	 * @testdox Should respect conversion guards for product and coupon prices.
	 */
	public function test_respects_conversion_guards_for_product_and_coupon_prices(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		add_filter( 'wcpay_multi_currency_should_convert_product_price', '__return_false' );
		add_filter( 'wcpay_multi_currency_should_convert_coupon_amount', '__return_false' );

		$this->assertSame( '10.00', $sut->get_product_price_string( '10.00', (object) array() ) );
		$this->assertSame( '0', $sut->get_product_price_string( 0, (object) array() ) );
		$this->assertSame( '5.00', $sut->get_coupon_amount( '5.00', $this->create_coupon() ) );
		$this->assertSame( '5.00', $sut->get_coupon_amount( '5.00', $this->create_coupon( true ) ) );
		$this->assertSame( 0, $sut->get_coupon_min_max_amount( 0 ) );
	}

	/**
	 * @testdox Should convert free shipping minimums and persist projected order meta.
	 */
	public function test_converts_free_shipping_minimums_and_persists_order_meta(): void {
		$sut               = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$free_shipping     = (object) array(
			'id'         => 'free_shipping',
			'min_amount' => '10.00',
		);
		$flat_rate         = (object) array(
			'id'         => 'flat_rate',
			'min_amount' => '10.00',
		);
		$converted_methods = $sut->convert_free_shipping_method_min_amount( array( $free_shipping, $flat_rate ) );
		$order             = wc_create_order();
		$order_id          = $order->get_id();
		$order->set_currency( 'GBP' );
		$order->save();

		$sut->add_order_meta( $order_id, $order );
		$order = wc_get_order( $order_id );

		$this->assertSame( 20.0, $converted_methods[0]->min_amount );
		$this->assertSame( '10.00', $converted_methods[1]->min_amount );
		$this->assertSame( '0.82', $order->get_meta( '_wcpay_multi_currency_order_exchange_rate', true ) );
		$this->assertSame( 'USD', $order->get_meta( '_wcpay_multi_currency_order_default_currency', true ) );
	}

	/**
	 * @testdox Should convert Store API and query-loop price ranges.
	 */
	public function test_converts_store_api_and_query_loop_price_ranges(): void {
		$sut      = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$response = new WP_REST_Response(
			array(
				'price_range' => (object) array(
					'min_price' => '10.00',
					'max_price' => '12.00',
				),
			)
		);
		$request  = new WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$query    = array(
			'post_type'  => 'product',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => array(
				array(
					'key'     => '_price',
					'value'   => '8.2',
					'compare' => '>=',
				),
				array(
					'key'     => '_price',
					'value'   => '8.21',
					'compare' => '<=',
				),
			),
		);

		$response = $sut->maybe_modify_price_ranges_rest_response( $response, null, $request );
		$query    = $sut->maybe_modify_price_ranges_query_var( $query, null, 1 );

		$data = $response->get_data();
		$this->assertSame( '20', $data['price_range']->min_price );
		$this->assertSame( '24', $data['price_range']->max_price );
		$this->assertSame( 'floor-8.2', $query['meta_query'][0]['value'] );
		$this->assertSame( 'ceil-8.21', $query['meta_query'][1]['value'] );
	}

	/**
	 * Create a frontend prices controller with a static runtime owner.
	 *
	 * @param string $owner                  Runtime owner.
	 * @param bool   $should_project_queries Whether query price filters should project.
	 * @return MultiCurrencyFrontendPricesController
	 */
	private function create_controller( string $owner, bool $should_project_queries = true ): MultiCurrencyFrontendPricesController {
		$controller = new MultiCurrencyFrontendPricesController();
		$controller->init( $this->create_arbiter( $owner ) );
		$controller->set_price_projection_service( $this->create_projection_service( $should_project_queries ) );

		return $controller;
	}

	/**
	 * Create a static multi-currency runtime arbiter.
	 *
	 * @param string $owner Runtime owner.
	 * @return MultiCurrencyRuntimeArbiter
	 */
	private function create_arbiter( string $owner ): MultiCurrencyRuntimeArbiter {
		return new class( $owner ) extends MultiCurrencyRuntimeArbiter {
			/**
			 * Runtime owner.
			 *
			 * @var string
			 */
			private string $owner;

			/**
			 * Constructor.
			 *
			 * @param string $owner Runtime owner.
			 */
			public function __construct( string $owner ) {
				$this->owner = $owner;
			}

			/**
			 * Get the multi-currency runtime owner for the current site.
			 *
			 * @return string
			 */
			public function get_runtime_owner(): string {
				return $this->owner;
			}

			/**
			 * Tell whether core multi-currency may register price/currency hooks.
			 *
			 * @return bool
			 */
			public function should_core_register(): bool {
				return MultiCurrencyRuntimeArbiter::OWNER_CORE === $this->owner;
			}
		};
	}

	/**
	 * Create a deterministic price projection service.
	 *
	 * @param bool $should_project_queries Whether query price filters should project.
	 * @return MultiCurrencyPriceProjectionService
	 */
	private function create_projection_service( bool $should_project_queries ): MultiCurrencyPriceProjectionService {
		return new class( $should_project_queries ) extends MultiCurrencyPriceProjectionService {
			/**
			 * Whether query price filters should project.
			 *
			 * @var bool
			 */
			private bool $should_project_queries;

			/**
			 * Constructor.
			 *
			 * @param bool $should_project_queries Whether query price filters should project.
			 */
			public function __construct( bool $should_project_queries ) {
				$this->should_project_queries = $should_project_queries;
			}

			/**
			 * Project a converted price for the selected currency.
			 *
			 * @param mixed  $price Price.
			 * @param string $type  Price type.
			 * @return float
			 */
			public function get_price( $price, string $type ): float {
				$multipliers = array(
					'product'       => 2.0,
					'shipping'      => 3.0,
					'coupon'        => 4.0,
					'exchange_rate' => 0.5,
				);

				return (float) $price * ( $multipliers[ $type ] ?? 1.0 );
			}

			/**
			 * Project order meta candidates for a selected-currency order.
			 *
			 * @param string $order_currency Order currency code.
			 * @return array<string,float|string>
			 */
			public function get_order_meta_candidates( string $order_currency ): array {
				if ( 'GBP' !== strtoupper( $order_currency ) ) {
					return array();
				}

				return array(
					'_wcpay_multi_currency_order_exchange_rate'    => 0.82,
					'_wcpay_multi_currency_order_default_currency' => 'USD',
				);
			}

			/**
			 * Tell whether query price filters should be projected.
			 *
			 * @return bool
			 */
			public function should_project_between_selected_and_default_currency(): bool {
				return $this->should_project_queries;
			}

			/**
			 * Project a selected-currency price-filter value to default currency.
			 *
			 * @param mixed  $amount  Query amount.
			 * @param string $compare Query comparison operator.
			 * @return string
			 */
			public function get_price_filter_query_value( $amount, string $compare ): string {
				return '<=' === $compare ? 'ceil-' . (string) $amount : 'floor-' . (string) $amount;
			}
		};
	}

	/**
	 * Create a coupon test double.
	 *
	 * @param bool $is_percent Whether this is a percentage coupon.
	 * @return object
	 */
	private function create_coupon( bool $is_percent = false ): object {
		return new class( $is_percent ) {
			/**
			 * Whether this is a percentage coupon.
			 *
			 * @var bool
			 */
			private bool $is_percent;

			/**
			 * Constructor.
			 *
			 * @param bool $is_percent Whether this is a percentage coupon.
			 */
			public function __construct( bool $is_percent ) {
				$this->is_percent = $is_percent;
			}

			/**
			 * Tell whether the coupon belongs to the given type set.
			 *
			 * @param string[] $types Coupon types.
			 * @return bool
			 */
			public function is_type( array $types ): bool {
				return $this->is_percent && in_array( 'percent', $types, true );
			}
		};
	}
}
