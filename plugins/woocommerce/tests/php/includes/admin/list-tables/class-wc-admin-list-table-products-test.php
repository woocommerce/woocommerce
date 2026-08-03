<?php
/**
 * Unit tests for the products admin list table.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\ProductStockStatus;

require_once WC_ABSPATH . 'includes/admin/list-tables/class-wc-admin-list-table-products.php';

/**
 * WC_Admin_List_Table_Products tests.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_List_Table_Products_Test extends WC_Unit_Test_Case {

	/**
	 * Original global stock management option.
	 *
	 * @var string|false
	 */
	private $original_manage_stock;

	/**
	 * Previous value of the sitewide low stock amount option, restored in tearDown.
	 *
	 * @var string
	 */
	private $previous_low_stock_amount_option;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_manage_stock            = get_option( 'woocommerce_manage_stock' );
		$this->previous_low_stock_amount_option = get_option( 'woocommerce_notify_low_stock_amount', 2 );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		if ( false === $this->original_manage_stock ) {
			delete_option( 'woocommerce_manage_stock' );
		} else {
			update_option( 'woocommerce_manage_stock', $this->original_manage_stock );
		}

		update_option( 'woocommerce_notify_low_stock_amount', $this->previous_low_stock_amount_option );
		unset( $_GET['stock_status'] );

		parent::tearDown();
	}

	/**
	 * @testdox Products list out-of-stock filter includes variable products with out-of-stock variations.
	 */
	public function test_out_of_stock_filter_includes_variable_products_with_out_of_stock_variations() {
		update_option( 'woocommerce_manage_stock', 'no' );

		$simple_out_of_stock = WC_Helper_Product::create_simple_product();
		$simple_out_of_stock->set_manage_stock( false );
		$simple_out_of_stock->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$simple_out_of_stock->set_virtual( true );
		$simple_out_of_stock->save();

		$variable_with_out_of_stock_child = $this->create_variable_product_with_child_stock_statuses(
			array(
				ProductStockStatus::OUT_OF_STOCK,
				ProductStockStatus::OUT_OF_STOCK,
				ProductStockStatus::IN_STOCK,
			)
		);

		$variable_in_stock = $this->create_variable_product_with_child_stock_statuses(
			array(
				ProductStockStatus::IN_STOCK,
				ProductStockStatus::IN_STOCK,
			)
		);

		$variable_with_private_out_of_stock_child = $this->create_variable_product_with_child_stock_statuses(
			array(
				ProductStockStatus::OUT_OF_STOCK,
				ProductStockStatus::IN_STOCK,
			)
		);

		$private_variation = null;
		foreach ( $variable_with_private_out_of_stock_child->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( ProductStockStatus::OUT_OF_STOCK === $child->get_stock_status() ) {
				$private_variation = $child;
				break;
			}
		}
		$this->assertInstanceOf( WC_Product_Variation::class, $private_variation );
		$private_variation->set_status( 'private' );
		$private_variation->save();
		WC_Product_Variable::sync( $variable_with_private_out_of_stock_child );
		$variable_with_private_out_of_stock_child = wc_get_product( $variable_with_private_out_of_stock_child->get_id() );

		$this->assertSame( ProductStockStatus::IN_STOCK, $variable_with_out_of_stock_child->get_stock_status() );
		$this->assertSame( ProductStockStatus::IN_STOCK, $variable_with_private_out_of_stock_child->get_stock_status() );

		$fixture_ids = array(
			$simple_out_of_stock->get_id(),
			$variable_with_out_of_stock_child->get_id(),
			$variable_in_stock->get_id(),
			$variable_with_private_out_of_stock_child->get_id(),
		);

		$this->assertSame(
			array( $simple_out_of_stock->get_id(), $variable_with_out_of_stock_child->get_id() ),
			array_values( array_intersect( $this->query_product_ids_for_stock_status( ProductStockStatus::OUT_OF_STOCK ), $fixture_ids ) )
		);
		$this->assertSame(
			array( $variable_with_out_of_stock_child->get_id(), $variable_in_stock->get_id(), $variable_with_private_out_of_stock_child->get_id() ),
			array_values( array_intersect( $this->query_product_ids_for_stock_status( ProductStockStatus::IN_STOCK ), $fixture_ids ) )
		);
		$this->assertSame(
			array( $simple_out_of_stock->get_id() ),
			array_values( array_intersect( $this->query_product_ids_for_stock_status( ProductStockStatus::OUT_OF_STOCK, 'filter_virtual_post_clauses' ), $fixture_ids ) )
		);
	}

	/**
	 * @testdox Should include a "Low stock" option in the stock status filter dropdown.
	 */
	public function test_render_products_stock_status_filter_includes_low_stock_option() {
		$list_table = new WC_Admin_List_Table_Products();

		ob_start();
		$list_table->render_products_stock_status_filter();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'value="lowstock"', $output );
	}

	/**
	 * @testdox Should hook the low stock clause, not the exact-match stock status clause, when stock_status=lowstock.
	 */
	public function test_query_filters_hooks_low_stock_clause_for_lowstock_status() {
		$_GET['stock_status'] = 'lowstock'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$list_table = new WC_Admin_List_Table_Products();
		$this->call_query_filters( $list_table, array() );

		$this->assertNotFalse( has_filter( 'posts_clauses', array( $list_table, 'filter_low_stock_post_clauses' ) ) );
		$this->assertFalse( has_filter( 'posts_clauses', array( $list_table, 'filter_stock_status_post_clauses' ) ) );

		$list_table->remove_ordering_args();
	}

	/**
	 * @testdox Should return only in-stock products at or below their low stock threshold.
	 */
	public function test_filter_low_stock_post_clauses_returns_expected_products() {
		update_option( 'woocommerce_notify_low_stock_amount', 5 );

		$low_stock_custom_threshold = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'     => true,
				'stock_quantity'   => 3,
				'low_stock_amount' => 4,
				'stock_status'     => ProductStockStatus::IN_STOCK,
			)
		);

		$above_custom_threshold = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'     => true,
				'stock_quantity'   => 10,
				'low_stock_amount' => 4,
				'stock_status'     => ProductStockStatus::IN_STOCK,
			)
		);

		$low_stock_sitewide_threshold = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 5,
				'stock_status'   => ProductStockStatus::IN_STOCK,
			)
		);

		$well_stocked = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 50,
				'stock_status'   => ProductStockStatus::IN_STOCK,
			)
		);

		// Stock status is recalculated from quantity on save (see WC_Product::validate_props()),
		// so a quantity of 0 with default backorders is what actually produces "out of stock".
		$out_of_stock = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'     => true,
				'stock_quantity'   => 0,
				'low_stock_amount' => 4,
			)
		);

		$list_table = new WC_Admin_List_Table_Products();
		add_filter( 'posts_clauses', array( $list_table, 'filter_low_stock_post_clauses' ) );

		$query     = new WP_Query(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'fields'      => 'ids',
			)
		);
		$found_ids = $query->posts;

		remove_filter( 'posts_clauses', array( $list_table, 'filter_low_stock_post_clauses' ) );

		$this->assertContains( $low_stock_custom_threshold->get_id(), $found_ids, 'Product under its custom low stock amount should be included' );
		$this->assertContains( $low_stock_sitewide_threshold->get_id(), $found_ids, 'Product under the sitewide threshold should be included' );
		$this->assertNotContains( $above_custom_threshold->get_id(), $found_ids, 'Product above its custom low stock amount should be excluded' );
		$this->assertNotContains( $well_stocked->get_id(), $found_ids, 'Well stocked product should be excluded' );
		$this->assertNotContains( $out_of_stock->get_id(), $found_ids, 'Out of stock product should be excluded even if under threshold' );
	}

	/**
	 * Call the protected query_filters() method.
	 *
	 * @param WC_Admin_List_Table_Products $list_table List table instance.
	 * @param array                        $query_vars Query vars.
	 * @return array
	 */
	private function call_query_filters( $list_table, $query_vars ) {
		$method = new ReflectionMethod( $list_table, 'query_filters' );
		$method->setAccessible( true );
		return $method->invoke( $list_table, $query_vars );
	}

	/**
	 * @testdox Should treat an empty sitewide low stock amount option as a threshold of 1, matching the Analytics low-in-stock report, rather than 0.
	 */
	public function test_filter_low_stock_post_clauses_normalizes_empty_sitewide_threshold() {
		update_option( 'woocommerce_notify_low_stock_amount', '' );

		$low_stock_no_amount = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 1,
				'stock_status'   => ProductStockStatus::IN_STOCK,
			)
		);

		$list_table = new WC_Admin_List_Table_Products();
		add_filter( 'posts_clauses', array( $list_table, 'filter_low_stock_post_clauses' ) );

		$query     = new WP_Query(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'fields'      => 'ids',
			)
		);
		$found_ids = $query->posts;

		remove_filter( 'posts_clauses', array( $list_table, 'filter_low_stock_post_clauses' ) );

		$this->assertContains( $low_stock_no_amount->get_id(), $found_ids, 'Quantity of 1 with an empty sitewide option should still be treated as low stock (threshold normalized to 1, not 0)' );
	}

	/**
	 * Create a variable product with children that use the supplied stock statuses.
	 *
	 * @param array $stock_statuses Child variation stock statuses.
	 * @return WC_Product_Variable
	 */
	private function create_variable_product_with_child_stock_statuses( array $stock_statuses ) {
		$product = new WC_Product_Variable();
		$product->set_manage_stock( false );
		$product->save();

		foreach ( $stock_statuses as $index => $stock_status ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product->get_id() );
			$variation->set_status( 'publish' );
			$variation->set_regular_price( 10 + $index );
			$variation->set_manage_stock( false );
			$variation->set_stock_status( $stock_status );
			$variation->save();
		}

		$product = wc_get_product( $product->get_id() );
		WC_Product_Variable::sync( $product );

		return wc_get_product( $product->get_id() );
	}

	/**
	 * Query product IDs through the products list table stock-status filter.
	 *
	 * @param string      $stock_status      Stock status to query.
	 * @param string|null $additional_filter Optional clause filter to register after the stock filter.
	 * @return array
	 */
	private function query_product_ids_for_stock_status( $stock_status, $additional_filter = null ) {
		$sut          = ( new ReflectionClass( WC_Admin_List_Table_Products::class ) )->newInstanceWithoutConstructor();
		$original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Test cleanup restores the raw original request data.

		$_GET['stock_status'] = $stock_status;
		add_filter( 'posts_clauses', array( $sut, 'filter_stock_status_post_clauses' ) );
		if ( $additional_filter ) {
			add_filter( 'posts_clauses', array( $sut, $additional_filter ) );
		}

		try {
			$query = new WP_Query(
				array(
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'post_status'    => 'publish',
					'post_type'      => 'product',
					'posts_per_page' => -1,
				)
			);

			return array_map( 'intval', $query->posts );
		} finally {
			remove_filter( 'posts_clauses', array( $sut, 'filter_stock_status_post_clauses' ) );
			if ( $additional_filter ) {
				remove_filter( 'posts_clauses', array( $sut, $additional_filter ) );
			}
			$_GET = $original_get;
		}
	}
}
