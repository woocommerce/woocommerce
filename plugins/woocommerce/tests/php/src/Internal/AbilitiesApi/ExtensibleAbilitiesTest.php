<?php
/**
 * ExtensibleAbilitiesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Enums\ProductStatus;
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

		$this->original_init_count = $wp_actions['init'] ?? null;
		$wp_actions['init']        = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::setUp();

		wc_get_container()->get( AbilitiesLoader::class );
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
				'resource'      => 'product',
				'namespace'     => 'test-ext',
				'field'         => 'code',
				'output_schema' => self::CODE_SCHEMA,
				'get_callback'  => static function ( \WC_Product $product ) {
					$code = $product->get_meta( '_test_code' );
					return '' === $code ? null : $code;
				},
			)
		);
		ExtensibleAbilities::register_field(
			array(
				'resource'      => 'product',
				'namespace'     => 'other_ext',
				'field'         => 'code',
				'output_schema' => array( 'type' => 'integer' ),
				'get_callback'  => static function ( \WC_Product $product ) {
					return '' === $product->get_meta( '_test_code' ) ? null : 7;
				},
			)
		);
		ExtensibleAbilities::register_field(
			array(
				'resource'      => 'order',
				'namespace'     => 'test-ext',
				'field'         => 'note',
				'output_schema' => array( 'type' => 'string' ),
				'get_callback'  => static function ( \WC_Order $order ) {
					return 'order-' . $order->get_id();
				},
			)
		);
		ExtensibleAbilities::register_field(
			array(
				'resource'      => 'order_item',
				'namespace'     => 'test-ext',
				'field'         => 'gift',
				'output_schema' => array( 'type' => 'string' ),
				'get_callback'  => static function ( \WC_Order_Item $item ) {
					return 'item-' . $item->get_id();
				},
			)
		);
		$writable = array(
			array( 'product', 'test-ext', 'color', array( 'type' => 'string' ) ),
			array( 'order', 'test-ext', 'gift_message', array( 'type' => 'string' ) ),
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
					'resource'      => $resource,
					'namespace'     => $namespace,
					'field'         => $field,
					'output_schema' => $schema,
					'input_schema'  => self::nullable( $schema ),
					'get_callback'  => static function ( \WC_Data $data ) use ( $field ) {
						return $data->meta_exists( '_test_' . $field ) ? $data->get_meta( '_test_' . $field ) : null;
					},
					'set_callback'  => static function ( \WC_Data $data, $value ) use ( $field ) {
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
	 * The schema of a writable test field, which declares null to delete the value.
	 *
	 * @param array<string, mixed> $schema Schema of the value.
	 * @return array<string, mixed>
	 */
	private static function nullable( array $schema ): array {
		$top = array_intersect_key( $schema, array( 'description' => true ) );
		return $top + array( 'anyOf' => array( array_diff_key( $schema, $top ), array( 'type' => 'null' ) ) );
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
	 * @testdox Should combine Core's term filters with an extension's OR tax_query with AND.
	 */
	public function test_term_filters_combine_with_extension_or_tax_query(): void {
		$gifts  = wp_insert_term( 'Gifts', 'product_cat' )['term_id'];
		$sale   = wp_insert_term( 'Sale', 'product_cat' )['term_id'];
		$summer = wp_insert_term( 'Summer', 'product_tag' )['term_id'];
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_query_filter(
					array(
						'resource'  => 'product',
						'namespace' => 'test-ext',
						'filter'    => 'gifts_or_sale',
						'schema'    => array( 'type' => 'boolean' ),
						'callback'  => static function () {
							return array(
								'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
									array(
										'relation' => 'OR',
										array(
											'taxonomy' => 'product_cat',
											'field'    => 'slug',
											'terms'    => 'gifts',
										),
										array(
											'taxonomy' => 'product_cat',
											'field'    => 'slug',
											'terms'    => 'sale',
										),
									),
								),
							);
						},
					)
				);
			}
		);
		$matched = array();
		foreach ( array( array( $gifts ), array( $sale ) ) as $categories ) {
			$product = \WC_Helper_Product::create_simple_product();
			$product->set_category_ids( $categories );
			$product->set_tag_ids( array( $summer ) );
			$product->save();
			$matched[] = $product->get_id();
		}
		$category_only = \WC_Helper_Product::create_simple_product();
		$category_only->set_category_ids( array( $gifts ) );
		$category_only->save();
		$tag_only = \WC_Helper_Product::create_simple_product();
		$tag_only->set_tag_ids( array( $summer ) );
		$tag_only->save();

		$output = $this->run_ability(
			'woocommerce/products-query',
			array(
				'tag'     => array( $summer ),
				'filters' => array( 'extensions' => array( 'test-ext' => array( 'gifts_or_sale' => 'true' ) ) ),
			)
		);

		$this->assertEqualsCanonicalizing( $matched, array_column( $output['products'], 'id' ) );
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
	 * @testdox Should keep the orders whose IDs every filter returns, from data that is not meta, still apply Core's exclude, and narrow Core's include both ways, with HPOS and with posts storage.
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
			$narrowed = $this->run_ability( 'woocommerce/orders-query', array( 'include' => array( $orders['gift'], $other->get_id() ) ) + $filters( array( 'contains_product' => $coffee->get_id() ) ) );
			$disjoint = $this->run_ability( 'woocommerce/orders-query', array( 'include' => array( $other->get_id() ) ) + $filters( array( 'contains_product' => $coffee->get_id() ) ) );
			$minus    = $this->run_ability( 'woocommerce/orders-query', array( 'include' => array( $orders['gift'], $orders['plain'] ) ) + $filters( array( 'without' => array( $orders['plain'] ) ) ) );

			$this->assertEqualsCanonicalizing( array_values( $orders ), array_column( $contains['orders'], 'id' ) );
			$this->assertSame( array( $orders['gift'] ), array_column( $both['orders'], 'id' ) );
			$this->assertSame( array(), $none['orders'] );
			$this->assertEqualsCanonicalizing( array( $orders['gift'], $other->get_id() ), array_column( $without['orders'], 'id' ) );
			$this->assertSame( array( $orders['plain'] ), array_column( $excluded['orders'], 'id' ) );
			$this->assertSame( array( $orders['gift'] ), array_column( $both_out['orders'], 'id' ) );
			$this->assertSame( array( $orders['gift'] ), array_column( $narrowed['orders'], 'id' ), 'An extension filter should narrow include, not widen it.' );
			$this->assertSame( array(), $disjoint['orders'], 'An extension filter that shares no ID with include should return no orders.' );
			$this->assertSame( array( $orders['gift'] ), array_column( $minus['orders'], 'id' ), 'IDs an extension filter excludes should leave include.' );
		} finally {
			remove_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_contains_product_filter' ) );
			remove_all_filters( 'pre_option_' . DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION );
			$this->toggle_cot_feature_and_usage( $original );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * @testdox Should return only the orders that both a search and an extension's ID filter match, with HPOS and with posts storage.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos Whether HPOS is on.
	 */
	public function test_search_intersects_with_order_ids_filter( bool $hpos ): void {
		$original = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->toggle_cot_feature_and_usage( $hpos );
		add_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_contains_product_filter' ) );

		try {
			$coffee = \WC_Helper_Product::create_simple_product();
			$orders = array();
			foreach ( array(
				'jane_coffee' => array( 'Jane', $coffee ),
				'jane_other'  => array( 'Jane', null ),
				'john_coffee' => array( 'John', $coffee ),
			) as $key => list( $first_name, $product ) ) {
				$order = \WC_Helper_Order::create_order( 0, $product );
				$order->set_billing_first_name( $first_name );
				$order->save();
				$orders[ $key ] = $order->get_id();
			}

			$result = $this->run_ability(
				'woocommerce/orders-query',
				array(
					'search'  => 'Jane',
					'filters' => array( 'extensions' => array( 'test-ext' => array( 'contains_product' => $coffee->get_id() ) ) ),
				)
			);

			$this->assertSame( array( $orders['jane_coffee'] ), array_column( $result['orders'], 'id' ) );
		} finally {
			remove_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_contains_product_filter' ) );
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
				'resource'      => 'product',
				'namespace'     => 'late-ext',
				'field'         => 'code',
				'output_schema' => array( 'type' => 'string' ),
				'get_callback'  => static fn() => 'late',
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
					'resource'      => 'ticket',
					'namespace'     => 'test-ext',
					'field'         => 'code',
					'output_schema' => array( 'type' => 'string' ),
					'get_callback'  => static fn() => 'T1',
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
		$this->assertSame( array( 'product', 'order', 'order_item', 'product_term', 'product_review', 'customer' ), array_keys( $resources->getValue() ) );
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
				'resource'      => 'product',
				'namespace'     => 'test-ext',
				'field'         => 'code',
				'output_schema' => array( 'type' => 'string' ),
				'get_callback'  => static fn() => 'T1',
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
			'resource'      => 'order',
			'namespace'     => 'test-ext',
			'field'         => 'note',
			'output_schema' => array( 'type' => 'string' ),
			'get_callback'  => static fn() => 'second',
		);
		AbilitiesLoader::register_core_resources();
		$this->register_test_fields();

		ExtensibleAbilities::register_field( $valid );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'namespace' => 'Test Ext' ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => '' ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'output_schema' => null ) ) );
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
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'output_schema' => array( 'description' => 'x' ) ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'output_schema' => array( 'type' => array( 'string', 'null' ) ) ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => 'other' ), array( 'output_schema' => array( 'type' => 'null' ) ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'namespace' => '0' ) ) );
		ExtensibleAbilities::register_field( array_merge( $valid, array( 'field' => '123' ) ) );
		$order = \WC_Helper_Order::create_order();

		$this->assertSame(
			array( 'test-ext' => array( 'note' => 'order-' . $order->get_id() ) ),
			ExtensibleAbilities::add_fields_to_object( array(), 'order', $order )['extensions']
		);
	}

	/**
	 * @testdox Should refuse an output schema without a type.
	 * @dataProvider provider_refused_output_schemas
	 * @param array $schema Output schema.
	 */
	public function test_a_refused_output_schema_does_not_register_the_field( array $schema ): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		AbilitiesLoader::register_core_resources();

		ExtensibleAbilities::register_field(
			array(
				'resource'      => 'order',
				'namespace'     => 'refused',
				'field'         => 'value',
				'output_schema' => $schema,
				'get_callback'  => '__return_null',
			)
		);

		$this->assertArrayNotHasKey( 'refused', ExtensibleAbilities::get_fields( 'order' ) );
	}

	/**
	 * Output schemas that register_field() refuses.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function provider_refused_output_schemas(): array {
		return array(
			'no type' => array( array( 'description' => 'x' ) ),
			'anyOf'   => array( array( 'anyOf' => array( array( 'type' => 'string' ) ) ) ),
			'oneOf'   => array( array( 'oneOf' => array( array( 'type' => 'string' ) ) ) ),
			'allOf'   => array( array( 'allOf' => array( array( 'type' => 'string' ) ) ) ),
		);
	}

	/**
	 * @testdox Should refuse an input schema and a query filter schema without a type.
	 * @dataProvider provider_malformed_schemas
	 * @param array $schema Schema.
	 */
	public function test_a_malformed_schema_is_refused( array $schema ): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_query_filter' );
		AbilitiesLoader::register_core_resources();

		ExtensibleAbilities::register_field(
			array(
				'resource'      => 'order',
				'namespace'     => 'refused',
				'field'         => 'value',
				'output_schema' => array( 'type' => 'string' ),
				'get_callback'  => '__return_null',
				'input_schema'  => $schema,
				'set_callback'  => '__return_null',
			)
		);
		ExtensibleAbilities::register_query_filter(
			array(
				'resource'  => 'order',
				'namespace' => 'refused',
				'filter'    => 'value',
				'schema'    => $schema,
				'callback'  => '__return_empty_array',
			)
		);

		$this->assertArrayNotHasKey( 'refused', ExtensibleAbilities::get_fields( 'order' ) );
		$this->assertSame(
			array( 'type' => 'object' ),
			ExtensibleAbilities::add_query_filters_schema( array( 'type' => 'object' ), 'order' )
		);
	}

	/**
	 * Schemas without a type, or with an anyOf branch without one type name, for a field input and for a query filter.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function provider_malformed_schemas(): array {
		return array(
			'no type'                => array( array( 'description' => 'x' ) ),
			'empty anyOf'            => array( array( 'anyOf' => array() ) ),
			'anyOf branch type list' => array( array( 'anyOf' => array( array( 'type' => array( 'string', 'null' ) ), array( 'type' => 'integer' ) ) ) ),
			'anyOf branch no type'   => array( array( 'anyOf' => array( array( 'type' => 'string' ), array( 'enum' => array( 'a' ) ) ) ) ),
			'anyOf branch bad type'  => array( array( 'anyOf' => array( array( 'type' => 'integre' ), array( 'type' => 'null' ) ) ) ),
			'anyOf with named keys'  => array(
				array(
					'anyOf' => array(
						'a' => array( 'type' => 'string' ),
						'b' => array( 'type' => 'null' ),
					),
				),
			),
		);
	}

	/**
	 * @testdox Should refuse a field whose input schema has a list of types or an unknown type at the top level.
	 * @testWith [["string", "null"]]
	 *           ["integre"]
	 *
	 * @param mixed $type Top-level type of the input schema.
	 */
	public function test_a_top_level_type_list_in_an_input_schema_is_refused( $type ): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		AbilitiesLoader::register_core_resources();

		ExtensibleAbilities::register_field(
			array(
				'resource'      => 'order',
				'namespace'     => 'refused',
				'field'         => 'value',
				'output_schema' => array( 'type' => 'string' ),
				'get_callback'  => '__return_null',
				'input_schema'  => array( 'type' => $type ),
				'set_callback'  => '__return_null',
			)
		);

		$this->assertArrayNotHasKey( 'refused', ExtensibleAbilities::get_fields( 'order' ) );
	}

	/**
	 * @testdox Should keep the WordPress message for an anyOf under a top-level type whose branches do not each have their own type name.
	 * @testWith [[{"enum":["small"]},{"enum":["large"]}]]
	 *           [[{"type":["integer","null"]},{"type":"null"}]]
	 *           [[{"type":"string","enum":["small"]},{"type":"string","pattern":"^x"}]]
	 *
	 * @param array $any_of The anyOf branches.
	 */
	public function test_an_any_of_branch_without_a_type_keeps_the_wordpress_message( array $any_of ): void {
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( $any_of ) {
				ExtensibleAbilities::register_field(
					array(
						'resource'     => 'product',
						'namespace'    => 'untyped',
						'field'        => 'size',
						'input_schema' => array(
							'type'  => 'string',
							'anyOf' => $any_of,
						),
						'set_callback' => '__return_null',
					)
				);
			}
		);
		$this->reset_registries();
		$product = \WC_Helper_Product::create_simple_product();

		$refused = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'untyped' => array( 'size' => 'huge' ) ),
			)
		);

		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 'extensions.untyped.size does not match any of the expected formats.', $refused->get_data()['message'] );
	}

	/**
	 * @testdox Should name each anyOf type once when several branches share a type and the value matches none.
	 */
	public function test_an_any_of_type_mismatch_names_each_type_once(): void {
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'     => 'product',
						'namespace'    => 'shared',
						'field'        => 'size',
						'input_schema' => array(
							'anyOf' => array(
								array(
									'type' => 'string',
									'enum' => array( 'small' ),
								),
								array(
									'type'    => 'string',
									'pattern' => '^x',
								),
								array( 'type' => 'null' ),
							),
						),
						'set_callback' => '__return_null',
					)
				);
			}
		);
		$this->reset_registries();
		$product = \WC_Helper_Product::create_simple_product();

		$refused = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'shared' => array( 'size' => 7 ) ),
			)
		);

		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 'extensions.shared.size is not of type string,null.', $refused->get_data()['message'] );
	}

	/**
	 * @testdox Should register a field whose input schema is an anyOf of single types, publish it as declared and pass null or a typed value to the set_callback.
	 */
	public function test_an_any_of_input_schema_takes_null_and_a_typed_value(): void {
		$input_schema = array(
			'description' => 'Days of the trial.',
			'anyOf'       => array( array( 'type' => 'integer' ), array( 'type' => 'null' ) ),
		);
		$received     = array();
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( $input_schema, &$received ) {
				ExtensibleAbilities::register_field(
					array(
						'resource'     => 'product',
						'namespace'    => 'nullable',
						'field'        => 'days',
						'input_schema' => $input_schema,
						'set_callback' => static function ( \WC_Data $data, $value ) use ( &$received ) {
							unset( $data );
							$received[] = $value;
						},
					)
				);
			}
		);
		$this->reset_registries();
		$product = \WC_Helper_Product::create_simple_product();
		$write   = fn( $value ) => $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'nullable' => array( 'days' => $value ) ),
			)
		);

		$published = $this->get_ability( 'woocommerce/product-update' )['input_schema']['properties']['extensions']['properties']['nullable']['properties']['days'];
		$this->assertSame( $input_schema, $published );
		$this->assertSame( 200, $write( 7 )->get_status() );
		$this->assertSame( 200, $write( null )->get_status() );
		$refused = $write( 'many' );
		$this->assertSame( 400, $refused->get_status() );
		$this->assertStringContainsString( 'extensions.nullable.days', $refused->get_data()['message'] );
		$this->assertSame( array( 7, null ), $received );

		$received = array();
		$result   = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'nullable' => array( 'days' => '8' ) ),
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( array( 8 ), $received, 'A direct call gets the value cast to the integer branch.' );
	}

	/**
	 * @testdox Should refuse a query filter schema that has an anyOf and no top-level type.
	 */
	public function test_a_query_filter_schema_needs_a_type_even_with_an_any_of(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_query_filter' );
		AbilitiesLoader::register_core_resources();

		ExtensibleAbilities::register_query_filter(
			array(
				'resource'  => 'order',
				'namespace' => 'refused',
				'filter'    => 'value',
				'schema'    => array( 'anyOf' => array( array( 'type' => 'string' ), array( 'type' => 'null' ) ) ),
				'callback'  => '__return_empty_array',
			)
		);

		$this->assertSame(
			array( 'type' => 'object' ),
			ExtensibleAbilities::add_query_filters_schema( array( 'type' => 'object' ), 'order' )
		);
	}

	/**
	 * @testdox Should publish an input schema with null, a combinator or a branch without a type as declared, and an output schema as declared.
	 */
	public function test_valid_schemas_are_published_as_declared(): void {
		$input_schemas = array(
			'nullable'    => array( 'anyOf' => array( array( 'type' => 'string' ), array( 'type' => 'null' ) ) ),
			'any_of'      => array(
				'type'       => 'object',
				'properties' => array( 'a' => array( 'anyOf' => array( array( 'type' => 'string' ), array( 'type' => 'null' ) ) ) ),
			),
			'enum_null'   => array(
				'anyOf' => array(
					array(
						'type' => 'string',
						'enum' => array( 'a' ),
					),
					array( 'type' => 'null' ),
				),
			),
			'enum_mixed'  => array(
				'type' => 'string',
				'enum' => array( 'a', 1 ),
			),
			'branch'      => array(
				'type'  => 'object',
				'anyOf' => array( array( 'required' => array( 'a' ) ), array( 'required' => array( 'b' ) ) ),
			),
			'nested_null' => array(
				'type'       => 'object',
				'properties' => array( 'a' => array( 'type' => array( 'string', 'null' ) ) ),
				'required'   => array( 'a' ),
			),
			'different'   => array(
				'type'                 => 'object',
				'properties'           => array( 'delete' => array( 'type' => 'boolean' ) ),
				'additionalProperties' => false,
			),
		);
		$output_schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'tags' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
						'enum' => array( 'a', 'b' ),
					),
				),
			),
			'additionalProperties' => false,
		);
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( $input_schemas, $output_schema ) {
				foreach ( $input_schemas as $field => $input_schema ) {
					ExtensibleAbilities::register_field(
						array(
							'resource'      => 'product',
							'namespace'     => 'declared',
							'field'         => $field,
							'output_schema' => $output_schema,
							'get_callback'  => '__return_null',
							'input_schema'  => $input_schema,
							'set_callback'  => '__return_null',
						)
					);
				}
			}
		);
		$this->reset_registries();

		$published_input  = $this->get_ability( 'woocommerce/product-update' )['input_schema']['properties']['extensions']['properties']['declared']['properties'];
		$published_output = $this->get_output_schema( 'woocommerce/product-update' )['properties']['product']['properties']['extensions']['properties']['declared']['properties'];

		$this->assertSame( $input_schemas, $published_input );
		$this->assertSame( array_fill_keys( array_keys( $input_schemas ), $output_schema ), $published_output );
	}

	/**
	 * @testdox Should publish every WooCommerce ability schema as JSON whose keywords have the value types that an MCP client compiles.
	 */
	public function test_published_schemas_have_the_keyword_types_that_mcp_clients_compile(): void {
		$schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'code'   => array(
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => 20,
					'pattern'   => '^[a-z]+$',
					'format'    => 'hex-color',
				),
				'amount' => array(
					'type'             => 'number',
					'minimum'          => 1,
					'exclusiveMinimum' => 1,
					'maximum'          => 100.5,
					'multipleOf'       => 0.5,
				),
				'tags'   => array(
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
						'enum' => array( 'a', 'b' ),
					),
					'minItems'    => 0,
					'uniqueItems' => true,
				),
			),
			'required'             => array( 'code' ),
			'additionalProperties' => false,
		);
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( $schema ) {
				foreach ( array( 'product', 'order', 'order_item' ) as $resource ) {
					ExtensibleAbilities::register_field(
						array(
							'resource'      => $resource,
							'namespace'     => 'compiled',
							'field'         => 'value',
							'output_schema' => $schema,
							'get_callback'  => '__return_null',
							'input_schema'  => $schema,
							'set_callback'  => '__return_null',
						)
					);
				}
			}
		);
		$this->reset_registries();
		$this->get_ability( 'woocommerce/product-update' );

		$errors = array();
		$names  = array();
		foreach ( wp_get_abilities() as $ability ) {
			if ( 0 !== strpos( $ability->get_name(), 'woocommerce/' ) ) {
				continue;
			}
			$names[] = $ability->get_name();
			foreach ( array(
				'input'  => $ability->get_input_schema(),
				'output' => $ability->get_output_schema(),
			) as $kind => $ability_schema ) {
				$json   = json_decode( (string) wp_json_encode( $ability_schema ) );
				$errors = array_merge( $errors, self::get_compile_errors( $json, $ability->get_name() . ' ' . $kind ) );
			}
		}

		$this->assertContains( 'woocommerce/product-update', $names );
		$this->assertContains( 'woocommerce/orders-query', $names );
		$this->assertEmpty( $errors, implode( "\n", $errors ) );
	}

	/**
	 * Find the keywords in a decoded JSON schema whose value has a type that the MCP SDK client
	 * refuses when it compiles the schema with Ajv. The types come from the keyword definitions of Ajv.
	 *
	 * @param mixed  $schema Schema decoded from JSON, with maps as objects and lists as arrays.
	 * @param string $path   Path of the schema, used in the message.
	 * @return string[] The problems.
	 */
	private static function get_compile_errors( $schema, string $path ): array {
		if ( is_bool( $schema ) ) {
			return array();
		}
		if ( ! is_object( $schema ) ) {
			return array( $path . ' is not a schema object' );
		}
		$is_number = static fn( $value ) => is_int( $value ) || is_float( $value );
		$checks    = array(
			'type'                 => static fn( $value ) => is_string( $value ) || ( is_array( $value ) && ! empty( $value ) ),
			'required'             => static fn( $value ) => is_array( $value ) && empty( array_filter( $value, static fn( $name ) => ! is_string( $name ) ) ),
			'enum'                 => static fn( $value ) => is_array( $value ) && ! empty( $value ),
			'properties'           => 'is_object',
			'patternProperties'    => 'is_object',
			'additionalProperties' => static fn( $value ) => is_bool( $value ) || is_object( $value ),
			'items'                => static fn( $value ) => is_bool( $value ) || is_object( $value ) || is_array( $value ),
			'anyOf'                => static fn( $value ) => is_array( $value ) && ! empty( $value ),
			'oneOf'                => static fn( $value ) => is_array( $value ) && ! empty( $value ),
			'allOf'                => static fn( $value ) => is_array( $value ) && ! empty( $value ),
			'minLength'            => $is_number,
			'maxLength'            => $is_number,
			'minItems'             => $is_number,
			'maxItems'             => $is_number,
			'minProperties'        => $is_number,
			'maxProperties'        => $is_number,
			'minimum'              => $is_number,
			'maximum'              => $is_number,
			'exclusiveMinimum'     => $is_number,
			'exclusiveMaximum'     => $is_number,
			'multipleOf'           => $is_number,
			'pattern'              => 'is_string',
			'format'               => 'is_string',
			'uniqueItems'          => 'is_bool',
		);

		$errors = array();
		foreach ( $checks as $keyword => $check ) {
			if ( property_exists( $schema, $keyword ) && ! $check( $schema->$keyword ) ) {
				$errors[] = $path . '.' . $keyword . ' has a value that does not compile';
			}
		}

		$children = array();
		foreach ( array( 'properties', 'patternProperties' ) as $keyword ) {
			if ( isset( $schema->$keyword ) && is_object( $schema->$keyword ) ) {
				foreach ( get_object_vars( $schema->$keyword ) as $key => $child ) {
					$children[ $path . '.' . $keyword . '.' . $key ] = $child;
				}
			}
		}
		foreach ( array( 'additionalProperties', 'items' ) as $keyword ) {
			if ( isset( $schema->$keyword ) && is_object( $schema->$keyword ) ) {
				$children[ $path . '.' . $keyword ] = $schema->$keyword;
			}
		}
		foreach ( array( 'anyOf', 'oneOf', 'allOf' ) as $keyword ) {
			if ( isset( $schema->$keyword ) && is_array( $schema->$keyword ) ) {
				foreach ( $schema->$keyword as $index => $child ) {
					$children[ $path . '.' . $keyword . '.' . $index ] = $child;
				}
			}
		}
		foreach ( $children as $child_path => $child ) {
			$errors = array_merge( $errors, self::get_compile_errors( $child, $child_path ) );
		}
		return $errors;
	}

	/**
	 * @testdox Should need at least one complete pair of callback and schema, and refuse the old arguments.
	 */
	public function test_a_field_needs_complete_arguments(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		AbilitiesLoader::register_core_resources();
		$base = array(
			'resource'  => 'order',
			'namespace' => 'refused',
			'field'     => 'value',
		);
		$read = array(
			'get_callback'  => '__return_null',
			'output_schema' => array( 'type' => 'string' ),
		);
		$sets = array(
			array(),
			array( 'get_callback' => '__return_null' ),
			array( 'output_schema' => array( 'type' => 'string' ) ),
			array(
				'get_callback' => '__return_null',
				'schema'       => array( 'type' => 'string' ),
			),
			array( 'set_callback' => '__return_null' ),
			array( 'input_schema' => array( 'type' => 'string' ) ),
			array(
				'set_callback' => 'not_a_function',
				'input_schema' => array( 'type' => 'string' ),
			),
			$read + array( 'set_callback' => '__return_null' ),
			$read + array( 'input_schema' => array( 'type' => 'string' ) ),
			$read + array(
				'input_schema' => array( 'type' => 'string' ),
				'set_callback' => 'not_a_function',
			),
		);
		foreach ( $sets as $args ) {
			ExtensibleAbilities::register_field( $base + $args );
		}

		$this->assertArrayNotHasKey( 'refused', ExtensibleAbilities::get_fields( 'order' ) );
	}

	/**
	 * @testdox Should list a read-only field only in the output, and refuse a write to it.
	 */
	public function test_a_read_only_field_is_only_in_the_output(): void {
		add_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_test_fields' ) );
		$this->reset_registries();
		$product = \WC_Helper_Product::create_simple_product();

		$response = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'test-ext' => array( 'code' => 'x' ) ),
			)
		);

		$input_schema = $this->get_ability( 'woocommerce/product-update' )['input_schema']['properties']['extensions']['properties'];
		$this->assertSame( 400, $response->get_status() );
		$this->assertArrayNotHasKey( 'code', $input_schema['test-ext']['properties'] );
		$this->assertArrayHasKey( 'code', $this->get_output_schema( 'woocommerce/product-update' )['properties']['product']['properties']['extensions']['properties']['test-ext']['properties'] );
	}

	/**
	 * @testdox Should accept a write-only field in the input and leave it out of the output and its schema.
	 */
	public function test_a_write_only_field_is_only_in_the_input(): void {
		$received = array();
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( &$received ) {
				ExtensibleAbilities::register_field(
					array(
						'resource'     => 'product',
						'namespace'    => 'write-ext',
						'field'        => 'secret',
						'input_schema' => array( 'type' => 'string' ),
						'set_callback' => static function ( $product, $value ) use ( &$received ) {
							$received[] = $value;
						},
					)
				);
			}
		);
		$this->reset_registries();
		$product = \WC_Helper_Product::create_simple_product();

		$output = $this->run_ability(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'write-ext' => array( 'secret' => 'x' ) ),
			)
		);

		$input_schema = $this->get_ability( 'woocommerce/product-update' )['input_schema']['properties']['extensions']['properties'];
		$this->assertSame( array( 'x' ), $received );
		$this->assertArrayNotHasKey( 'write-ext', $output['product']['extensions'] ?? array() );
		$this->assertArrayHasKey( 'secret', $input_schema['write-ext']['properties'] );
		$this->assertArrayNotHasKey( 'write-ext', $this->get_output_schema( 'woocommerce/product-update' )['properties']['product']['properties']['extensions']['properties'] );
	}

	/**
	 * @testdox Should leave a namespace with no readable field out of the output schema, and keep the schema when no namespace has one.
	 */
	public function test_a_namespace_with_only_write_only_fields_is_not_in_the_output_schema(): void {
		AbilitiesLoader::register_core_resources();
		$schema = array( 'type' => 'object' );
		ExtensibleAbilities::register_field(
			array(
				'resource'     => 'order',
				'namespace'    => 'write-ext',
				'field'        => 'secret',
				'input_schema' => array( 'type' => 'string' ),
				'set_callback' => '__return_null',
			)
		);

		$this->assertSame( $schema, ExtensibleAbilities::add_fields_schema( $schema, 'order' ) );

		ExtensibleAbilities::register_field(
			array(
				'resource'      => 'order',
				'namespace'     => 'read-ext',
				'field'         => 'code',
				'output_schema' => array( 'type' => 'string' ),
				'get_callback'  => '__return_null',
			)
		);

		$this->assertSame( array( 'read-ext' ), array_keys( ExtensibleAbilities::add_fields_schema( $schema, 'order' )['properties']['extensions']['properties'] ) );
	}

	/**
	 * @testdox Should refuse a null input unless the input schema declares it, at the top level and nested.
	 */
	public function test_a_null_input_passes_only_where_it_is_declared(): void {
		$received = array();
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( &$received ) {
				$fields = array(
					'plain'           => array( 'type' => 'string' ),
					'nested_declared' => array(
						'type'       => 'object',
						'properties' => array( 'a' => array( 'type' => array( 'string', 'null' ) ) ),
					),
					'nested_refused'  => array(
						'type'       => 'object',
						'properties' => array( 'a' => array( 'type' => 'string' ) ),
					),
				);
				foreach ( $fields as $field => $schema ) {
					ExtensibleAbilities::register_field(
						array(
							'resource'      => 'product',
							'namespace'     => 'nulls',
							'field'         => $field,
							'output_schema' => array( 'type' => 'string' ),
							'get_callback'  => '__return_null',
							'input_schema'  => $schema,
							'set_callback'  => static function ( \WC_Data $data, $value ) use ( &$received, $field ) {
								$received[ $field ] = $value;
							},
						)
					);
				}
			}
		);
		$this->reset_registries();
		$product = \WC_Helper_Product::create_simple_product();
		$write   = fn( array $fields ) => $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'nulls' => $fields ),
			)
		);

		$this->assertSame( 400, $write( array( 'plain' => null ) )->get_status() );
		$this->assertSame( 400, $write( array( 'nested_refused' => array( 'a' => null ) ) )->get_status() );
		$this->assertSame( 200, $write( array( 'nested_declared' => array( 'a' => null ) ) )->get_status() );
		$this->assertSame( array( 'nested_declared' => array( 'a' => null ) ), $received );
	}

	/**
	 * @testdox Should send an empty array for an object as an empty object at any depth, and keep free-form parts as they are.
	 */
	public function test_an_empty_array_for_an_object_is_an_empty_object(): void {
		$output = $this->read_field_values(
			array(
				'flat'    => array( array( 'type' => 'object' ), array() ),
				'nested'  => array(
					array(
						'type'       => 'object',
						'properties' => array( 'inner' => array( 'type' => 'object' ) ),
					),
					array( 'inner' => array() ),
				),
				'in_list' => array(
					array(
						'type'  => 'array',
						'items' => array( 'type' => 'object' ),
					),
					array( array() ),
				),
				'free'    => array( array( 'type' => 'object' ), array( 'list' => array() ) ),
			)
		);

		$this->assertSame(
			'{"flat":{},"nested":{"inner":{}},"in_list":[{}],"free":{"list":[]}}',
			wp_json_encode( $output )
		);
	}

	/**
	 * @testdox Should keep an empty array for an array as an empty list.
	 */
	public function test_an_empty_array_for_an_array_is_an_empty_list(): void {
		$output = $this->read_field_values(
			array(
				'flat'       => array( array( 'type' => 'array' ), array() ),
				'of_objects' => array(
					array(
						'type'  => 'array',
						'items' => array( 'type' => 'object' ),
					),
					array(),
				),
				'in_object'  => array(
					array(
						'type'       => 'object',
						'properties' => array(
							'list' => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
						),
					),
					array( 'list' => array() ),
				),
			)
		);

		$this->assertSame( '{"flat":[],"of_objects":[],"in_object":{"list":[]}}', wp_json_encode( $output ) );
	}

	/**
	 * @testdox Should return product amounts as strings when the getters return an int or a float, and save the write.
	 */
	public function test_product_amounts_are_returned_as_strings(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$this->add_test_filter( 'woocommerce_product_get_price', static fn() => 12.5 );
		$this->add_test_filter( 'woocommerce_product_get_regular_price', static fn() => 15 );
		$this->add_test_filter( 'woocommerce_product_get_sale_price', static fn() => 12.5 );

		$query = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => $product->get_id() ) );
		$write = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $product->get_id(),
				'name' => 'Saved',
			)
		);

		$this->assertNotWPError( $query );
		$this->assertSame( '12.5', $query['products'][0]['price'] );
		$this->assertSame( '15', $query['products'][0]['regular_price'] );
		$this->assertSame( '12.5', $query['products'][0]['sale_price'] );
		$this->assertNotWPError( $write );
		$this->assertSame( '12.5', $write['product']['price'] );
		$this->assertSame( 'Saved', get_post( $product->get_id() )->post_title );
	}

	/**
	 * @testdox Should return the order total and line item amounts as strings when the getters return an int or a float.
	 */
	public function test_order_amounts_are_returned_as_strings(): void {
		$order = \WC_Helper_Order::create_order();
		$this->add_test_filter( 'woocommerce_order_get_total', static fn() => 40 );
		$this->add_test_filter( 'woocommerce_order_item_get_subtotal', static fn() => 30 );
		$this->add_test_filter( 'woocommerce_order_item_get_total', static fn() => 25.5 );

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'id'                 => $order->get_id(),
				'include_line_items' => true,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( '40', $result['orders'][0]['total'] );
		$this->assertSame( '30', $result['orders'][0]['line_items'][0]['subtotal'] );
		$this->assertSame( '25.5', $result['orders'][0]['line_items'][0]['total'] );
	}

	/**
	 * @testdox Should cast a value to its schema type, and leave out a null.
	 */
	public function test_field_values_follow_their_schema(): void {
		AbilitiesLoader::register_core_resources();
		$this->register_test_fields();
		$fields = array(
			'count' => array(
				'output_schema' => array( 'type' => 'integer' ),
				'get_callback'  => static fn() => '12',
			),
			'empty' => array(
				'output_schema' => array( 'type' => 'string' ),
				'get_callback'  => '__return_null',
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

		$this->assertSame( array( 'count' => 12 ), $output['extensions']['values'] );
	}

	/**
	 * @testdox Should fail products-query and product-update with a named error when a product field value does not match its schema.
	 */
	public function test_a_product_field_value_that_does_not_match_its_schema_fails_the_call(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		$this->register_invalid_field( 'product', array( 'type' => 'integer' ), 'many' );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen one' ) );
		\WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen two' ) );

		$query = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'search' => 'Pen' ) );
		$write = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assert_field_output_invalid( $query, 'product' );
		$this->assert_field_output_invalid( $write, 'product' );
		$this->assertTrue( $write->get_error_data()['saved'] );
		$this->assertSame( $product->get_id(), $write->get_error_data()['id'] );
		$this->assertStringContainsString( 'The change is saved on the product ' . $product->get_id() . ',', $write->get_error_message() );
		$this->assertSame( 'Pencil', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should fail orders-query with a named error when an order or line item field value does not match its schema.
	 * @dataProvider invalid_order_field_values
	 *
	 * @param string $resource_name Resource.
	 * @param array  $schema        Field schema.
	 * @param mixed  $value         Value that the get_callback returns.
	 */
	public function test_an_order_field_value_that_does_not_match_its_schema_fails_the_call( string $resource_name, array $schema, $value ): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		$this->register_invalid_field( $resource_name, $schema, $value );
		$order = \WC_Helper_Order::create_order();

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'id'                 => $order->get_id(),
				'include_line_items' => true,
			)
		);

		$this->assert_field_output_invalid( $result, $resource_name );
	}

	/**
	 * @testdox Should name the saved order and note in the error when order-add-note saves and an order field value then fails its schema.
	 */
	public function test_an_order_note_that_saves_before_a_field_fails_names_the_saved_note(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		$this->register_invalid_field( 'order', array( 'type' => 'integer' ), 'many' );
		$order = \WC_Helper_Order::create_order();

		$result = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'   => $order->get_id(),
				'note' => 'Packed.',
			)
		);

		$this->assert_field_output_invalid( $result, 'order' );
		$this->assertStringContainsString( 'The change is saved on the order ' . $order->get_id() . ',', $result->get_error_message() );
		$this->assertTrue( $result->get_error_data()['saved'] );
		$this->assertSame( $order->get_id(), $result->get_error_data()['id'] );
		$note = wc_get_order_note( $result->get_error_data()['note_id'] );
		$this->assertSame( $order->get_id(), (int) get_comment( $note->id )->comment_post_ID );
		$this->assertSame( 'Packed.', $note->content );
	}

	/**
	 * @testdox Should name the saved order in the error when order-update-status saves and an order field value then fails its schema.
	 */
	public function test_an_order_status_that_saves_before_a_field_fails_names_the_saved_order(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		$this->register_invalid_field( 'order', array( 'type' => 'integer' ), 'many' );
		$order = \WC_Helper_Order::create_order();

		$result = wp_get_ability( 'woocommerce/order-update-status' )->execute(
			array(
				'id'     => $order->get_id(),
				'status' => 'completed',
			)
		);

		$this->assert_field_output_invalid( $result, 'order' );
		$this->assertStringContainsString( 'The change is saved on the order ' . $order->get_id() . ',', $result->get_error_message() );
		$this->assertTrue( $result->get_error_data()['saved'] );
		$this->assertSame( $order->get_id(), $result->get_error_data()['id'] );
		$this->assertSame( 'completed', wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Should name the saved order in the error when order-update saves and an order or line item field value then fails its schema.
	 * @dataProvider invalid_order_field_values
	 *
	 * @param string $resource_name Resource.
	 * @param array  $schema        Field schema.
	 * @param mixed  $value         Value that the get_callback returns.
	 */
	public function test_an_order_update_that_saves_before_a_field_fails_names_the_saved_order( string $resource_name, array $schema, $value ): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		$this->register_invalid_field( $resource_name, $schema, $value );
		$order = \WC_Helper_Order::create_order();

		$result = wp_get_ability( 'woocommerce/order-update' )->execute(
			array(
				'id'            => $order->get_id(),
				'customer_note' => 'Probe',
			)
		);

		$this->assert_field_output_invalid( $result, $resource_name );
		$this->assertStringContainsString( 'The change is saved on the order ' . $order->get_id() . ',', $result->get_error_message() );
		$this->assertTrue( $result->get_error_data()['saved'] );
		$this->assertSame( $order->get_id(), $result->get_error_data()['id'] );
		$this->assertSame( 'Probe', wc_get_order( $order->get_id() )->get_customer_note() );
	}

	/**
	 * @testdox Should fail orders-query with a named error that keeps the exception text out when a get_callback throws.
	 */
	public function test_a_get_callback_that_throws_fails_the_call(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'      => 'order',
						'namespace'     => 'invalid-ext',
						'field'         => 'bad',
						'output_schema' => array( 'type' => 'string' ),
						'get_callback'  => static function () {
							throw new \RuntimeException( 'Secret failure' );
						},
					)
				);
			}
		);
		$order = \WC_Helper_Order::create_order();

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute( array( 'id' => $order->get_id() ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_extension_failed', $result->get_error_code(), $result->get_error_message() );
		$this->assertSame( 'The "invalid-ext" extension failed while reading the "bad" field of the order.', $result->get_error_message() );
		$this->assertSame(
			array(
				'status'    => 500,
				'namespace' => 'invalid-ext',
				'field'     => 'bad',
			),
			$result->get_error_data()
		);
	}

	/**
	 * Field values that do not match their schema.
	 *
	 * @return array<string, array{0: string, 1: array, 2: mixed}>
	 */
	public function invalid_order_field_values(): array {
		return array(
			'order field of the wrong type' => array( 'order', array( 'type' => 'integer' ), 'many' ),
			'nested object on a line item'  => array(
				'order_item',
				array(
					'type'       => 'object',
					'properties' => array(
						'inner' => array(
							'type'       => 'object',
							'properties' => array( 'count' => array( 'type' => 'integer' ) ),
						),
					),
				),
				array( 'inner' => array( 'count' => 'many' ) ),
			),
			'list that fails sanitization'  => array(
				'order',
				array(
					'type'        => 'array',
					'items'       => array( 'type' => 'integer' ),
					'uniqueItems' => true,
				),
				array( '12', 12 ),
			),
		);
	}

	/**
	 * Register the field invalid-ext.bad on a resource, with a get_callback that returns a fixed value.
	 *
	 * @param string $resource_name Resource.
	 * @param array  $schema        Field schema.
	 * @param mixed  $value         Value that the get_callback returns.
	 */
	private function register_invalid_field( string $resource_name, array $schema, $value ): void {
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( $resource_name, $schema, $value ) {
				ExtensibleAbilities::register_field(
					array(
						'resource'      => $resource_name,
						'namespace'     => 'invalid-ext',
						'field'         => 'bad',
						'output_schema' => $schema,
						'get_callback'  => static fn() => $value,
					)
				);
			}
		);
	}

	/**
	 * Assert that a result is the error for the invalid-ext.bad field.
	 *
	 * @param mixed  $result        Ability result.
	 * @param string $resource_name Resource.
	 */
	private function assert_field_output_invalid( $result, string $resource_name ): void {
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_field_output_invalid', $result->get_error_code(), $result->get_error_message() );
		$this->assertStringContainsString( '"invalid-ext" extension', $result->get_error_message() );
		$this->assertStringContainsString( '"bad" field of the ' . $resource_name, $result->get_error_message() );
		$data = $result->get_error_data();
		$this->assertSame( 500, $data['status'] );
		$this->assertSame( 'invalid-ext', $data['namespace'] );
		$this->assertSame( 'bad', $data['field'] );
		$this->assertNotEmpty( $data['details'] );
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
	 * @testdox Should write extension fields in product create and in order status update.
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
		$updated = $this->run_ability(
			'woocommerce/order-update-status',
			array(
				'id'         => $order->get_id(),
				'status'     => 'on-hold',
				'extensions' => array( 'test-ext' => array( 'gift_message' => 'Happy birthday' ) ),
			)
		);

		$saved = wc_get_order( $order->get_id() );
		$this->assertSame( 'blue', wc_get_product( $created['product']['id'] )->get_meta( '_test_color' ) );
		$this->assertSame( 'on-hold', $saved->get_status() );
		$this->assertSame( 'Happy birthday', $saved->get_meta( '_test_gift_message' ) );
		$this->assertSame( 'Happy birthday', $updated['order']['extensions']['test-ext']['gift_message'] );
	}

	/**
	 * @testdox Should keep backslashes and quotes in the name and descriptions of a created product.
	 */
	public function test_product_create_keeps_backslashes_in_post_fields(): void {
		$created = $this->run_ability(
			'woocommerce/product-create',
			array(
				'name'              => 'Cable 1\\2" it\'s',
				'description'       => 'Path C:\\temp',
				'short_description' => 'Short C:\\temp',
			)
		);
		$post    = get_post( $created['product']['id'] );

		$this->assertSame( 'Cable 1\\2" it\'s', $post->post_title, 'The stored name should keep its backslash and quotes.' );
		$this->assertSame( 'Path C:\\temp', $post->post_content, 'The stored description should keep its backslash.' );
		$this->assertSame( 'Short C:\\temp', $post->post_excerpt, 'The stored short description should keep its backslash.' );
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
				$this->assertSame( 'extensions.other_ext.count is not of type integer,null.', $response->get_data()['message'] );
			}
		}
		$saved = wc_get_product( $product->get_id() );
		$this->assertSame( 'Pen', $saved->get_name() );
		$this->assertFalse( $saved->meta_exists( '_test_color' ) );
	}

	/**
	 * @testdox Should say what is wrong with one bad extension field, and save nothing.
	 * @testWith [{"nope":{"x":"red"}}, "No extension registered the namespace \"nope\" for this ability. Remove it from the input, or use a namespace from the input schema."]
	 *           [{"nope":"red"}, "No extension registered the namespace \"nope\" for this ability. Remove it from the input, or use a namespace from the input schema."]
	 *           [{"nope":{}}, "No extension registered the namespace \"nope\" for this ability. Remove it from the input, or use a namespace from the input schema."]
	 *           [{"other_ext":{"cuont":3}}, "The extension namespace \"other_ext\" has no field \"cuont\". The fields you can write in this namespace are: count, size. Use one of them, or remove \"other_ext.cuont\" from the input."]
	 *           [{"test-ext":{"code":"A2"}}, "The extension field \"test-ext.code\" is read-only. Remove it from the input."]
	 *           [{"other_ext":{"size":"huge"}}, "extensions.other_ext.size does not match the expected format. Reason: extensions.other_ext.size is not one of small and large."]
	 *
	 * @param array  $extensions Value of the `extensions` input.
	 * @param string $message    Expected error message.
	 */
	public function test_a_bad_extension_field_is_refused_by_name( array $extensions, string $message ): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		$result = wp_get_ability( 'woocommerce/product-update' )->validate_input(
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => $extensions,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( array( 'woocommerce_ability_invalid_fields' ), $result->get_error_codes() );
		$this->assertSame( $message, $result->get_error_message() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should say that a namespace has no writable fields when a field does not exist and all its fields are read-only.
	 */
	public function test_an_unknown_field_in_a_read_only_namespace_is_refused(): void {
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'      => 'product',
						'namespace'     => 'read_only_ext',
						'field'         => 'code',
						'output_schema' => array( 'type' => 'string' ),
						'get_callback'  => static fn() => 'A1',
					)
				);
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();
		$product = \WC_Helper_Product::create_simple_product();

		$result = wp_get_ability( 'woocommerce/product-update' )->validate_input(
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'read_only_ext' => array( 'cdoe' => 'A2' ) ),
			)
		);

		$this->assertSame( 'The extension namespace "read_only_ext" has no field "cdoe", and it has no fields you can write. Remove "read_only_ext.cdoe" from the input.', $result->get_error_message() );
	}

	/**
	 * @testdox Should name every bad extension field in the first message of one error, for a client that reads only that message.
	 */
	public function test_every_bad_extension_field_is_in_the_first_message(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array(
					'nope'      => array( 'x' => 'y' ),
					'test-ext'  => array( 'code' => 'A2' ),
					'other_ext' => array(
						'cuont' => 3,
						'count' => 'many',
					),
				),
			)
		);

		$this->assertWPError( $result );
		$this->assertCount( 1, $result->get_error_messages() );
		$this->assertSame(
			implode(
				' ',
				array(
					'No extension registered the namespace "nope" for this ability. Remove it from the input, or use a namespace from the input schema.',
					'The extension field "test-ext.code" is read-only. Remove it from the input.',
					'The extension namespace "other_ext" has no field "cuont". The fields you can write in this namespace are: count, size. Use one of them, or remove "other_ext.cuont" from the input.',
					'extensions.other_ext.count is not of type integer,null.',
				)
			),
			$result->get_error_message()
		);
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should name the bad fields of the product and of its variations in one message on $ability, and run no validator.
	 * @testWith ["woocommerce/product-create"]
	 *           ["woocommerce/product-update"]
	 *
	 * @param string $ability Ability name.
	 */
	public function test_bad_fields_on_the_product_and_its_variations_are_refused_together( string $ability ): void {
		$validated = 0;
		$this->register_validator(
			'product',
			'test-ext',
			static function () use ( &$validated ) {
				++$validated;
				return null;
			}
		);
		$input = $this->get_shirt_input();
		if ( 'woocommerce/product-update' === $ability ) {
			$input['id'] = \WC_Helper_Product::create_variation_product()->get_id();
		}
		$input['extensions']                  = array( 'other_ext' => array( 'size' => 'huge' ) );
		$input['variations'][0]['extensions'] = array( 'other_ext' => array( 'count' => 'many' ) );
		$input['variations'][1]['extensions'] = array( 'nope' => array( 'x' => 'y' ) );

		$result = wp_get_ability( $ability )->execute( $input );

		$this->assertSame(
			'extensions.other_ext.size does not match the expected format. Reason: extensions.other_ext.size is not one of small and large. variations[0]: extensions.other_ext.count is not of type integer,null. variations[1]: No extension registered the namespace "nope" for this ability. Remove it from the input, or use a namespace from the input schema.',
			$result->get_error_message()
		);
		$this->assertSame( 0, $validated );
		$this->assertSame( 0, wc_get_product_id_by_sku( 'shirt-s' ) );
	}

	/**
	 * @testdox Should suggest only the fields that apply to a variation.
	 */
	public function test_an_unknown_field_on_a_variation_suggests_only_variation_fields(): void {
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				$fields = array(
					'simple_only'    => 'simple',
					'variation_only' => 'variation',
				);
				foreach ( $fields as $field => $object_type ) {
					ExtensibleAbilities::register_field(
						array(
							'resource'      => 'product',
							'namespace'     => 'typed-ext',
							'field'         => $field,
							'output_schema' => array( 'type' => 'string' ),
							'input_schema'  => array( 'type' => 'string' ),
							'object_types'  => array( $object_type ),
							'get_callback'  => static fn() => 'value',
							'set_callback'  => static function () {},
						)
					);
				}
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();
		$input = $this->get_shirt_input();

		$input['variations'][0]['extensions'] = array( 'typed-ext' => array( 'typo' => 'x' ) );

		$result = wp_get_ability( 'woocommerce/product-create' )->execute( $input );

		$this->assertSame(
			'variations[0]: The extension namespace "typed-ext" has no field "typo". The fields you can write in this namespace are: variation_only. Use one of them, or remove "typed-ext.typo" from the input.',
			$result->get_error_message()
		);
	}

	/**
	 * @testdox Should keep the schema error when no extension field is at fault.
	 * @testWith [{"not_a_core_field":"x"}]
	 *           [{"extensions":{"test-ext":"red"}}]
	 *           [{"extensions":"red"}]
	 *
	 * @param array $bad_input Input that only the schema refuses.
	 */
	public function test_the_schema_error_stands_without_a_bad_extension_field( array $bad_input ): void {
		$product = \WC_Helper_Product::create_simple_product();

		$result = wp_get_ability( 'woocommerce/product-update' )->validate_input( array( 'id' => $product->get_id() ) + $bad_input );

		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should keep the extensions input schema closed.
	 */
	public function test_the_extensions_input_schema_stays_closed(): void {
		$extensions = $this->get_ability( 'woocommerce/product-update' )['input_schema']['properties']['extensions'];

		$this->assertFalse( $extensions['additionalProperties'] );
		$this->assertFalse( $extensions['properties']['test-ext']['additionalProperties'] );
	}

	/**
	 * @testdox Should write valid extension fields on the product and on its variations.
	 */
	public function test_valid_extension_fields_on_the_product_and_its_variations_are_written(): void {
		$input = $this->get_shirt_input();

		$input['extensions']                  = array( 'test-ext' => array( 'color' => 'red' ) );
		$input['variations'][0]['extensions'] = array( 'other_ext' => array( 'count' => 3 ) );

		$this->run_ability( 'woocommerce/product-create', $input );

		$this->assertSame( 'red', self::$received['color'] ?? null );
		$this->assertSame( 3, self::$received['count'] ?? null );
		$this->assertGreaterThan( 0, wc_get_product_id_by_sku( 'shirt-s' ) );
	}

	/**
	 * @testdox Should refuse the change and save nothing when an update callback throws.
	 */
	public function test_a_set_callback_that_throws_saves_nothing(): void {
		$product  = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$expected = array(
			'throw' => array( 400, 'test-ext:test_thrown' ),
			'crash' => array( 500, 'test-ext:woocommerce_ability_extension_failed' ),
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
	public function test_a_value_that_fails_its_schema_runs_no_set_callback(): void {
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
	 * @testdox Should give a sync the parent that a variation update saves.
	 */
	public function test_a_sync_gets_the_parent_of_an_updated_variation(): void {
		$saved = array();
		$this->register_sync(
			'product',
			'test-ext',
			static function ( array $objects ) use ( &$saved ) {
				$saved = array_map( static fn( \WC_Data $item ) => $item->get_id(), $objects );
				return null;
			}
		);
		$created = $this->run_ability( 'woocommerce/product-create', $this->get_shirt_input() );

		$this->run_ability(
			'woocommerce/product-update',
			array(
				'id'            => $created['product']['variation_ids'][0],
				'regular_price' => '8.00',
			)
		);

		$this->assertSame( array( $created['product']['variation_ids'][0], $created['product']['id'] ), $saved );
		$this->assertSame( '8.00', wc_get_product( $created['product']['id'] )->get_price() );
	}

	/**
	 * @testdox Should give a validator every object of the call, with the field writes of all of them applied.
	 */
	public function test_a_validator_sees_the_other_variations_of_the_call(): void {
		$seen = array();
		$this->register_validator(
			'product',
			'test-ext',
			static function ( \WC_Product $product, ActionableAbility $ability, array $context ) use ( &$seen ) {
				if ( $product instanceof \WC_Product_Variation ) {
					$seen[ $product->get_sku() ] = array_map( static fn( \WC_Product $item ) => $item->get_meta( '_test_color' ), $context['objects'] );
				}
				return null;
			}
		);
		$input                                = $this->get_shirt_input();
		$input['variations'][0]['extensions'] = array( 'test-ext' => array( 'color' => 'red' ) );
		$input['variations'][1]['extensions'] = array( 'test-ext' => array( 'color' => 'blue' ) );

		$this->run_ability( 'woocommerce/product-create', $input );

		$this->assertSame( array( '', 'red', 'blue' ), $seen['shirt-s'], 'The first variation sees the color of the second one.' );
		$this->assertSame( array( '', 'red', 'blue' ), $seen['shirt-m'] );
	}

	/**
	 * @testdox Should drop the cache of a deleted product when a delete hook throws.
	 */
	public function test_a_throwing_delete_hook_drops_the_cache_of_the_product(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$cleaned = array();
		$record  = static function ( $id ) use ( &$cleaned ) {
			$cleaned[] = (int) $id;
		};
		$this->add_test_action(
			'woocommerce_delete_product',
			static function () use ( $record ) {
				add_action( 'clean_post_cache', $record );
				throw new \RuntimeException( 'Delete hook failed.' );
			}
		);

		try {
			$result = wp_get_ability( 'woocommerce/product-delete' )->execute(
				array(
					'id'    => $product->get_id(),
					'force' => true,
				)
			);
		} finally {
			remove_action( 'clean_post_cache', $record );
		}

		$this->assertWPError( $result );
		$this->assertContains( $product->get_id(), $cleaned );
	}

	/**
	 * @testdox Should fail a trash, and keep the product, when a query of the call hits a deadlock.
	 */
	public function test_a_deadlock_fails_the_call_and_keeps_the_product(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$this->add_test_action(
			'trashed_post',
			static function () {
				global $wpdb;
				$wpdb->last_error = 'Deadlock found when trying to get lock; try restarting transaction';
			}
		);

		$response = $this->run_ability_response( 'woocommerce/product-delete', array( 'id' => $product->get_id() ) );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'woocommerce_transaction_rolled_back', $response->get_data()['code'] );
		$this->assertSame( ProductStatus::PUBLISH, get_post_status( $product->get_id() ) );
	}

	/**
	 * @testdox Should drop the cache of an order when a sync refuses the change.
	 */
	public function test_a_sync_refusal_drops_the_cache_of_the_order(): void {
		$this->register_order_status_ability();
		$order   = \WC_Helper_Order::create_order();
		$cleaned = array();
		$record  = static function ( $id ) use ( &$cleaned ) {
			$cleaned[] = (int) $id;
		};
		$this->register_sync(
			'order',
			'test-ext',
			static function () use ( $record ) {
				add_action( 'clean_post_cache', $record );
				return new \WP_Error( 'test_refused', 'Refused.' );
			}
		);

		try {
			$response = $this->run_ability_response(
				'test-ext/order-status',
				array(
					'id'     => $order->get_id(),
					'status' => 'completed',
				)
			);
		} finally {
			remove_action( 'clean_post_cache', $record );
		}

		$this->assertSame( 400, $response->get_status() );
		$this->assertContains( $order->get_id(), $cleaned );
		$this->assertNotSame( 'completed', wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Should drop the meta cache of a product when a sync refuses the change.
	 */
	public function test_a_sync_refusal_drops_the_meta_cache_of_the_product(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_test_color', 'red' );
		$product->save();
		$this->register_sync(
			'product',
			'test-ext',
			static function ( array $saved ) {
				wc_get_product( $saved[0]->get_id() )->get_meta( '_test_color' );
				return new \WP_Error( 'test_refused', 'Refused.' );
			}
		);

		$response = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'extensions' => array( 'test-ext' => array( 'color' => 'green' ) ),
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'red', get_post_meta( $product->get_id(), '_test_color', true ) );
		$this->assertSame( 'red', wc_get_product( $product->get_id() )->get_meta( '_test_color' ) );
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
		$this->assertSame( 'deleted', $deleted['status'] );
		$this->assertFalse( wc_get_product( $created['product']['id'] ) );
		$this->assertNull( get_post( $created['product']['variation_ids'][0] ), 'The variations go with the parent.' );
	}

	/**
	 * @testdox Should give a validator the primary object, so a new variation can read its unsaved parent.
	 */
	public function test_a_validator_gets_the_primary_object(): void {
		$seen = array();
		$this->register_validator(
			'product',
			'test-ext',
			static function ( \WC_Product $product, ActionableAbility $ability, array $context ) use ( &$seen ) {
				if ( $product instanceof \WC_Product_Variation ) {
					$seen[] = array( $product->get_parent_id(), $context['primary']->get_name(), $context['deleting'] );
				}
				return null;
			}
		);

		$this->run_ability( 'woocommerce/product-create', $this->get_shirt_input() );

		$this->assertSame(
			array(
				array( 0, 'Shirt', false ),
				array( 0, 'Shirt', false ),
			),
			$seen
		);
	}

	/**
	 * @testdox Should run validators on a variation that a parent update deletes, and delete nothing when one refuses.
	 */
	public function test_a_validator_can_refuse_deleting_a_variation_in_an_update(): void {
		$created = $this->run_ability( 'woocommerce/product-create', $this->get_shirt_input() );
		$kept    = $created['product']['variation_ids'][0];
		$other   = $created['product']['variation_ids'][1];
		$seen    = array();
		$this->register_validator(
			'product',
			'test-ext',
			static function ( \WC_Product $product, ActionableAbility $ability, array $context ) use ( &$seen, $kept ) {
				$seen[ $product->get_id() ] = $context['deleting'];
				return $context['deleting'] && $kept === $product->get_id()
					? new \WP_Error( 'test_keep', 'Keep it.' )
					: null;
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();

		$refused = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $created['product']['id'],
				'name'       => 'Renamed',
				'variations' => array(
					array(
						'id'     => $kept,
						'delete' => true,
					),
				),
			)
		);
		$deleted = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $created['product']['id'],
				'variations' => array(
					array(
						'id'     => $other,
						'delete' => true,
					),
				),
			)
		);

		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 'Keep it.', $refused->get_data()['message'] );
		$this->assertSame( 'Shirt', wc_get_product( $created['product']['id'] )->get_name() );
		$this->assertInstanceOf( \WC_Product_Variation::class, wc_get_product( $kept ) );
		$this->assertSame( 200, $deleted->get_status() );
		$this->assertTrue( $seen[ $other ] );
		$this->assertFalse( $seen[ $created['product']['id'] ] );
		$this->assertNull( get_post( $other ) );
	}

	/**
	 * @testdox Should run validators on the variations of a deleted parent, and delete nothing when one refuses.
	 */
	public function test_a_validator_can_refuse_deleting_a_variable_parent(): void {
		$created = $this->run_ability( 'woocommerce/product-create', $this->get_shirt_input() );
		$kept    = $created['product']['variation_ids'][1];
		$seen    = array();
		$refuse  = true;
		$this->register_validator(
			'product',
			'test-ext',
			static function ( \WC_Product $product, ActionableAbility $ability, array $context ) use ( &$seen, &$refuse, $kept ) {
				$seen[ $product->get_id() ] = array( $ability->get_name(), $context['deleting'] );
				return $refuse && $kept === $product->get_id()
					? new \WP_Error( 'test_keep', 'Keep it.' )
					: null;
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();
		$input = array(
			'id'    => $created['product']['id'],
			'force' => true,
		);

		$refused = $this->run_ability_response( 'woocommerce/product-delete', $input );
		$after   = wc_get_product( $created['product']['id'] );
		$refuse  = false;
		$deleted = $this->run_ability( 'woocommerce/product-delete', $input );

		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 'Keep it.', $refused->get_data()['message'] );
		$this->assertInstanceOf( \WC_Product_Variable::class, $after );
		$this->assertSame( 'deleted', $deleted['status'] );
		foreach ( $created['product']['variation_ids'] as $variation_id ) {
			$this->assertSame( array( 'woocommerce/product-delete', true ), $seen[ $variation_id ] );
		}
		$this->assertSame( array( 'woocommerce/product-delete', true ), $seen[ $created['product']['id'] ] );
	}

	/**
	 * @testdox Should let an order validator refuse a trash and a permanent delete, and give the syncs the removed order.
	 */
	public function test_a_validator_can_refuse_deleting_an_order(): void {
		$order    = \WC_Helper_Order::create_order();
		$order_id = $order->get_id();
		$refuse   = true;
		$seen     = array();
		$synced   = array();
		$this->register_validator(
			'order',
			'test-ext',
			static function ( \WC_Order $order, ActionableAbility $ability, array $context ) use ( &$seen, &$refuse ) {
				$seen[] = array( $order->get_id(), $ability->get_name(), $context['deleting'] );
				return $refuse ? new \WP_Error( 'test_cancel_first', 'Cancel it first.' ) : null;
			}
		);
		$this->register_sync(
			'order',
			'test-ext',
			static function ( array $saved, ActionableAbility $ability, array $context ) use ( &$synced ) {
				unset( $ability );
				$synced[] = array(
					count( $saved ),
					array_map( static fn( \WC_Order $order ) => $order->get_id(), $context['trashed'] ),
					array_map( static fn( \WC_Order $order ) => $order->get_id(), $context['deleted'] ),
				);
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();

		$refused_trash  = $this->run_ability_response( 'woocommerce/order-delete', array( 'id' => $order_id ) );
		$refused_delete = $this->run_ability_response(
			'woocommerce/order-delete',
			array(
				'id'    => $order_id,
				'force' => true,
			)
		);
		$after_refusals = wc_get_order( $order_id )->get_status();
		$refuse         = false;
		$trashed        = $this->run_ability( 'woocommerce/order-delete', array( 'id' => $order_id ) );
		$deleted        = $this->run_ability(
			'woocommerce/order-delete',
			array(
				'id'    => $order_id,
				'force' => true,
			)
		);

		foreach ( array( $refused_trash, $refused_delete ) as $refused ) {
			$this->assertSame( 400, $refused->get_status() );
			$this->assertSame( 'test-ext:test_cancel_first', $refused->get_data()['code'] );
		}
		$this->assertNotSame( 'trash', $after_refusals, 'A refused call should leave the order where it was.' );
		$this->assertSame( array( $order_id, 'woocommerce/order-delete', true ), $seen[0] );
		$this->assertSame( 'trash', $trashed['status'] );
		$this->assertSame( 'deleted', $deleted['status'] );
		$this->assertFalse( wc_get_order( $order_id ) );
		$this->assertSame(
			array(
				array( 0, array( $order_id ), array() ),
				array( 0, array(), array( $order_id ) ),
			),
			$synced,
			'The syncs should see the removed order, with its ID, and no saved object.'
		);
	}

	/**
	 * @testdox Should run validators on the refunds of a force-deleted order, delete nothing when one refuses, and give the syncs the deleted refunds.
	 */
	public function test_a_validator_can_refuse_deleting_the_refunds_of_an_order(): void {
		$order     = \WC_Helper_Order::create_order();
		$order_id  = $order->get_id();
		$refund_id = wc_create_refund(
			array(
				'order_id' => $order_id,
				'amount'   => 1,
			)
		)->get_id();
		$refuse    = true;
		$seen      = array();
		$deleted   = array();
		$this->register_validator(
			'order',
			'test-ext',
			static function ( \WC_Abstract_Order $order, ActionableAbility $ability, array $context ) use ( &$seen, &$refuse, $refund_id ) {
				unset( $ability );
				$seen[ $order->get_id() ] = $context['deleting'];
				return $refuse && $refund_id === $order->get_id() ? new \WP_Error( 'test_keep_refund', 'Keep the refund.' ) : null;
			}
		);
		$this->register_sync(
			'order',
			'test-ext',
			static function ( array $saved, ActionableAbility $ability, array $context ) use ( &$deleted ) {
				unset( $saved, $ability );
				$deleted = array_map( static fn( \WC_Abstract_Order $order ) => $order->get_id(), $context['deleted'] );
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();
		$input = array(
			'id'    => $order_id,
			'force' => true,
		);

		$refused = $this->run_ability_response( 'woocommerce/order-delete', $input );
		$kept    = array( wc_get_order( $order_id ), wc_get_order( $refund_id ) );
		$refuse  = false;
		$result  = $this->run_ability( 'woocommerce/order-delete', $input );

		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 'test-ext:test_keep_refund', $refused->get_data()['code'] );
		$this->assertInstanceOf( \WC_Order::class, $kept[0], 'A refused refund should keep the order.' );
		$this->assertInstanceOf( \WC_Order_Refund::class, $kept[1] );
		$this->assertTrue( $seen[ $refund_id ], 'The validators should see the refund as deleted.' );
		$this->assertSame( 'deleted', $result['status'] );
		$this->assertSame( array( $order_id, $refund_id ), $deleted );
	}

	/**
	 * @testdox Should run validators on a child order and its refund that HPOS deletes with the parent, and delete nothing when one refuses.
	 */
	public function test_a_validator_can_refuse_deleting_a_child_order(): void {
		$original = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->toggle_cot_feature_and_usage( true );

		try {
			$order_id = \WC_Helper_Order::create_order()->get_id();
			$child    = \WC_Helper_Order::create_order();
			$child->set_parent_id( $order_id );
			$child->save();
			$child_id      = $child->get_id();
			$grandchild_id = wc_create_refund(
				array(
					'order_id' => $child_id,
					'amount'   => 1,
				)
			)->get_id();
			$refuse        = true;
			$seen          = array();
			$deleted       = array();
			$this->register_validator(
				'order',
				'test-ext',
				static function ( \WC_Abstract_Order $order, ActionableAbility $ability, array $context ) use ( &$seen, &$refuse, $grandchild_id ) {
					unset( $ability );
					$seen[ $order->get_id() ] = $context['deleting'];
					return $refuse && $grandchild_id === $order->get_id() ? new \WP_Error( 'test_keep_child', 'Keep the refund of the child.' ) : null;
				}
			);
			$this->register_sync(
				'order',
				'test-ext',
				static function ( array $saved, ActionableAbility $ability, array $context ) use ( &$deleted ) {
					unset( $saved, $ability );
					$deleted = array_map( static fn( \WC_Abstract_Order $order ) => $order->get_id(), $context['deleted'] );
				}
			);
			$this->reset_registries();
			AbilitiesLoader::init_extensions();
			$input = array(
				'id'    => $order_id,
				'force' => true,
			);

			$refused = $this->run_ability_response( 'woocommerce/order-delete', $input );
			$kept    = array( wc_get_order( $order_id ), wc_get_order( $child_id ), wc_get_order( $grandchild_id ) );
			$refuse  = false;
			$result  = $this->run_ability( 'woocommerce/order-delete', $input );

			$this->assertSame( 400, $refused->get_status() );
			$this->assertSame( 'test-ext:test_keep_child', $refused->get_data()['code'] );
			$this->assertInstanceOf( \WC_Order::class, $kept[0], 'A refused refund of the child should keep the parent.' );
			$this->assertInstanceOf( \WC_Order::class, $kept[1] );
			$this->assertInstanceOf( \WC_Order_Refund::class, $kept[2] );
			$this->assertTrue( $seen[ $child_id ], 'The validators should see the child as deleted.' );
			$this->assertTrue( $seen[ $grandchild_id ], 'The validators should see the refund of the child as deleted.' );
			$this->assertSame( 'deleted', $result['status'] );
			$this->assertFalse( wc_get_order( $child_id ), 'HPOS should delete the child with its parent.' );
			$this->assertFalse( wc_get_order( $grandchild_id ), 'HPOS should delete the refund of the child too.' );
			$this->assertSame( array( $order_id, $child_id, $grandchild_id ), $deleted );
		} finally {
			$this->toggle_cot_feature_and_usage( $original );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * @testdox Should refuse a forced delete and keep everything when a refund appears after the validators ran.
	 *
	 * @dataProvider provider_order_storage
	 *
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	public function test_a_refund_added_after_validation_stops_a_forced_delete( bool $hpos ): void {
		$original = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->toggle_cot_feature_and_usage( $hpos );

		try {
			$order_id  = \WC_Helper_Order::create_order()->get_id();
			$refund_id = 0;
			$this->register_validator(
				'order',
				'test-ext',
				static function ( \WC_Abstract_Order $order ) use ( &$refund_id, $order_id ) {
					if ( 0 === $refund_id && $order_id === $order->get_id() ) {
						$refund_id = wc_create_refund(
							array(
								'order_id' => $order_id,
								'amount'   => 1,
							)
						)->get_id();
					}
					return null;
				}
			);
			$this->reset_registries();
			AbilitiesLoader::init_extensions();

			$result = $this->run_ability_response(
				'woocommerce/order-delete',
				array(
					'id'    => $order_id,
					'force' => true,
				)
			);

			$this->assertSame( 409, $result->get_status() );
			$this->assertSame( 'woocommerce_ability_order_children_changed', $result->get_data()['code'] );
			$this->assertInstanceOf( \WC_Order::class, wc_get_order( $order_id ) );
			$this->assertInstanceOf( \WC_Order_Refund::class, wc_get_order( $refund_id ) );
		} finally {
			$this->toggle_cot_feature_and_usage( $original );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * Order storage modes.
	 *
	 * @return array<string, array{0: bool}>
	 */
	public function provider_order_storage(): array {
		return array(
			'posts storage' => array( false ),
			'HPOS storage'  => array( true ),
		);
	}

	/**
	 * @testdox Should restore a trashed or deleted order and its refund when a sync refuses, with HPOS and with posts storage, with and without sync.
	 * @testWith [true, false]
	 *           [true, true]
	 *           [false, false]
	 *           [false, true]
	 *
	 * @param bool $hpos Whether HPOS is on.
	 * @param bool $sync Whether HPOS and posts storage sync.
	 */
	public function test_a_sync_refusal_restores_a_removed_order( bool $hpos, bool $sync ): void {
		$original = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->toggle_cot_feature_and_usage( $hpos );
		if ( $sync ) {
			$this->enable_cot_sync();
		} else {
			$this->disable_cot_sync();
		}

		try {
			$order  = \WC_Helper_Order::create_order();
			$status = $order->get_status();
			$refund = wc_create_refund(
				array(
					'order_id' => $order->get_id(),
					'amount'   => 1,
				)
			);
			$this->register_sync(
				'order',
				'test-ext',
				static fn() => new \WP_Error( 'test_ledger_down', 'Ledger is down.' )
			);
			$this->reset_registries();
			AbilitiesLoader::init_extensions();

			$trash = $this->run_ability_response( 'woocommerce/order-delete', array( 'id' => $order->get_id() ) );
			$after = wc_get_order( $order->get_id() );
			$force = $this->run_ability_response(
				'woocommerce/order-delete',
				array(
					'id'    => $order->get_id(),
					'force' => true,
				)
			);

			$this->assertSame( 400, $trash->get_status() );
			$this->assertSame( $status, $after->get_status(), 'A refused trash should leave the order out of the trash.' );
			$this->assertSame( 400, $force->get_status() );
			$this->assertInstanceOf( \WC_Order::class, wc_get_order( $order->get_id() ), 'A refused delete should keep the order.' );
			$this->assertInstanceOf( \WC_Order_Refund::class, wc_get_order( $refund->get_id() ), 'A refused delete should keep the refund.' );
		} finally {
			remove_all_filters( 'pre_option_' . DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION );
			$this->toggle_cot_feature_and_usage( $original );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * @testdox Should return the stored slug from product-create, for the product and its variations.
	 */
	public function test_product_create_returns_the_stored_slug(): void {
		$created = $this->run_ability(
			'woocommerce/product-create',
			array_merge( $this->get_shirt_input(), array( 'status' => 'publish' ) )
		);

		$this->assertSame( 'shirt', $created['product']['slug'] );
		$this->assertSame( get_post_field( 'post_name', $created['variations'][0]['id'] ), $created['variations'][0]['slug'] );
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
		$this->assertStringEndsWith( 'test_range_refused', $response->get_data()['code'] );
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
	 * @testdox Should refuse the call and save nothing when a secondary object names a resource that is not registered.
	 */
	public function test_a_secondary_object_with_an_unregistered_resource_is_refused(): void {
		$this->setExpectedIncorrectUsage( TestProductWithItemAbility::class . '::change' );
		$this->register_product_with_item_ability();
		TestProductWithItemAbility::$item_resource = 'refund';
		$product                                   = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );

		try {
			$response = $this->run_ability_response(
				'test-ext/product-with-item',
				array(
					'id'   => $product->get_id(),
					'name' => 'Pencil',
				)
			);
		} finally {
			TestProductWithItemAbility::$item_resource = 'order_item';
		}

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_ability_resource_unknown', $response->get_data()['code'] );
		$this->assertSame( 'refund', $response->get_data()['data']['resource'] );
		$this->assertSame( 'test-ext/product-with-item', $response->get_data()['data']['ability'] );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
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
	 * @testdox Should call WC_Order::update_status() with the feature off, and refuse an order class that only overrides it with the feature on.
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

			$response = $this->run_ability_response(
				'woocommerce/order-update-status',
				array(
					'id'     => $order->get_id(),
					'status' => 'completed',
				)
			);

			$calls = $order_class::$calls;
			if ( $enabled ) {
				$this->assertSame( 400, $response->get_status() );
				$this->assertSame( 'woocommerce_data_staging_unsupported', $response->get_data()['code'] );
				$this->assertSame( 0, $calls );
				$this->assertSame( 'pending', wc_get_order( $order->get_id() )->get_status() );
			} else {
				$this->assertSame( 200, $response->get_status() );
				$this->assertSame( 1, $calls );
				$this->assertSame( 'completed', wc_get_order( $order->get_id() )->get_status() );
			}
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

		$this->assertSame( array( 'small', 'large' ), $schema['anyOf'][0]['enum'] );
		$this->assertSame( array( 'type' => 'null' ), $schema['anyOf'][1] );
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

		$this->assertSame( array( 'string', 'null' ), array_column( $schema['properties']['test-ext']['properties']['color']['anyOf'], 'type' ) );
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
	 * @testdox Should save nothing when change() returns a WP_Error without errors.
	 */
	public function test_an_empty_error_from_change_saves_nothing(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$ability = new class(
			'test-ext/product-rename',
			array(
				'label'               => 'Rename product',
				'description'         => 'Renames a product.',
				'category'            => 'woocommerce',
				'output_schema'       => array( 'type' => 'object' ),
				'permission_callback' => '__return_true',
			)
		) extends TestProductRenameAbility {
			/**
			 * Rename the product, then refuse with an empty error.
			 *
			 * @param \WC_Product $subject Product.
			 * @param array       $input   Ability input.
			 * @return \WP_Error
			 */
			protected function change( $subject, array $input ) {
				parent::change( $subject, $input );
				return new \WP_Error();
			}
		};

		$result = $ability->execute_change(
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should return a fixed 500 error when the order write fails, though the order's save() catches the exception.
	 */
	public function test_a_failed_order_write_returns_a_fixed_error(): void {
		$this->register_order_status_ability();
		$order = \WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();
		$throw = static function () {
			throw new \RuntimeException( 'Storage text.' );
		};
		add_action( 'woocommerce_before_order_object_save', $throw );

		$response = $this->run_ability_response(
			'test-ext/order-status',
			array(
				'id'     => $order->get_id(),
				'status' => 'completed',
			)
		);

		remove_action( 'woocommerce_before_order_object_save', $throw );
		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_ability_save_failed', $response->get_data()['code'] );
		$this->assertStringNotContainsString( 'Storage text.', wp_json_encode( $response->get_data() ) );
		$this->assertSame( 'pending', wc_get_order( $order->get_id() )->get_status() );
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

			$this->assertSame( array( 'string', 'null' ), array_column( $extensions['test-ext']['properties']['color']['anyOf'], 'type' ) );
			$this->assertSame( array( 'integer', 'null' ), array_column( $extensions['other_ext']['properties']['count']['anyOf'], 'type' ) );
			$this->assertArrayNotHasKey( 'code', $extensions['test-ext']['properties'] );
			$this->assertSame( 2, substr_count( wp_json_encode( $schema ), '"color"' ) );
			$this->assertArrayNotHasKey( 'oneOf', $schema );
		}
		$order = $this->get_ability( 'woocommerce/order-update-status' )['input_schema'];
		$this->assertSame( array( 'string', 'null' ), array_column( $order['properties']['extensions']['properties']['test-ext']['properties']['gift_message']['anyOf'], 'type' ) );
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
					'resource'      => 'product',
					'namespace'     => 'test-ext',
					'field'         => 'plan',
					'output_schema' => array( 'type' => 'string' ),
					'input_schema'  => array( 'type' => 'string' ),
					'object_types'  => array( 'simple' ),
					'get_callback'  => static fn() => 'monthly',
					'set_callback'  => static function ( \WC_Product $product, $value ) {
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

		$this->assertSame( 'Applies to: simple.', $schema['properties']['extensions']['properties']['test-ext']['properties']['plan']['description'] );
		$this->assertArrayHasKey( 'plan', $schema['properties']['extensions']['properties']['test-ext']['properties'] );
		$this->assertSame( 400, $rejected->get_status() );
		$this->assertSame( 'test-ext:woocommerce_ability_field_object_type', $rejected->get_data()['code'] );
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
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				$fields = array(
					'simple_only'    => array( 'simple' ),
					'variation_only' => array( 'variation' ),
				);
				foreach ( $fields as $field => $object_types ) {
					ExtensibleAbilities::register_field(
						array(
							'resource'      => 'product',
							'namespace'     => 'typed-ext',
							'field'         => $field,
							'output_schema' => array( 'type' => 'string' ),
							'input_schema'  => array( 'type' => 'string' ),
							'object_types'  => $object_types,
							'get_callback'  => static fn() => 'value',
							'set_callback'  => static function ( \WC_Product $product, $value ) use ( $field ) {
								$product->update_meta_data( '_test_' . $field, $value );
							},
						)
					);
				}
				ExtensibleAbilities::register_field(
					array(
						'resource'      => 'product',
						'namespace'     => 'simple-only-ext',
						'field'         => 'plan',
						'output_schema' => array( 'type' => 'string' ),
						'object_types'  => array( 'simple' ),
						'get_callback'  => static fn() => 'value',
					)
				);
			}
		);
		$this->reset_registries();

		$ability = $this->get_ability( 'woocommerce/product-create' );
		$output  = $this->run_ability( 'woocommerce/product-create', $this->get_shirt_input() );

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
	 * @testdox Should refuse a variation field that does not apply to a variation through the closed variation schema.
	 */
	public function test_refuses_a_variation_field_that_does_not_apply_to_a_variation(): void {
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'      => 'product',
						'namespace'     => 'typed-ext',
						'field'         => 'simple_only',
						'output_schema' => array( 'type' => 'string' ),
						'input_schema'  => array( 'type' => 'string' ),
						'object_types'  => array( 'simple' ),
						'get_callback'  => static fn() => 'value',
						'set_callback'  => static function ( \WC_Product $product, $value ) {
							$product->update_meta_data( '_test_simple_only', $value );
						},
					)
				);
			}
		);
		$this->reset_registries();

		$input                                = $this->get_shirt_input();
		$input['variations'][0]['extensions'] = array( 'typed-ext' => array( 'simple_only' => 'nope' ) );
		$schema                               = $this->get_ability( 'woocommerce/product-create' )['input_schema'];
		$refused                              = $this->run_ability_response( 'woocommerce/product-create', $input );

		$this->assertArrayNotHasKey( 'typed-ext', $schema['properties']['variations']['items']['properties']['extensions']['properties'] );
		$this->assertFalse( $schema['properties']['variations']['items']['properties']['extensions']['additionalProperties'] );
		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 'woocommerce_ability_invalid_fields', $refused->get_data()['code'] );
		$this->assertSame( 'variations[0]: The extension namespace "typed-ext" has no field "simple_only", and it has no fields you can write. Remove "typed-ext.simple_only" from the input.', $refused->get_data()['message'] );
		$this->assertSame( 0, wc_get_product_id_by_sku( 'shirt-s' ) );
	}

	/**
	 * @testdox Should name a variation field that does not apply to a variation and list the fields that do.
	 */
	public function test_a_variation_field_that_does_not_apply_lists_the_variation_fields(): void {
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				$fields = array(
					'simple_only'    => 'simple',
					'variation_only' => 'variation',
				);
				foreach ( $fields as $field => $object_type ) {
					ExtensibleAbilities::register_field(
						array(
							'resource'      => 'product',
							'namespace'     => 'typed-ext',
							'field'         => $field,
							'output_schema' => array( 'type' => 'string' ),
							'input_schema'  => array( 'type' => 'string' ),
							'object_types'  => array( $object_type ),
							'get_callback'  => static fn() => 'value',
							'set_callback'  => static function () {},
						)
					);
				}
			}
		);
		$this->reset_registries();

		$input                                = $this->get_shirt_input();
		$input['variations'][1]['extensions'] = array( 'typed-ext' => array( 'simple_only' => 'nope' ) );
		$refused                              = $this->run_ability_response( 'woocommerce/product-create', $input );

		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 'woocommerce_ability_invalid_fields', $refused->get_data()['code'] );
		$this->assertSame( 'variations[1]: The extension namespace "typed-ext" has no field "simple_only". The fields you can write in this namespace are: variation_only. Use one of them, or remove "typed-ext.simple_only" from the input.', $refused->get_data()['message'] );
		$this->assertSame( 0, wc_get_product_id_by_sku( 'shirt-s' ) );
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
						'resource'      => 'product',
						'namespace'     => 'test-ext',
						'field'         => 'plan',
						'output_schema' => array( 'type' => 'string' ),
						'input_schema'  => array( 'type' => 'string' ),
						'object_types'  => array( 'test_variation' ),
						'get_callback'  => static fn( \WC_Product $product ) => $product->get_meta( '_test_plan' ),
						'set_callback'  => static function ( \WC_Product $product, $value ) {
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
	 * @testdox Should refuse a type change into or out of a variable product type that an extension registers.
	 */
	public function test_refuses_a_type_change_into_or_out_of_a_variable_subclass(): void {
		$this->register_variation_subclass();

		$input         = $this->get_shirt_input();
		$input['type'] = 'test-variable';
		$created       = $this->run_ability( 'woocommerce/product-create', $input );
		$simple        = \WC_Helper_Product::create_simple_product();

		$out_of = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'   => $created['product']['id'],
				'type' => 'simple',
			)
		);
		$into   = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'   => $simple->get_id(),
				'type' => 'test-variable',
			)
		);

		$this->assertSame( 'woocommerce_product_type_unsupported', $out_of->get_data()['code'] );
		$this->assertSame( 'woocommerce_product_type_unsupported', $into->get_data()['code'] );
		$this->assertInstanceOf( TestVariableProduct::class, wc_get_product( $created['product']['id'] ) );
		$this->assertCount( 2, wc_get_product( $created['product']['id'] )->get_children() );
		$this->assertSame( 'simple', wc_get_product( $simple->get_id() )->get_type() );
	}

	/**
	 * @testdox Should refuse a type change into a variable class that a class filter returns only for a saved product.
	 */
	public function test_refuses_a_type_change_when_the_class_filter_depends_on_the_product_id(): void {
		$this->add_test_action(
			'product_type_selector',
			static fn( array $types ): array => $types + array( 'test-variable' => 'Test variable' )
		);
		$this->add_test_action(
			'woocommerce_data_stores',
			static fn( array $stores ): array => $stores + array( 'product-test-variable' => 'WC_Product_Variable_Data_Store_CPT' )
		);
		$unsaved_class = get_class(
			new class() extends \WC_Product_Simple {
				/**
				 * Get the product type.
				 *
				 * @return string
				 */
				public function get_type() {
					return 'test-variable';
				}
			}
		);
		$this->add_test_action(
			'woocommerce_product_class',
			static function ( $classname, $product_type, $post_type, $product_id ) use ( $unsaved_class ) {
				if ( 'test-variable' !== $product_type ) {
					return $classname;
				}
				return $product_id > 0 ? TestVariableProduct::class : $unsaved_class;
			},
			10,
			4
		);
		$simple = \WC_Helper_Product::create_simple_product();

		$refused = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'   => $simple->get_id(),
				'type' => 'test-variable',
			)
		);

		$this->assertSame( 'woocommerce_product_type_unsupported', $refused->get_data()['code'] ?? null );
		$this->assertSame( 'simple', wc_get_product( $simple->get_id() )->get_type() );
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
	 * @testdox Should write an order extension field and the shipping address in one save, and save nothing when a validator refuses.
	 */
	public function test_order_update_writes_extension_fields_and_runs_validators(): void {
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'      => 'order',
						'namespace'     => 'test-ext',
						'field'         => 'priority',
						'output_schema' => array( 'type' => 'string' ),
						'input_schema'  => array( 'type' => 'string' ),
						'get_callback'  => static function ( \WC_Order $order ) {
							return $order->get_meta( '_test_priority' );
						},
						'set_callback'  => static function ( \WC_Order $order, $value ) {
							$order->update_meta_data( '_test_priority', $value );
						},
					)
				);
			}
		);
		$this->register_validator(
			'order',
			'test-ext',
			static function ( \WC_Order $order ) {
				return 'Closed' === $order->get_shipping_city() ? new \WP_Error( 'test_refused', 'Refused.' ) : null;
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();
		$order = \WC_Helper_Order::create_order();
		$saves = 0;
		$count = static function () use ( &$saves ) {
			++$saves;
		};
		$this->add_test_action( 'woocommerce_update_order', $count );

		$output  = $this->run_ability(
			'woocommerce/order-update',
			array(
				'id'         => $order->get_id(),
				'shipping'   => array( 'city' => 'Lisbon' ),
				'extensions' => array( 'test-ext' => array( 'priority' => 'high' ) ),
			)
		);
		$refused = $this->run_ability_response(
			'woocommerce/order-update',
			array(
				'id'         => $order->get_id(),
				'shipping'   => array( 'city' => 'Closed' ),
				'extensions' => array( 'test-ext' => array( 'priority' => 'low' ) ),
			)
		);

		wp_cache_flush();
		$saved = wc_get_order( $order->get_id() );
		$this->assertSame( 1, $saves );
		$this->assertSame( 'high', $output['order']['extensions']['test-ext']['priority'] );
		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 'test-ext:test_refused', $refused->get_data()['code'] );
		$this->assertSame( 'Lisbon', $saved->get_shipping_city() );
		$this->assertSame( 'high', $saved->get_meta( '_test_priority' ) );
	}

	/**
	 * @testdox Should create an order with an extension field in one save, show validators the sent status, and create nothing when a validator refuses.
	 */
	public function test_order_create_writes_extension_fields_and_runs_validators(): void {
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'      => 'order',
						'namespace'     => 'test-ext',
						'field'         => 'priority',
						'output_schema' => array( 'type' => 'string' ),
						'input_schema'  => array( 'type' => 'string' ),
						'get_callback'  => static function ( \WC_Order $order ) {
							return $order->get_meta( '_test_priority' );
						},
						'set_callback'  => static function ( \WC_Order $order, $value ) {
							$order->update_meta_data( '_test_priority', $value );
						},
					)
				);
			}
		);
		$seen = array();
		$this->register_validator(
			'order',
			'test-ext',
			static function ( \WC_Order $order ) use ( &$seen ) {
				$seen[] = array( $order->get_status(), count( $order->get_items() ), $order->get_total() );
				return 'Closed' === $order->get_shipping_city() ? new \WP_Error( 'test_refused', 'Refused.' ) : null;
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();
		$product = \WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '10' ) );
		$writes  = array(
			'woocommerce_new_order'    => 0,
			'woocommerce_update_order' => 0,
		);
		foreach ( array_keys( $writes ) as $hook ) {
			$this->add_test_action(
				$hook,
				static function () use ( &$writes, $hook ) {
					++$writes[ $hook ];
				}
			);
		}
		$before = wc_get_orders(
			array(
				'limit'  => -1,
				'return' => 'ids',
			)
		);

		$output  = $this->run_ability(
			'woocommerce/order-create',
			array(
				'status'     => 'processing',
				'shipping'   => array( 'city' => 'Lisbon' ),
				'line_items' => array( array( 'product_id' => $product->get_id() ) ),
				'extensions' => array( 'test-ext' => array( 'priority' => 'high' ) ),
			)
		);
		$refused = $this->run_ability_response(
			'woocommerce/order-create',
			array(
				'shipping'   => array( 'city' => 'Closed' ),
				'line_items' => array( array( 'product_id' => $product->get_id() ) ),
				'extensions' => array( 'test-ext' => array( 'priority' => 'low' ) ),
			)
		);

		wp_cache_flush();
		$saved = wc_get_order( $output['order']['id'] );
		$this->assertSame( 1, $writes['woocommerce_new_order'] );
		$this->assertSame( 0, $writes['woocommerce_update_order'], 'The order should save once, with its lines, totals and status.' );
		$this->assertSame( array( 'processing', 1, '10.00' ), $seen[0], 'The validator should see the status and totals that the save writes.' );
		$this->assertSame( 'high', $output['order']['extensions']['test-ext']['priority'] );
		$this->assertSame( 'high', $saved->get_meta( '_test_priority' ) );
		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 'test-ext:test_refused', $refused->get_data()['code'] );
		$this->assertEqualsCanonicalizing(
			array_merge( $before, array( $saved->get_id() ) ),
			wc_get_orders(
				array(
					'limit'  => -1,
					'return' => 'ids',
				)
			),
			'The refused call should create no order.'
		);
	}

	/**
	 * @testdox Should show an order validator the saved address fields that Core refuses, and save nothing.
	 */
	public function test_an_order_validator_sees_the_saved_values_of_refused_address_fields(): void {
		$seen = array();
		$this->register_validator(
			'order',
			'test-ext',
			static function ( \WC_Order $order ) use ( &$seen ) {
				$seen[] = array( $order->get_shipping_country(), $order->get_shipping_state(), $order->get_shipping_postcode(), $order->get_shipping_city() );
				return null;
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();
		$order = \WC_Helper_Order::create_order();
		$order->set_shipping_country( 'US' );
		$order->set_shipping_state( 'CA' );
		$order->set_shipping_postcode( '94110' );
		$order->set_shipping_city( 'Before' );
		$order->save();

		$bad_country  = $this->run_ability_response(
			'woocommerce/order-update',
			array(
				'id'       => $order->get_id(),
				'shipping' => array(
					'country'  => 'XX',
					'state'    => 'ZZ',
					'postcode' => 'nope',
					'city'     => 'After',
				),
			)
		);
		$bad_postcode = $this->run_ability_response(
			'woocommerce/order-update',
			array(
				'id'       => $order->get_id(),
				'shipping' => array(
					'postcode' => 'not a zip',
					'city'     => 'After',
				),
			)
		);

		$this->assertSame( 400, $bad_country->get_status() );
		$this->assertSame( 400, $bad_postcode->get_status() );
		$this->assertSame(
			array(
				array( 'US', 'CA', '94110', 'After' ),
				array( 'US', 'CA', '94110', 'After' ),
			),
			$seen
		);
		wp_cache_flush();
		$this->assertSame( 'Before', wc_get_order( $order->get_id() )->get_shipping_city() );
	}

	/**
	 * @testdox Should recalculate with calculate_totals() after the save when the feature is off.
	 */
	public function test_order_update_uses_the_saving_totals_with_the_feature_off(): void {
		$this->set_feature( false );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		\WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate'          => '10',
				'tax_rate_name'     => 'Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 1,
				'tax_rate_class'    => '',
			)
		);
		$product = \WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '10' ) );
		$order   = wc_create_order();
		$order->add_product( $product, 1 );
		$order->set_status( 'pending' );
		$order->save();

		$calls  = array();
		$record = static function ( $and_taxes, $listened, $staged ) use ( &$calls ) {
			$calls[] = array(
				'staged'   => $staged,
				'quantity' => array_sum( array_map( static fn( $item ) => $item->get_quantity(), wc_get_order( $listened->get_id() )->get_items() ) ),
				'tax_ids'  => array_map( static fn( $tax ) => $tax->get_id(), array_values( $listened->get_taxes() ) ),
			);
		};
		add_action( 'woocommerce_order_after_calculate_totals', $record, 10, 3 );

		$result = wp_get_ability( 'woocommerce/order-update' )->execute(
			array(
				'id'         => $order->get_id(),
				'line_items' => array(
					array(
						'id'       => array_key_first( $order->get_items() ),
						'quantity' => 3,
					),
				),
			)
		);
		remove_action( 'woocommerce_order_after_calculate_totals', $record, 10 );
		update_option( 'woocommerce_calc_taxes', 'no' );

		$this->assertNotWPError( $result );
		$this->assertCount( 1, $calls );
		$this->assertFalse( $calls[0]['staged'] );
		$this->assertSame( 3, $calls[0]['quantity'] );
		$this->assertGreaterThan( 0, $calls[0]['tax_ids'][0] );
		$this->assertSame( '33.00', wc_get_order( $order->get_id() )->get_total() );
	}

	/**
	 * @testdox Should save no line change and fire no totals action when a validator refuses, and fire it staged once when the change saves.
	 */
	public function test_order_update_refusal_saves_no_line_change(): void {
		$this->register_validator(
			'order',
			'test-ext',
			static function ( \WC_Order $order ) {
				return count( $order->get_shipping_methods() ) === 0 ? new \WP_Error( 'test_refused', 'Keep one shipping line.' ) : null;
			}
		);
		$this->reset_registries();
		AbilitiesLoader::init_extensions();
		$order = \WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();
		$shipping_id = array_key_first( $order->get_shipping_methods() );
		$total       = $order->get_total();
		$staged      = array();
		$record      = static function ( $and_taxes, $order, $is_staged = false ) use ( &$staged ) {
			$staged[] = $is_staged;
		};
		add_action( 'woocommerce_order_after_calculate_totals', $record, 10, 3 );

		$refused = $this->run_ability_response(
			'woocommerce/order-update',
			array(
				'id'           => $order->get_id(),
				'remove_items' => array( $shipping_id ),
			)
		);

		wp_cache_flush();
		$saved = wc_get_order( $order->get_id() );
		$this->assertSame( 'test-ext:test_refused', $refused->get_data()['code'] );
		$this->assertArrayHasKey( $shipping_id, $saved->get_shipping_methods() );
		$this->assertSame( $total, $saved->get_total() );
		$this->assertSame( array(), $staged );

		$saves = 0;
		$count = static function () use ( &$saves ) {
			++$saves;
		};
		add_action( 'woocommerce_update_order', $count );

		$output = $this->run_ability(
			'woocommerce/order-update',
			array(
				'id'             => $order->get_id(),
				'shipping_lines' => array(
					array(
						'id'    => $shipping_id,
						'total' => '25.00',
					),
				),
			)
		);

		remove_action( 'woocommerce_update_order', $count );
		remove_action( 'woocommerce_order_after_calculate_totals', $record, 10 );
		$this->assertSame( 1, $saves );
		$this->assertSame( array( true ), $staged );
		$this->assertSame( '25.00', wc_format_decimal( $output['order']['shipping_lines'][0]['total'], 2 ) );
	}

	/**
	 * @testdox Should refuse a line change, and save nothing, when the order class overrides add_product() without its twin.
	 */
	public function test_order_update_refuses_class_without_stage_twin(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();
		$order_class  = get_class(
			new class() extends \WC_Order {
				/**
				 * Add a product with a rule of this class.
				 *
				 * @param \WC_Product $product Product.
				 * @param int         $qty     Quantity.
				 * @param array       $args    Arguments.
				 * @return int
				 */
				public function add_product( $product, $qty = 1, $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- The test needs an add_product() override without a stage_product_item() twin.
					return parent::add_product( $product, $qty, $args );
				}
			}
		);
		$filter_class = static fn() => $order_class;
		add_filter( 'woocommerce_order_class', $filter_class, 100 );
		$product      = \WC_Helper_Product::create_simple_product();
		$count        = count( $order->get_items() );

		$refused = $this->run_ability_response(
			'woocommerce/order-update',
			array(
				'id'         => $order->get_id(),
				'line_items' => array( array( 'product_id' => $product->get_id() ) ),
			)
		);

		remove_filter( 'woocommerce_order_class', $filter_class, 100 );
		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 'woocommerce_data_staging_unsupported', $refused->get_data()['code'] );
		$this->assertStringContainsString( 'stage_product_item', $refused->get_data()['message'] );
		$this->assertCount( $count, ( new \WC_Order( $order->get_id() ) )->get_items() );
	}

	/**
	 * @testdox Should add a product line through add_product() of a class without the stage twin when the feature is off, and save nothing when a later line is refused.
	 */
	public function test_order_update_falls_back_to_add_product_with_the_feature_off(): void {
		$this->set_feature( false );
		$order = \WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();
		$order_class  = get_class(
			new class() extends \WC_Order {
				/**
				 * Calls of add_product().
				 *
				 * @var int
				 */
				public static $calls = 0;

				/**
				 * Add a product with a rule of this class.
				 *
				 * @param \WC_Product $product Product.
				 * @param int         $qty     Quantity.
				 * @param array       $args    Arguments.
				 * @return int
				 */
				public function add_product( $product, $qty = 1, $args = array() ) {
					++self::$calls;
					return parent::add_product( $product, $qty, $args );
				}
			}
		);
		$filter_class = static fn() => $order_class;
		add_filter( 'woocommerce_order_class', $filter_class, 100 );
		$product      = \WC_Helper_Product::create_simple_product();
		$count        = count( $order->get_items() );

		try {
			$refused = $this->run_ability_response(
				'woocommerce/order-update',
				array(
					'id'         => $order->get_id(),
					'line_items' => array(
						array( 'product_id' => $product->get_id() ),
						array( 'product_id' => 999999 ),
					),
				)
			);
			$updated = $this->run_ability(
				'woocommerce/order-update',
				array(
					'id'         => $order->get_id(),
					'line_items' => array( array( 'product_id' => $product->get_id() ) ),
				)
			);
		} finally {
			remove_filter( 'woocommerce_order_class', $filter_class, 100 );
		}

		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 1, $order_class::$calls );
		$this->assertCount( $count + 1, $updated['order']['line_items'] );
		$this->assertCount( $count + 1, ( new \WC_Order( $order->get_id() ) )->get_items() );
	}

	/**
	 * @testdox Should delete the product line that add_product() wrote when the order save fails with the feature off.
	 */
	public function test_order_update_deletes_the_added_line_when_the_save_fails_with_the_feature_off(): void {
		$this->set_feature( false );
		$order = \WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();
		$product = \WC_Helper_Product::create_simple_product();
		$count   = count( $order->get_items() );
		$fail    = static function () {
			throw new \Exception( 'Disk full.' );
		};

		add_action( 'woocommerce_before_order_object_save', $fail );
		try {
			$refused = $this->run_ability_response(
				'woocommerce/order-update',
				array(
					'id'         => $order->get_id(),
					'line_items' => array( array( 'product_id' => $product->get_id() ) ),
				)
			);
		} finally {
			remove_action( 'woocommerce_before_order_object_save', $fail );
		}

		$this->assertGreaterThanOrEqual( 400, $refused->get_status() );
		$this->assertCount( $count, ( new \WC_Order( $order->get_id() ) )->get_items() );
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
	 * @testdox Should name the field and keep the message when a set_callback rejects a value.
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
		$this->assertSame( 'other_ext:test_rejected', $response->get_data()['code'] );
		$this->assertSame( 'Rejected.', $response->get_data()['message'] );
		$this->assertSame( 'extensions.other_ext.count', $response->get_data()['data']['field'] );
		$this->assertSame( 'other_ext', $response->get_data()['data']['namespace'] );
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
		$this->assertSame( 'test-ext:test_locked', $response->get_data()['code'] );
		$this->assertSame( 'Use test-ext/order-unlock first.', $response->get_data()['message'] );
		$this->assertSame( 'test-ext', $response->get_data()['data']['namespace'] );
		$this->assertSame( 1, did_action( 'woocommerce_order_status_changed' ) - $changes );
		$this->assertSame( 'on-hold', $allowed['status'] );
		$this->assertSame( 'test-ext/order-status', $ability );
	}

	/**
	 * @testdox Should stage the status on a plain order, so an order validator sees it, and save nothing or fire no status action when the validator refuses.
	 */
	public function test_an_order_validator_sees_the_staged_status(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( 'processing' );
		$order->save();
		$seen = array();
		$this->register_validator(
			'order',
			'other_ext',
			static function ( \WC_Order $subject ) use ( &$seen ) {
				$seen[] = array( $subject->get_status(), $subject->get_meta( '_test_gift_message' ) );
				if ( 'completed' === $subject->get_status() && 'Hi' === $subject->get_meta( '_test_gift_message' ) ) {
					return new \WP_Error( 'test_gift_not_sent', 'Send the gift message first.' );
				}
				return null;
			}
		);
		$edits   = did_action( 'woocommerce_order_edit_status' );
		$changes = did_action( 'woocommerce_order_status_changed' );

		$refused = $this->run_ability_response(
			'woocommerce/order-update-status',
			array(
				'id'         => $order->get_id(),
				'status'     => 'completed',
				'extensions' => array( 'test-ext' => array( 'gift_message' => 'Hi' ) ),
			)
		);

		$saved = wc_get_order( $order->get_id() );
		$this->assertSame( 400, $refused->get_status() );
		$this->assertStringEndsWith( 'test_gift_not_sent', $refused->get_data()['code'] );
		$this->assertSame( 'processing', $saved->get_status() );
		$this->assertFalse( $saved->meta_exists( '_test_gift_message' ) );
		$this->assertSame( 0, did_action( 'woocommerce_order_edit_status' ) - $edits );
		$this->assertSame( 0, did_action( 'woocommerce_order_status_changed' ) - $changes );

		$this->run_ability(
			'woocommerce/order-update-status',
			array(
				'id'         => $order->get_id(),
				'status'     => 'completed',
				'extensions' => array( 'test-ext' => array( 'gift_message' => 'Thanks' ) ),
			)
		);

		$saved = wc_get_order( $order->get_id() );
		$this->assertSame( 'completed', $saved->get_status() );
		$this->assertSame( 'Thanks', $saved->get_meta( '_test_gift_message' ) );
		$this->assertSame( 1, did_action( 'woocommerce_order_edit_status' ) - $edits );
		$this->assertSame( 1, did_action( 'woocommerce_order_status_changed' ) - $changes );
		$this->assertSame( array( array( 'completed', 'Hi' ), array( 'completed', 'Thanks' ) ), $seen );
	}

	/**
	 * @testdox Should show the staged status and its completed date to an order validator, and fire woocommerce_order_edit_status before the write.
	 */
	public function test_an_order_validator_sees_the_dates_that_the_status_sets(): void {
		$order = \WC_Helper_Order::create_order();
		$seen  = array();
		$this->register_validator(
			'order',
			'other_ext',
			static function ( \WC_Order $data ) use ( &$seen ) {
				$seen[] = array( $data->get_status(), null !== $data->get_date_completed( 'edit' ) );
				return null;
			}
		);
		$events   = array();
		$listener = static function ( $order_id ) use ( &$events ) {
			$events[] = wc_get_order( $order_id )->get_status();
		};
		add_action( 'woocommerce_order_edit_status', $listener );

		$this->run_ability(
			'woocommerce/order-update-status',
			array(
				'id'     => $order->get_id(),
				'status' => 'completed',
			)
		);

		remove_action( 'woocommerce_order_edit_status', $listener );
		$this->assertSame( array( array( 'completed', true ) ), $seen );
		$this->assertSame( array( 'pending' ), $events );
		$this->assertSame( 'completed', wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Should stage the status on an order class that overrides update_status() and stage_status(), with its rule, and refuse a subclass that overrides update_status() again.
	 */
	public function test_an_order_class_that_stages_keeps_its_rule(): void {
		$order_class  = TestStagingOrder::class;
		$filter_class = static function () use ( &$order_class ) {
			return $order_class;
		};
		add_filter( 'woocommerce_order_class', $filter_class, 100 );
		$order = \WC_Helper_Order::create_order();

		$refused     = $this->run_ability_response(
			'woocommerce/order-update-status',
			array(
				'id'     => $order->get_id(),
				'status' => 'on-hold',
			)
		);
		$updated     = $this->run_ability(
			'woocommerce/order-update-status',
			array(
				'id'     => $order->get_id(),
				'status' => 'processing',
			)
		);
		$order_class = get_class(
			new class() extends TestStagingOrder {
				/**
				 * Mark the order, then change the status.
				 *
				 * @param string $new_status    Status to change the order to.
				 * @param string $note          Optional note.
				 * @param bool   $manual_update Whether a person made the change.
				 * @return bool
				 */
				public function update_status( $new_status, $note = '', $manual_update = false ) {
					$this->update_meta_data( '_test_child_rule', 'yes' );
					return parent::update_status( $new_status, $note, $manual_update );
				}
			}
		);
		$child       = \WC_Helper_Order::create_order();
		$again       = $this->run_ability_response(
			'woocommerce/order-update-status',
			array(
				'id'     => $child->get_id(),
				'status' => 'completed',
			)
		);

		remove_filter( 'woocommerce_order_class', $filter_class, 100 );
		$this->assertSame( 409, $refused->get_status() );
		$this->assertSame( 'test_on_hold_refused', $refused->get_data()['code'] );
		$this->assertSame( 'processing', $updated['order']['status'] );
		$this->assertSame( 400, $again->get_status() );
		$this->assertSame( 'woocommerce_data_staging_unsupported', $again->get_data()['code'] );
		$this->assertSame( 'processing', wc_get_order( $order->get_id() )->get_status() );
		$this->assertSame( 'pending', wc_get_order( $child->get_id() )->get_status() );
	}

	/**
	 * @testdox Should stage the status on an order class that overrides stage_status() only.
	 */
	public function test_an_order_class_that_overrides_stage_status_only_is_staged(): void {
		$order_class  = get_class(
			new class() extends \WC_Order {
				/**
				 * Mark the order, then stage the status.
				 *
				 * @param string       $new_status    Status to change the order to.
				 * @param string|false $note          Optional note.
				 * @param bool         $manual_update Whether a person made the change.
				 * @return array
				 */
				public function stage_status( $new_status, $note = '', $manual_update = false ) {
					$this->update_meta_data( '_test_staged', 'yes' );
					return parent::stage_status( $new_status, $note, $manual_update );
				}
			}
		);
		$filter_class = static function () use ( $order_class ) {
			return $order_class;
		};
		add_filter( 'woocommerce_order_class', $filter_class, 100 );
		$order = \WC_Helper_Order::create_order();

		$updated = $this->run_ability(
			'woocommerce/order-update-status',
			array(
				'id'     => $order->get_id(),
				'status' => 'completed',
			)
		);

		remove_filter( 'woocommerce_order_class', $filter_class, 100 );
		$this->assertSame( 'completed', $updated['order']['status'] );
		$saved = wc_get_order( $order->get_id() );
		$this->assertSame( 'completed', $saved->get_status() );
		$this->assertSame( 'yes', $saved->get_meta( '_test_staged' ) );
	}

	/**
	 * @testdox Should refuse an order class whose parent overrides update_status() without stage_status(), and name that parent.
	 */
	public function test_an_order_class_below_an_unstaged_parent_is_refused(): void {
		$order_class  = get_class(
			new class() extends TestUnstagedOrder {
				/**
				 * Mark the order, then stage the status.
				 *
				 * @param string       $new_status    Status to change the order to.
				 * @param string|false $note          Optional note.
				 * @param bool         $manual_update Whether a person made the change.
				 * @return array
				 */
				public function stage_status( $new_status, $note = '', $manual_update = false ) {
					$this->update_meta_data( '_test_staged', 'yes' );
					return parent::stage_status( $new_status, $note, $manual_update );
				}
			}
		);
		$filter_class = static fn() => $order_class;
		add_filter( 'woocommerce_order_class', $filter_class, 100 );
		$order        = \WC_Helper_Order::create_order();

		$response = $this->run_ability_response(
			'woocommerce/order-update-status',
			array(
				'id'     => $order->get_id(),
				'status' => 'completed',
			)
		);

		remove_filter( 'woocommerce_order_class', $filter_class, 100 );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_data_staging_unsupported', $response->get_data()['code'] );
		$this->assertStringContainsString( TestUnstagedOrder::class, $response->get_data()['message'] );
		$this->assertStringContainsString( 'stage_status()', $response->get_data()['message'] );
		$this->assertSame( 'pending', wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Should refuse an ability that calls a stage method on an order class that overrides the saving method only, and save nothing.
	 */
	public function test_any_ability_that_stages_refuses_an_unadopted_order_class(): void {
		$order_class  = get_class(
			new class() extends \WC_Order {
				/**
				 * Mark the order, then complete the payment.
				 *
				 * @param string $transaction_id Transaction ID.
				 * @return bool
				 */
				public function payment_complete( $transaction_id = '' ) {
					$this->update_meta_data( '_test_override', 'yes' );
					return parent::payment_complete( $transaction_id );
				}
			}
		);
		$filter_class = static fn() => $order_class;
		add_filter( 'woocommerce_order_class', $filter_class, 100 );
		$this->register_order_status_ability( TestOrderStageAbility::class );
		$this->reset_registries();
		$order = \WC_Helper_Order::create_order();

		$response = $this->run_ability_response(
			'test-ext/order-status',
			array(
				'id'     => $order->get_id(),
				'status' => 'completed',
			)
		);

		remove_filter( 'woocommerce_order_class', $filter_class, 100 );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_data_staging_unsupported', $response->get_data()['code'] );
		$this->assertStringContainsString( 'stage_payment_complete()', $response->get_data()['message'] );
		$this->assertSame( 'pending', wc_get_order( $order->get_id() )->get_status() );
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
	 * @testdox Should run every validator and return all refusals in one error, and save nothing.
	 */
	public function test_every_validator_runs_and_all_refusals_are_returned(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$this->register_validator(
			'product',
			'first_ext',
			static function () {
				return new \WP_Error( 'test_first_refused', 'First refused.' );
			}
		);
		$this->register_validator(
			'product',
			'second_ext',
			static function () {
				return new \WP_Error( 'test_second_refused', 'Second refused.', array( 'status' => 409 ) );
			}
		);

		$response = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		$data = $response->get_data();
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'first_ext:test_first_refused', $data['code'] );
		$this->assertSame( 'First refused. Second refused.', $data['message'] );
		$this->assertSame( 'first_ext', $data['data']['namespace'] );
		$this->assertCount( 1, $data['additional_errors'] );
		$this->assertSame( 'second_ext:test_second_refused', $data['additional_errors'][0]['code'] );
		$this->assertSame( 'Second refused.', $data['additional_errors'][0]['message'] );
		$this->assertSame( 'second_ext', $data['additional_errors'][0]['data']['namespace'] );
		$this->assertSame( 409, $data['additional_errors'][0]['data']['status'] );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should keep the namespace of each refusal when validators of two namespaces return the same code.
	 */
	public function test_refusals_with_the_same_code_keep_their_namespace(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		foreach ( array( 'first_ext', 'second_ext' ) as $extension_namespace ) {
			$this->register_validator(
				'product',
				$extension_namespace,
				static function () use ( $extension_namespace ) {
					return new \WP_Error( 'test_refused', $extension_namespace . ' refused.' );
				}
			);
		}

		$data = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		)->get_data();

		$this->assertSame( 'first_ext:test_refused', $data['code'] );
		$this->assertSame( 'first_ext refused. second_ext refused.', $data['message'] );
		$this->assertSame( 'first_ext', $data['data']['namespace'] );
		$this->assertCount( 1, $data['additional_errors'] );
		$this->assertSame( 'second_ext:test_refused', $data['additional_errors'][0]['code'] );
		$this->assertSame( 'second_ext refused.', $data['additional_errors'][0]['message'] );
		$this->assertSame( 'second_ext', $data['additional_errors'][0]['data']['namespace'] );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should give a repeated code in the same namespace a numeric suffix, so each refusal keeps its own data.
	 */
	public function test_refusals_with_the_same_code_in_one_namespace_keep_their_data(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		foreach ( array( 'name', 'price' ) as $field ) {
			$this->register_validator(
				'product',
				'ns',
				static function () use ( $field ) {
					return new \WP_Error( 'test_refused', $field . ' refused.', array( 'field' => $field ) );
				}
			);
		}

		$data = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		)->get_data();

		$this->assertSame( 'ns:test_refused', $data['code'] );
		$this->assertSame( 'name refused. price refused.', $data['message'] );
		$this->assertSame( 'name', $data['data']['field'] );
		$this->assertCount( 1, $data['additional_errors'] );
		$this->assertSame( 'ns:test_refused:2', $data['additional_errors'][0]['code'] );
		$this->assertSame( 'price refused.', $data['additional_errors'][0]['message'] );
		$this->assertSame( 'price', $data['additional_errors'][0]['data']['field'] );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should give a refusal the suffix when a crash in the same namespace already uses its code.
	 */
	public function test_a_crash_does_not_share_a_code_with_a_refusal_in_the_same_namespace(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$this->register_validator(
			'product',
			'ns',
			static function () {
				throw new \RuntimeException( 'Validator broke.' );
			}
		);
		$this->register_validator(
			'product',
			'ns',
			static function () {
				return new \WP_Error( 'woocommerce_ability_extension_failed', 'Name refused.' );
			}
		);

		$response = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);
		$data     = $response->get_data();

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'ns:woocommerce_ability_extension_failed', $data['code'] );
		$this->assertSame( 500, $data['data']['status'] );
		$this->assertCount( 1, $data['additional_errors'] );
		$this->assertSame( 'ns:woocommerce_ability_extension_failed:2', $data['additional_errors'][0]['code'] );
		$this->assertSame( 'Name refused.', $data['additional_errors'][0]['message'] );
		$this->assertSame( 400, $data['additional_errors'][0]['data']['status'] );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should run the other validators after one throws, keep the run order, and give every code of a refusal its namespace and status.
	 */
	public function test_validators_run_after_a_throw_and_every_code_gets_its_namespace(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$this->register_validator(
			'product',
			'first_ext',
			static function () {
				throw new \RuntimeException( 'Validator broke.' );
			}
		);
		$this->register_validator(
			'product',
			'second_ext',
			static function () {
				$error = new \WP_Error( 'test_name_refused', 'Name refused.' );
				$error->add( 'test_price_refused', 'Price refused.' );
				return $error;
			}
		);

		$response = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);
		$data     = $response->get_data();

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'first_ext:woocommerce_ability_extension_failed', $data['code'] );
		$this->assertSame( 'first_ext', $data['data']['namespace'] );
		$this->assertSame( 500, $data['data']['status'] );
		$this->assertCount( 2, $data['additional_errors'] );
		$this->assertSame( 'second_ext:test_name_refused', $data['additional_errors'][0]['code'] );
		$this->assertSame( 'second_ext', $data['additional_errors'][0]['data']['namespace'] );
		$this->assertSame( 400, $data['additional_errors'][0]['data']['status'] );
		$this->assertSame( 'second_ext:test_price_refused', $data['additional_errors'][1]['code'] );
		$this->assertSame( 'second_ext', $data['additional_errors'][1]['data']['namespace'] );
		$this->assertSame( 400, $data['additional_errors'][1]['data']['status'] );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
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
		$this->assertSame( 'other_ext:test_rename_refused', $response->get_data()['code'] );
		$this->assertSame( 'other_ext', $response->get_data()['data']['namespace'] );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should return a fixed message and the field or namespace when a set_callback or a validator throws, and save nothing.
	 */
	public function test_a_callback_that_throws_returns_a_clear_error(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'      => 'product',
						'namespace'     => 'test-ext',
						'field'         => 'broken',
						'input_schema'  => array( 'type' => 'string' ),
						'get_callback'  => '__return_null',
						'output_schema' => array( 'type' => 'string' ),
						'set_callback'  => static function () {
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
		$this->assertSame( 'test-ext:woocommerce_ability_extension_failed', $field->get_data()['code'] );
		$this->assertSame( 'An extension failed while checking this change.', $field->get_data()['message'] );
		$this->assertSame( 'extensions.test-ext.broken', $field->get_data()['data']['field'] );
		$this->assertSame( 500, $validator->get_status() );
		$this->assertSame( 'An extension failed while checking this change.', $validator->get_data()['message'] );
		$this->assertSame( 'other_ext', $validator->get_data()['data']['namespace'] );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should return every field refusal and the validator refusals in one error, and save nothing.
	 */
	public function test_field_and_validator_refusals_come_together(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				foreach ( array( 'first', 'second' ) as $field ) {
					ExtensibleAbilities::register_field(
						array(
							'resource'      => 'product',
							'namespace'     => 'test-ext',
							'field'         => $field,
							'output_schema' => array( 'type' => 'string' ),
							'input_schema'  => array( 'type' => 'string' ),
							'get_callback'  => '__return_null',
							'set_callback'  => static function () use ( $field ) {
								return new \WP_Error( 'test_bad_value', $field . ' refused.', array( 'status' => 422 ) );
							},
						)
					);
				}
			}
		);
		$this->register_validator(
			'product',
			'other_ext',
			static fn() => new \WP_Error( 'test_bad_size', 'Size refused.' )
		);

		$response = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'name'       => 'Pencil',
				'extensions' => array(
					'test-ext' => array(
						'first'  => 'x',
						'second' => 'y',
					),
				),
			)
		);

		$data = $response->get_data();
		$this->assertSame( 422, $response->get_status() );
		$this->assertSame( 'test-ext:test_bad_value', $data['code'] );
		$this->assertSame( 'extensions.test-ext.first', $data['data']['field'] );
		$this->assertSame( 'test-ext:test_bad_value:2', $data['additional_errors'][0]['code'] );
		$this->assertSame( 'extensions.test-ext.second', $data['additional_errors'][0]['data']['field'] );
		$this->assertSame( 'other_ext:test_bad_size', $data['additional_errors'][1]['code'] );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should return every Core refusal with the field and validator refusals in one message, and save nothing.
	 */
	public function test_core_refusals_come_with_field_and_validator_refusals(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Pen' ) );
		\WC_Helper_Product::create_simple_product( true, array( 'sku' => 'TAKEN' ) );
		$this->add_test_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'      => 'product',
						'namespace'     => 'test-ext',
						'field'         => 'note',
						'output_schema' => array( 'type' => 'string' ),
						'input_schema'  => array( 'type' => 'string' ),
						'get_callback'  => '__return_null',
						'set_callback'  => static fn() => new \WP_Error( 'test_bad_note', 'Note refused.' ),
					)
				);
			}
		);
		$seen_name = null;
		$this->register_validator(
			'product',
			'other_ext',
			static function ( \WC_Product $subject ) use ( &$seen_name ) {
				$seen_name = $subject->get_name();
				return new \WP_Error( 'test_refused', 'Validator refused.' );
			}
		);

		$response = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'           => $product->get_id(),
				'name'         => 'Pencil',
				'sku'          => 'TAKEN',
				'external_url' => 'https://example.com',
				'extensions'   => array( 'test-ext' => array( 'note' => 'x' ) ),
			)
		);

		$data = $response->get_data();
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame(
			array( 'woocommerce_product_field_unsupported', 'product_invalid_sku', 'test-ext:test_bad_note', 'other_ext:test_refused' ),
			array_merge( array( $data['code'] ), array_column( $data['additional_errors'], 'code' ) ),
			'Core refusals come first, then the field and validator refusals, in run order.'
		);
		$this->assertStringStartsWith( 'The simple product type does not take external_url.', $data['message'] );
		$this->assertStringEndsWith( 'Note refused. Validator refused.', $data['message'], 'The first message names every refusal, because MCP clients show only that one.' );
		$this->assertSame( 'Pencil', $seen_name, 'The validator sees the values that Core accepted.' );
		$this->assertSame( 'Pen', wc_get_product( $product->get_id() )->get_name() );
	}

	/**
	 * @testdox Should show the data of a refusal once, with no additional_data.
	 */
	public function test_refusal_data_is_not_repeated(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$this->register_validator(
			'product',
			'test-ext',
			static function () {
				$error = new \WP_Error( 'test_refused', 'First message.', array( 'status' => 409 ) );
				$error->add( 'test_refused', 'Second message.' );
				return $error;
			}
		);

		$response = $this->run_ability_response(
			'woocommerce/product-update',
			array(
				'id'   => $product->get_id(),
				'name' => 'Pencil',
			)
		);

		$data = $response->get_data();
		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'test-ext', $data['data']['namespace'] );
		$this->assertArrayNotHasKey( 'additional_data', $data );
		$this->assertSame( 'Second message.', $data['additional_errors'][0]['message'] );
		$this->assertArrayNotHasKey( 'additional_data', $data['additional_errors'][0] );
	}

	/**
	 * @testdox Should refuse a class outside WC_Data that overrides a saving method without its stage method.
	 */
	public function test_refuse_unstaged_override_works_for_a_class_outside_wc_data(): void {
		$adopted   = new class() extends TestStagedService {
			/**
			 * Save an item.
			 */
			public function add_item(): void {}

			/**
			 * Stage an item.
			 */
			public function stage_item(): void {}
		};
		$unadopted = new class() extends TestStagedService {
			/**
			 * Save an item.
			 */
			public function add_item(): void {}
		};

		ExtensibleAbilities::refuse_unstaged_override( new TestStagedService(), 'add_item', 'stage_item' );
		ExtensibleAbilities::refuse_unstaged_override( $adopted, 'add_item', 'stage_item' );

		$this->expectException( \WC_Data_Exception::class );
		$this->expectExceptionMessage( 'overrides add_item() and needs to override stage_item() too' );
		ExtensibleAbilities::refuse_unstaged_override( $unadopted, 'add_item', 'stage_item' );
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
	 * Register fields on the order resource and read their values.
	 *
	 * @param array<string, array{0: array<string, mixed>, 1: mixed}> $fields Schema and value, keyed by field.
	 * @return array<string, mixed> The values under the namespace.
	 */
	private function read_field_values( array $fields ): array {
		AbilitiesLoader::register_core_resources();
		foreach ( $fields as $field => list( $schema, $value ) ) {
			ExtensibleAbilities::register_field(
				array(
					'resource'      => 'order',
					'namespace'     => 'values',
					'field'         => $field,
					'output_schema' => $schema,
					'get_callback'  => static fn() => $value,
				)
			);
		}

		$output = ExtensibleAbilities::add_fields_to_object( array(), 'order', \WC_Helper_Order::create_order() );
		return $output['extensions']['values'];
	}

	/**
	 * Add a filter and remove it in tearDown.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Number of arguments.
	 */
	private function add_test_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		add_filter( $hook, $callback, $priority, $accepted_args );
		$this->test_actions[] = array( $hook, $callback, $priority );
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
	 *
	 * @param string $ability_class Class of the ability.
	 */
	private function register_order_status_ability( string $ability_class = TestOrderStatusAbility::class ): void {
		$this->add_test_action(
			'wp_abilities_api_init',
			static function () use ( $ability_class ) {
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
						'ability_class'       => $ability_class,
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

		foreach ( array( 'fields', 'validators', 'syncs', 'filters' ) as $property ) {
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
