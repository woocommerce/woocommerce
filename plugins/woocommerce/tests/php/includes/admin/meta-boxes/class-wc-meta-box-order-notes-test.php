<?php
/**
 * Tests for paginated notes in the order editor.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

/**
 * Tests for WC_Meta_Box_Order_Notes.
 */
class WC_Meta_Box_Order_Notes_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Should return ten notes per page in newest-first order, scoped to the order.
	 */
	public function test_get_page(): void {
		$order = wc_create_order();
		$ids   = array();
		for ( $i = 1; $i <= 21; $i++ ) {
			$ids[] = $order->add_order_note( 'Note ' . $i );
		}
		$other_order = wc_create_order();
		$other_order->add_order_note( 'Another order' );

		$first  = WC_Meta_Box_Order_Notes::get_page( $order->get_id() );
		$second = WC_Meta_Box_Order_Notes::get_page( $order->get_id(), 2 );
		$last   = WC_Meta_Box_Order_Notes::get_page( $order->get_id(), 3 );

		$this->assertCount( 10, $first['notes'], 'The first page should contain ten notes.' );
		$this->assertSame( 21, $first['total'], 'Only this order should contribute to the count.' );
		$this->assertSame( 3, $first['total_pages'], 'Twenty-one notes should span three pages.' );
		$this->assertSame( array_reverse( array_slice( $ids, 11 ) ), array_column( $first['notes'], 'id' ), 'The first page should show the newest notes.' );
		$this->assertSame( array_reverse( array_slice( $ids, 1, 10 ) ), array_column( $second['notes'], 'id' ), 'The next page should not overlap the first.' );
		$this->assertSame( array( $ids[0] ), array_column( $last['notes'], 'id' ), 'The final page should contain the remaining note.' );
	}

	/**
	 * @testdox Should move back to the last available page after its final note is deleted.
	 */
	public function test_deleted_last_page(): void {
		$order = wc_create_order();
		for ( $i = 0; $i < 11; $i++ ) {
			$order->add_order_note( 'Note ' . $i );
		}
		$last = WC_Meta_Box_Order_Notes::get_page( $order->get_id(), 2 );
		wc_delete_order_note( reset( $last['notes'] )->id );

		$result = WC_Meta_Box_Order_Notes::get_page( $order->get_id(), 2 );

		$this->assertSame( 1, $result['page'], 'An empty final page should fall back to the preceding page.' );
		$this->assertCount( 10, $result['notes'], 'The remaining notes should fill the first page.' );
		$this->assertSame( 10, $result['total'], 'The total should reflect the deletion.' );
	}

	/**
	 * @testdox Should return an empty first page for an order with no notes or an unsaved order.
	 */
	public function test_empty_order(): void {
		$order = wc_create_order();
		foreach ( array( 0, $order->get_id() ) as $order_id ) {
			$result = WC_Meta_Box_Order_Notes::get_page( $order_id, 50 );
			$this->assertCount( 0, $result['notes'], 'An empty order should not load notes from another order.' );
			$this->assertSame( 0, $result['total'], 'An empty order should have no notes.' );
			$this->assertSame( 1, $result['page'], 'An empty order should remain on page one.' );
			$this->assertSame( 1, $result['total_pages'], 'The page input should have a valid minimum and maximum.' );
		}
	}

	/**
	 * @testdox Should clamp a page below one to the first page.
	 */
	public function test_negative_page(): void {
		$order = wc_create_order();
		$order->add_order_note( 'A note' );

		$result = WC_Meta_Box_Order_Notes::get_page( $order->get_id(), -1 );

		$this->assertSame( 1, $result['page'], 'Negative pages should load the first page.' );
		$this->assertCount( 1, $result['notes'], 'The first page should contain the note.' );
	}
}
