<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Queries\Products;

use Automattic\WooCommerce\Api\Enums\Products\ProductStatus;
use Automattic\WooCommerce\Api\Enums\Products\ProductType;
use Automattic\WooCommerce\Api\Enums\Products\StockStatus;
use Automattic\WooCommerce\Api\InputTypes\Products\ProductFilterInput;
use Automattic\WooCommerce\Api\Pagination\IdCursorFilter;
use Automattic\WooCommerce\Api\Pagination\PaginationParams;
use Automattic\WooCommerce\Api\Queries\Products\ListProducts;
use ReflectionClass;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Unit tests for {@see ListProducts}.
 */
class ListProductsTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var ListProducts
	 */
	private ListProducts $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		// IdCursorFilter registers its posts_where hook once per request and
		// remembers it on a static flag. WP_UnitTestCase resets $wp_filter on
		// every tear_down(), so the actual hook is gone but the flag stays
		// true — leaving subsequent cursor tests without a working filter.
		$reflection = new ReflectionClass( IdCursorFilter::class );
		$property   = $reflection->getProperty( 'registered' );
		$property->setAccessible( true );
		$property->setValue( null, false );

		$this->sut = new ListProducts();
	}

	/**
	 * Build a {@see ProductFilterInput} with the given fields.
	 *
	 * @param ?ProductStatus $status       Optional product status filter.
	 * @param ?StockStatus   $stock_status Optional stock status filter.
	 * @param ?string        $search       Optional search keyword.
	 */
	private function filters(
		?ProductStatus $status = null,
		?StockStatus $stock_status = null,
		?string $search = null,
	): ProductFilterInput {
		return new ProductFilterInput( $status, $stock_status, $search );
	}

	/**
	 * @testdox execute() returns all products with ascending IDs by default.
	 */
	public function test_execute_returns_all_products_in_ascending_order(): void {
		$a = WC_Helper_Product::create_simple_product();
		$b = WC_Helper_Product::create_simple_product();
		$c = WC_Helper_Product::create_simple_product();

		$connection = $this->sut->execute( new PaginationParams(), $this->filters() );

		$this->assertSame( 3, $connection->total_count );
		$this->assertCount( 3, $connection->nodes );
		$this->assertSame( $a->get_id(), $connection->nodes[0]->id );
		$this->assertSame( $b->get_id(), $connection->nodes[1]->id );
		$this->assertSame( $c->get_id(), $connection->nodes[2]->id );
	}

	/**
	 * @testdox execute() honors `first` and signals has_next_page when more remain.
	 */
	public function test_execute_paginates_forward_with_first(): void {
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();

		$connection = $this->sut->execute( new PaginationParams( first: 2 ), $this->filters() );

		$this->assertCount( 2, $connection->nodes );
		$this->assertSame( 3, $connection->total_count );
		$this->assertTrue( $connection->page_info->has_next_page );
		$this->assertFalse( $connection->page_info->has_previous_page );
	}

	/**
	 * @testdox execute() honors `after` and returns products with IDs > cursor.
	 */
	public function test_execute_paginates_forward_with_after_cursor(): void {
		$first = WC_Helper_Product::create_simple_product();
		$mid   = WC_Helper_Product::create_simple_product();
		$last  = WC_Helper_Product::create_simple_product();

		$after_cursor = base64_encode( (string) $first->get_id() );

		$connection = $this->sut->execute(
			new PaginationParams( first: 10, after: $after_cursor ),
			$this->filters()
		);

		$this->assertCount( 2, $connection->nodes );
		$this->assertSame( $mid->get_id(), $connection->nodes[0]->id );
		$this->assertSame( $last->get_id(), $connection->nodes[1]->id );
		$this->assertTrue( $connection->page_info->has_previous_page );
		$this->assertFalse( $connection->page_info->has_next_page );
	}

	/**
	 * @testdox execute() honors `last` and returns the trailing page in ascending order.
	 */
	public function test_execute_paginates_backward_with_last(): void {
		$a = WC_Helper_Product::create_simple_product();
		$b = WC_Helper_Product::create_simple_product();
		$c = WC_Helper_Product::create_simple_product();

		$connection = $this->sut->execute( new PaginationParams( last: 2 ), $this->filters() );

		$this->assertCount( 2, $connection->nodes );
		$this->assertSame( $b->get_id(), $connection->nodes[0]->id );
		$this->assertSame( $c->get_id(), $connection->nodes[1]->id );
		$this->assertTrue( $connection->page_info->has_previous_page );
		$this->assertFalse( $connection->page_info->has_next_page );
	}

	/**
	 * @testdox execute() honors `before` and reports has_next_page=true (more remain after the window).
	 */
	public function test_execute_paginates_backward_with_before_cursor(): void {
		$a = WC_Helper_Product::create_simple_product();
		$b = WC_Helper_Product::create_simple_product();
		$c = WC_Helper_Product::create_simple_product();

		$before_cursor = base64_encode( (string) $c->get_id() );

		$connection = $this->sut->execute(
			new PaginationParams( last: 10, before: $before_cursor ),
			$this->filters()
		);

		$this->assertCount( 2, $connection->nodes );
		$this->assertSame( $a->get_id(), $connection->nodes[0]->id );
		$this->assertSame( $b->get_id(), $connection->nodes[1]->id );
		$this->assertTrue( $connection->page_info->has_next_page );
	}

	/**
	 * @testdox execute() filters by product status.
	 */
	public function test_execute_filters_by_status(): void {
		WC_Helper_Product::create_simple_product( true, array( 'status' => 'publish' ) );
		$draft = WC_Helper_Product::create_simple_product( true, array( 'status' => 'draft' ) );

		$connection = $this->sut->execute(
			new PaginationParams(),
			$this->filters( status: ProductStatus::Draft )
		);

		$this->assertSame( 1, $connection->total_count );
		$this->assertCount( 1, $connection->nodes );
		$this->assertSame( $draft->get_id(), $connection->nodes[0]->id );
	}

	/**
	 * @testdox execute() filters by stock_status InStock.
	 */
	public function test_execute_filters_by_stock_status_in_stock(): void {
		$in_stock = WC_Helper_Product::create_simple_product( true, array( 'stock_status' => 'instock' ) );
		WC_Helper_Product::create_simple_product( true, array( 'stock_status' => 'outofstock' ) );

		$connection = $this->sut->execute(
			new PaginationParams(),
			$this->filters( stock_status: StockStatus::InStock )
		);

		$this->assertCount( 1, $connection->nodes );
		$this->assertSame( $in_stock->get_id(), $connection->nodes[0]->id );
	}

	/**
	 * @testdox execute() filters by stock_status OutOfStock.
	 */
	public function test_execute_filters_by_stock_status_out_of_stock(): void {
		WC_Helper_Product::create_simple_product( true, array( 'stock_status' => 'instock' ) );
		$out_of_stock = WC_Helper_Product::create_simple_product( true, array( 'stock_status' => 'outofstock' ) );

		$connection = $this->sut->execute(
			new PaginationParams(),
			$this->filters( stock_status: StockStatus::OutOfStock )
		);

		$this->assertCount( 1, $connection->nodes );
		$this->assertSame( $out_of_stock->get_id(), $connection->nodes[0]->id );
	}

	/**
	 * @testdox execute() filters by stock_status OnBackorder.
	 */
	public function test_execute_filters_by_stock_status_on_backorder(): void {
		WC_Helper_Product::create_simple_product( true, array( 'stock_status' => 'instock' ) );
		$on_backorder = WC_Helper_Product::create_simple_product( true, array( 'stock_status' => 'onbackorder' ) );

		$connection = $this->sut->execute(
			new PaginationParams(),
			$this->filters( stock_status: StockStatus::OnBackorder )
		);

		$this->assertCount( 1, $connection->nodes );
		$this->assertSame( $on_backorder->get_id(), $connection->nodes[0]->id );
	}

	/**
	 * @testdox execute() filters by stock_status Other (non-standard values).
	 */
	public function test_execute_filters_by_stock_status_other(): void {
		WC_Helper_Product::create_simple_product( true, array( 'stock_status' => 'instock' ) );
		$custom = WC_Helper_Product::create_simple_product();
		update_post_meta( $custom->get_id(), '_stock_status', 'plugin_custom' );

		$connection = $this->sut->execute(
			new PaginationParams(),
			$this->filters( stock_status: StockStatus::Other )
		);

		$this->assertCount( 1, $connection->nodes );
		$this->assertSame( $custom->get_id(), $connection->nodes[0]->id );
	}

	/**
	 * @testdox execute() filters by product_type Simple.
	 */
	public function test_execute_filters_by_product_type_simple(): void {
		$simple   = WC_Helper_Product::create_simple_product();
		$external = WC_Helper_Product::create_external_product();

		$connection = $this->sut->execute(
			new PaginationParams(),
			$this->filters(),
			ProductType::Simple
		);

		$ids = array_map( static fn( $node ): int => $node->id, $connection->nodes );
		$this->assertContains( $simple->get_id(), $ids );
		$this->assertNotContains( $external->get_id(), $ids );
	}

	/**
	 * @testdox execute() filters by product_type Other (non-standard types).
	 */
	public function test_execute_filters_by_product_type_other(): void {
		$simple = WC_Helper_Product::create_simple_product();
		$custom = WC_Helper_Product::create_simple_product();
		wp_set_object_terms( $custom->get_id(), 'plugin_custom_type', 'product_type' );

		$connection = $this->sut->execute(
			new PaginationParams(),
			$this->filters(),
			ProductType::Other
		);

		$ids = array_map( static fn( $node ): int => $node->id, $connection->nodes );
		$this->assertContains( $custom->get_id(), $ids );
		$this->assertNotContains( $simple->get_id(), $ids );
	}

	/**
	 * @testdox execute() filters by search keyword against the product name.
	 */
	public function test_execute_filters_by_search(): void {
		$widget = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Blue Widget' ) );
		WC_Helper_Product::create_simple_product( true, array( 'name' => 'Red Gadget' ) );

		$connection = $this->sut->execute(
			new PaginationParams(),
			$this->filters( search: 'Widget' )
		);

		$this->assertCount( 1, $connection->nodes );
		$this->assertSame( $widget->get_id(), $connection->nodes[0]->id );
	}

	/**
	 * @testdox execute() reports total_count after filters, not the unfiltered total.
	 */
	public function test_total_count_reflects_filters(): void {
		WC_Helper_Product::create_simple_product( true, array( 'status' => 'publish' ) );
		WC_Helper_Product::create_simple_product( true, array( 'status' => 'publish' ) );
		WC_Helper_Product::create_simple_product( true, array( 'status' => 'draft' ) );

		$connection = $this->sut->execute(
			new PaginationParams( first: 1 ),
			$this->filters( status: ProductStatus::Published )
		);

		$this->assertSame( 2, $connection->total_count );
		$this->assertCount( 1, $connection->nodes );
	}

	/**
	 * @testdox each edge carries a base64-encoded ID cursor.
	 */
	public function test_edges_carry_base64_id_cursors(): void {
		$product = WC_Helper_Product::create_simple_product();

		$connection = $this->sut->execute( new PaginationParams(), $this->filters() );

		$this->assertCount( 1, $connection->edges );
		$this->assertSame( base64_encode( (string) $product->get_id() ), $connection->edges[0]->cursor );
		$this->assertSame( $product->get_id(), $connection->edges[0]->node->id );
	}

	/**
	 * @testdox start_cursor and end_cursor on page_info mirror the first and last edge cursors.
	 */
	public function test_page_info_carries_start_and_end_cursors(): void {
		$first = WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();
		$last = WC_Helper_Product::create_simple_product();

		$connection = $this->sut->execute( new PaginationParams(), $this->filters() );

		$this->assertSame( base64_encode( (string) $first->get_id() ), $connection->page_info->start_cursor );
		$this->assertSame( base64_encode( (string) $last->get_id() ), $connection->page_info->end_cursor );
	}

	/**
	 * @testdox an empty result set returns no edges and null start/end cursors.
	 */
	public function test_empty_result_set(): void {
		$connection = $this->sut->execute( new PaginationParams(), $this->filters() );

		$this->assertSame( 0, $connection->total_count );
		$this->assertSame( array(), $connection->edges );
		$this->assertSame( array(), $connection->nodes );
		$this->assertNull( $connection->page_info->start_cursor );
		$this->assertNull( $connection->page_info->end_cursor );
		$this->assertFalse( $connection->page_info->has_next_page );
		$this->assertFalse( $connection->page_info->has_previous_page );
	}

	/**
	 * @testdox first=N with exactly N matching products reports has_next_page=false.
	 */
	public function test_first_equal_to_total_reports_no_next_page(): void {
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();

		$connection = $this->sut->execute( new PaginationParams( first: 2 ), $this->filters() );

		$this->assertCount( 2, $connection->nodes );
		$this->assertSame( 2, $connection->total_count );
		$this->assertFalse( $connection->page_info->has_next_page );
	}
}
