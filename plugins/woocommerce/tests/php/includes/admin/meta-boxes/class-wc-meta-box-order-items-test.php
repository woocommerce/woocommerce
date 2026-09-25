<?php
/**
 * Tests for the order items meta box.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;

require_once WC_ABSPATH . 'includes/admin/wc-meta-box-functions.php';
require_once WC_ABSPATH . 'includes/admin/meta-boxes/class-wc-meta-box-order-items.php';

/**
 * Tests for WC_Meta_Box_Order_Items.
 */
class WC_Meta_Box_Order_Items_Test extends WC_Unit_Test_Case {

	/**
	 * Whether the global order existed before the test.
	 *
	 * @var bool
	 */
	private $had_global_order;

	/**
	 * Original global order value.
	 *
	 * @var mixed
	 */
	private $original_global_order;

	/**
	 * Set up test state.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->had_global_order      = array_key_exists( 'theorder', $GLOBALS );
		$this->original_global_order = $GLOBALS['theorder'] ?? null;

		update_option( 'date_format', 'Y-m-d' );
	}

	/**
	 * Restore test state.
	 */
	public function tearDown(): void {
		try {
			if ( $this->had_global_order ) {
				$GLOBALS['theorder'] = $this->original_global_order;
			} else {
				unset( $GLOBALS['theorder'] );
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox The meta box renders the order total and the payment date in the Paid row of a paid order.
	 */
	public function test_renders_the_paid_row_for_a_completed_order_with_a_paid_date(): void {
		$order = $this->create_order( OrderStatus::COMPLETED, '2024-03-05 10:00:00' );

		$output = $this->render_items_meta_box( $order );

		$this->assertSame(
			$this->to_text( wc_price( 200, array( 'currency' => $order->get_currency() ) ) ),
			$this->get_totals_row_total( $output, 'Paid:' ),
			'The Paid row should show the order total.'
		);
		$this->assertSame( '2024-03-05', $this->get_paid_row_description( $output ), 'The Paid row should show the payment date.' );
	}

	/**
	 * @testdox The meta box omits the Paid row for a completed order that has no payment date.
	 */
	public function test_omits_the_paid_row_when_a_completed_order_has_no_paid_date(): void {
		$order = $this->create_order( OrderStatus::COMPLETED, null );

		$this->assertNull( $order->get_date_paid(), 'The order fixture should have no payment date.' );

		$output = $this->render_items_meta_box( $order );

		$this->assert_order_totals_are_rendered( $output, $order );
		$this->assertNull( $this->get_totals_row_total( $output, 'Paid:' ), 'No Paid row should be rendered.' );
	}

	/**
	 * @testdox The meta box omits the Paid row for an order whose status is not one of the paid statuses.
	 */
	public function test_omits_the_paid_row_for_a_status_that_is_not_paid(): void {
		$order = $this->create_order( OrderStatus::PENDING, '2024-03-05 10:00:00' );

		$this->assertNotNull( $order->get_date_paid(), 'The order fixture should have a payment date.' );

		$output = $this->render_items_meta_box( $order );

		$this->assert_order_totals_are_rendered( $output, $order );
		$this->assertNull( $this->get_totals_row_total( $output, 'Paid:' ), 'No Paid row should be rendered.' );
	}

	/**
	 * Create a saved order with one line item and a total of 200.
	 *
	 * @param string      $status    Status to save the order with.
	 * @param string|null $date_paid Payment date, or null for an order that has none.
	 * @return WC_Order
	 */
	private function create_order( string $status, ?string $date_paid ): WC_Order {
		$product = WC_Helper_Product::create_simple_product();

		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 2 );
		$item->set_subtotal( '200' );
		$item->set_total( '200' );

		$order = new WC_Order();
		$order->add_item( $item );
		$order->set_total( '200' );

		// set_status() fills in a missing payment date for a paid status, so the date is set afterwards.
		$order->set_status( $status );
		$order->set_date_paid( $date_paid );
		$order->save();

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Render the order items meta box.
	 *
	 * @param WC_Order $order Order to render.
	 * @return string
	 */
	private function render_items_meta_box( WC_Order $order ): string {
		$GLOBALS['theorder'] = $order;

		ob_start();
		try {
			WC_Meta_Box_Order_Items::output( $order );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		return $output;
	}

	/**
	 * Assert the meta box rendered the order totals, so an absent Paid row cannot be an empty render.
	 *
	 * @param string   $html  Rendered meta box markup.
	 * @param WC_Order $order Order that was rendered.
	 */
	private function assert_order_totals_are_rendered( string $html, WC_Order $order ): void {
		$this->assertSame(
			$this->to_text( wc_price( 200, array( 'currency' => $order->get_currency() ) ) ),
			$this->get_totals_row_total( $html, 'Order Total:' ),
			'The meta box should render the order totals.'
		);
	}

	/**
	 * Get the total rendered in an order totals row, or null when the row is absent.
	 *
	 * @param string $html  Rendered meta box markup.
	 * @param string $label Label of the row to read, including its colon.
	 * @return string|null
	 */
	private function get_totals_row_total( string $html, string $label ): ?string {
		$nodes = $this->query_totals_row( $html, $label, "td[contains(concat(' ', normalize-space(@class), ' '), ' total ')]" );

		$this->assertLessThan( 2, $nodes->length, "At most one {$label} row should be rendered." );

		return 0 === $nodes->length ? null : $this->to_text( $nodes->item( 0 )->textContent );
	}

	/**
	 * Get the description rendered under the Paid row.
	 *
	 * @param string $html Rendered meta box markup.
	 * @return string|null
	 */
	private function get_paid_row_description( string $html ): ?string {
		$nodes = $this->query_totals_row( $html, 'Paid:', "following-sibling::tr[1]//span[contains(concat(' ', normalize-space(@class), ' '), ' description ')]" );

		return 0 === $nodes->length ? null : $this->to_text( $nodes->item( 0 )->textContent );
	}

	/**
	 * Query nodes relative to a labelled row of an order totals table.
	 *
	 * @param string $html     Rendered meta box markup.
	 * @param string $label    Label of the row to query, including its colon.
	 * @param string $relative XPath to evaluate against the row.
	 * @return DOMNodeList
	 */
	private function query_totals_row( string $html, string $label, string $relative ): DOMNodeList {
		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<!DOCTYPE html><html><body>' . $html . '</body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The order items meta box output should be valid enough for DOM parsing.' );

		$row = "//table[contains(concat(' ', normalize-space(@class), ' '), ' wc-order-totals ')]"
			. "//tr[td[contains(concat(' ', normalize-space(@class), ' '), ' label ')][normalize-space(.)='{$label}']]";

		$nodes = ( new DOMXPath( $document ) )->query( $row . '/' . $relative );

		$this->assertNotFalse( $nodes, "The {$label} row XPath query should be valid." );

		return $nodes;
	}

	/**
	 * Reduce rendered markup to its comparable text.
	 *
	 * @param string $html Markup to reduce.
	 * @return string
	 */
	private function to_text( string $html ): string {
		$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );

		return trim( (string) preg_replace( '/\s+/', ' ', $text ) );
	}
}
