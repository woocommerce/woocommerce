<?php
/**
 * AbilityResourcesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Abilities;

use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Tests\Internal\AbilitiesApi\TestOrderStatusAbility;
use Automattic\WooCommerce\Tests\Internal\AbilitiesApi\TestProductRenameAbility;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Namespaced extension fields in the product and order abilities, read and written through the REST route.
 */
class AbilityResourcesTest extends \WC_REST_Unit_Test_Case {

	use AbilityRegistriesTrait;
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
			'product',
			'test-ext',
			'code',
			array(
				'schema'       => self::CODE_SCHEMA,
				'get_callback' => static function ( \WC_Product $product ) {
					$code = $product->get_meta( '_test_code' );
					return '' === $code ? null : $code;
				},
			)
		);
		ExtensibleAbilities::register_field(
			'product',
			'other_ext',
			'code',
			array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static function ( \WC_Product $product ) {
					return '' === $product->get_meta( '_test_code' ) ? null : 7;
				},
			)
		);
		ExtensibleAbilities::register_field(
			'order',
			'test-ext',
			'note',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static function ( \WC_Order $order ) {
					return 'order-' . $order->get_id();
				},
			)
		);
		ExtensibleAbilities::register_field(
			'order_item',
			'test-ext',
			'gift',
			array(
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
				$resource,
				$namespace,
				$field,
				array(
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
			'product',
			'test-ext',
			'has_code',
			array( 'type' => 'boolean' ),
			static function ( bool $has_code ) {
				return array(
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => '_test_code',
							'compare' => $has_code ? 'EXISTS' : 'NOT EXISTS',
						),
					),
				);
			}
		);
		ExtensibleAbilities::register_query_filter(
			'product',
			'test-ext',
			'category',
			array( 'type' => 'string' ),
			static function ( string $slug ) {
				return array(
					'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'slug',
							'terms'    => $slug,
						),
					),
				);
			}
		);
		ExtensibleAbilities::register_query_filter(
			'order',
			'test-ext',
			'tag',
			array( 'type' => 'string' ),
			static function ( string $tag ) {
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
			}
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
			'order',
			'test-ext',
			'without',
			array(
				'type'  => 'array',
				'items' => array( 'type' => 'integer' ),
			),
			static fn( array $ids ) => array( 'exclude_ids' => $ids )
		);
		ExtensibleAbilities::register_query_filter(
			'order',
			'test-ext',
			'contains_product',
			array( 'type' => 'integer' ),
			static function ( int $product_id ) {
				global $wpdb;
				return array(
					'include_ids' => $wpdb->get_col(
						$wpdb->prepare(
							"SELECT DISTINCT items.order_id FROM {$wpdb->prefix}woocommerce_order_items AS items INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta ON meta.order_item_id = items.order_item_id WHERE meta.meta_key = '_product_id' AND meta.meta_value = %d",
							$product_id
						)
					),
				);
			}
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
						'product',
						'test-ext',
						$filter,
						'picked' === $filter ? array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						) : array( 'type' => 'boolean' ),
						$callback
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
						$resource,
						'test-ext',
						$filter,
						array( 'type' => 'boolean' ),
						$callback
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
					'product',
					'test-ext',
					'empty_clause',
					array( 'type' => 'boolean' ),
					static fn() => array( 'meta_query' => array() ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				);
			}
		);
		$this->get_ability( 'woocommerce/products-query' )['input_schema'];

		$this->assertSame( array(), $this->get_query_args( 'product', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'empty_clause' => true ) ) ) ) ) );
	}

	/**
	 * @testdox Should refuse a filter on a resource that is not registered, and accept one on a registered resource.
	 */
	public function test_a_filter_on_an_unsupported_resource_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_query_filter' );
		$input  = array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'gift' => true ) ) ) );
		$filter = static function () {
			ExtensibleAbilities::register_query_filter(
				'ticket',
				'test-ext',
				'gift',
				array( 'type' => 'boolean' ),
				static fn() => array( 'meta_query' => array( array( 'key' => 'gift' ) ) ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			);
		};

		$filter();
		$this->assertSame( array(), $this->get_query_args( 'ticket', $input ) );

		$register_resource = static function () {
			AbilityResources::register_resource( 'ticket' );
		};
		add_action( 'woocommerce_extensible_abilities_resources_init', $register_resource );
		add_action( 'woocommerce_extensible_abilities_init', $filter );
		$this->get_ability( 'woocommerce/products-query' )['input_schema'];
		remove_action( 'woocommerce_extensible_abilities_resources_init', $register_resource );
		remove_action( 'woocommerce_extensible_abilities_init', $filter );

		$this->assertSame( array( 'meta_query' => array( array( array( 'key' => 'gift' ) ) ) ), $this->get_query_args( 'ticket', $input ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}

	/**
	 * @testdox Should refuse a filter whose namespace or name is only numbers.
	 */
	public function test_a_filter_with_a_numeric_slug_is_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_query_filter' );
		$register = static function () {
			foreach ( array( array( '0', 'gift' ), array( 'test-ext', '42' ) ) as list( $extension_namespace, $filter ) ) {
				ExtensibleAbilities::register_query_filter(
					'product',
					$extension_namespace,
					$filter,
					array( 'type' => 'boolean' ),
					static fn() => array( 'meta_query' => array( array( 'key' => 'gift' ) ) ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
				'order',
				'test-ext',
				'tags',
				array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'uniqueItems' => true,
				),
				static function () use ( &$called ) {
					$called = true;
					return array();
				}
			);
		};
		add_action( 'woocommerce_extensible_abilities_init', $register );
		$this->get_ability( 'woocommerce/orders-query' );
		remove_action( 'woocommerce_extensible_abilities_init', $register );

		$result = $this->get_query_args( 'order', array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'tags' => array( 'gift', 'gift' ) ) ) ) ) );

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
			'product',
			'late-ext',
			'gift',
			array( 'type' => 'boolean' ),
			static fn() => array( 'meta_query' => array( array( 'key' => 'gift' ) ) ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		);

		$this->assertSame( array(), $this->get_query_args( 'product', array( 'filters' => array( 'extensions' => array( 'late-ext' => array( 'gift' => true ) ) ) ) ) );
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
		$this->assertSame( array(), AbilityResources::output_schema( 'customer' )['properties'] );
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
			'product',
			'late-ext',
			'code',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => 'late',
			)
		);
		$products = array_column( $this->run_ability( 'woocommerce/products-query', array( 'search' => 'Pen late' ) )['products'], null, 'id' );

		$this->assertArrayNotHasKey( 'late-ext', $products[ $product->get_id() ]['extensions'] ?? array() );
		$this->assertArrayNotHasKey( 'late-ext', $this->get_extension_values( 'product', $product ) ?? array() );
	}

	/**
	 * @testdox Should let an extension declare its own resource with root fields, the same way Core does.
	 */
	public function test_an_extension_resource_has_root_fields(): void {
		$register_resource = static function () {
			AbilityResources::register_resource( 'ticket' );
			AbilityResources::register_field(
				'ticket',
				'id',
				array(
					'schema'       => array( 'type' => 'integer' ),
					'get_callback' => static fn( array $ticket ) => $ticket['id'],
				)
			);
			AbilityResources::register_field(
				'ticket',
				'email',
				array(
					'schema'        => array( 'type' => 'string' ),
					'get_callback'  => static fn( array $ticket ) => $ticket['email'],
					'personal_data' => true,
				)
			);
		};
		add_action( 'woocommerce_extensible_abilities_resources_init', $register_resource );
		$this->withhold_personal_data();
		$this->get_output_schema( 'woocommerce/products-query' );
		remove_action( 'woocommerce_extensible_abilities_resources_init', $register_resource );

		$output = AbilityResources::output_data(
			'ticket',
			array(
				'id'    => 7,
				'email' => 'jane@example.com',
			)
		);

		$this->assertSame( array( 'id' => 7 ), $output );
		$this->assertSame( array( 'id' ), array_keys( AbilityResources::output_schema( 'ticket' )['properties'] ) );
	}

	/**
	 * @testdox Should refuse a root field that an extension adds to a Core resource, or after the init action.
	 */
	public function test_root_fields_on_resources_of_others_are_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$register = static function () {
			AbilityResources::register_field(
				'product',
				'early',
				array(
					'schema'       => array( 'type' => 'string' ),
					'get_callback' => static fn() => 'x',
				)
			);
		};
		$late     = static function () {
			AbilityResources::register_field(
				'product',
				'late',
				array(
					'schema'       => array( 'type' => 'string' ),
					'get_callback' => static fn() => 'x',
				)
			);
		};
		add_action( 'woocommerce_extensible_abilities_resources_init', $register );
		add_action( 'woocommerce_extensible_abilities_init', $late );
		$schema = $this->get_output_schema( 'woocommerce/products-query' )['properties']['products']['items']['properties'];
		remove_action( 'woocommerce_extensible_abilities_resources_init', $register );
		remove_action( 'woocommerce_extensible_abilities_init', $late );

		$this->assertArrayNotHasKey( 'early', $schema );
		$this->assertArrayNotHasKey( 'late', $schema );
		$this->assertArrayHasKey( 'name', $schema );
	}

	/**
	 * @testdox Should refuse a root field that an extension adds to its own resource after the resources action.
	 */
	public function test_root_fields_on_an_extension_resource_close_after_the_resources_action(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$register_resource = static function () {
			AbilityResources::register_resource( 'ticket' );
		};
		$late              = static function () {
			AbilityResources::register_field(
				'ticket',
				'late',
				array(
					'schema'       => array( 'type' => 'string' ),
					'get_callback' => static fn() => 'x',
				)
			);
		};
		add_action( 'woocommerce_extensible_abilities_resources_init', $register_resource );
		add_action( 'woocommerce_extensible_abilities_init', $late );
		$this->get_output_schema( 'woocommerce/products-query' );
		remove_action( 'woocommerce_extensible_abilities_resources_init', $register_resource );
		remove_action( 'woocommerce_extensible_abilities_init', $late );

		$this->assertTrue( AbilityResources::is_registered( 'ticket' ) );
		$this->assertArrayNotHasKey( 'late', AbilityResources::output_schema( 'ticket' )['properties'] );
	}

	/**
	 * @testdox Should refuse a root field on a Core resource after Core registered it, also while the feature is off.
	 */
	public function test_root_fields_on_core_resources_are_refused_while_the_feature_is_off(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'no' );
		AbilitiesLoader::register_core_resources();

		AbilityResources::register_field(
			'product',
			'early',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => 'x',
			)
		);

		$this->assertArrayNotHasKey( 'early', AbilityResources::output_schema( 'product' )['properties'] );
	}

	/**
	 * @testdox Should refuse a root field named extensions, which would clash with the extension fields.
	 */
	public function test_a_root_field_named_extensions_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$this->register_resources( 'ticket' );

		AbilityResources::register_field(
			'ticket',
			'extensions',
			array(
				'schema'       => array( 'type' => 'object' ),
				'get_callback' => static fn() => array(),
			)
		);

		$this->assertSame( array(), AbilityResources::output_schema( 'ticket' )['properties'] );
	}

	/**
	 * @testdox Should refuse a $ref that the registry would not resolve: in an extension field, or under oneOf in a root field.
	 */
	public function test_an_unresolved_ref_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		AbilitiesLoader::register_core_resources();
		$this->register_resources( 'ticket' );

		ExtensibleAbilities::register_field(
			'product',
			'test-ext',
			'related',
			array(
				'schema'       => array(
					'type' => 'object',
					'$ref' => 'product',
				),
				'get_callback' => static fn() => null,
			)
		);
		AbilityResources::register_field(
			'ticket',
			'product',
			array(
				'schema'       => array(
					'type'  => array( 'object', 'null' ),
					'oneOf' => array(
						array( '$ref' => 'product' ),
						array( 'type' => 'null' ),
					),
				),
				'get_callback' => static fn() => null,
			)
		);

		$this->assertArrayNotHasKey( 'extensions', AbilityResources::output_schema( 'product' )['properties'] );
		$this->assertSame( array(), AbilityResources::output_schema( 'ticket' )['properties'] );
	}

	/**
	 * @testdox Should report a $ref to a resource that is not registered once, and fall back to a plain object.
	 */
	public function test_a_ref_to_an_unregistered_resource_is_reported(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::resolve_schema' );
		$this->register_resources( 'ticket' );
		AbilityResources::register_field(
			'ticket',
			'event',
			array(
				'schema'       => array( '$ref' => 'event' ),
				'get_callback' => static fn() => array( 'id' => 1 ),
			)
		);

		$reports = 0;
		add_action(
			'doing_it_wrong_run',
			static function ( $function_name ) use ( &$reports ) {
				$reports += AbilityResources::class . '::resolve_schema' === $function_name ? 1 : 0;
			}
		);

		$schema = AbilityResources::output_schema( 'ticket' );
		AbilityResources::output_schema( 'ticket' );
		$output = AbilityResources::output_data( 'ticket', array() );

		$this->assertSame( 1, $reports );
		$this->assertSame( array( 'type' => 'object' ), $schema['properties']['event'] );
		$this->assertEquals( new \stdClass(), $output['event'] );
	}

	/**
	 * @testdox Should refuse a namespace or an update_callback on a root field, since only extension fields have them.
	 */
	public function test_a_root_field_with_extension_field_arguments_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$this->register_resources( 'ticket' );

		AbilityResources::register_field(
			'ticket',
			'id',
			array(
				'schema'          => array( 'type' => 'integer' ),
				'get_callback'    => static fn() => 1,
				'update_callback' => '__return_null',
			)
		);
		AbilityResources::register_field(
			'ticket',
			'code',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => 'T1',
				'namespace'    => 'test-ext',
			)
		);

		$this->assertSame( array(), AbilityResources::output_schema( 'ticket' )['properties'] );
	}

	/**
	 * @testdox Should let an extension register fields on a resource that another extension registers, whatever the hook priorities.
	 */
	public function test_register_resource_lets_an_extension_register_fields_on_its_own_resource(): void {
		$register_resource = static function () {
			AbilityResources::register_resource( 'ticket' );
		};
		$register_field    = static function () {
			ExtensibleAbilities::register_field(
				'ticket',
				'test-ext',
				'code',
				array(
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

		$values = $this->get_extension_values( 'ticket', new \stdClass() );
		$schema = AbilityResources::output_schema( 'ticket' );

		$this->assertSame( array( 'test-ext' => array( 'code' => 'T1' ) ), $values );
		$this->assertSame( array( 'type' => 'string' ), $schema['properties']['extensions']['properties']['test-ext']['properties']['code'] );
	}

	/**
	 * @testdox Should refuse a resource that is registered after the resources action.
	 */
	public function test_a_resource_registered_after_the_resources_action_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_resource' );
		$this->get_output_schema( 'woocommerce/products-query' );

		AbilityResources::register_resource( 'late_resource' );

		$this->assertFalse( AbilityResources::is_registered( 'late_resource' ) );
	}

	/**
	 * @testdox Should refuse a Core resource registered before Core, also while the feature is off.
	 */
	public function test_a_core_resource_claimed_before_core_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_resource' );
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_field' );
		$this->set_feature( false );

		AbilityResources::register_resource( 'order' );
		AbilityResources::register_field(
			'order',
			'billing_email',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => 'jane@example.com',
			)
		);
		AbilitiesLoader::register_core_resources();
		$shared = AbilityResources::output_schema( 'order' )['properties'];
		$this->withhold_personal_data();
		$withheld = AbilityResources::output_schema( 'order' )['properties'];

		$this->assertSame( array( 'string', 'null' ), $shared['billing_email']['type'] );
		$this->assertArrayNotHasKey( 'billing_email', $withheld );
		$this->assertArrayHasKey( 'total', $withheld );
	}

	/**
	 * @testdox Should refuse a resource name made only of digits.
	 */
	public function test_a_resource_name_of_only_digits_is_refused(): void {
		$this->setExpectedIncorrectUsage( AbilityResources::class . '::register_resource' );

		$this->register_resources( '42' );

		$resources = new \ReflectionProperty( AbilityResources::class, 'resources' );
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
			'product',
			'test-ext',
			'code',
			array(
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
					'output_schema'       => AbilityResources::output_schema( 'product', array( 'id', 'extensions' ) ),
					'execute_callback'    => static function ( array $input ) {
						return AbilityResources::output_data( 'product', wc_get_product( $input['id'] ), array( 'id', 'extensions' ) );
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
			'schema'       => array( 'type' => 'string' ),
			'get_callback' => static fn() => 'second',
		);
		AbilitiesLoader::register_core_resources();
		$this->register_test_fields();

		ExtensibleAbilities::register_field( 'order', 'test-ext', 'note', $valid );
		ExtensibleAbilities::register_field( 'order', 'Test Ext', 'note', $valid );
		ExtensibleAbilities::register_field( 'order', '0', 'note', $valid );
		ExtensibleAbilities::register_field( 'order', 'test-ext', '', $valid );
		ExtensibleAbilities::register_field( 'order', 'test-ext', '123', $valid );
		ExtensibleAbilities::register_field( 'line_item', 'test-ext', 'other', $valid );
		ExtensibleAbilities::register_field( 'order', 'test-ext', 'other', array_merge( $valid, array( 'schema' => null ) ) );
		ExtensibleAbilities::register_field( 'order', 'test-ext', 'other', array_merge( $valid, array( 'schema' => array( 'description' => 'x' ) ) ) );
		ExtensibleAbilities::register_field(
			'order',
			'test-ext',
			'other',
			array_merge(
				$valid,
				array(
					'schema' => array(
						'type'     => 'string',
						'required' => true,
					),
				)
			)
		);
		ExtensibleAbilities::register_field( 'order', 'test-ext', 'other', array_merge( $valid, array( 'get_callback' => 'not_a_function' ) ) );
		ExtensibleAbilities::register_field( 'order', 'test-ext', 'other', array_merge( $valid, array( 'update_callback' => 'not_a_function' ) ) );
		ExtensibleAbilities::register_field( 'order', 'test-ext', 'other', array_merge( $valid, array( 'personal_data' => 'yes' ) ) );
		$order = \WC_Helper_Order::create_order();

		$this->assertSame(
			array( 'test-ext' => array( 'note' => 'order-' . $order->get_id() ) ),
			$this->get_extension_values( 'order', $order )
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
				'order',
				'values',
				$field,
				array(
					'schema'       => $args['schema'],
					'get_callback' => $args['get_callback'],
				)
			);
		}
		$order = \WC_Helper_Order::create_order();

		$values = $this->get_extension_values( 'order', $order );

		$this->assertSame(
			array(
				'count' => 12,
				'empty' => null,
			),
			$values['values']
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
					'product',
					'test-ext',
					'count',
					array(
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
	 * @testdox Should leave line items out of orders-query unless asked.
	 */
	public function test_orders_query_leaves_line_items_out_unless_asked(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();

		$queried = $this->run_ability( 'woocommerce/orders-query', array( 'id' => $order->get_id() ) );
		$asked   = $this->run_ability(
			'woocommerce/orders-query',
			array(
				'id'                 => $order->get_id(),
				'include_line_items' => true,
			)
		);

		$this->assertArrayNotHasKey( 'line_items', $queried['orders'][0] );
		$this->assertNotEmpty( $asked['orders'][0]['line_items'] );
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
		$this->assertSame( array( 'color' => 'green' ), $output['product']['extensions']['test-ext'] );
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
				$this->assertSame( array( 'color' => 'blue' ), $output['product']['extensions']['test-ext'] );
			}

			remove_action( 'wp_abilities_api_init', $register, 20 );
		}
	}

	/**
	 * @testdox Should list the writable fields one time at the top level of the product input schemas.
	 */
	public function test_input_schemas_list_writable_fields_one_time(): void {
		foreach ( array( 'woocommerce/product-create', 'woocommerce/product-update' ) as $name ) {
			$schema     = $this->get_ability( $name )['input_schema'];
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
		$order = $this->get_ability( 'woocommerce/order-update-status' )['input_schema'];
		$this->assertArrayNotHasKey( 'extensions', $order['properties'] );
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
					'product',
					'test-ext',
					'broken',
					array(
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
		AbilitiesLoader::register_core_resources();
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
	 * @testdox Should refuse a validator without a slug namespace, or on an unknown resource.
	 */
	public function test_invalid_validators_are_refused(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_validator' );
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_validator( 'order', '', '__return_null' );
				ExtensibleAbilities::register_validator( 'order', 'Test Ext', '__return_null' );
				ExtensibleAbilities::register_validator( 'refund', 'test-ext', '__return_null' );
			}
		);
		$this->reset_registries();
		AbilitiesLoader::register_core_resources();
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
		AbilitiesLoader::register_core_resources();
		AbilitiesLoader::init_extensions();

		ExtensibleAbilities::register_validator(
			'order',
			'test-ext',
			'__return_null'
		);

		$this->assertEmpty( ExtensibleAbilities::get_validators( 'order' ) );
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
					$resource_name,
					$extension_namespace,
					$callback
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
	 * Extension field values of an object, keyed by namespace, then field.
	 *
	 * @param string $resource_name Resource.
	 * @param object $subject       Object to read.
	 * @return array|null Null when no extension returned a value.
	 */
	private function get_extension_values( string $resource_name, $subject ): ?array {
		$output = AbilityResources::output_data( $resource_name, $subject, array( 'extensions' ) );
		return $output['extensions'] ?? null;
	}

	/**
	 * Query arguments that the extension filters of a resource add for an input.
	 *
	 * @param string $resource_name Resource.
	 * @param array  $input         Ability input.
	 * @return array|\WP_Error
	 */
	private function get_query_args( string $resource_name, array $input ) {
		return ExtensibleAbilities::get_query_args( $resource_name, $input );
	}

	/**
	 * Withhold personal data for the rest of the test, also where it was already decided.
	 */
	private function withhold_personal_data(): void {
		add_filter( 'woocommerce_abilities_share_personal_data', '__return_false' );
		$this->forget_personal_data_decisions();
	}

	/**
	 * Turn the feature on or off.
	 *
	 * @param bool $enabled Whether the feature is on.
	 */
	private function set_feature( bool $enabled ): void {
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );
	}
}
