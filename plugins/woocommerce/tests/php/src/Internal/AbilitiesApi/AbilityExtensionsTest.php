<?php
/**
 * AbilityExtensionsTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\AbilityExtensions;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Namespaced extension fields in the output of the product and order abilities, read through the REST route.
 */
class AbilityExtensionsTest extends \WC_REST_Unit_Test_Case {

	use HPOSToggleTrait;

	private const CODE_SCHEMA = array(
		'type'  => 'string',
		'title' => 'Test code',
	);

	/**
	 * Shared administrator used as the current user.
	 *
	 * @var int
	 */
	private static $administrator_id;

	/**
	 * Original init action count restored in tearDown.
	 *
	 * @var int|null
	 */
	private $original_init_count;

	/**
	 * Create immutable class fixtures.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		global $wp_actions;

		if ( ! function_exists( 'wp_register_ability' ) ) {
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
		}

		$this->original_init_count = $wp_actions['init'] ?? null;
		$wp_actions['init']        = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::setUp();

		if ( class_exists( 'WP_REST_Abilities_Init' ) && ! isset( $this->server->get_routes()['/wp-abilities/v1/abilities'] ) ) {
			\WP_REST_Abilities_Init::register_routes( $this->server );
		}

		AbilitiesLoader::init();
		$this->reset_registries();
		wp_set_current_user( self::$administrator_id );
		$this->set_feature( true );
		add_action( 'woocommerce_ability_extensions_init', array( $this, 'register_test_fields' ) );
		add_action( 'woocommerce_ability_extensions_init', array( $this, 'register_test_filters' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		remove_action( 'woocommerce_ability_extensions_init', array( $this, 'register_test_fields' ) );
		remove_action( 'woocommerce_ability_extensions_init', array( $this, 'register_test_filters' ) );
		$this->set_feature( false );
		$this->reset_registries();
		wp_set_current_user( 0 );

		parent::tearDown();

		if ( null !== $this->original_init_count ) {
			$wp_actions['init'] = $this->original_init_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			unset( $wp_actions['init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Register the fields of two extensions, the way an extension does inside woocommerce_ability_extensions_init.
	 *
	 * @internal
	 */
	public function register_test_fields(): void {
		AbilityExtensions::register_field(
			array(
				'resource'     => 'product',
				'namespace'    => 'test-ext',
				'field'        => 'code',
				'schema'       => self::CODE_SCHEMA,
				'get_callback' => static function ( \WC_Product $product ) {
					$code = $product->get_meta( '_test_code' );
					return '' === $code ? null : $code;
				},
			)
		);
		AbilityExtensions::register_field(
			array(
				'resource'     => 'product',
				'namespace'    => 'other_ext',
				'field'        => 'code',
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static function ( \WC_Product $product ) {
					return '' === $product->get_meta( '_test_code' ) ? null : 7;
				},
			)
		);
		AbilityExtensions::register_field(
			array(
				'resource'     => 'order',
				'namespace'    => 'test-ext',
				'field'        => 'note',
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function ( \WC_Order $order ) {
					return 'order-' . $order->get_id();
				},
			)
		);
		AbilityExtensions::register_field(
			array(
				'resource'     => 'order_item',
				'namespace'    => 'test-ext',
				'field'        => 'gift',
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function ( \WC_Order_Item $item ) {
					return 'item-' . $item->get_id();
				},
			)
		);
	}

	/**
	 * Register a product filter and an order filter, the way an extension does inside woocommerce_ability_extensions_init.
	 *
	 * @internal
	 */
	public function register_test_filters(): void {
		AbilityExtensions::register_query_filter(
			array(
				'resource'  => 'product',
				'namespace' => 'test-ext',
				'filter'    => 'has_code',
				'schema'    => array( 'type' => 'boolean' ),
				'callback'  => static function ( bool $has_code ) {
					return array(
						'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							array(
								'key'     => '_test_code',
								'compare' => $has_code ? 'EXISTS' : 'NOT EXISTS',
							),
						),
					);
				},
			)
		);
		AbilityExtensions::register_query_filter(
			array(
				'resource'  => 'product',
				'namespace' => 'test-ext',
				'filter'    => 'category',
				'schema'    => array( 'type' => 'string' ),
				'callback'  => static function ( string $slug ) {
					return array(
						'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							array(
								'taxonomy' => 'product_cat',
								'field'    => 'slug',
								'terms'    => $slug,
							),
						),
					);
				},
			)
		);
		AbilityExtensions::register_query_filter(
			array(
				'resource'  => 'order',
				'namespace' => 'test-ext',
				'filter'    => 'tag',
				'schema'    => array( 'type' => 'string' ),
				'callback'  => static function ( string $tag ) {
					if ( 'bad' === $tag ) {
						return new \WP_Error( 'test_bad_tag', 'Bad tag.', array( 'status' => 400 ) );
					}
					return array(
						'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							array(
								'key'   => '_test_tag',
								'value' => $tag,
							),
						),
					);
				},
			)
		);
	}

	/**
	 * @testdox Should return only the products that match an extension filter sent as a query string, with correct pagination.
	 */
	public function test_product_filter_returns_matching_products(): void {
		$with_code = array();
		foreach ( array( 'A1', 'A2', 'A3' ) as $code ) {
			$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen ' . $code ) );
			$product->update_meta_data( '_test_code', $code );
			$product->save();
			$with_code[] = $product->get_id();
		}
		$without_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen plain' ) );
		$filters      = array( 'extensions' => array( 'test-ext' => array( 'has_code' => 'true' ) ) );

		$page_one = $this->run_ability(
			'woocommerce/products-query',
			array(
				'search'   => 'Pen',
				'per_page' => 2,
				'filters'  => $filters,
			)
		);
		$page_two = $this->run_ability(
			'woocommerce/products-query',
			array(
				'search'   => 'Pen',
				'per_page' => 2,
				'page'     => 2,
				'filters'  => $filters,
			)
		);
		$plain    = $this->run_ability(
			'woocommerce/products-query',
			array(
				'search'  => 'Pen',
				'filters' => array( 'extensions' => array( 'test-ext' => array( 'has_code' => 'false' ) ) ),
			)
		);

		$this->assertSame( 2, $page_one['total_pages'] );
		$this->assertEqualsCanonicalizing( $with_code, array_merge( array_column( $page_one['products'], 'id' ), array_column( $page_two['products'], 'id' ) ) );
		$this->assertSame( array( $without_code->get_id() ), array_column( $plain['products'], 'id' ) );
	}

	/**
	 * @testdox Should add a tax_query filter to Core's own product type clauses with AND.
	 */
	public function test_product_tax_filter_adds_to_core_clauses(): void {
		$category = wp_insert_term( 'Gifts', 'product_cat' )['term_id'];
		$simple   = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen simple' ) );
		$external = \WC_Helper_Product::create_external_product();
		foreach ( array( $simple, $external ) as $product ) {
			$product->set_category_ids( array( $category ) );
			$product->save();
		}
		\WC_Helper_Product::create_external_product();

		$output = $this->run_ability(
			'woocommerce/products-query',
			array(
				'product_type_alias' => 'affiliate',
				'filters'            => array( 'extensions' => array( 'test-ext' => array( 'category' => 'gifts' ) ) ),
			)
		);

		$this->assertSame( array( $external->get_id() ), array_column( $output['products'], 'id' ) );
	}

	/**
	 * @testdox Should not filter a product query that a hook runs inside the ability's query.
	 */
	public function test_product_filter_does_not_reach_a_nested_query(): void {
		$with_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen coded' ) );
		$with_code->update_meta_data( '_test_code', 'A1' );
		$with_code->save();
		$without_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen plain' ) );
		$nested       = null;
		$run_nested   = static function ( $wp_query_args ) use ( &$nested ) {
			if ( null === $nested ) {
				$nested = array();
				$nested = wc_get_products(
					array(
						'limit'  => -1,
						'return' => 'ids',
					)
				);
			}
			return $wp_query_args;
		};
		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', $run_nested, 5 );

		$output = $this->run_ability(
			'woocommerce/products-query',
			array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'has_code' => 'true' ) ) ) )
		);

		remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', $run_nested, 5 );
		$this->assertSame( array( $with_code->get_id() ), array_column( $output['products'], 'id' ) );
		$this->assertContains( $without_code->get_id(), $nested );
	}

	/**
	 * @testdox Should return only the orders that match an extension filter, with HPOS and with posts storage, with and without sync.
	 * @testWith [true, false]
	 *           [true, true]
	 *           [false, false]
	 *           [false, true]
	 *
	 * @param bool $hpos Whether HPOS is on.
	 * @param bool $sync Whether HPOS and posts storage sync.
	 */
	public function test_order_filter_returns_matching_orders( bool $hpos, bool $sync ): void {
		$original = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->setup_cot();
		$this->toggle_cot_feature_and_usage( $hpos );
		if ( $sync ) {
			$this->enable_cot_sync();
		} else {
			$this->disable_cot_sync();
		}

		try {
			$tagged = array();
			foreach ( array( 'gift', 'gift', 'plain' ) as $tag ) {
				$order = \WC_Helper_Order::create_order();
				$order->update_meta_data( '_test_tag', $tag );
				$order->save();
				if ( 'gift' === $tag ) {
					$tagged[] = $order->get_id();
				}
			}

			$output = $this->run_ability(
				'woocommerce/orders-query',
				array(
					'per_page' => 1,
					'filters'  => array( 'extensions' => array( 'test-ext' => array( 'tag' => 'gift' ) ) ),
				)
			);

			$this->assertSame( $hpos, OrderUtil::custom_orders_table_usage_is_enabled() );
			$this->assertSame( $sync, OrderUtil::is_custom_order_tables_in_sync() );
			$this->assertSame( 2, $output['total_pages'] );
			$this->assertContains( $output['orders'][0]['id'], $tagged );
		} finally {
			$this->clean_up_cot_setup();
			remove_all_filters( 'pre_option_' . DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION );
			$this->toggle_cot_feature_and_usage( $original );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * @testdox Should keep the orders whose IDs every filter returns, from data that is not meta, with HPOS and with posts storage.
	 * @testWith [true, false]
	 *           [true, true]
	 *           [false, false]
	 *           [false, true]
	 *
	 * @param bool $hpos Whether HPOS is on.
	 * @param bool $sync Whether HPOS and posts storage sync.
	 */
	public function test_order_ids_filter_intersects_with_other_filters( bool $hpos, bool $sync ): void {
		$original = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->setup_cot();
		$this->toggle_cot_feature_and_usage( $hpos );
		if ( $sync ) {
			$this->enable_cot_sync();
		} else {
			$this->disable_cot_sync();
		}
		add_action( 'woocommerce_ability_extensions_init', array( $this, 'register_contains_product_filter' ) );

		try {
			$coffee = \WC_Helper_Product::create_simple_product();
			$orders = array();
			foreach ( array( 'gift', 'plain' ) as $tag ) {
				$order = \WC_Helper_Order::create_order( 0, $coffee );
				$order->update_meta_data( '_test_tag', $tag );
				$order->save();
				$orders[ $tag ] = $order->get_id();
			}
			$other = \WC_Helper_Order::create_order();
			$other->save();
			$filters = static fn( array $values ) => array( 'filters' => array( 'extensions' => array( 'test-ext' => $values ) ) );

			$contains = $this->run_ability( 'woocommerce/orders-query', $filters( array( 'contains_product' => $coffee->get_id() ) ) );
			$both     = $this->run_ability(
				'woocommerce/orders-query',
				$filters(
					array(
						'contains_product' => $coffee->get_id(),
						'tag'              => 'gift',
					)
				)
			);
			$none     = $this->run_ability( 'woocommerce/orders-query', $filters( array( 'contains_product' => 999999 ) ) );
			$without  = $this->run_ability( 'woocommerce/orders-query', $filters( array( 'without' => array( $orders['plain'] ) ) ) );

			$this->assertEqualsCanonicalizing( array_values( $orders ), array_column( $contains['orders'], 'id' ) );
			$this->assertSame( array( $orders['gift'] ), array_column( $both['orders'], 'id' ) );
			$this->assertSame( array(), $none['orders'] );
			$this->assertEqualsCanonicalizing( array( $orders['gift'], $other->get_id() ), array_column( $without['orders'], 'id' ) );
		} finally {
			remove_action( 'woocommerce_ability_extensions_init', array( $this, 'register_contains_product_filter' ) );
			$this->clean_up_cot_setup();
			remove_all_filters( 'pre_option_' . DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION );
			$this->toggle_cot_feature_and_usage( $original );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * Register an order filter that reads order items, which are not meta, and returns order IDs,
	 * and one that excludes the order IDs it receives.
	 *
	 * @internal
	 */
	public function register_contains_product_filter(): void {
		AbilityExtensions::register_query_filter(
			array(
				'resource'  => 'order',
				'namespace' => 'test-ext',
				'filter'    => 'without',
				'schema'    => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
				'callback'  => static fn( array $ids ) => array( 'exclude_ids' => $ids ),
			)
		);
		AbilityExtensions::register_query_filter(
			array(
				'resource'  => 'order',
				'namespace' => 'test-ext',
				'filter'    => 'contains_product',
				'schema'    => array( 'type' => 'integer' ),
				'callback'  => static function ( int $product_id ) {
					global $wpdb;
					return array(
						'include_ids' => $wpdb->get_col(
							$wpdb->prepare(
								"SELECT DISTINCT items.order_id FROM {$wpdb->prefix}woocommerce_order_items AS items INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta ON meta.order_item_id = items.order_item_id WHERE meta.meta_key = '_product_id' AND meta.meta_value = %d",
								$product_id
							)
						),
					);
				},
			)
		);
	}

	/**
	 * @testdox Should keep the products whose IDs a filter includes, drop the IDs a filter excludes, and refuse IDs that are not integers.
	 */
	public function test_product_ids_filters_include_and_exclude(): void {
		$coded = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen coded' ) );
		$coded->update_meta_data( '_test_code', 'A1' );
		$coded->save();
		$plain = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen plain' ) );
		add_action(
			'woocommerce_ability_extensions_init',
			static function () use ( $plain ) {
				$callbacks = array(
					'picked'    => static fn( array $ids ) => array( 'include_ids' => $ids ),
					'not_plain' => static fn() => array( 'exclude_ids' => array( $plain->get_id() ) ),
					'not_ids'   => static fn() => array( 'include_ids' => array( 'x' ) ),
				);
				foreach ( $callbacks as $filter => $callback ) {
					AbilityExtensions::register_query_filter(
						array(
							'resource'  => 'product',
							'namespace' => 'test-ext',
							'filter'    => $filter,
							'schema'    => 'picked' === $filter ? array(
								'type'  => 'array',
								'items' => array( 'type' => 'integer' ),
							) : array( 'type' => 'boolean' ),
							'callback'  => $callback,
						)
					);
				}
			}
		);
		$filters = static fn( array $values ) => array(
			'search'  => 'Pen',
			'filters' => array( 'extensions' => array( 'test-ext' => $values ) ),
		);

		$picked   = $this->run_ability( 'woocommerce/products-query', $filters( array( 'picked' => array( $coded->get_id(), $plain->get_id() ) ) ) );
		$both     = $this->run_ability(
			'woocommerce/products-query',
			$filters(
				array(
					'picked'   => array( $coded->get_id(), $plain->get_id() ),
					'has_code' => true,
				)
			)
		);
		$empty    = $this->run_ability( 'woocommerce/products-query', $filters( array( 'picked' => array() ) ) );
		$excluded = $this->run_ability( 'woocommerce/products-query', $filters( array( 'not_plain' => true ) ) );
		$narrowed = $this->run_ability(
			'woocommerce/products-query',
			$filters(
				array(
					'not_plain' => true,
					'picked'    => array( $coded->get_id(), $plain->get_id() ),
				)
			)
		);
		$nothing  = $this->run_ability(
			'woocommerce/products-query',
			$filters(
				array(
					'not_plain' => true,
					'picked'    => array( $plain->get_id() ),
				)
			)
		);
		$not_ids  = $this->dispatch_ability( 'woocommerce/products-query', $filters( array( 'not_ids' => true ) ) );

		$this->assertEqualsCanonicalizing( array( $coded->get_id(), $plain->get_id() ), array_column( $picked['products'], 'id' ) );
		$this->assertSame( array( $coded->get_id() ), array_column( $both['products'], 'id' ) );
		$this->assertSame( array(), $empty['products'] );
		$this->assertSame( array( $coded->get_id() ), array_column( $excluded['products'], 'id' ) );
		$this->assertSame( array( $coded->get_id() ), array_column( $narrowed['products'], 'id' ) );
		$this->assertSame( array(), $nothing['products'] );
		$this->assertSame( 'woocommerce_ability_extension_filter_failed', $not_ids->get_data()['code'] );
	}

	/**
	 * @testdox Should list the filters in the input schemas, and refuse a filter that is not registered.
	 */
	public function test_input_schemas_list_filters_and_refuse_unknown_ones(): void {
		$products = $this->get_input_schema( 'woocommerce/products-query' )['properties']['filters']['properties']['extensions'];
		$orders   = $this->get_input_schema( 'woocommerce/orders-query' )['properties']['filters']['properties']['extensions'];

		$response = $this->dispatch_ability(
			'woocommerce/orders-query',
			array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'unknown' => 'x' ) ) ) )
		);

		$this->assertSame( array( 'type' => 'boolean' ), $products['properties']['test-ext']['properties']['has_code'] );
		$this->assertSame( array( 'type' => 'string' ), $orders['properties']['test-ext']['properties']['tag'] );
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * @testdox Should return the error of a filter that refuses its value, or that returns arguments Core does not support.
	 */
	public function test_a_filter_that_refuses_or_fails_returns_an_error(): void {
		add_action(
			'woocommerce_ability_extensions_init',
			static function () {
				$callbacks = array(
					array( 'order', 'broken', static fn() => array( 'post__in' => array( 1 ) ) ),
					array( 'order', 'taxed', static fn() => array( 'tax_query' => array( array( 'taxonomy' => 'product_cat' ) ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array( 'product', 'scalar', static fn() => array( 'meta_query' => 'x' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				);
				foreach ( $callbacks as list( $resource, $filter, $callback ) ) {
					AbilityExtensions::register_query_filter(
						array(
							'resource'  => $resource,
							'namespace' => 'test-ext',
							'filter'    => $filter,
							'schema'    => array( 'type' => 'boolean' ),
							'callback'  => $callback,
						)
					);
				}
			}
		);

		$refused = $this->dispatch_ability( 'woocommerce/orders-query', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'tag' => 'bad' ) ) ) ) );
		$failed  = $this->dispatch_ability( 'woocommerce/orders-query', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'broken' => true ) ) ) ) );
		$taxed   = $this->dispatch_ability( 'woocommerce/orders-query', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'taxed' => true ) ) ) ) );
		$scalar  = $this->dispatch_ability( 'woocommerce/products-query', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'scalar' => true ) ) ) ) );

		$this->assertSame( 'test_bad_tag', $refused->get_data()['code'] );
		$this->assertSame( 'woocommerce_ability_extension_filter_failed', $failed->get_data()['code'] );
		$this->assertSame( 'woocommerce_ability_extension_filter_failed', $taxed->get_data()['code'] );
		$this->assertSame( 'woocommerce_ability_extension_filter_failed', $scalar->get_data()['code'] );
	}

	/**
	 * @testdox Should accept a filter that returns an empty clause, and not filter the query.
	 */
	public function test_a_filter_that_returns_an_empty_clause_does_not_filter(): void {
		add_action(
			'woocommerce_ability_extensions_init',
			static function () {
				AbilityExtensions::register_query_filter(
					array(
						'resource'  => 'product',
						'namespace' => 'test-ext',
						'filter'    => 'empty_clause',
						'schema'    => array( 'type' => 'boolean' ),
						'callback'  => static fn() => array( 'meta_query' => array() ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					)
				);
			}
		);
		$this->get_input_schema( 'woocommerce/products-query' );

		$this->assertSame( array(), AbilityExtensions::get_query_args( 'product', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'empty_clause' => true ) ) ) ) ) );
	}

	/**
	 * @testdox Should refuse a filter on a resource that is not registered, and accept one on a registered resource.
	 */
	public function test_a_filter_on_an_unsupported_resource_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityExtensions::class . '::register_query_filter' );
		$input  = array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'gift' => true ) ) ) );
		$filter = array(
			'resource'  => 'ticket',
			'namespace' => 'test-ext',
			'filter'    => 'gift',
			'schema'    => array( 'type' => 'boolean' ),
			'callback'  => static fn() => array( 'meta_query' => array( array( 'key' => 'gift' ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		);

		AbilityExtensions::register_query_filter( $filter );
		$this->assertSame( array(), AbilityExtensions::get_query_args( 'ticket', $input ) );

		$register_resource = static function () {
			AbilityExtensions::register_resource( 'ticket' );
		};
		$register          = static function () use ( $filter ) {
			AbilityExtensions::register_query_filter( $filter );
		};
		add_action( 'woocommerce_ability_resources_init', $register_resource );
		add_action( 'woocommerce_ability_extensions_init', $register );
		$this->get_input_schema( 'woocommerce/products-query' );
		remove_action( 'woocommerce_ability_resources_init', $register_resource );
		remove_action( 'woocommerce_ability_extensions_init', $register );

		$this->assertSame( array( 'meta_query' => array( array( array( 'key' => 'gift' ) ) ) ), AbilityExtensions::get_query_args( 'ticket', $input ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}

	/**
	 * @testdox Should refuse a filter that is registered after the init action.
	 */
	public function test_a_filter_registered_after_the_init_action_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityExtensions::class . '::register_query_filter' );
		$this->get_input_schema( 'woocommerce/products-query' );

		AbilityExtensions::register_query_filter(
			array(
				'resource'  => 'product',
				'namespace' => 'late-ext',
				'filter'    => 'gift',
				'schema'    => array( 'type' => 'boolean' ),
				'callback'  => static fn() => array( 'meta_query' => array( array( 'key' => 'gift' ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		$this->assertSame( array(), AbilityExtensions::get_query_args( 'product', array( 'filters' => array( 'extensions' => array( 'late-ext' => array( 'gift' => true ) ) ) ) ) );
	}

	/**
	 * @testdox Should refuse an id sent together with filters.
	 */
	public function test_an_id_with_filters_is_refused(): void {
		$input = array(
			'id'      => 1,
			'filters' => array( 'extensions' => array( 'test-ext' => array( 'has_code' => true ) ) ),
		);

		$products = $this->dispatch_ability( 'woocommerce/products-query', $input );
		$orders   = $this->dispatch_ability( 'woocommerce/orders-query', array( 'id' => 1 ) + array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'tag' => 'gift' ) ) ) ) );

		$this->assertSame( 400, $products->get_status() );
		$this->assertSame( 'woocommerce_ability_filters_with_id', $products->get_data()['code'] );
		$this->assertSame( 400, $orders->get_status() );
		$this->assertSame( 'woocommerce_ability_filters_with_id', $orders->get_data()['code'] );
	}

	/**
	 * @testdox Should return the fields of each extension under its namespace, and leave out a product with no values.
	 */
	public function test_product_reads_return_fields_under_their_namespace(): void {
		$with_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen with code' ) );
		$with_code->update_meta_data( '_test_code', 'A1' );
		$with_code->save();
		$without_code = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen without code' ) );
		$expected     = array(
			'test-ext'  => array( 'code' => 'A1' ),
			'other_ext' => array( 'code' => 7 ),
		);

		$products = array_column( $this->run_ability( 'woocommerce/products-query', array( 'search' => 'Pen' ) )['products'], null, 'id' );
		$updated  = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'   => $with_code->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assertSame( $expected, $products[ $with_code->get_id() ]['extensions'] );
		$this->assertArrayNotHasKey( 'extensions', $products[ $without_code->get_id() ] );
		$this->assertSame( $expected, $updated['product']['extensions'] );
	}

	/**
	 * @testdox Should return order and line item fields under their namespace.
	 */
	public function test_order_reads_return_fields_under_their_namespace(): void {
		$order = \WC_Helper_Order::create_order();
		$item  = current( $order->get_items() );

		$listed = $this->run_ability(
			'woocommerce/orders-query',
			array(
				'per_page'           => 1,
				'include_line_items' => true,
			)
		)['orders'][0];
		$noted  = $this->run_ability(
			'woocommerce/order-add-note',
			array(
				'id'   => $order->get_id(),
				'note' => 'Hi',
			)
		);

		$this->assertSame( array( 'test-ext' => array( 'note' => 'order-' . $order->get_id() ) ), $listed['extensions'] );
		$this->assertSame( array( 'test-ext' => array( 'gift' => 'item-' . $item->get_id() ) ), $listed['line_items'][0]['extensions'] );
		$this->assertSame( array( 'test-ext' => array( 'note' => 'order-' . $order->get_id() ) ), $noted['order']['extensions'] );
	}

	/**
	 * @testdox Should list the fields of each namespace in the output schemas, and add nothing for a resource with no fields.
	 */
	public function test_output_schemas_list_fields_by_namespace(): void {
		$products = $this->get_output_schema( 'woocommerce/products-query' )['properties']['products']['items']['properties']['extensions'];
		$product  = $this->get_output_schema( 'woocommerce/product-update' )['properties']['product']['properties']['extensions'];
		$order    = $this->get_output_schema( 'woocommerce/orders-query' )['properties']['orders']['items']['properties'];

		$this->assertSame( self::CODE_SCHEMA, $products['properties']['test-ext']['properties']['code'] );
		$this->assertSame( array( 'type' => 'integer' ), $products['properties']['other_ext']['properties']['code'] );
		$this->assertSame( self::CODE_SCHEMA, $product['properties']['test-ext']['properties']['code'] );
		$this->assertSame( array( 'type' => 'string' ), $order['extensions']['properties']['test-ext']['properties']['note'] );
		$this->assertSame( array( 'type' => 'string' ), $order['line_items']['items']['properties']['extensions']['properties']['test-ext']['properties']['gift'] );
		$this->assertSame( array( 'type' => 'object' ), AbilityExtensions::add_fields_schema( array( 'type' => 'object' ), 'customer' ) );
	}

	/**
	 * @testdox Should fire woocommerce_ability_extensions_init one time, before the abilities build their schemas.
	 */
	public function test_init_action_fires_once_before_the_schemas(): void {
		$events     = array();
		$on_init    = static function () use ( &$events ) {
			$events[] = 'extensions';
		};
		$on_ability = static function ( $args, $name ) use ( &$events ) {
			$events[] = $name;
			return $args;
		};
		add_action( 'woocommerce_ability_extensions_init', $on_init );
		add_filter( 'wp_register_ability_args', $on_ability, 10, 2 );

		$this->get_output_schema( 'woocommerce/products-query' );
		do_action( 'wp_abilities_api_init' );

		remove_action( 'woocommerce_ability_extensions_init', $on_init );
		remove_filter( 'wp_register_ability_args', $on_ability, 10 );
		$this->assertSame( 1, did_action( 'woocommerce_ability_extensions_init' ) );
		$this->assertSame( 'extensions', $events[0] );
		$this->assertSame( 1, count( array_keys( $events, 'extensions', true ) ) );
		$this->assertContains( 'woocommerce/products-query', $events );
	}

	/**
	 * @testdox Should refuse a field that is registered after the init action, and keep the abilities valid.
	 */
	public function test_a_field_registered_after_the_init_action_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityExtensions::class . '::register_field' );
		$this->get_output_schema( 'woocommerce/products-query' );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen late' ) );

		AbilityExtensions::register_field(
			array(
				'resource'     => 'product',
				'namespace'    => 'late-ext',
				'field'        => 'code',
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => 'late',
			)
		);
		$products = array_column( $this->run_ability( 'woocommerce/products-query', array( 'search' => 'Pen late' ) )['products'], null, 'id' );

		$this->assertArrayNotHasKey( 'late-ext', $products[ $product->get_id() ]['extensions'] ?? array() );
		$this->assertArrayNotHasKey( 'late-ext', AbilityExtensions::add_fields_to_object( array(), 'product', $product )['extensions'] ?? array() );
	}

	/**
	 * @testdox Should let an extension register fields on a resource that another extension registers, whatever the hook priorities.
	 */
	public function test_register_resource_lets_an_extension_register_fields_on_its_own_resource(): void {
		$register_resource = static function () {
			AbilityExtensions::register_resource( 'ticket' );
		};
		$register_field    = static function () {
			AbilityExtensions::register_field(
				array(
					'resource'     => 'ticket',
					'namespace'    => 'test-ext',
					'field'        => 'code',
					'schema'       => array( 'type' => 'string' ),
					'get_callback' => static fn() => 'T1',
				)
			);
		};
		add_action( 'woocommerce_ability_resources_init', $register_resource, 99 );
		add_action( 'woocommerce_ability_extensions_init', $register_field, 1 );
		$this->get_output_schema( 'woocommerce/products-query' );
		remove_action( 'woocommerce_ability_resources_init', $register_resource, 99 );
		remove_action( 'woocommerce_ability_extensions_init', $register_field, 1 );

		$output = AbilityExtensions::add_fields_to_object( array(), 'ticket', new \stdClass() );
		$schema = AbilityExtensions::add_fields_schema( array( 'type' => 'object' ), 'ticket' );

		$this->assertSame( array( 'test-ext' => array( 'code' => 'T1' ) ), $output['extensions'] );
		$this->assertSame( array( 'type' => 'string' ), $schema['properties']['extensions']['properties']['test-ext']['properties']['code'] );
	}

	/**
	 * @testdox Should refuse a resource that is registered after the resources action.
	 */
	public function test_a_resource_registered_after_the_resources_action_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityExtensions::class . '::register_resource' );
		$this->get_output_schema( 'woocommerce/products-query' );

		AbilityExtensions::register_resource( 'late_resource' );

		$resources = new \ReflectionProperty( AbilityExtensions::class, 'resources' );
		$resources->setAccessible( true );
		$this->assertSame( array( 'product', 'order', 'order_item' ), array_keys( $resources->getValue() ) );
	}

	/**
	 * @testdox Should not fire woocommerce_ability_extensions_init or change the abilities with the feature off.
	 */
	public function test_nothing_changes_with_the_feature_off(): void {
		$this->set_feature( false );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$product->update_meta_data( '_test_code', 'A1' );
		$product->save();
		\WC_Helper_Order::create_order();

		$products = $this->run_ability( 'woocommerce/products-query', array( 'search' => 'Pen' ) )['products'];
		$orders   = $this->run_ability(
			'woocommerce/orders-query',
			array(
				'per_page'           => 1,
				'include_line_items' => true,
			)
		)['orders'];

		$this->assertSame( 0, did_action( 'woocommerce_ability_resources_init' ) );
		$this->assertSame( 0, did_action( 'woocommerce_ability_extensions_init' ) );
		$this->assertArrayNotHasKey( 'filters', $this->get_input_schema( 'woocommerce/products-query' )['properties'] );
		$this->assertSame( 400, $this->dispatch_ability( 'woocommerce/products-query', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'has_code' => true ) ) ) ) )->get_status() );
		$this->assertArrayNotHasKey( 'extensions', $this->get_output_schema( 'woocommerce/products-query' )['properties']['products']['items']['properties'] );
		$this->assertArrayNotHasKey( 'extensions', $products[0] );
		$this->assertArrayNotHasKey( 'extensions', $orders[0] );
		$this->assertArrayNotHasKey( 'extensions', $orders[0]['line_items'][0] );
	}

	/**
	 * @testdox Should let an ability that is not Core's return the registered fields.
	 */
	public function test_an_ability_that_is_not_cores_returns_the_fields(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_test_code', 'X9' );
		$product->save();
		$register = static function () {
			wp_register_ability(
				'test-ext/product-read',
				array(
					'label'               => 'Read product',
					'description'         => 'Reads a product.',
					'category'            => 'woocommerce',
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array( 'id' => array( 'type' => 'integer' ) ),
					),
					'output_schema'       => AbilityExtensions::add_fields_schema(
						array(
							'type'       => 'object',
							'properties' => array( 'id' => array( 'type' => 'integer' ) ),
						),
						'product'
					),
					'execute_callback'    => static function ( array $input ) {
						$product = wc_get_product( $input['id'] );
						return AbilityExtensions::add_fields_to_object( array( 'id' => $product->get_id() ), 'product', $product );
					},
					'permission_callback' => '__return_true',
					'meta'                => array(
						'show_in_rest' => true,
						'annotations'  => array( 'readonly' => true ),
					),
				)
			);
		};
		add_action( 'wp_abilities_api_init', $register, 20 );

		$output = $this->run_ability( 'test-ext/product-read', array( 'id' => $product->get_id() ) );

		remove_action( 'wp_abilities_api_init', $register, 20 );
		$this->assertSame( array( 'code' => 'X9' ), $output['extensions']['test-ext'] );
	}

	/**
	 * @testdox Should refuse a field with invalid arguments, and keep the first registration of a namespace and field.
	 */
	public function test_invalid_and_duplicate_fields_are_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityExtensions::class . '::register_field' );
		$valid = array(
			'resource'     => 'order',
			'namespace'    => 'test-ext',
			'field'        => 'note',
			'schema'       => array( 'type' => 'string' ),
			'get_callback' => static fn() => 'second',
		);
		AbilitiesLoader::register_core_resources();
		$this->register_test_fields();

		AbilityExtensions::register_field( $valid );
		AbilityExtensions::register_field( array_merge( $valid, array( 'namespace' => 'Test Ext' ) ) );
		AbilityExtensions::register_field( array_merge( $valid, array( 'field' => '' ) ) );
		AbilityExtensions::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'schema' => null ) ) );
		AbilityExtensions::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'get_callback' => 'not_a_function' ) ) );
		AbilityExtensions::register_field(
			array_merge(
				$valid,
				array(
					'field'    => 'other',
					'resource' => 'line_item',
				)
			)
		);
		AbilityExtensions::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'schema' => array( 'description' => 'x' ) ) ) );
		$order = \WC_Helper_Order::create_order();

		$this->assertSame(
			array( 'test-ext' => array( 'note' => 'order-' . $order->get_id() ) ),
			AbilityExtensions::add_fields_to_object( array(), 'order', $order )['extensions']
		);
	}

	/**
	 * @testdox Should leave out a field that throws or fails sanitization, cast a value to its schema type, and keep a null that the schema allows.
	 */
	public function test_field_values_follow_their_schema(): void {
		AbilitiesLoader::register_core_resources();
		$this->register_test_fields();
		$fields = array(
			'broken' => array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function () {
					throw new \RuntimeException( 'Broken' );
				},
			),
			'count'  => array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static fn() => '12',
			),
			'empty'  => array(
				'schema'       => array( 'type' => array( 'string', 'null' ) ),
				'get_callback' => '__return_null',
			),
			'dupes'  => array(
				'schema'       => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'integer' ),
					'uniqueItems' => true,
				),
				'get_callback' => static fn() => array( '12', 12 ),
			),
		);
		foreach ( $fields as $field => $args ) {
			AbilityExtensions::register_field(
				array(
					'resource'  => 'order',
					'namespace' => 'values',
					'field'     => $field,
				) + $args
			);
		}
		$order = \WC_Helper_Order::create_order();

		$output = AbilityExtensions::add_fields_to_object( array(), 'order', $order );

		$this->assertSame(
			array(
				'count' => 12,
				'empty' => null,
			),
			$output['extensions']['values']
		);
	}

	/**
	 * @testdox Should leave out and report one time a value that does not match its schema, and keep the output valid.
	 */
	public function test_a_value_that_does_not_match_its_schema_is_left_out(): void {
		$this->setExpectedIncorrectUsage( AbilityExtensions::class . '::register_field' );
		add_action(
			'woocommerce_ability_extensions_init',
			static function () {
				AbilityExtensions::register_field(
					array(
						'resource'     => 'product',
						'namespace'    => 'test-ext',
						'field'        => 'count',
						'schema'       => array( 'type' => 'integer' ),
						'get_callback' => static fn() => 'many',
					)
				);
			}
		);
		$reported = array();
		add_action(
			'doing_it_wrong_run',
			static function ( $function_name, $message ) use ( &$reported ) {
				$reported[] = $message;
			},
			10,
			2
		);
		$first = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen one' ) );
		$first->update_meta_data( '_test_code', 'A1' );
		$first->save();
		\WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen two' ) );

		$products = array_column( $this->run_ability( 'woocommerce/products-query', array( 'search' => 'Pen' ) )['products'], null, 'id' );

		$this->assertSame( array( 'code' => 'A1' ), $products[ $first->get_id() ]['extensions']['test-ext'] );
		$matches = array_filter(
			$reported,
			static fn( $message ) => false !== strpos( $message, '"test-ext.count" of "product"' )
		);
		$this->assertCount( 1, $matches );
	}

	/**
	 * Run an ability through the REST route and return its output.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Ability input.
	 * @return array
	 */
	private function run_ability( string $name, array $input ): array {
		$response = $this->dispatch_ability( $name, $input );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		return $response->get_data();
	}

	/**
	 * Run an ability through the REST route and return the response.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Ability input.
	 * @return \WP_REST_Response
	 */
	private function dispatch_ability( string $name, array $input ): \WP_REST_Response {
		$readonly = ! empty( wp_get_ability( $name )->get_meta()['annotations']['readonly'] );
		$request  = new \WP_REST_Request( $readonly ? 'GET' : 'POST', '/wp-abilities/v1/abilities/' . $name . '/run' );
		if ( $readonly ) {
			$request->set_query_params( array( 'input' => $input ) );
		} else {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( array( 'input' => $input ) ) );
		}

		return $this->server->dispatch( $request );
	}

	/**
	 * Read an ability's output schema through the REST route.
	 *
	 * @param string $name Ability name.
	 * @return array
	 */
	private function get_output_schema( string $name ): array {
		return $this->get_ability_data( $name )['output_schema'];
	}

	/**
	 * Read an ability's input schema through the REST route.
	 *
	 * @param string $name Ability name.
	 * @return array
	 */
	private function get_input_schema( string $name ): array {
		return $this->get_ability_data( $name )['input_schema'];
	}

	/**
	 * Read an ability through the REST route.
	 *
	 * @param string $name Ability name.
	 * @return array
	 */
	private function get_ability_data( string $name ): array {
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/' . $name ) );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		return $response->get_data();
	}

	/**
	 * Turn the feature on or off.
	 *
	 * @param bool $enabled Whether the feature is on.
	 */
	private function set_feature( bool $enabled ): void {
		update_option( 'woocommerce_feature_' . AbilityExtensions::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );
	}

	/**
	 * Drop every registered field and ability, so the next read fires the init actions again.
	 */
	private function reset_registries(): void {
		global $wp_actions;

		foreach ( array( 'fields', 'reported', 'filters' ) as $property ) {
			$reflection = new \ReflectionProperty( AbilityExtensions::class, $property );
			$reflection->setAccessible( true );
			$reflection->setValue( null, array() );
		}

		$reflection = new \ReflectionProperty( AbilityExtensions::class, 'resources' );
		$reflection->setAccessible( true );
		$reflection->setValue( null, array() );

		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'], $wp_actions['woocommerce_ability_resources_init'], $wp_actions['woocommerce_ability_extensions_init'] );
	}
}
