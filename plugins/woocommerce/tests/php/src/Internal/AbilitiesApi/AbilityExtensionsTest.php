<?php
/**
 * AbilityExtensionsTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\AbilityExtensions;
use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Namespaced extension fields in the product and order abilities, read and written through the REST route.
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
	 * Values that the update callbacks received, keyed by field.
	 *
	 * @var array<string, mixed>
	 */
	private static $received = array();

	/**
	 * Original init action count restored in tearDown.
	 *
	 * @var int|null
	 */
	private $original_init_count;

	/**
	 * Actions that a test added, removed in tearDown.
	 *
	 * @var array<int, array{0: string, 1: callable, 2: int}>
	 */
	private $test_actions = array();

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
		self::$received = array();
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
		foreach ( $this->test_actions as list( $hook, $callback, $priority ) ) {
			remove_action( $hook, $callback, $priority );
		}
		$this->test_actions = array();
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
		$writable = array(
			array( 'product', 'test-ext', 'color', array( 'type' => 'string' ) ),
			array( 'product', 'other_ext', 'count', array( 'type' => 'integer' ) ),
			array(
				'product',
				'other_ext',
				'size',
				array(
					'type' => 'string',
					'enum' => array( 'small', 'large' ),
				),
			),
			array( 'order', 'test-ext', 'tag', array( 'type' => 'string' ) ),
		);
		foreach ( $writable as list( $resource, $namespace, $field, $schema ) ) {
			AbilityExtensions::register_field(
				array(
					'resource'        => $resource,
					'namespace'       => $namespace,
					'field'           => $field,
					'schema'          => $schema,
					'get_callback'    => static function ( \WC_Data $data ) use ( $field ) {
						return $data->meta_exists( '_test_' . $field ) ? $data->get_meta( '_test_' . $field ) : null;
					},
					'update_callback' => static function ( \WC_Data $data, $value ) use ( $field ) {
						self::$received[ $field ] = $value;
						if ( 'reject' === $value || -1 === $value ) {
							return new \WP_Error( 'test_rejected', 'Rejected.' );
						}
						if ( null === $value ) {
							$data->delete_meta_data( '_test_' . $field );
							return null;
						}
						$data->update_meta_data( '_test_' . $field, $value );
						return null;
					},
				)
			);
		}
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
	 * @testdox Should save a change to Core fields and the fields of two extensions one time, with each value cast to its schema.
	 */
	public function test_a_write_saves_core_and_extension_fields_one_time(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$saves   = did_action( 'woocommerce_update_product' );

		$output = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array(
					'test-ext'  => array( 'color' => 'red' ),
					'other_ext' => array( 'count' => '12' ),
				),
			)
		);

		$saved = wc_get_product( $product->get_id() );
		$this->assertSame( 1, did_action( 'woocommerce_update_product' ) - $saves );
		$this->assertSame( 12, self::$received['count'] );
		$this->assertSame( 'Pencil', $saved->get_name() );
		$this->assertSame( 'red', $saved->get_meta( '_test_color' ) );
		$this->assertSame( '12', (string) $saved->get_meta( '_test_count' ) );
		$this->assertSame(
			array(
				'test-ext'  => array( 'color' => 'red' ),
				'other_ext' => array( 'count' => 12 ),
			),
			$output['product']['extensions']
		);
	}

	/**
	 * @testdox Should accept extension fields in product create and order status update.
	 */
	public function test_product_create_and_order_status_update_write_fields(): void {
		$order = \WC_Helper_Order::create_order();

		$created = $this->run_ability(
			'woocommerce/product-create',
			array(
				'name'       => 'Mug',
				'extensions' => array( 'test-ext' => array( 'color' => 'blue' ) ),
			)
		);
		$this->run_ability(
			'woocommerce/order-update-status',
			array(
				'id'         => $order->get_id(),
				'status'     => 'completed',
				'extensions' => array( 'test-ext' => array( 'tag' => 'vip' ) ),
			)
		);

		$saved_order = wc_get_order( $order->get_id() );
		$this->assertSame( 'blue', wc_get_product( $created['product']['id'] )->get_meta( '_test_color' ) );
		$this->assertSame( 'completed', $saved_order->get_status() );
		$this->assertSame( 'vip', $saved_order->get_meta( '_test_tag' ) );
	}

	/**
	 * @testdox Should write extension fields to each variation of a variable product from its own extensions input.
	 */
	public function test_product_create_writes_fields_to_each_variation(): void {
		$created = $this->run_ability(
			'woocommerce/product-create',
			array(
				'name'               => 'Shirt',
				'product_type_alias' => 'variable',
				'attributes'         => array(
					array(
						'name'    => 'Size',
						'options' => array( 'Small', 'Large' ),
					),
				),
				'variations'         => array(
					array(
						'attributes'    => array( 'Size' => 'Small' ),
						'regular_price' => '10',
						'extensions'    => array( 'test-ext' => array( 'color' => 'red' ) ),
					),
					array(
						'attributes'    => array( 'Size' => 'Large' ),
						'regular_price' => '15',
					),
				),
				'extensions'         => array( 'test-ext' => array( 'color' => 'blue' ) ),
			)
		);

		list( $small, $large ) = array_map( 'wc_get_product', $created['product']['variations'] );
		$this->assertSame( 'blue', wc_get_product( $created['product']['id'] )->get_meta( '_test_color' ) );
		$this->assertSame( 'red', $small->get_meta( '_test_color' ) );
		$this->assertFalse( $large->meta_exists( '_test_color' ) );
	}

	/**
	 * @testdox Should run a validator on each variation and save nothing when it refuses one.
	 */
	public function test_a_validator_refuses_a_variation_and_nothing_is_saved(): void {
		$this->register_validator(
			'product',
			'other_ext',
			static function ( \WC_Product $subject ) {
				if ( $subject instanceof \WC_Product_Variation && '15' === $subject->get_regular_price() ) {
					return new \WP_Error( 'test_no_fifteen', 'No variation at 15.', array( 'status' => 409 ) );
				}
				return null;
			}
		);

		$response = $this->run_ability_response(
			'woocommerce/product-create',
			array(
				'name'               => 'Shirt Refused',
				'product_type_alias' => 'variable',
				'attributes'         => array(
					array(
						'name'    => 'Size',
						'options' => array( 'Small', 'Large' ),
					),
				),
				'variations'         => array(
					array(
						'attributes'    => array( 'Size' => 'Small' ),
						'regular_price' => '10',
					),
					array(
						'attributes'    => array( 'Size' => 'Large' ),
						'regular_price' => '15',
					),
				),
			)
		);

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'other_ext', $response->get_data()['data']['namespace'] );
		$this->assertSame( array(), wc_get_products( array( 'name' => 'Shirt Refused' ) ) );
		$this->assertSame(
			array(),
			get_posts(
				array(
					'post_type'   => 'product_variation',
					'post_status' => 'any',
					'fields'      => 'ids',
				)
			)
		);
	}

	/**
	 * @testdox Should fire woocommerce_order_edit_status one time for an accepted status change, and not at all when an extension refuses it.
	 */
	public function test_order_status_update_fires_the_edit_action_only_for_an_accepted_change(): void {
		$order    = \WC_Helper_Order::create_order();
		$edits    = array();
		$listener = static function ( $order_id, $status ) use ( &$edits ) {
			$edits[] = array( $order_id, $status );
		};
		add_action( 'woocommerce_order_edit_status', $listener, 10, 2 );

		try {
			$refused = $this->dispatch_ability(
				'woocommerce/order-update-status',
				array(
					'id'         => $order->get_id(),
					'status'     => 'completed',
					'extensions' => array( 'test-ext' => array( 'tag' => 'reject' ) ),
				)
			);
			$this->assertSame( 400, $refused->get_status() );
			$this->assertSame( 'test_rejected', $refused->get_data()['code'] );
			$this->assertSame( 'pending', wc_get_order( $order->get_id() )->get_status() );
			$this->assertSame( array(), $edits );

			$this->run_ability(
				'woocommerce/order-update-status',
				array(
					'id'     => $order->get_id(),
					'status' => 'completed',
				)
			);
		} finally {
			remove_action( 'woocommerce_order_edit_status', $listener, 10 );
		}

		$this->assertSame( 'completed', wc_get_order( $order->get_id() )->get_status() );
		$this->assertSame( array( array( $order->get_id(), 'completed' ) ), $edits );
	}

	/**
	 * @testdox Should save nothing when a value is rejected, does not match its schema, or belongs to a read-only field.
	 */
	public function test_a_rejected_value_saves_nothing(): void {
		$product  = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$rejected = array(
			array( 'other_ext' => array( 'count' => -1 ) ),
			array( 'other_ext' => array( 'count' => 'many' ) ),
			array( 'other_ext' => array( 'code' => 7 ) ),
			array( 'unknown' => array( 'color' => 'red' ) ),
		);

		foreach ( $rejected as $extensions ) {
			$response = $this->dispatch_ability(
				'woocommerce/product-update',
				array(
					'id'         => $product->get_id(),
					'name'       => 'Pencil',
					'extensions' => array( 'test-ext' => array( 'color' => 'red' ) ) + $extensions,
				)
			);

			$this->assertSame( 400, $response->get_status(), wp_json_encode( $extensions ) );
			if ( isset( $extensions['other_ext']['count'] ) && 'many' === $extensions['other_ext']['count'] ) {
				$this->assertStringContainsString( 'input[extensions][other_ext][count] is not of type integer,null.', $response->get_data()['message'] );
			}
		}
		$saved = wc_get_product( $product->get_id() );
		$this->assertSame( 'Pen', $saved->get_name() );
		$this->assertFalse( $saved->meta_exists( '_test_color' ) );
	}

	/**
	 * @testdox Should check every extension value before any update callback runs, when a later value fails its schema.
	 */
	public function test_a_value_that_fails_its_schema_runs_no_update_callback(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		$response = $this->dispatch_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array(
					'test-ext'  => array( 'color' => 'red' ),
					'other_ext' => array( 'count' => 'many' ),
				),
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( array(), self::$received );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should delete the value of a field when the input value is null.
	 */
	public function test_a_null_value_deletes_the_field_value(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_test_color', 'red' );
		$product->save();

		$output = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'test-ext' => array( 'color' => null ) ),
			)
		);

		$this->assertFalse( wc_get_product( $product->get_id() )->meta_exists( '_test_color' ) );
		$this->assertArrayNotHasKey( 'extensions', $output['product'] );
	}

	/**
	 * @testdox Should call WC_Order::update_status() with the feature off, and skip it with the feature on by design.
	 */
	public function test_the_feature_off_path_calls_update_status(): void {
		$calls        = 0;
		$order_class  = get_class(
			new class() extends \WC_Order {
				/**
				 * Calls of update_status().
				 *
				 * @var int
				 */
				public static $calls = 0;

				/**
				 * Count the call, then update the status.
				 *
				 * @param string $new_status    Status to change the order to.
				 * @param string $note          Optional note.
				 * @param bool   $manual_update Whether a person made the change.
				 * @return bool
				 */
				public function update_status( $new_status, $note = '', $manual_update = false ) {
					++self::$calls;
					return parent::update_status( $new_status, $note, $manual_update );
				}
			}
		);
		$filter_class = static fn() => $order_class;
		add_filter( 'woocommerce_order_class', $filter_class, 100 );

		foreach ( array( false, true ) as $enabled ) {
			$this->set_feature( $enabled );
			$this->reset_registries();
			$order_class::$calls = 0;
			$order               = \WC_Helper_Order::create_order();

			$this->run_ability(
				'woocommerce/order-update-status',
				array(
					'id'     => $order->get_id(),
					'status' => 'completed',
				)
			);

			$calls = $order_class::$calls;
			$this->assertSame( $enabled ? 0 : 1, $calls, $enabled ? 'feature on' : 'feature off' );
			$this->assertSame( 'completed', wc_get_order( $order->get_id() )->get_status() );
		}

		remove_filter( 'woocommerce_order_class', $filter_class, 100 );
	}

	/**
	 * @testdox Should accept null for a field with an enum, to delete its value, and still refuse a value outside the enum.
	 */
	public function test_a_null_value_deletes_the_value_of_an_enum_field(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_test_size', 'small' );
		$product->save();

		$schema   = $this->get_ability_data( 'woocommerce/product-update' )['input_schema']['properties']['extensions']['properties']['other_ext']['properties']['size'];
		$rejected = $this->dispatch_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'other_ext' => array( 'size' => 'medium' ) ),
			)
		);
		$this->run_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'other_ext' => array( 'size' => null ) ),
			)
		);

		$this->assertSame( array( 'small', 'large', null ), $schema['enum'] );
		$this->assertSame( 400, $rejected->get_status() );
		$this->assertFalse( wc_get_product( $product->get_id() )->meta_exists( '_test_size' ) );
	}

	/**
	 * @testdox Should let an ActionableAbility of an extension accept the writable fields with no extra code.
	 */
	public function test_an_extension_actionable_ability_writes_the_fields(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$this->register_rename_ability();

		$schema = $this->get_ability_data( 'test-ext/product-rename' )['input_schema']['properties']['extensions'];
		$output = $this->run_ability(
			'test-ext/product-rename',
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array( 'test-ext' => array( 'color' => 'green' ) ),
			)
		);

		$this->assertSame( array( 'string', 'null' ), $schema['properties']['test-ext']['properties']['color']['type'] );
		$this->assertArrayNotHasKey( 'code', $schema['properties']['test-ext']['properties'] );
		$this->assertSame( 'Pencil', wc_get_product( $product->get_id() )->get_name() );
		$this->assertSame( array( 'color' => 'green' ), $output['extensions']['test-ext'] );
	}

	/**
	 * @testdox Should list the writable fields one time at the top level of the product input schemas.
	 */
	public function test_input_schemas_list_writable_fields_one_time(): void {
		foreach ( array( 'woocommerce/product-create', 'woocommerce/product-update' ) as $name ) {
			$schema     = $this->get_ability_data( $name )['input_schema'];
			$extensions = $schema['properties']['extensions']['properties'];

			$this->assertSame( array( 'string', 'null' ), $extensions['test-ext']['properties']['color']['type'] );
			$this->assertSame( array( 'integer', 'null' ), $extensions['other_ext']['properties']['count']['type'] );
			$this->assertArrayNotHasKey( 'code', $extensions['test-ext']['properties'] );
			$this->assertSame( 1, substr_count( wp_json_encode( $schema ), '"color"' ) );
			foreach ( $schema['oneOf'] as $branch ) {
				$this->assertSame( 'object', $branch['properties']['extensions']['type'] );
				$this->assertArrayNotHasKey( 'properties', $branch['properties']['extensions'] );
			}
		}
		$order = $this->get_ability_data( 'woocommerce/order-update-status' )['input_schema'];
		$this->assertSame( array( 'string', 'null' ), $order['properties']['extensions']['properties']['test-ext']['properties']['tag']['type'] );
	}

	/**
	 * @testdox Should not accept extension fields in the write abilities with the feature off.
	 */
	public function test_writes_do_not_change_with_the_feature_off(): void {
		$this->set_feature( false );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		$schema   = $this->get_ability_data( 'woocommerce/product-update' )['input_schema'];
		$rejected = $this->dispatch_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'test-ext' => array( 'color' => 'red' ) ),
			)
		);
		$output   = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assertNotInstanceOf( ActionableAbility::class, wp_get_ability( 'woocommerce/product-update' ) );
		$this->assertArrayNotHasKey( 'extensions', $schema['properties'] ?? array() );
		$this->assertArrayNotHasKey( 'extensions', $schema['oneOf'][0]['properties'] );
		$this->assertSame( 400, $rejected->get_status() );
		$this->assertSame( 'Pencil', $output['product']['name'] );
		$this->assertFalse( wc_get_product( $product->get_id() )->meta_exists( '_test_color' ) );
	}

	/**
	 * @testdox Should name the field and keep the message when an update_callback rejects a value.
	 */
	public function test_a_rejected_value_names_the_field(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$response = $this->dispatch_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'other_ext' => array( 'count' => -1 ) ),
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'test_rejected', $response->get_data()['code'] );
		$this->assertSame( 'Rejected.', $response->get_data()['message'] );
		$this->assertSame( 'extensions.other_ext.count', $response->get_data()['data']['field'] );
	}

	/**
	 * @testdox Should save nothing and run no status listeners when a validator refuses an order status change with no extension fields.
	 */
	public function test_a_validator_refuses_a_status_change(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_test_locked', 'yes' );
		$order->save();
		$ability = null;
		$this->register_validator(
			'order',
			'test-ext',
			static function ( \WC_Order $subject, \WP_Ability $current ) use ( &$ability ) {
				$ability = $current->get_name();
				if ( 'yes' === $subject->get_meta( '_test_locked' ) && 'completed' === $subject->get_status() ) {
					return new \WP_Error( 'test_locked', 'Use test-ext/order-unlock first.' );
				}
				return null;
			}
		);
		$changes = did_action( 'woocommerce_order_status_changed' );

		$response = $this->dispatch_ability(
			'woocommerce/order-update-status',
			array(
				'id'     => $order->get_id(),
				'status' => 'completed',
			)
		);
		$allowed  = $this->run_ability(
			'woocommerce/order-update-status',
			array(
				'id'     => $order->get_id(),
				'status' => 'on-hold',
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'test_locked', $response->get_data()['code'] );
		$this->assertSame( 'Use test-ext/order-unlock first.', $response->get_data()['message'] );
		$this->assertSame( 'test-ext', $response->get_data()['data']['namespace'] );
		$this->assertSame( 1, did_action( 'woocommerce_order_status_changed' ) - $changes );
		$this->assertSame( 'on-hold', $allowed['order']['status'] );
		$this->assertSame( 'woocommerce/order-update-status', $ability );
	}

	/**
	 * @testdox Should run a validator on the changed product, after the field callbacks, and save nothing when it refuses.
	 */
	public function test_a_validator_sees_the_changed_product(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$this->register_validator(
			'product',
			'other_ext',
			static function ( \WC_Product $subject ) {
				if ( 'Pencil' === $subject->get_name() && 'red' === $subject->get_meta( '_test_color' ) ) {
					return new \WP_Error( 'test_red_pencil', 'No red pencils.', array( 'status' => 409 ) );
				}
				return null;
			}
		);

		$response = $this->dispatch_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array( 'test-ext' => array( 'color' => 'red' ) ),
			)
		);

		$saved = wc_get_product( $product->get_id() );
		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'other_ext', $response->get_data()['data']['namespace'] );
		$this->assertSame( 'Pen', $saved->get_name() );
		$this->assertFalse( $saved->meta_exists( '_test_color' ) );
	}

	/**
	 * @testdox Should run a validator in an ActionableAbility of an extension, with that ability.
	 */
	public function test_a_validator_runs_in_an_extension_actionable_ability(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$this->register_rename_ability();
		$this->register_validator(
			'product',
			'other_ext',
			static function ( \WC_Product $subject, \WP_Ability $ability ) {
				return 'test-ext/product-rename' === $ability->get_name()
					? new \WP_Error( 'test_rename_refused', 'Rename refused.' )
					: null;
			}
		);

		$response = $this->dispatch_ability(
			'test-ext/product-rename',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'test_rename_refused', $response->get_data()['code'] );
		$this->assertSame( 'other_ext', $response->get_data()['data']['namespace'] );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should return a fixed message and the field or namespace when an update_callback or a validator throws, and save nothing.
	 */
	public function test_a_callback_that_throws_returns_a_clear_error(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$this->add_test_action(
			'woocommerce_ability_extensions_init',
			static function () {
				AbilityExtensions::register_field(
					array(
						'resource'        => 'product',
						'namespace'       => 'test-ext',
						'field'           => 'broken',
						'schema'          => array( 'type' => 'string' ),
						'get_callback'    => '__return_null',
						'update_callback' => static function () {
							throw new \RuntimeException( 'Field broke.' );
						},
					)
				);
			}
		);
		$this->register_validator(
			'product',
			'other_ext',
			static function ( \WC_Product $subject ) {
				if ( 'Boom' === $subject->get_name() ) {
					throw new \RuntimeException( 'Validator broke.' );
				}
				return null;
			}
		);

		$field     = $this->dispatch_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array( 'test-ext' => array( 'broken' => 'x' ) ),
			)
		);
		$validator = $this->dispatch_ability(
			'woocommerce/product-update',
			array(
				'id'   => $product->get_id(),
				'name' => 'Boom',
			)
		);

		$this->assertSame( 500, $field->get_status() );
		$this->assertSame( 'woocommerce_ability_extension_failed', $field->get_data()['code'] );
		$this->assertSame( 'An extension failed while checking this change.', $field->get_data()['message'] );
		$this->assertSame( 'extensions.test-ext.broken', $field->get_data()['data']['field'] );
		$this->assertSame( 500, $validator->get_status() );
		$this->assertSame( 'An extension failed while checking this change.', $validator->get_data()['message'] );
		$this->assertSame( 'other_ext', $validator->get_data()['data']['namespace'] );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should not run validators with the feature off.
	 */
	public function test_validators_do_not_run_with_the_feature_off(): void {
		$this->set_feature( false );
		$order = \WC_Helper_Order::create_order();
		AbilityExtensions::register_validator(
			array(
				'resource'  => 'order',
				'namespace' => 'test-ext',
				'callback'  => static function () {
					return new \WP_Error( 'test_refused', 'Refused.' );
				},
			)
		);

		$output = $this->run_ability(
			'woocommerce/order-update-status',
			array(
				'id'     => $order->get_id(),
				'status' => 'completed',
			)
		);

		$this->assertSame( 'completed', $output['order']['status'] );
	}

	/**
	 * @testdox Should refuse a validator without a slug namespace or a callback.
	 */
	public function test_invalid_validators_are_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityExtensions::class . '::register_validator' );

		AbilityExtensions::register_validator(
			array(
				'resource' => 'order',
				'callback' => '__return_null',
			)
		);
		AbilityExtensions::register_validator(
			array(
				'resource'  => 'order',
				'namespace' => 'test-ext',
			)
		);

		$this->assertEmpty( AbilityExtensions::get_validators( 'order' ) );
	}

	/**
	 * Add an action that tearDown removes.
	 *
	 * @param string   $hook     Action name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 */
	private function add_test_action( string $hook, callable $callback, int $priority = 10 ): void {
		add_action( $hook, $callback, $priority );
		$this->test_actions[] = array( $hook, $callback, $priority );
	}

	/**
	 * Register a validator inside woocommerce_ability_extensions_init, the way an extension does.
	 *
	 * @param string   $resource_name       Resource.
	 * @param string   $extension_namespace Extension namespace.
	 * @param callable $callback            Validator.
	 */
	private function register_validator( string $resource_name, string $extension_namespace, callable $callback ): void {
		$this->add_test_action(
			'woocommerce_ability_extensions_init',
			static function () use ( $resource_name, $extension_namespace, $callback ) {
				AbilityExtensions::register_validator(
					array(
						'resource'  => $resource_name,
						'namespace' => $extension_namespace,
						'callback'  => $callback,
					)
				);
			}
		);
	}

	/**
	 * Register test-ext/product-rename, an ActionableAbility of an extension.
	 */
	private function register_rename_ability(): void {
		$this->add_test_action(
			'wp_abilities_api_init',
			static function () {
				wp_register_ability(
					'test-ext/product-rename',
					array(
						'label'               => 'Rename product',
						'description'         => 'Renames a product.',
						'category'            => 'woocommerce',
						'input_schema'        => array(
							'type'                 => 'object',
							'properties'           => array(
								'id'   => array( 'type' => 'integer' ),
								'name' => array( 'type' => 'string' ),
							),
							'additionalProperties' => false,
						),
						'output_schema'       => AbilityExtensions::add_fields_schema( array( 'type' => 'object' ), 'product' ),
						'permission_callback' => '__return_true',
						'ability_class'       => TestProductRenameAbility::class,
						'meta'                => array( 'show_in_rest' => true ),
					)
				);
			},
			20
		);
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

		foreach ( array( 'fields', 'reported', 'validators', 'filters' ) as $property ) {
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
