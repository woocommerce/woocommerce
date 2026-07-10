<?php
/**
 * Tests for the My Account orders template.
 */

declare( strict_types = 1 );

/**
 * My Account orders template test.
 */
class WC_My_Account_Orders_Template_Test extends WC_Unit_Test_Case {

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_account_orders_column_order-status' );
		remove_all_actions( 'woocommerce_my_account_my_orders_column_order-status' );

		parent::tearDown();
	}

	/**
	 * @testdox Default order status output can be changed by composable filters.
	 */
	public function test_order_status_column_filters_compose_with_default_output(): void {
		$order = $this->create_order_with_status( 'processing' );

		add_filter(
			'woocommerce_account_orders_column_order-status',
			static function ( string $column_content ): string {
				return $column_content . '<span class="first-filter">First filter</span>';
			},
			10,
			1
		);
		add_filter(
			'woocommerce_account_orders_column_order-status',
			static function ( string $column_content ): string {
				return $column_content . '<span class="second-filter">Second filter</span>';
			},
			20,
			1
		);

		$html = $this->render_orders_template( $order );

		$this->assertStringContainsString( 'Processing', $html, 'Default order status output should remain available to filters.' );
		$this->assertStringContainsString( 'First filter', $html, 'First filter output should render.' );
		$this->assertStringContainsString( 'Second filter', $html, 'Second filter output should render.' );
		$this->assertSame( 1, substr_count( $html, 'Processing' ), 'Default order status should not be duplicated when filters compose.' );
	}

	/**
	 * @testdox Legacy order status column action still replaces the full cell.
	 */
	public function test_legacy_order_status_column_action_still_replaces_default_output(): void {
		$order = $this->create_order_with_status( 'processing' );

		add_filter(
			'woocommerce_account_orders_column_order-status',
			static function (): string {
				return '<span class="filtered-status">Filtered status</span>';
			}
		);
		add_action(
			'woocommerce_my_account_my_orders_column_order-status',
			static function (): void {
				echo '<span class="legacy-status">Legacy status</span>';
			}
		);

		$html = $this->render_orders_template( $order );

		$this->assertStringContainsString( 'Legacy status', $html, 'Existing action callbacks should still render.' );
		$this->assertStringNotContainsString( 'Processing', $html, 'Existing action callbacks should still suppress default output.' );
		$this->assertStringNotContainsString( 'Filtered status', $html, 'New filters should not run when legacy action replacement is active.' );
	}

	/**
	 * Create an order with a specific status.
	 *
	 * @param string $status Order status without wc- prefix.
	 * @return WC_Order
	 */
	private function create_order_with_status( string $status ): WC_Order {
		$order = WC_Helper_Order::create_order();
		$order->set_status( $status );
		$order->save();

		return $order;
	}

	/**
	 * Render the active My Account orders template for one order.
	 *
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	private function render_orders_template( WC_Order $order ): string {
		return wc_get_template_html(
			'myaccount/orders.php',
			array(
				'current_page'    => 1,
				'customer_orders' => (object) array(
					'orders'        => array( $order->get_id() ),
					'total'         => 1,
					'max_num_pages' => 1,
				),
				'has_orders'      => true,
				'wp_button_class' => '',
			)
		);
	}
}
