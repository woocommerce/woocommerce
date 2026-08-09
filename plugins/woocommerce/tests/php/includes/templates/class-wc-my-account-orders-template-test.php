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
	 * Hooks registered by the current test.
	 *
	 * @var array<int, array{type: 'filter'|'action', hook_name: string, callback: callable, priority: int}>
	 */
	private array $registered_hooks = array();

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		foreach ( $this->registered_hooks as $hook ) {
			if ( 'filter' === $hook['type'] ) {
				remove_filter( $hook['hook_name'], $hook['callback'], $hook['priority'] );
			} else {
				remove_action( $hook['hook_name'], $hook['callback'], $hook['priority'] );
			}
		}

		$this->registered_hooks = array();

		parent::tearDown();
	}

	/**
	 * @testdox Default order status output can be changed by composable filters.
	 */
	public function test_order_status_column_filters_compose_with_default_output(): void {
		$order = $this->create_order_with_status( 'processing' );

		$this->add_test_filter(
			'woocommerce_account_orders_column_content_order-status',
			static function ( string $column_content ): string {
				return $column_content . '<span class="first-filter">First filter</span>';
			},
			10,
			1
		);
		$this->add_test_filter(
			'woocommerce_account_orders_column_content_order-status',
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
		$order        = $this->create_order_with_status( 'processing' );
		$filter_calls = 0;

		$this->add_test_filter(
			'woocommerce_account_orders_column_content_order-status',
			static function () use ( &$filter_calls ): string {
				++$filter_calls;
				return '<span class="filtered-status">Filtered status</span>';
			}
		);
		$this->add_test_action(
			'woocommerce_my_account_my_orders_column_order-status',
			static function (): void {
				echo '<span class="legacy-status">Legacy status</span>';
			}
		);

		$html = $this->render_orders_template( $order );

		$this->assertStringContainsString( 'Legacy status', $html, 'Existing action callbacks should still render.' );
		$this->assertSame( 0, $filter_calls, 'Default-content filters should not run when a legacy action replaces the cell.' );
		$this->assertStringNotContainsString( 'Processing', $html, 'Existing action callbacks should still suppress default output.' );
		$this->assertStringNotContainsString( 'Filtered status', $html, 'New filters should not run when legacy action replacement is active.' );
	}

	/**
	 * @testdox Built-in order columns expose their default content and context to filters.
	 * @dataProvider default_column_provider
	 *
	 * @param string $column_id       Column ID.
	 * @param string $default_content Semantic content expected from the default renderer.
	 */
	public function test_default_order_columns_are_filterable( string $column_id, string $default_content ): void {
		$order              = $this->create_order_with_status( 'processing' );
		$filtered_content   = null;
		$filtered_order     = null;
		$filtered_column_id = null;

		$this->add_test_filter(
			'woocommerce_account_orders_column_content_' . $column_id,
			static function ( string $content, WC_Order $current_order, string $current_column_id ) use ( &$filtered_content, &$filtered_order, &$filtered_column_id ): string {
				$filtered_content   = $content;
				$filtered_order     = $current_order;
				$filtered_column_id = $current_column_id;
				return $content . '<span class="column-filter-marker">Filtered column</span>';
			},
			10,
			3
		);

		$html = $this->render_orders_template( $order );

		$this->assertIsString( $filtered_content, 'The column content filter should run.' );
		$this->assertStringContainsString( $default_content, $filtered_content, 'The filter should receive the existing default column HTML.' );
		$this->assertInstanceOf( WC_Order::class, $filtered_order, 'The filter should receive an order object.' );
		$this->assertSame( $order->get_id(), $filtered_order instanceof WC_Order ? $filtered_order->get_id() : null, 'The filter should receive the current order.' );
		$this->assertSame( $column_id, $filtered_column_id, 'The filter should receive the current column ID.' );
		$this->assertStringContainsString( 'Filtered column', $html, 'The filtered column HTML should render.' );
	}

	/**
	 * @testdox Existing account order column actions do not collide with content filters.
	 */
	public function test_existing_account_order_column_actions_do_not_collide_with_content_filters(): void {
		$order               = $this->create_order_with_status( 'processing' );
		$legacy_action_calls = 0;

		$this->add_test_filter(
			'woocommerce_account_orders_columns',
			static function ( array $columns ): array {
				$columns['order-type'] = 'Order type';
				return $columns;
			}
		);
		$this->add_test_action(
			'woocommerce_account_orders_column_order-type',
			static function () use ( &$legacy_action_calls ): void {
				++$legacy_action_calls;
			}
		);
		$this->add_test_filter(
			'woocommerce_account_orders_column_content_order-type',
			static function (): string {
				return '<span class="filtered-order-type">Filtered order type</span>';
			}
		);

		$html = $this->render_orders_template( $order );

		$this->assertSame( 0, $legacy_action_calls, 'A content filter should not invoke callbacks registered on the existing action-style hook name.' );
		$this->assertStringContainsString( 'Filtered order type', $html, 'A custom column should render content from its filter.' );
	}

	/**
	 * Built-in columns and representative default content.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function default_column_provider(): array {
		return array(
			'order number'  => array( 'order-number', 'View order number' ),
			'order date'    => array( 'order-date', '<time datetime=' ),
			'order status'  => array( 'order-status', 'Processing' ),
			'order total'   => array( 'order-total', 'woocommerce-Price-amount' ),
			'order actions' => array( 'order-actions', 'woocommerce-button' ),
		);
	}

	/**
	 * Create an order with a specific status.
	 *
	 * @param string $status Order status without wc- prefix.
	 * @return WC_Order
	 */
	private function create_order_with_status( string $status ): WC_Order {
		$order = wc_create_order( array( 'status' => $status ) );
		if ( is_wp_error( $order ) ) {
			throw new RuntimeException( 'Could not create an order for the template test.' );
		}

		return $order;
	}

	/**
	 * Register a filter that will be removed during tear down.
	 *
	 * @param string   $hook_name     Filter name.
	 * @param callable $callback      Filter callback.
	 * @param int      $priority      Filter priority.
	 * @param int      $accepted_args Number of accepted arguments.
	 */
	private function add_test_filter( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		add_filter( $hook_name, $callback, $priority, $accepted_args );
		$this->registered_hooks[] = array(
			'type'      => 'filter',
			'hook_name' => $hook_name,
			'callback'  => $callback,
			'priority'  => $priority,
		);
	}

	/**
	 * Register an action that will be removed during tear down.
	 *
	 * @param string   $hook_name     Action name.
	 * @param callable $callback      Action callback.
	 * @param int      $priority      Action priority.
	 * @param int      $accepted_args Number of accepted arguments.
	 */
	private function add_test_action( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		add_action( $hook_name, $callback, $priority, $accepted_args );
		$this->registered_hooks[] = array(
			'type'      => 'action',
			'hook_name' => $hook_name,
			'callback'  => $callback,
			'priority'  => $priority,
		);
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
