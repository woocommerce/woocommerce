<?php
/**
 * ExtensibleAbilitiesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Namespaced extension fields in the product and order abilities, read and written through the REST route.
 */
class ExtensibleAbilitiesTest extends \WC_REST_Unit_Test_Case {

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

		$previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		try {
			self::setup_cot_tables();
			if ( OrderUtil::custom_orders_table_usage_is_enabled() !== $previous_hpos_state ) {
				OrderHelper::toggle_cot_feature_and_usage( $previous_hpos_state );
			}
		} finally {
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
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
		add_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_test_fields' ) );
		add_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_test_filters' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		remove_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_test_fields' ) );
		remove_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_test_filters' ) );
		foreach ( $this->test_actions as list( $hook, $callback, $priority ) ) {
			remove_action( $hook, $callback, $priority );
		}
		$this->test_actions = array();
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
	 * Register the fields of two extensions, the way an extension does inside woocommerce_extensible_abilities_init.
	 *
	 * @internal
	 */
	public function register_test_fields(): void {
		ExtensibleAbilities::register_field(
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
		ExtensibleAbilities::register_field(
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
		ExtensibleAbilities::register_field(
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
		ExtensibleAbilities::register_field(
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
		);
		foreach ( $writable as list( $resource, $namespace, $field, $schema ) ) {
			ExtensibleAbilities::register_field(
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
						if ( 'throw' === $value ) {
							throw new \WC_Data_Exception( 'test_thrown', 'Thrown.' );
						}
						if ( 'crash' === $value ) {
							throw new \RuntimeException( 'Crashed.' );
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
	 * Register a product filter and an order filter, the way an extension does inside woocommerce_extensible_abilities_init.
	 *
	 * @internal
	 */
	public function register_test_filters(): void {
		ExtensibleAbilities::register_query_filter(
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
		ExtensibleAbilities::register_query_filter(
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
		ExtensibleAbilities::register_query_filter(
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
				'type'    => 'external',
				'filters' => array( 'extensions' => array( 'test-ext' => array( 'category' => 'gifts' ) ) ),
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
			remove_all_filters( 'pre_option_' . DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION );
			$this->toggle_cot_feature_and_usage( $original );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * @testdox Should keep the orders whose IDs every filter returns, from data that is not meta, and still apply Core's exclude, with HPOS and with posts storage.
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
		$this->toggle_cot_feature_and_usage( $hpos );
		if ( $sync ) {
			$this->enable_cot_sync();
		} else {
			$this->disable_cot_sync();
		}
		add_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_contains_product_filter' ) );

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
			$excluded = $this->run_ability( 'woocommerce/orders-query', array( 'exclude' => array( $orders['gift'] ) ) + $filters( array( 'contains_product' => $coffee->get_id() ) ) );
			$both_out = $this->run_ability( 'woocommerce/orders-query', array( 'exclude' => array( $other->get_id() ) ) + $filters( array( 'without' => array( $orders['plain'] ) ) ) );

			$this->assertEqualsCanonicalizing( array_values( $orders ), array_column( $contains['orders'], 'id' ) );
			$this->assertSame( array( $orders['gift'] ), array_column( $both['orders'], 'id' ) );
			$this->assertSame( array(), $none['orders'] );
			$this->assertEqualsCanonicalizing( array( $orders['gift'], $other->get_id() ), array_column( $without['orders'], 'id' ) );
			$this->assertSame( array( $orders['plain'] ), array_column( $excluded['orders'], 'id' ) );
			$this->assertSame( array( $orders['gift'] ), array_column( $both_out['orders'], 'id' ) );
		} finally {
			remove_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_contains_product_filter' ) );
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
		ExtensibleAbilities::register_query_filter(
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
		ExtensibleAbilities::register_query_filter(
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
			'woocommerce_extensible_abilities_init',
			static function () use ( $plain ) {
				$callbacks = array(
					'picked'    => static fn( array $ids ) => array( 'include_ids' => $ids ),
					'not_plain' => static fn() => array( 'exclude_ids' => array( $plain->get_id() ) ),
					'not_ids'   => static fn() => array( 'include_ids' => array( 'x' ) ),
				);
				foreach ( $callbacks as $filter => $callback ) {
					ExtensibleAbilities::register_query_filter(
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
		$not_ids  = $this->run_ability_response( 'woocommerce/products-query', $filters( array( 'not_ids' => true ) ) );

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
		$products = $this->get_ability( 'woocommerce/products-query' )['input_schema']['properties']['filters']['properties']['extensions'];
		$orders   = $this->get_ability( 'woocommerce/orders-query' )['input_schema']['properties']['filters']['properties']['extensions'];

		$response = $this->run_ability_response(
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
			'woocommerce_extensible_abilities_init',
			static function () {
				$callbacks = array(
					array( 'order', 'broken', static fn() => array( 'post__in' => array( 1 ) ) ),
					array( 'order', 'taxed', static fn() => array( 'tax_query' => array( array( 'taxonomy' => 'product_cat' ) ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array( 'product', 'scalar', static fn() => array( 'meta_query' => 'x' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				);
				foreach ( $callbacks as list( $resource, $filter, $callback ) ) {
					ExtensibleAbilities::register_query_filter(
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

		$refused = $this->run_ability_response( 'woocommerce/orders-query', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'tag' => 'bad' ) ) ) ) );
		$failed  = $this->run_ability_response( 'woocommerce/orders-query', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'broken' => true ) ) ) ) );
		$taxed   = $this->run_ability_response( 'woocommerce/orders-query', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'taxed' => true ) ) ) ) );
		$scalar  = $this->run_ability_response( 'woocommerce/products-query', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'scalar' => true ) ) ) ) );

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
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_query_filter(
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
		$this->get_ability( 'woocommerce/products-query' )['input_schema'];

		$this->assertSame( array(), ExtensibleAbilities::get_query_args( 'product', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'empty_clause' => true ) ) ) ) ) );
	}

	/**
	 * @testdox Should refuse a filter on a resource that is not registered, and accept one on a registered resource.
	 */
	public function test_a_filter_on_an_unsupported_resource_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_query_filter' );
		$input  = array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'gift' => true ) ) ) );
		$filter = array(
			'resource'  => 'ticket',
			'namespace' => 'test-ext',
			'filter'    => 'gift',
			'schema'    => array( 'type' => 'boolean' ),
			'callback'  => static fn() => array( 'meta_query' => array( array( 'key' => 'gift' ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		);

		ExtensibleAbilities::register_query_filter( $filter );
		$this->assertSame( array(), ExtensibleAbilities::get_query_args( 'ticket', $input ) );

		$register_resource = static function () {
			ExtensibleAbilities::register_resource( 'ticket' );
		};
		$register          = static function () use ( $filter ) {
			ExtensibleAbilities::register_query_filter( $filter );
		};
		add_action( 'woocommerce_extensible_abilities_resources_init', $register_resource );
		add_action( 'woocommerce_extensible_abilities_init', $register );
		$this->get_ability( 'woocommerce/products-query' )['input_schema'];
		remove_action( 'woocommerce_extensible_abilities_resources_init', $register_resource );
		remove_action( 'woocommerce_extensible_abilities_init', $register );

		$this->assertSame( array( 'meta_query' => array( array( array( 'key' => 'gift' ) ) ) ), ExtensibleAbilities::get_query_args( 'ticket', $input ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}

	/**
	 * @testdox Should refuse a filter whose namespace or name is only numbers.
	 */
	public function test_a_filter_with_a_numeric_slug_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_query_filter' );
		$register = static function () {
			foreach ( array( array( '0', 'gift' ), array( 'test-ext', '42' ) ) as list( $extension_namespace, $filter ) ) {
				ExtensibleAbilities::register_query_filter(
					array(
						'resource'  => 'product',
						'namespace' => $extension_namespace,
						'filter'    => $filter,
						'schema'    => array( 'type' => 'boolean' ),
						'callback'  => static fn() => array( 'meta_query' => array( array( 'key' => 'gift' ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					)
				);
			}
		};
		add_action( 'woocommerce_extensible_abilities_init', $register );
		$schema = $this->get_ability( 'woocommerce/products-query' )['input_schema'];
		remove_action( 'woocommerce_extensible_abilities_init', $register );

		$this->assertSame( array( 'test-ext' ), array_keys( $schema['properties']['filters']['properties']['extensions']['properties'] ) );
		$this->assertSame( array( 'has_code', 'category' ), array_keys( $schema['properties']['filters']['properties']['extensions']['properties']['test-ext']['properties'] ) );
	}

	/**
	 * @testdox Should return a 400 error and not run the filter when a value that skipped validation fails its schema.
	 */
	public function test_a_filter_value_that_fails_its_schema_is_refused(): void {
		$called   = false;
		$register = static function () use ( &$called ) {
			ExtensibleAbilities::register_query_filter(
				array(
					'resource'  => 'order',
					'namespace' => 'test-ext',
					'filter'    => 'tags',
					'schema'    => array(
						'type'        => 'array',
						'items'       => array( 'type' => 'string' ),
						'uniqueItems' => true,
					),
					'callback'  => static function () use ( &$called ) {
						$called = true;
						return array();
					},
				)
			);
		};
		add_action( 'woocommerce_extensible_abilities_init', $register );
		$this->get_ability( 'woocommerce/orders-query' );
		remove_action( 'woocommerce_extensible_abilities_init', $register );

		$result = ExtensibleAbilities::get_query_args( 'order', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'tags' => array( 'gift', 'gift' ) ) ) ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertFalse( $called );
	}

	/**
	 * @testdox Should refuse a filter that is registered after the init action.
	 */
	public function test_a_filter_registered_after_the_init_action_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_query_filter' );
		$this->get_ability( 'woocommerce/products-query' )['input_schema'];

		ExtensibleAbilities::register_query_filter(
			array(
				'resource'  => 'product',
				'namespace' => 'late-ext',
				'filter'    => 'gift',
				'schema'    => array( 'type' => 'boolean' ),
				'callback'  => static fn() => array( 'meta_query' => array( array( 'key' => 'gift' ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		$this->assertSame( array(), ExtensibleAbilities::get_query_args( 'product', array( 'filters' => array( 'extensions' => array( 'late-ext' => array( 'gift' => true ) ) ) ) ) );
	}

	/**
	 * @testdox Should refuse an id sent together with filters.
	 */
	public function test_an_id_with_filters_is_refused(): void {
		$input = array(
			'id'      => 1,
			'filters' => array( 'extensions' => array( 'test-ext' => array( 'has_code' => true ) ) ),
		);

		$products = $this->run_ability_response( 'woocommerce/products-query', $input );
		$orders   = $this->run_ability_response( 'woocommerce/orders-query', array( 'id' => 1 ) + array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'tag' => 'gift' ) ) ) ) );

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
		$this->assertSame( array( 'type' => 'object' ), ExtensibleAbilities::add_fields_schema( array( 'type' => 'object' ), 'customer' ) );
		$this->assertFalse( $products['additionalProperties'] );
		$this->assertFalse( $products['properties']['test-ext']['additionalProperties'] );
	}

	/**
	 * @testdox Should fire woocommerce_extensible_abilities_init one time, before the abilities build their schemas.
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
		add_action( 'woocommerce_extensible_abilities_init', $on_init );
		add_filter( 'wp_register_ability_args', $on_ability, 10, 2 );

		$this->get_output_schema( 'woocommerce/products-query' );
		do_action( 'wp_abilities_api_init' );

		remove_action( 'woocommerce_extensible_abilities_init', $on_init );
		remove_filter( 'wp_register_ability_args', $on_ability, 10 );
		$this->assertSame( 1, did_action( 'woocommerce_extensible_abilities_init' ) );
		$this->assertSame( 'extensions', $events[0] );
		$this->assertSame( 1, count( array_keys( $events, 'extensions', true ) ) );
		$this->assertContains( 'woocommerce/products-query', $events );
	}

	/**
	 * @testdox Should refuse a field that is registered after the init action, and keep the abilities valid.
	 */
	public function test_a_field_registered_after_the_init_action_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		$this->get_output_schema( 'woocommerce/products-query' );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen late' ) );

		ExtensibleAbilities::register_field(
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
		$this->assertArrayNotHasKey( 'late-ext', ExtensibleAbilities::add_fields_to_object( array(), 'product', $product )['extensions'] ?? array() );
	}

	/**
	 * @testdox Should let an extension register fields on a resource that another extension registers, whatever the hook priorities.
	 */
	public function test_register_resource_lets_an_extension_register_fields_on_its_own_resource(): void {
		$register_resource = static function () {
			ExtensibleAbilities::register_resource( 'ticket' );
		};
		$register_field    = static function () {
			ExtensibleAbilities::register_field(
				array(
					'resource'     => 'ticket',
					'namespace'    => 'test-ext',
					'field'        => 'code',
					'schema'       => array( 'type' => 'string' ),
					'get_callback' => static fn() => 'T1',
				)
			);
		};
		add_action( 'woocommerce_extensible_abilities_resources_init', $register_resource, 99 );
		add_action( 'woocommerce_extensible_abilities_init', $register_field, 1 );
		$this->get_output_schema( 'woocommerce/products-query' );
		remove_action( 'woocommerce_extensible_abilities_resources_init', $register_resource, 99 );
		remove_action( 'woocommerce_extensible_abilities_init', $register_field, 1 );

		$output = ExtensibleAbilities::add_fields_to_object( array(), 'ticket', new \stdClass() );
		$schema = ExtensibleAbilities::add_fields_schema( array( 'type' => 'object' ), 'ticket' );

		$this->assertSame( array( 'test-ext' => array( 'code' => 'T1' ) ), $output['extensions'] );
		$this->assertSame( array( 'type' => 'string' ), $schema['properties']['extensions']['properties']['test-ext']['properties']['code'] );
	}

	/**
	 * @testdox Should refuse a resource that is registered after the resources action.
	 */
	public function test_a_resource_registered_after_the_resources_action_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_resource' );
		$this->get_output_schema( 'woocommerce/products-query' );

		ExtensibleAbilities::register_resource( 'late_resource' );

		$resources = new \ReflectionProperty( ExtensibleAbilities::class, 'resources' );
		$resources->setAccessible( true );
		$this->assertSame( array( 'product', 'order', 'order_item' ), array_keys( $resources->getValue() ) );
	}

	/**
	 * @testdox Should refuse a resource name made only of digits.
	 */
	public function test_a_resource_name_of_only_digits_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_resource' );

		ExtensibleAbilities::register_resource( '42' );

		$resources = new \ReflectionProperty( ExtensibleAbilities::class, 'resources' );
		$resources->setAccessible( true );
		$this->assertSame( array(), $resources->getValue() );
	}

	/**
	 * @testdox Should tell an extension to use the init action when it registers a field before the resources exist.
	 */
	public function test_a_field_registered_before_the_resources_points_to_the_init_action(): void {
		$messages = array();
		$capture  = static function ( $function_name, $message ) use ( &$messages ) {
			$messages[] = $message;
		};
		add_action( 'doing_it_wrong_run', $capture, 10, 2 );
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );

		ExtensibleAbilities::register_field(
			array(
				'resource'     => 'product',
				'namespace'    => 'test-ext',
				'field'        => 'code',
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => 'T1',
			)
		);
		remove_action( 'doing_it_wrong_run', $capture, 10 );

		$this->assertStringContainsString( 'inside the woocommerce_extensible_abilities_init action', $messages[0] ?? '' );
	}

	/**
	 * @testdox Should not fire woocommerce_extensible_abilities_init or change the abilities with the feature off.
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

		$this->assertSame( 0, did_action( 'woocommerce_extensible_abilities_resources_init' ) );
		$this->assertSame( 0, did_action( 'woocommerce_extensible_abilities_init' ) );
		$this->assertArrayNotHasKey( 'filters', $this->get_ability( 'woocommerce/products-query' )['input_schema']['properties'] );
		$this->assertSame( 400, $this->run_ability_response( 'woocommerce/products-query', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'has_code' => true ) ) ) ) )->get_status() );
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
					'output_schema'       => ExtensibleAbilities::add_fields_schema(
						array(
							'type'       => 'object',
							'properties' => array( 'id' => array( 'type' => 'integer' ) ),
						),
						'product'
					),
					'execute_callback'    => static function ( array $input ) {
						$product = wc_get_product( $input['id'] );
						return ExtensibleAbilities::add_fields_to_object( array( 'id' => $product->get_id() ), 'product', $product );
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
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		$valid = array(
			'resource'     => 'order',
			'namespace'    => 'test-ext',
			'field'        => 'note',
			'schema'       => array( 'type' => 'string' ),
			'get_callback' => static fn() => 'second',
		);
		AbilitiesLoader::register_core_resources();
		$this->register_test_fields();

		ExtensibleAbilities::register_field( $valid );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'namespace' => 'Test Ext' ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => '' ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'schema' => null ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'get_callback' => 'not_a_function' ) ) );
		ExtensibleAbilities::register_field(
			array_merge(
				$valid,
				array(
					'field'    => 'other',
					'resource' => 'line_item',
				)
			)
		);
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'schema' => array( 'description' => 'x' ) ) ) );
		ExtensibleAbilities::register_field(
			array_merge(
				$valid,
				array( 'field' => 'other' ),
				array(
					'schema' => array(
						'type'     => 'string',
						'required' => true,
					),
				)
			)
		);
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'namespace' => '0' ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => '123' ) ) );
		$order = \WC_Helper_Order::create_order();

		$this->assertSame(
			array( 'test-ext' => array( 'note' => 'order-' . $order->get_id() ) ),
			ExtensibleAbilities::add_fields_to_object( array(), 'order', $order )['extensions']
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
			ExtensibleAbilities::register_field(
				array(
					'resource'  => 'order',
					'namespace' => 'values',
					'field'     => $field,
				) + $args
			);
		}
		$order = \WC_Helper_Order::create_order();

		$output = ExtensibleAbilities::add_fields_to_object( array(), 'order', $order );

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
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
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
	 * @testdox Should accept extension fields in product create, and none in order status update.
	 */
	public function test_product_create_writes_fields_and_order_status_update_does_not(): void {
		$order = \WC_Helper_Order::create_order();

		$created = $this->run_ability(
			'woocommerce/product-create',
			array(
				'name'       => 'Mug',
				'extensions' => array( 'test-ext' => array( 'color' => 'blue' ) ),
			)
		);
		$schema  = $this->get_ability( 'woocommerce/order-update-status' )['input_schema'];

		$this->assertSame( 'blue', wc_get_product( $created['product']['id'] )->get_meta( '_test_color' ) );
		$this->assertArrayNotHasKey( 'extensions', $schema['properties'] );
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
			$response = $this->run_ability_response(
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
	 * @testdox Should refuse the change and save nothing when an update callback throws.
	 */
	public function test_an_update_callback_that_throws_saves_nothing(): void {
		$product  = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$expected = array(
			'throw' => array( 400, 'test_thrown' ),
			'crash' => array( 500, 'woocommerce_ability_extension_failed' ),
		);

		foreach ( $expected as $value => list( $status, $code ) ) {
			$response = $this->run_ability_response(
				'woocommerce/product-update',
				array(
					'id'         => $product->get_id(),
					'name'       => 'Pencil',
					'extensions' => array( 'test-ext' => array( 'color' => $value ) ),
				)
			);

			$this->assertSame( $status, $response->get_status(), $value );
			$this->assertSame( $code, $response->get_data()['code'], $value );
		}
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should check every extension value before any update callback runs, when a later value fails its schema.
	 */
	public function test_a_value_that_fails_its_schema_runs_no_update_callback(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		$response = $this->run_ability_response(
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
	 * @testdox Should run a sync once with every saved object, the primary object first, inside the transaction.
	 */
	public function test_a_sync_runs_once_with_every_saved_object(): void {
		$calls = array();
		$this->register_sync(
			'product',
			'test-ext',
			static function ( array $saved, ActionableAbility $ability ) use ( &$calls ) {
				$calls[] = array( $ability->get_name(), array_map( 'get_class', $saved ), array_map( static fn( \WC_Data $saved_object ) => $saved_object->get_id(), $saved ) );
				$prices  = array();
				foreach ( $saved as $object ) {
					if ( $object instanceof \WC_Product_Variation ) {
						$prices[] = (float) $object->get_regular_price();
					}
				}
				if ( $prices && $saved[0] instanceof \WC_Product_Variable ) {
					$saved[0]->update_meta_data( '_test_min_price', min( $prices ) );
					$saved[0]->save();
				}
				return null;
			}
		);

		$created = $this->run_ability( 'woocommerce/product-create', $this->get_shirt_input() );

		$this->assertCount( 1, $calls );
		$this->assertSame( 'woocommerce/product-create', $calls[0][0] );
		$this->assertSame( array( 'WC_Product_Variable', 'WC_Product_Variation', 'WC_Product_Variation' ), $calls[0][1] );
		$this->assertSame( array_merge( array( $created['product']['id'] ), $created['product']['variation_ids'] ), $calls[0][2] );
		$this->assertSame( '10', wc_get_product( $created['product']['id'] )->get_meta( '_test_min_price' ) );
	}

	/**
	 * @testdox Should give a sync the deleted objects and the input of the call.
	 */
	public function test_a_sync_gets_the_deleted_objects_and_the_input(): void {
		$contexts = array();
		$this->register_sync(
			'product',
			'test-ext',
			static function ( array $saved, ActionableAbility $ability, array $context ) use ( &$contexts ) {
				$contexts[] = array(
					'saved'   => array_map( static fn( \WC_Data $item ) => $item->get_id(), $saved ),
					'deleted' => array_map( static fn( \WC_Data $item ) => $item->get_id(), $context['deleted'] ),
					'trashed' => array_map( static fn( \WC_Data $item ) => $item->get_id(), $context['trashed'] ),
					'input'   => $context['input'],
				);
				return null;
			}
		);

		$created = $this->run_ability( 'woocommerce/product-create', $this->get_shirt_input() );
		$input   = array(
			'id'         => $created['product']['id'],
			'variations' => array(
				array(
					'id'     => $created['product']['variation_ids'][1],
					'delete' => true,
				),
			),
		);
		$this->run_ability( 'woocommerce/product-update', $input );
		$this->run_ability(
			'woocommerce/product-delete',
			array(
				'id'    => $created['product']['variation_ids'][0],
				'force' => true,
			)
		);
		$this->run_ability( 'woocommerce/product-delete', array( 'id' => $created['product']['id'] ) );
		$this->run_ability(
			'woocommerce/product-delete',
			array(
				'id'    => $created['product']['id'],
				'force' => true,
			)
		);

		$this->assertCount( 5, $contexts );
		$this->assertSame( array(), $contexts[0]['deleted'] );
		$this->assertSame( array( $created['product']['id'] ), $contexts[1]['saved'] );
		$this->assertSame( array( $created['product']['variation_ids'][1] ), $contexts[1]['deleted'] );
		$this->assertSame( $input, $contexts[1]['input'] );
		$this->assertSame( array( $created['product']['id'] ), $contexts[2]['saved'], 'A variation delete saves the parent.' );
		$this->assertSame( array( $created['product']['variation_ids'][0] ), $contexts[2]['deleted'] );
		$this->assertSame( array(), $contexts[3]['saved'] );
		$this->assertSame( array(), $contexts[3]['deleted'], 'A trash deletes nothing for good.' );
		$this->assertSame( array( $created['product']['id'] ), $contexts[3]['trashed'], 'A trash reports the parent as trashed.' );
		$this->assertSame( array( $created['product']['id'] ), $contexts[4]['deleted'], 'A parent delete reports the parent; its variations were deleted before.' );
		$this->assertSame( array(), $contexts[4]['trashed'] );
	}

	/**
	 * @testdox Should let a validator refuse a delete, and then delete nothing.
	 */
	public function test_a_validator_can_refuse_a_delete(): void {
		$this->register_validator(
			'product',
			'test-ext',
			static fn( \WC_Product $product, ActionableAbility $ability ) => 'woocommerce/product-delete' === $ability->get_name() && 'keep' === $product->get_sku()
				? new \WP_Error( 'test_keep', 'Keep it.' )
				: null
		);
		$created = $this->run_ability( 'woocommerce/product-create', $this->get_shirt_input() );
		$kept    = \WC_Helper_Product::create_simple_product( true, array( 'sku' => 'keep' ) );

		$refused = $this->run_ability_response(
			'woocommerce/product-delete',
			array(
				'id'    => $kept->get_id(),
				'force' => true,
			)
		);
		$deleted = $this->run_ability(
			'woocommerce/product-delete',
			array(
				'id'    => $created['product']['id'],
				'force' => true,
			)
		);

		$this->assertSame( 400, $refused->get_status() );
		$this->assertStringEndsWith( 'test_keep', $refused->get_data()['code'] );
		$this->assertInstanceOf( \WC_Product::class, wc_get_product( $kept->get_id() ) );
		$this->assertTrue( $deleted['deleted'] );
		$this->assertFalse( wc_get_product( $created['product']['id'] ) );
		$this->assertNull( get_post( $created['product']['variation_ids'][0] ), 'The variations go with the parent.' );
	}

	/**
	 * @testdox Should undo every save of the call when a sync returns a WP_Error.
	 */
	public function test_a_refusal_in_a_sync_rolls_back_the_call(): void {
		$this->register_sync(
			'product',
			'test-ext',
			static function ( array $saved ) {
				return $saved[0] instanceof \WC_Product_Variable
					? new \WP_Error( 'test_range_refused', 'The range is not allowed.', array( 'status' => 409 ) )
					: null;
			}
		);

		$response = $this->run_ability_response( 'woocommerce/product-create', $this->get_shirt_input() );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'test-ext:test_range_refused', $response->get_data()['code'] );
		$this->assertSame( 'test-ext', $response->get_data()['data']['namespace'] );
		$this->assertSame( 0, wc_get_product_id_by_sku( 'shirt-s' ) );
		$this->assertSame(
			array(),
			get_posts(
				array(
					'post_type'   => array( 'product', 'product_variation' ),
					'post_status' => 'any',
					'fields'      => 'ids',
				)
			)
		);
	}

	/**
	 * @testdox Should not run a sync with the feature off.
	 */
	public function test_a_sync_does_not_run_with_the_feature_off(): void {
		$fired = 0;
		$this->register_sync(
			'product',
			'test-ext',
			static function () use ( &$fired ) {
				++$fired;
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();
		$this->set_feature( false );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		$output = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assertNotEmpty( ExtensibleAbilities::get_syncs( 'product' ) );
		$this->assertSame( 'Pencil', $output['product']['name'] );
		$this->assertSame( 0, $fired );
	}

	/**
	 * @testdox Should not run a sync of a resource that the call did not save.
	 */
	public function test_a_sync_of_a_resource_that_was_not_saved_does_not_run(): void {
		$fired = 0;
		$this->register_sync(
			'order_item',
			'test-ext',
			static function () use ( &$fired ) {
				++$fired;
			}
		);

		$this->run_ability( 'woocommerce/product-create', $this->get_shirt_input() );

		$this->assertSame( 0, $fired );
	}

	/**
	 * @testdox Should refuse a sync on a resource that is not registered.
	 */
	public function test_a_sync_on_an_unregistered_resource_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_sync' );
		$this->register_sync( 'refund', 'test-ext', '__return_null' );
		$this->reset_registries();
		AbilitiesLoader::init_extensions();

		$this->assertEmpty( ExtensibleAbilities::get_syncs( 'refund' ) );
	}

	/**
	 * @testdox Should run the validators of a secondary object's own resource, and not those of the primary object's resource.
	 */
	public function test_a_secondary_object_gets_the_validators_of_its_own_resource(): void {
		$checked = array(
			'product'    => array(),
			'order_item' => array(),
		);
		foreach ( array_keys( $checked ) as $resource_name ) {
			$this->register_validator(
				$resource_name,
				'test-ext',
				static function ( \WC_Data $subject ) use ( &$checked, $resource_name ) {
					$checked[ $resource_name ][] = get_class( $subject );
					return null;
				}
			);
		}
		$this->register_product_with_item_ability();
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		$this->run_ability(
			'test-ext/product-with-item',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assertSame( array( 'WC_Product_Simple' ), $checked['product'] );
		$this->assertSame( array( 'WC_Order_Item_Product' ), $checked['order_item'] );
	}

	/**
	 * @testdox Should warn when a secondary object names a resource that is not registered, and still run the call.
	 */
	public function test_a_secondary_object_with_an_unregistered_resource_warns(): void {
		$this->setExpectedIncorrectUsage( TestProductWithItemAbility::class . '::change' );
		$this->register_product_with_item_ability();
		TestProductWithItemAbility::$item_resource = 'refund';
		$product                                   = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		try {
			$this->run_ability(
				'test-ext/product-with-item',
				array(
					'id'   => $product->get_id(),
					'name' => 'Pencil',
				)
			);
		} finally {
			TestProductWithItemAbility::$item_resource = 'order_item';
		}

		$this->assertSame( 'Pencil', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * Register the test ability that renames a product and returns an order item as a secondary object.
	 */
	private function register_product_with_item_ability(): void {
		$this->add_test_action(
			'wp_abilities_api_init',
			static function () {
				wp_register_ability(
					'test-ext/product-with-item',
					array(
						'label'               => 'Rename product with an item',
						'description'         => 'Renames a product and changes an order item.',
						'category'            => 'woocommerce',
						'input_schema'        => array(
							'type'       => 'object',
							'properties' => array(
								'id'   => array( 'type' => 'integer' ),
								'name' => array( 'type' => 'string' ),
							),
						),
						'output_schema'       => array( 'type' => 'object' ),
						'permission_callback' => '__return_true',
						'ability_class'       => TestProductWithItemAbility::class,
						'meta'                => array( 'show_in_rest' => true ),
					)
				);
			},
			20
		);
	}

	/**
	 * The input of a variable shirt with two variations.
	 *
	 * @return array
	 */
	private function get_shirt_input(): array {
		return array(
			'name'       => 'Shirt',
			'type'       => 'variable',
			'attributes' => array(
				array(
					'name'    => 'Size',
					'options' => array( 'S', 'M' ),
				),
			),
			'variations' => array(
				array(
					'attributes'    => array(
						array(
							'name'   => 'Size',
							'option' => 'S',
						),
					),
					'sku'           => 'shirt-s',
					'regular_price' => '10.00',
				),
				array(
					'attributes'    => array(
						array(
							'name'   => 'Size',
							'option' => 'M',
						),
					),
					'sku'           => 'shirt-m',
					'regular_price' => '12.00',
				),
			),
		);
	}

	/**
	 * @testdox Should write extension fields to each variation and run validators on them before any save.
	 */
	public function test_variations_get_extension_fields_and_validators(): void {
		$this->register_validator(
			'product',
			'other_ext',
			static function ( \WC_Product $subject ) {
				return $subject instanceof \WC_Product_Variation && 'red' === $subject->get_meta( '_test_color' )
					? new \WP_Error( 'test_red_variation', 'No red variations.' )
					: null;
			}
		);
		$input = array(
			'name'       => 'Shirt',
			'type'       => 'variable',
			'attributes' => array(
				array(
					'name'    => 'Size',
					'options' => array( 'S', 'M' ),
				),
			),
			'variations' => array(
				array(
					'attributes' => array(
						array(
							'name'   => 'Size',
							'option' => 'S',
						),
					),
					'extensions' => array( 'test-ext' => array( 'color' => 'blue' ) ),
				),
				array(
					'attributes' => array(
						array(
							'name'   => 'Size',
							'option' => 'M',
						),
					),
					'extensions' => array( 'test-ext' => array( 'color' => 'red' ) ),
				),
			),
		);

		$schema  = wp_get_ability( 'woocommerce/product-create' )->get_input_schema();
		$refused = $this->run_ability_response( 'woocommerce/product-create', $input );
		$saved   = get_posts(
			array(
				'post_type'   => array( 'product', 'product_variation' ),
				'post_status' => 'any',
				'fields'      => 'ids',
			)
		);

		$input['variations'][1]['extensions']['test-ext']['color'] = 'green';
		$created = $this->run_ability( 'woocommerce/product-create', $input );

		$this->assertArrayHasKey( 'color', $schema['properties']['variations']['items']['properties']['extensions']['properties']['test-ext']['properties'] );
		$this->assertSame( 400, $refused->get_status() );
		$this->assertStringEndsWith( 'test_red_variation', $refused->get_data()['code'] );
		$this->assertSame( array(), $saved );

		$colors = array_map(
			static function ( int $variation_id ): string {
				return wc_get_product( $variation_id )->get_meta( '_test_color' );
			},
			$created['product']['variation_ids']
		);
		$this->assertSame( array( 'blue', 'green' ), $colors );
	}

	/**
	 * @testdox Should call WC_Order::update_status() with the feature off and on.
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
			$this->assertSame( 1, $calls, $enabled ? 'feature on' : 'feature off' );
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

		$schema   = $this->get_ability( 'woocommerce/product-update' )['input_schema']['properties']['extensions']['properties']['other_ext']['properties']['size'];
		$rejected = $this->run_ability_response(
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

		$schema = $this->get_ability( 'test-ext/product-rename' )['input_schema']['properties']['extensions'];
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
	 * @testdox Should return a fixed 500 error when the default save of an ActionableAbility throws.
	 */
	public function test_a_failed_default_save_returns_a_fixed_error(): void {
		$product  = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$register = static function () {
			wp_register_ability(
				'test-ext/product-rename',
				array(
					'label'               => 'Rename product',
					'description'         => 'Renames a product.',
					'category'            => 'woocommerce',
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array( 'type' => 'integer' ),
							'name' => array( 'type' => 'string' ),
						),
					),
					'output_schema'       => array( 'type' => 'object' ),
					'permission_callback' => '__return_true',
					'ability_class'       => TestProductRenameAbility::class,
					'meta'                => array( 'show_in_rest' => true ),
				)
			);
		};
		$throw    = static function () {
			throw new \RuntimeException( 'Storage text.' );
		};
		add_action( 'wp_abilities_api_init', $register, 20 );
		add_action( 'woocommerce_before_product_object_save', $throw );

		$response = $this->run_ability_response(
			'test-ext/product-rename',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		remove_action( 'woocommerce_before_product_object_save', $throw );
		remove_action( 'wp_abilities_api_init', $register, 20 );
		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_ability_save_failed', $response->get_data()['code'] );
		$this->assertStringNotContainsString( 'Storage text.', wp_json_encode( $response->get_data() ) );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should list the extensions key in anyOf and allOf branches that refuse other properties, and accept extension fields through an anyOf branch.
	 */
	public function test_combinator_branches_accept_the_extensions_key(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$branch  = array(
			'type'                 => 'object',
			'properties'           => array(
				'id'   => array( 'type' => 'integer' ),
				'name' => array( 'type' => 'string' ),
			),
			'additionalProperties' => false,
		);
		foreach ( array( 'anyOf', 'allOf' ) as $keyword ) {
			$name     = 'test-ext/product-rename-' . strtolower( $keyword );
			$register = static function () use ( $name, $keyword, $branch ) {
				wp_register_ability(
					$name,
					array(
						'label'               => 'Rename product',
						'description'         => 'Renames a product.',
						'category'            => 'woocommerce',
						'input_schema'        => array(
							'type'   => 'object',
							$keyword => array( $branch ),
						),
						'output_schema'       => ExtensibleAbilities::add_fields_schema( array( 'type' => 'object' ), 'product' ),
						'permission_callback' => '__return_true',
						'ability_class'       => TestProductRenameAbility::class,
						'meta'                => array( 'show_in_rest' => true ),
					)
				);
			};
			add_action( 'wp_abilities_api_init', $register, 20 );
			$this->reset_registries();

			$schema = $this->get_ability( $name )['input_schema'];
			$this->assertSame( 'object', $schema[ $keyword ][0]['properties']['extensions']['type'] ?? null, $keyword );

			if ( 'anyOf' === $keyword ) {
				$output = $this->run_ability(
					$name,
					array(
						'id'         => $product->get_id(),
						'name'       => 'Pencil',
						'extensions' => array( 'test-ext' => array( 'color' => 'blue' ) ),
					)
				);
				$this->assertSame( array( 'color' => 'blue' ), $output['extensions']['test-ext'] );
			}

			remove_action( 'wp_abilities_api_init', $register, 20 );
		}
	}

	/**
	 * @testdox Should list the writable fields one time at the top level of the product input schemas, plus one time for the variations.
	 */
	public function test_input_schemas_list_writable_fields_one_time(): void {
		foreach ( array( 'woocommerce/product-create', 'woocommerce/product-update' ) as $name ) {
			$schema     = $this->get_ability( $name )['input_schema'];
			$extensions = $schema['properties']['extensions']['properties'];

			$this->assertSame( array( 'string', 'null' ), $extensions['test-ext']['properties']['color']['type'] );
			$this->assertSame( array( 'integer', 'null' ), $extensions['other_ext']['properties']['count']['type'] );
			$this->assertArrayNotHasKey( 'code', $extensions['test-ext']['properties'] );
			$this->assertSame( 2, substr_count( wp_json_encode( $schema ), '"color"' ) );
			$this->assertArrayNotHasKey( 'oneOf', $schema );
		}
		$order = $this->get_ability( 'woocommerce/order-update-status' )['input_schema'];
		$this->assertArrayNotHasKey( 'extensions', $order['properties'] );
		$delete = $this->get_ability( 'woocommerce/product-delete' )['input_schema'];
		$this->assertArrayNotHasKey( 'extensions', $delete['properties'], 'A delete writes no extension fields.' );
	}

	/**
	 * @testdox Should leave a field with object_types out of the other objects and refuse a write to them.
	 */
	public function test_object_types_limit_a_field_to_some_objects(): void {
		$register = static function () {
			ExtensibleAbilities::register_field(
				array(
					'resource'        => 'product',
					'namespace'       => 'test-ext',
					'field'           => 'plan',
					'schema'          => array( 'type' => 'string' ),
					'object_types'    => array( 'simple' ),
					'get_callback'    => static fn() => 'monthly',
					'update_callback' => static function ( \WC_Product $product, $value ) {
						$product->update_meta_data( '_test_plan', $value );
					},
				)
			);
		};
		add_action( 'woocommerce_extensible_abilities_init', $register );
		$simple  = \WC_Helper_Product::create_simple_product();
		$grouped = \WC_Helper_Product::create_grouped_product();

		try {
			$schema   = $this->get_ability( 'woocommerce/product-create' )['input_schema'];
			$rejected = $this->run_ability_response(
				'woocommerce/product-update',
				array(
					'id'         => $grouped->get_id(),
					'extensions' => array( 'test-ext' => array( 'plan' => 'yearly' ) ),
				)
			);
			$updated  = $this->run_ability(
				'woocommerce/product-update',
				array(
					'id'         => $simple->get_id(),
					'extensions' => array( 'test-ext' => array( 'plan' => 'yearly' ) ),
				)
			);
			$read     = $this->run_ability( 'woocommerce/products-query', array( 'id' => $grouped->get_id() ) );
		} finally {
			remove_action( 'woocommerce_extensible_abilities_init', $register );
		}

		$this->assertArrayHasKey( 'plan', $schema['properties']['extensions']['properties']['test-ext']['properties'] );
		$this->assertSame( 400, $rejected->get_status() );
		$this->assertStringEndsWith( 'woocommerce_ability_field_object_type', $rejected->get_data()['code'] );
		$this->assertSame( 'The extensions.test-ext.plan field does not apply to the grouped type. It applies to simple.', $rejected->get_data()['message'] );
		$this->assertSame( '', wc_get_product( $grouped->get_id() )->get_meta( '_test_plan' ) );
		$this->assertSame( 'monthly', $updated['product']['extensions']['test-ext']['plan'] );
		$this->assertSame( 'yearly', wc_get_product( $simple->get_id() )->get_meta( '_test_plan' ) );
		$this->assertArrayNotHasKey( 'plan', $read['products'][0]['extensions']['test-ext'] ?? array() );
	}

	/**
	 * @testdox Should list in the variation schemas only the fields that can apply to a variation.
	 */
	public function test_variation_schemas_list_only_the_fields_that_apply_to_a_variation(): void {
		$register = static function () {
			foreach ( array( 'simple_only' => array( 'simple' ), 'variation_only' => array( 'variation' ) ) as $field => $object_types ) {
				ExtensibleAbilities::register_field(
					array(
						'resource'        => 'product',
						'namespace'       => 'typed-ext',
						'field'           => $field,
						'schema'          => array( 'type' => 'string' ),
						'object_types'    => $object_types,
						'get_callback'    => static fn() => 'value',
						'update_callback' => static function ( \WC_Product $product, $value ) use ( $field ) {
							$product->update_meta_data( '_test_' . $field, $value );
						},
					)
				);
			}
			ExtensibleAbilities::register_field(
				array(
					'resource'     => 'product',
					'namespace'    => 'simple-only-ext',
					'field'        => 'plan',
					'schema'       => array( 'type' => 'string' ),
					'object_types' => array( 'simple' ),
					'get_callback' => static fn() => 'value',
				)
			);
		};
		add_action( 'woocommerce_extensible_abilities_init', $register );

		try {
			$ability = $this->get_ability( 'woocommerce/product-create' );
			$output  = $this->run_ability( 'woocommerce/product-create', $this->get_shirt_input() );
			$input   = $this->get_shirt_input();

			unset( $input['sku'], $input['variations'][0]['sku'], $input['variations'][1]['sku'] );
			$input['variations'][0]['extensions'] = array( 'typed-ext' => array( 'simple_only' => 'nope' ) );

			$refused = $this->run_ability_response( 'woocommerce/product-create', $input );
		} finally {
			remove_action( 'woocommerce_extensible_abilities_init', $register );
		}

		$this->assertSame( 400, $refused->get_status() );
		$this->assertStringEndsWith( 'woocommerce_ability_field_object_type', $refused->get_data()['code'] );
		$this->assertSame( 'The extensions.typed-ext.simple_only field does not apply to the variation type. It applies to simple.', $refused->get_data()['message'] );

		$input_namespaces  = $ability['input_schema']['properties']['variations']['items']['properties']['extensions']['properties'];
		$output_namespaces = $ability['output_schema']['properties']['variations']['items']['properties']['extensions']['properties'];
		$product_input     = $ability['input_schema']['properties']['extensions']['properties'];
		$this->assertArrayHasKey( 'simple_only', $product_input['typed-ext']['properties'] );
		$this->assertArrayHasKey( 'variation_only', $input_namespaces['typed-ext']['properties'] );
		$this->assertFalse( $ability['input_schema']['properties']['variations']['items']['properties']['extensions']['additionalProperties'] );
		$this->assertFalse( $input_namespaces['typed-ext']['additionalProperties'] );
		$this->assertArrayNotHasKey( 'simple_only', $input_namespaces['typed-ext']['properties'] );
		$this->assertArrayHasKey( 'color', $input_namespaces['test-ext']['properties'], 'A field without object_types stays.' );
		$this->assertArrayHasKey( 'variation_only', $output_namespaces['typed-ext']['properties'] );
		$this->assertArrayNotHasKey( 'simple_only', $output_namespaces['typed-ext']['properties'] );
		$this->assertArrayNotHasKey( 'simple-only-ext', $output_namespaces, 'A namespace with no field for a variation goes away.' );
		$this->assertSame( 'value', $output['variations'][0]['extensions']['typed-ext']['variation_only'] );
	}

	/**
	 * Register a variable product type with a variation class of its own, the way
	 * an extension does, and a field limited to that variation class.
	 *
	 * @internal
	 */
	private function register_variation_subclass(): void {
		$this->add_test_action(
			'product_type_selector',
			static fn( array $types ): array => $types + array( 'test-variable' => 'Test variable' )
		);
		$this->add_test_action(
			'woocommerce_data_stores',
			static fn( array $stores ): array => $stores + array(
				'product-test-variable'  => 'WC_Product_Variable_Data_Store_CPT',
				'product-test_variation' => 'WC_Product_Variation_Data_Store_CPT',
			)
		);
		$this->add_test_action(
			'woocommerce_product_class',
			static function ( $classname, $product_type, $post_type, $product_id ) {
				if ( 'test-variable' === $product_type ) {
					return TestVariableProduct::class;
				}
				if ( 'test_variation' === $product_type ) {
					return TestVariationProduct::class;
				}
				if ( 'variation' === $product_type && $product_id > 0 && has_term( 'test-variable', 'product_type', (int) wp_get_post_parent_id( $product_id ) ) ) {
					return TestVariationProduct::class;
				}
				return $classname;
			},
			10,
			4
		);
		$this->add_test_action(
			'woocommerce_ability_variation_product_type',
			static fn( string $type, \WC_Product_Variable $parent_product ): string => 'test-variable' === $parent_product->get_type() ? 'test_variation' : $type,
			10,
			2
		);
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'        => 'product',
						'namespace'       => 'test-ext',
						'field'           => 'plan',
						'schema'          => array( 'type' => 'string' ),
						'object_types'    => array( 'test_variation' ),
						'get_callback'    => static fn( \WC_Product $product ) => $product->get_meta( '_test_plan' ),
						'update_callback' => static function ( \WC_Product $product, $value ) {
							$product->update_meta_data( '_test_plan', $value );
						},
					)
				);
			}
		);
	}

	/**
	 * @testdox Should read a saved variation whose class extends the variation class.
	 */
	public function test_reads_a_variation_of_a_subclass(): void {
		$this->register_variation_subclass();

		$parent_product = new TestVariableProduct();
		$parent_product->set_name( 'Test variable' );
		$parent_product->save();
		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $parent_product->get_id() );
		$variation->save();

		$read = $this->run_ability( 'woocommerce/products-query', array( 'id' => $variation->get_id() ) );

		$this->assertInstanceOf( TestVariationProduct::class, wc_get_product( $variation->get_id() ) );
		$this->assertSame( 'test_variation', $read['products'][0]['type'] );
	}

	/**
	 * @testdox Should build a new variation with the class that the parent type asks for, and write a field limited to it.
	 */
	public function test_creates_variations_of_a_subclass_with_their_fields(): void {
		$this->register_variation_subclass();

		$input                                = $this->get_shirt_input();
		$input['type']                        = 'test-variable';
		$input['attributes'][0]['options'][]  = 'L';
		$input['variations'][0]['extensions'] = array( 'test-ext' => array( 'plan' => 'monthly' ) );
		$created                              = $this->run_ability( 'woocommerce/product-create', $input );
		$new_item                             = array(
			'attributes' => array(
				array(
					'name'   => 'Size',
					'option' => 'L',
				),
			),
			'extensions' => array( 'test-ext' => array( 'plan' => 'yearly' ) ),
		);
		$updated                              = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'         => $created['product']['id'],
				'variations' => array( $new_item ),
			)
		);
		$variation_id                         = $created['variations'][0]['id'];

		$this->assertSame( 'test-variable', $created['product']['type'] );
		$this->assertSame( 'test_variation', $created['variations'][0]['type'] );
		$this->assertSame( 'monthly', $created['variations'][0]['extensions']['test-ext']['plan'] );
		$this->assertInstanceOf( TestVariationProduct::class, wc_get_product( $variation_id ) );
		$this->assertSame( 'product_variation', get_post_type( $variation_id ) );
		$this->assertSame( 'monthly', wc_get_product( $variation_id )->get_meta( '_test_plan' ) );
		$this->assertSame( 'test_variation', $updated['variations'][0]['type'] );
		$this->assertSame( 'yearly', wc_get_product( $updated['variations'][0]['id'] )->get_meta( '_test_plan' ) );
	}

	/**
	 * @testdox Should build the variation class of the parent when a variation is created on its own.
	 */
	public function test_creates_a_single_variation_of_a_subclass(): void {
		$this->register_variation_subclass();

		$input               = $this->get_shirt_input();
		$input['type']       = 'test-variable';
		$input['variations'] = array();
		$parent_id           = $this->run_ability( 'woocommerce/product-create', $input )['product']['id'];

		$created = $this->run_ability(
			'woocommerce/product-create',
			array(
				'type'       => 'variation',
				'parent_id'  => $parent_id,
				'attributes' => array(
					array(
						'name'   => 'Size',
						'option' => 'S',
					),
				),
				'extensions' => array( 'test-ext' => array( 'plan' => 'monthly' ) ),
			)
		);

		$this->assertSame( 'test_variation', $created['product']['type'] );
		$this->assertSame( 'monthly', wc_get_product( $created['product']['id'] )->get_meta( '_test_plan' ) );
	}

	/**
	 * @testdox Should keep plain variations for a plain variable product, and refuse a field limited to a subclass on them.
	 */
	public function test_plain_variable_product_builds_plain_variations(): void {
		$this->register_variation_subclass();

		$created = $this->run_ability( 'woocommerce/product-create', $this->get_shirt_input() );

		$input = $this->get_shirt_input();
		unset( $input['sku'], $input['variations'][0]['sku'], $input['variations'][1]['sku'] );
		$input['variations'][0]['extensions'] = array( 'test-ext' => array( 'plan' => 'monthly' ) );
		$refused                              = $this->run_ability_response( 'woocommerce/product-create', $input );

		$this->assertSame( 'variation', $created['variations'][0]['type'] );
		$this->assertSame( \WC_Product_Variation::class, get_class( wc_get_product( $created['variations'][0]['id'] ) ) );
		$this->assertSame( 400, $refused->get_status() );
		$this->assertStringEndsWith( 'woocommerce_ability_field_object_type', $refused->get_data()['code'] );
	}

	/**
	 * Create a variable product of the subclass type and a plain one, with two variations each.
	 *
	 * @return array{subclass: array, plain: array}
	 */
	private function create_subclass_and_plain_products(): array {
		$this->register_variation_subclass();

		$input         = $this->get_shirt_input();
		$input['type'] = 'test-variable';
		foreach ( $input['variations'] as $index => $variation ) {
			$input['variations'][ $index ]['sku'] = 'test-' . $variation['sku'];
		}

		return array(
			'plain'    => $this->run_ability( 'woocommerce/product-create', $this->get_shirt_input() ),
			'subclass' => $this->run_ability( 'woocommerce/product-create', $input ),
		);
	}

	/**
	 * @testdox Should accept a variation subclass type in a query and return only the variations of its parent type.
	 */
	public function test_queries_variations_by_subclass_type(): void {
		$created = $this->create_subclass_and_plain_products();

		$found = $this->run_ability( 'woocommerce/products-query', array( 'type' => 'test_variation' ) )['products'];

		$this->assertEqualsCanonicalizing( array_column( $created['subclass']['variations'], 'id' ), array_column( $found, 'id' ) );
		$this->assertSame( array( 'test_variation' ), array_values( array_unique( array_column( $found, 'type' ) ) ) );
	}

	/**
	 * @testdox Should page a variation subclass query inside the database query.
	 */
	public function test_pages_a_variation_subclass_query(): void {
		$created = $this->create_subclass_and_plain_products();
		$query   = static fn( int $page ): array => array(
			'type'     => 'test_variation',
			'per_page' => 1,
			'page'     => $page,
		);

		$first  = $this->run_ability( 'woocommerce/products-query', $query( 1 ) );
		$second = $this->run_ability( 'woocommerce/products-query', $query( 2 ) );
		$third  = $this->run_ability( 'woocommerce/products-query', $query( 3 ) );

		$this->assertSame( 2, $first['total_pages'] );
		$this->assertCount( 1, $first['products'] );
		$this->assertCount( 1, $second['products'] );
		$this->assertSame( array(), $third['products'] );
		$this->assertEqualsCanonicalizing(
			array_column( $created['subclass']['variations'], 'id' ),
			array( $first['products'][0]['id'], $second['products'][0]['id'] )
		);
	}

	/**
	 * @testdox Should combine a variation subclass type with parent_id and extension query filters.
	 */
	public function test_combines_a_variation_subclass_query_with_other_limits(): void {
		$created = $this->create_subclass_and_plain_products();
		$picked  = array( $created['subclass']['variations'][0]['id'], $created['plain']['variations'][0]['id'] );
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( $picked ) {
				ExtensibleAbilities::register_query_filter(
					array(
						'resource'  => 'product',
						'namespace' => 'test-ext',
						'filter'    => 'picked',
						'schema'    => array( 'type' => 'boolean' ),
						'callback'  => static fn() => array( 'include_ids' => $picked ),
					)
				);
			}
		);
		$this->reset_registries();

		$own_parent   = $this->run_ability(
			'woocommerce/products-query',
			array(
				'type'      => 'test_variation',
				'parent_id' => $created['subclass']['product']['id'],
			)
		);
		$other_parent = $this->run_ability(
			'woocommerce/products-query',
			array(
				'type'      => 'test_variation',
				'parent_id' => $created['plain']['product']['id'],
			)
		);
		$filtered     = $this->run_ability(
			'woocommerce/products-query',
			array(
				'type'    => 'test_variation',
				'filters' => array( 'extensions' => array( 'test-ext' => array( 'picked' => true ) ) ),
			)
		);

		$this->assertEqualsCanonicalizing( array_column( $created['subclass']['variations'], 'id' ), array_column( $own_parent['products'], 'id' ) );
		$this->assertSame( array(), $other_parent['products'] );
		$this->assertSame( array( $picked[0] ), array_column( $filtered['products'], 'id' ) );
	}

	/**
	 * @testdox Should still return plain and subclass variations for the variation type.
	 */
	public function test_variation_type_query_returns_every_variation(): void {
		$created = $this->create_subclass_and_plain_products();

		$found = $this->run_ability( 'woocommerce/products-query', array( 'type' => 'variation' ) )['products'];

		$this->assertEqualsCanonicalizing(
			array_merge( array_column( $created['subclass']['variations'], 'id' ), array_column( $created['plain']['variations'], 'id' ) ),
			array_column( $found, 'id' )
		);
		$this->assertEqualsCanonicalizing( array( 'test_variation', 'variation' ), array_values( array_unique( array_column( $found, 'type' ) ) ) );
	}

	/**
	 * @testdox Should refuse a query type that no registered variable type builds.
	 */
	public function test_refuses_a_query_type_that_is_not_listed(): void {
		$unregistered = $this->run_ability_response( 'woocommerce/products-query', array( 'type' => 'test_variation' ) );

		$this->register_variation_subclass();
		$this->reset_registries();
		$unknown = $this->run_ability_response( 'woocommerce/products-query', array( 'type' => 'other_variation' ) );

		$this->assertSame( 400, $unregistered->get_status() );
		$this->assertSame( 400, $unknown->get_status() );
	}

	/**
	 * @testdox Should list a variation subclass type on the query input only.
	 */
	public function test_lists_a_variation_subclass_type_on_the_query_input_only(): void {
		$type_list = fn( string $name ): array => $this->get_ability( $name )['input_schema']['properties']['type']['enum'];
		$create    = $type_list( 'woocommerce/product-create' );
		$update    = $type_list( 'woocommerce/product-update' );

		$this->register_variation_subclass();
		$this->reset_registries();

		$this->assertContains( 'test_variation', $type_list( 'woocommerce/products-query' ) );
		$this->assertSame( $create, array_values( array_diff( $type_list( 'woocommerce/product-create' ), array( 'test-variable' ) ) ) );
		$this->assertSame( $update, array_values( array_diff( $type_list( 'woocommerce/product-update' ), array( 'test-variable' ) ) ) );
	}

	/**
	 * @testdox Should refuse a variation type that does not build a variation.
	 */
	public function test_refuses_a_variation_type_that_is_not_a_variation(): void {
		$this->add_test_action( 'woocommerce_ability_variation_product_type', static fn(): string => 'simple' );

		$refused = $this->run_ability_response( 'woocommerce/product-create', $this->get_shirt_input() );

		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 'woocommerce_invalid_product_type', $refused->get_data()['code'] );
	}

	/**
	 * @testdox Should not apply a field with object_types to an object without a callable get_type().
	 */
	public function test_a_field_with_object_types_does_not_apply_to_an_object_of_unknown_type(): void {
		$restricted   = array( 'object_types' => array( 'simple' ) );
		$unrestricted = array();
		$untyped      = new \stdClass();
		$hidden_type  = new class() {
			/**
			 * A type that callers cannot read.
			 *
			 * @return string
			 */
			private function get_type(): string {
				return 'simple';
			}
		};

		$this->assertFalse( ExtensibleAbilities::field_applies_to( $restricted, $untyped ) );
		$this->assertFalse( ExtensibleAbilities::field_applies_to( $restricted, $hidden_type ) );
		$this->assertTrue( ExtensibleAbilities::field_applies_to( $unrestricted, $untyped ) );
		$this->assertTrue( ExtensibleAbilities::field_applies_to( $unrestricted, $hidden_type ) );
	}

	/**
	 * @testdox Should not accept extension fields in the write abilities with the feature off.
	 */
	public function test_writes_do_not_change_with_the_feature_off(): void {
		$this->set_feature( false );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		$schema   = $this->get_ability( 'woocommerce/product-update' )['input_schema'];
		$rejected = $this->run_ability_response(
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

		$this->assertInstanceOf( ActionableAbility::class, wp_get_ability( 'woocommerce/product-update' ) );
		$this->assertArrayNotHasKey( 'extensions', $schema['properties'] ?? array() );
		$this->assertSame( 400, $rejected->get_status() );
		$this->assertSame( 'Pencil', $output['product']['name'] );
		$this->assertFalse( wc_get_product( $product->get_id() )->meta_exists( '_test_color' ) );
	}

	/**
	 * @testdox Should name the field and keep the message when an update_callback rejects a value.
	 */
	public function test_a_rejected_value_names_the_field(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$response = $this->run_ability_response(
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
	 * @testdox Should save nothing and run no status listeners when a validator refuses an order status change in an extension ability.
	 */
	public function test_a_validator_refuses_a_status_change(): void {
		$this->register_order_status_ability();
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

		$response = $this->run_ability_response(
			'test-ext/order-status',
			array(
				'id'     => $order->get_id(),
				'status' => 'completed',
			)
		);
		$allowed  = $this->run_ability(
			'test-ext/order-status',
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
		$this->assertSame( 'on-hold', $allowed['status'] );
		$this->assertSame( 'test-ext/order-status', $ability );
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

		$response = $this->run_ability_response(
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

		$response = $this->run_ability_response(
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
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
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

		$field     = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array( 'test-ext' => array( 'broken' => 'x' ) ),
			)
		);
		$validator = $this->run_ability_response(
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
		$this->register_validator(
			'product',
			'test-ext',
			static function () {
				return new \WP_Error( 'test_refused', 'Refused.' );
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();
		$this->set_feature( false );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		$output = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assertNotEmpty( ExtensibleAbilities::get_validators( 'product' ) );
		$this->assertSame( 'Pencil', $output['product']['name'] );
	}

	/**
	 * @testdox Should refuse a validator without a slug namespace or a callback, or on an unknown resource.
	 */
	public function test_invalid_validators_are_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_validator' );
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				$invalid = array(
					array(
						'resource' => 'order',
						'callback' => '__return_null',
					),
					array(
						'resource'  => 'order',
						'namespace' => 'test-ext',
					),
					array(
						'resource'  => 'refund',
						'namespace' => 'test-ext',
						'callback'  => '__return_null',
					),
				);
				foreach ( $invalid as $args ) {
					ExtensibleAbilities::register_validator( $args );
				}
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();

		$this->assertEmpty( ExtensibleAbilities::get_validators( 'order' ) );
		$this->assertEmpty( ExtensibleAbilities::get_validators( 'refund' ) );
	}

	/**
	 * @testdox Should refuse a validator that is registered after the woocommerce_extensible_abilities_init action.
	 */
	public function test_a_late_validator_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_validator' );
		$this->reset_registries();
		AbilitiesLoader::init_extensions();

		ExtensibleAbilities::register_validator(
			array(
				'resource'  => 'order',
				'namespace' => 'test-ext',
				'callback'  => '__return_null',
			)
		);

		$this->assertEmpty( ExtensibleAbilities::get_validators( 'order' ) );
	}

	/**
	 * Add an action that tearDown removes.
	 *
	 * @param string   $hook     Action name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $accepted_args Number of arguments the callback takes.
	 */
	private function add_test_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		add_action( $hook, $callback, $priority, $accepted_args );
		$this->test_actions[] = array( $hook, $callback, $priority );
	}

	/**
	 * Register a validator inside woocommerce_extensible_abilities_init, the way an extension does.
	 *
	 * @param string   $resource_name       Resource.
	 * @param string   $extension_namespace Extension namespace.
	 * @param callable $callback            Validator.
	 */
	private function register_validator( string $resource_name, string $extension_namespace, callable $callback ): void {
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( $resource_name, $extension_namespace, $callback ) {
				ExtensibleAbilities::register_validator(
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
	 * Register a sync inside woocommerce_extensible_abilities_init, the way an extension does.
	 *
	 * @param string   $resource_name       Resource.
	 * @param string   $extension_namespace Extension namespace.
	 * @param callable $callback            Sync.
	 */
	private function register_sync( string $resource_name, string $extension_namespace, callable $callback ): void {
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( $resource_name, $extension_namespace, $callback ) {
				ExtensibleAbilities::register_sync(
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
	 * Register test-ext/order-status, an ActionableAbility of an extension that changes orders.
	 */
	private function register_order_status_ability(): void {
		$this->add_test_action(
			'wp_abilities_api_init',
			static function () {
				wp_register_ability(
					'test-ext/order-status',
					array(
						'label'               => 'Change order status',
						'description'         => 'Changes an order status.',
						'category'            => 'woocommerce',
						'input_schema'        => array(
							'type'                 => 'object',
							'properties'           => array(
								'id'     => array( 'type' => 'integer' ),
								'status' => array( 'type' => 'string' ),
							),
							'additionalProperties' => false,
						),
						'output_schema'       => array( 'type' => 'object' ),
						'permission_callback' => '__return_true',
						'ability_class'       => TestOrderStatusAbility::class,
						'meta'                => array( 'show_in_rest' => true ),
					)
				);
			},
			20
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
						'output_schema'       => ExtensibleAbilities::add_fields_schema( array( 'type' => 'object' ), 'product' ),
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
		$response = $this->run_ability_response( $name, $input );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		return $response->get_data();
	}

	/**
	 * Run an ability through the REST route.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Ability input.
	 * @return \WP_REST_Response
	 */
	private function run_ability_response( string $name, array $input ): \WP_REST_Response {
		$annotations = wp_get_ability( $name )->get_meta()['annotations'] ?? array();
		$readonly    = ! empty( $annotations['readonly'] );
		$method      = $readonly ? 'GET' : ( ! empty( $annotations['destructive'] ) && ! empty( $annotations['idempotent'] ) ? 'DELETE' : 'POST' );
		$request     = new \WP_REST_Request( $method, '/wp-abilities/v1/abilities/' . $name . '/run' );
		if ( 'POST' !== $method ) {
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
		return $this->get_ability( $name )['output_schema'];
	}

	/**
	 * Read an ability through the REST route.
	 *
	 * @param string $name Ability name.
	 * @return array
	 */
	private function get_ability( string $name ): array {
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
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );
	}

	/**
	 * Drop every registered field and ability, so the next read fires the init actions again.
	 */
	private function reset_registries(): void {
		global $wp_actions;

		foreach ( array( 'fields', 'reported', 'validators', 'syncs', 'filters' ) as $property ) {
			$reflection = new \ReflectionProperty( ExtensibleAbilities::class, $property );
			$reflection->setAccessible( true );
			$reflection->setValue( null, array() );
		}

		$reflection = new \ReflectionProperty( ExtensibleAbilities::class, 'resources' );
		$reflection->setAccessible( true );
		$reflection->setValue( null, array() );

		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'], $wp_actions['woocommerce_extensible_abilities_resources_init'], $wp_actions['woocommerce_extensible_abilities_init'] );
	}
}
