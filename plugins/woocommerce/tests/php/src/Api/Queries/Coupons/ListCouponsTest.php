<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Queries\Coupons;

use Automattic\WooCommerce\Api\Enums\Coupons\CouponStatus;
use Automattic\WooCommerce\Api\Pagination\IdCursorFilter;
use Automattic\WooCommerce\Api\Pagination\PaginationParams;
use Automattic\WooCommerce\Api\Queries\Coupons\ListCoupons;
use ReflectionClass;
use WC_Helper_Coupon;
use WC_Unit_Test_Case;

/**
 * Unit tests for {@see ListCoupons}.
 */
class ListCouponsTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var ListCoupons
	 */
	private ListCoupons $sut;

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

		$this->sut = new ListCoupons();
	}

	/**
	 * @testdox execute() returns all coupons with ascending IDs by default.
	 */
	public function test_execute_returns_all_coupons_in_ascending_order(): void {
		$a = WC_Helper_Coupon::create_coupon( 'a-coupon' );
		$b = WC_Helper_Coupon::create_coupon( 'b-coupon' );
		$c = WC_Helper_Coupon::create_coupon( 'c-coupon' );

		$connection = $this->sut->execute( new PaginationParams() );

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
		WC_Helper_Coupon::create_coupon( 'a' );
		WC_Helper_Coupon::create_coupon( 'b' );
		WC_Helper_Coupon::create_coupon( 'c' );

		$connection = $this->sut->execute( new PaginationParams( first: 2 ) );

		$this->assertCount( 2, $connection->nodes );
		$this->assertSame( 3, $connection->total_count );
		$this->assertTrue( $connection->page_info->has_next_page );
		$this->assertFalse( $connection->page_info->has_previous_page );
	}

	/**
	 * @testdox execute() honors `after` and returns coupons with IDs > cursor.
	 */
	public function test_execute_paginates_forward_with_after_cursor(): void {
		$first = WC_Helper_Coupon::create_coupon( 'a' );
		$mid   = WC_Helper_Coupon::create_coupon( 'b' );
		$last  = WC_Helper_Coupon::create_coupon( 'c' );

		$after_cursor = base64_encode( (string) $first->get_id() );

		$connection = $this->sut->execute(
			new PaginationParams( first: 10, after: $after_cursor )
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
		WC_Helper_Coupon::create_coupon( 'a' );
		$b = WC_Helper_Coupon::create_coupon( 'b' );
		$c = WC_Helper_Coupon::create_coupon( 'c' );

		$connection = $this->sut->execute( new PaginationParams( last: 2 ) );

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
		$a = WC_Helper_Coupon::create_coupon( 'a' );
		$b = WC_Helper_Coupon::create_coupon( 'b' );
		$c = WC_Helper_Coupon::create_coupon( 'c' );

		$before_cursor = base64_encode( (string) $c->get_id() );

		$connection = $this->sut->execute(
			new PaginationParams( last: 10, before: $before_cursor )
		);

		$this->assertCount( 2, $connection->nodes );
		$this->assertSame( $a->get_id(), $connection->nodes[0]->id );
		$this->assertSame( $b->get_id(), $connection->nodes[1]->id );
		$this->assertTrue( $connection->page_info->has_next_page );
	}

	/**
	 * @testdox execute() filters by status.
	 */
	public function test_execute_filters_by_status(): void {
		$published = WC_Helper_Coupon::create_coupon( 'published-coupon' );
		$draft_id  = wp_insert_post(
			array(
				'post_title'  => 'draft-coupon',
				'post_type'   => 'shop_coupon',
				'post_status' => 'draft',
			)
		);

		$connection = $this->sut->execute( new PaginationParams(), CouponStatus::Draft );

		$this->assertSame( 1, $connection->total_count );
		$this->assertCount( 1, $connection->nodes );
		$this->assertSame( $draft_id, $connection->nodes[0]->id );
		$this->assertNotSame( $published->get_id(), $connection->nodes[0]->id );
	}

	/**
	 * @testdox execute() reports total_count after filters, not the unfiltered total.
	 */
	public function test_total_count_reflects_filters(): void {
		WC_Helper_Coupon::create_coupon( 'a' );
		WC_Helper_Coupon::create_coupon( 'b' );
		wp_insert_post(
			array(
				'post_title'  => 'draft',
				'post_type'   => 'shop_coupon',
				'post_status' => 'draft',
			)
		);

		$connection = $this->sut->execute( new PaginationParams( first: 1 ), CouponStatus::Published );

		$this->assertSame( 2, $connection->total_count );
		$this->assertCount( 1, $connection->nodes );
	}

	/**
	 * @testdox each edge carries a base64-encoded ID cursor.
	 */
	public function test_edges_carry_base64_id_cursors(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'a' );

		$connection = $this->sut->execute( new PaginationParams() );

		$this->assertCount( 1, $connection->edges );
		$this->assertSame( base64_encode( (string) $coupon->get_id() ), $connection->edges[0]->cursor );
		$this->assertSame( $coupon->get_id(), $connection->edges[0]->node->id );
	}

	/**
	 * @testdox start_cursor and end_cursor on page_info mirror the first and last edge cursors.
	 */
	public function test_page_info_carries_start_and_end_cursors(): void {
		$first = WC_Helper_Coupon::create_coupon( 'a' );
		WC_Helper_Coupon::create_coupon( 'b' );
		$last = WC_Helper_Coupon::create_coupon( 'c' );

		$connection = $this->sut->execute( new PaginationParams() );

		$this->assertSame( base64_encode( (string) $first->get_id() ), $connection->page_info->start_cursor );
		$this->assertSame( base64_encode( (string) $last->get_id() ), $connection->page_info->end_cursor );
	}

	/**
	 * @testdox an empty result set returns no edges and null start/end cursors.
	 */
	public function test_empty_result_set(): void {
		$connection = $this->sut->execute( new PaginationParams() );

		$this->assertSame( 0, $connection->total_count );
		$this->assertSame( array(), $connection->edges );
		$this->assertSame( array(), $connection->nodes );
		$this->assertNull( $connection->page_info->start_cursor );
		$this->assertNull( $connection->page_info->end_cursor );
		$this->assertFalse( $connection->page_info->has_next_page );
		$this->assertFalse( $connection->page_info->has_previous_page );
	}

	/**
	 * @testdox first=N with exactly N matching coupons reports has_next_page=false.
	 */
	public function test_first_equal_to_total_reports_no_next_page(): void {
		WC_Helper_Coupon::create_coupon( 'a' );
		WC_Helper_Coupon::create_coupon( 'b' );

		$connection = $this->sut->execute( new PaginationParams( first: 2 ) );

		$this->assertCount( 2, $connection->nodes );
		$this->assertSame( 2, $connection->total_count );
		$this->assertFalse( $connection->page_info->has_next_page );
	}
}
