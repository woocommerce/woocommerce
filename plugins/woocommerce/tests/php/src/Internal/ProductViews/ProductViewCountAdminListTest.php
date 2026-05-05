<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductViews;

use Automattic\WooCommerce\Internal\ProductViews\ProductViewCountAdminList;
use Automattic\WooCommerce\Internal\ProductViews\ProductViewCountTracker;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductViewCountAdminList class.
 */
class ProductViewCountAdminListTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductViewCountAdminList
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ProductViewCountAdminList();
	}

	/**
	 * @testdox register() should add all required hooks.
	 */
	public function test_register_adds_all_hooks(): void {
		$this->sut->register();

		$this->assertNotFalse(
			has_filter( 'manage_product_posts_columns', array( $this->sut, 'add_views_column' ) ),
			'add_views_column should be hooked to manage_product_posts_columns'
		);
		$this->assertNotFalse(
			has_action( 'manage_product_posts_custom_column', array( $this->sut, 'render_views_column' ) ),
			'render_views_column should be hooked to manage_product_posts_custom_column'
		);
		$this->assertNotFalse(
			has_filter( 'manage_edit-product_sortable_columns', array( $this->sut, 'register_sortable_column' ) ),
			'register_sortable_column should be hooked to manage_edit-product_sortable_columns'
		);
		$this->assertNotFalse(
			has_action( 'pre_get_posts', array( $this->sut, 'handle_orderby' ) ),
			'handle_orderby should be hooked to pre_get_posts'
		);
	}

	/**
	 * @testdox add_views_column() should insert view_count before the date column.
	 */
	public function test_add_views_column_inserts_before_date(): void {
		$columns = array(
			'name'  => 'Name',
			'price' => 'Price',
			'date'  => 'Date',
		);

		$result = $this->sut->add_views_column( $columns );

		$keys      = array_keys( $result );
		$date_pos  = array_search( 'date', $keys, true );
		$views_pos = array_search( 'view_count', $keys, true );

		$this->assertArrayHasKey( 'view_count', $result, 'view_count column should be added' );
		$this->assertLessThan( $date_pos, $views_pos, 'view_count should appear before the date column' );
	}

	/**
	 * @testdox add_views_column() should append view_count when no date column exists.
	 */
	public function test_add_views_column_appends_when_no_date_column(): void {
		$columns = array(
			'name'  => 'Name',
			'price' => 'Price',
		);

		$result = $this->sut->add_views_column( $columns );

		$this->assertArrayHasKey( 'view_count', $result, 'view_count column should be added even without a date column' );
	}

	/**
	 * @testdox add_views_column() should preserve all existing columns.
	 */
	public function test_add_views_column_preserves_existing_columns(): void {
		$columns = array(
			'cb'    => '<input type="checkbox">',
			'name'  => 'Name',
			'price' => 'Price',
			'date'  => 'Date',
		);

		$result = $this->sut->add_views_column( $columns );

		foreach ( array_keys( $columns ) as $key ) {
			$this->assertArrayHasKey( $key, $result, "Original column '{$key}' should be preserved" );
		}
	}

	/**
	 * @testdox register_sortable_column() should add view_count to sortable columns.
	 */
	public function test_register_sortable_column_adds_view_count(): void {
		$result = $this->sut->register_sortable_column( array() );

		$this->assertArrayHasKey( 'view_count', $result, 'view_count should be registered as sortable' );
		$this->assertSame( 'view_count', $result['view_count'] );
	}

	/**
	 * @testdox register_sortable_column() should preserve existing sortable columns.
	 */
	public function test_register_sortable_column_preserves_existing(): void {
		$existing = array( 'price' => 'price', 'sku' => 'sku' );

		$result = $this->sut->register_sortable_column( $existing );

		$this->assertArrayHasKey( 'price', $result );
		$this->assertArrayHasKey( 'sku', $result );
	}

	/**
	 * @testdox render_views_column() should output a formatted view count for the view_count column.
	 */
	public function test_render_views_column_outputs_count(): void {
		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), ProductViewCountTracker::META_KEY, 1234 );

		ob_start();
		$this->sut->render_views_column( 'view_count', $product->get_id() );
		$output = ob_get_clean();

		$this->assertStringContainsString( '1,234', $output, 'Rendered output should contain formatted view count' );

		$product->delete( true );
	}

	/**
	 * @testdox render_views_column() should produce no output for unrelated columns.
	 */
	public function test_render_views_column_skips_other_columns(): void {
		$product = WC_Helper_Product::create_simple_product();

		ob_start();
		$this->sut->render_views_column( 'price', $product->get_id() );
		$output = ob_get_clean();

		$this->assertSame( '', $output, 'Unrelated columns should produce no output' );

		$product->delete( true );
	}

	/**
	 * @testdox handle_orderby() should configure meta query when orderby is view_count.
	 */
	public function test_handle_orderby_sets_meta_query(): void {
		set_current_screen( 'edit-product' );

		$query = new \WP_Query();
		$query->set( 'orderby', 'view_count' );
		$query->is_main_query = true;

		$reflection = new \ReflectionObject( $query );
		$property   = $reflection->getProperty( 'is_admin' );
		$property->setAccessible( true );
		$property->setValue( $query, true );

		$this->sut->handle_orderby( $query );

		$this->assertSame(
			ProductViewCountTracker::META_KEY,
			$query->get( 'meta_key' ),
			'meta_key should be set to the view count meta key'
		);
		$this->assertSame(
			'meta_value_num',
			$query->get( 'orderby' ),
			'orderby should be changed to meta_value_num for numeric sorting'
		);

		restore_current_screen();
	}

	/**
	 * @testdox handle_orderby() should not modify query when orderby is not view_count.
	 */
	public function test_handle_orderby_ignores_other_orderby_values(): void {
		$query = new \WP_Query();
		$query->set( 'orderby', 'date' );

		$this->sut->handle_orderby( $query );

		$this->assertSame( '', $query->get( 'meta_key' ), 'meta_key should not be set for other orderby values' );
	}
}
