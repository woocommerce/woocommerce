<?php

/**
 * Classes WC_Deprecated_Filter_Hooks & WC_Deprecated_Action_Hooks.
 * @package WooCommerce\Tests\Util
 * @since 2.7
 */
class WC_Tests_Deprecated_Hooks extends WC_Unit_Test_Case {

	protected $handlers = array();

	function setUp() {
		add_filter( 'deprecated_function_trigger_error', '__return_false' );
		$this->handlers = WC()->deprecated_hook_handlers;
	}

	/**
	 * Test the deprecated hook handlers are initialized
	 *
	 * @since 2.7
	 */
	function test_deprecated_hook_handlers_exist() {
		$this->assertArrayHasKey( 'filters', $this->handlers );
		$this->assertInstanceOf( 'WC_Deprecated_Filter_Hooks', $this->handlers['filters'] );

		$this->assertArrayHasKey( 'actions', $this->handlers );
		$this->assertInstanceOf( 'WC_Deprecated_Action_Hooks', $this->handlers['actions'] );
	}

	/**
	 * Test the get_old_hooks method
	 *
	 * @since 2.7
	 */
	function test_get_old_hooks() {
		$old_filters = $this->handlers['filters']->get_old_hooks( 'woocommerce_structured_data_order' );
		$old_actions = $this->handlers['actions']->get_old_hooks( 'woocommerce_new_order_item' );

		$this->assertContains( 'woocommerce_email_order_schema_markup', $old_filters );
		$this->assertContains( 'woocommerce_order_add_shipping', $old_actions );
	}

	/**
	 * Test the hook_in method
	 *
	 * @since 2.7
	 */
	function test_hook_in() {
		$this->assertTrue( (bool) has_filter( 'woocommerce_structured_data_order', array( $this->handlers['filters'], 'maybe_handle_deprecated_hook' ) ) );
		$this->assertTrue( (bool) has_action( 'woocommerce_new_order_item', array( $this->handlers['actions'], 'maybe_handle_deprecated_hook' ) ) );
	}

	/**
	 * Test the handle_deprecated_hook method in the filters handler
	 *
	 * @since 2.7
	 */
	function test_handle_deprecated_hook_filter() {
		$new_hook = 'wc_new_hook';
		$old_hook = 'wc_old_hook';
		$args = array( false );
		$return = -1;

		add_filter( $old_hook, function( $value ) {
			return ! $value;
		} );

		$result = $this->handlers['filters']->handle_deprecated_hook( $new_hook, $old_hook, $args, $return );
		$this->assertTrue( $result );
	}

	/**
	 * Test the handle_deprecated_hook method in the actions handler
	 *
	 * @since 2.7
	 */
	function test_handle_deprecated_hook_action() {
		$new_hook = 'wc_new_hook';
		$old_hook = 'wc_old_hook';
		$test_value = false;
		$args = array(  &$test_value );
		$return = -1;

		add_action( $old_hook, function( &$value ) {
			$value = true;
		} );

		$this->handlers['actions']->handle_deprecated_hook( $new_hook, $old_hook, $args, $return );
		$this->assertTrue( $test_value );
	}

	/**
	 * Test a complete deprecated filter mapping
	 *
	 * @since 2.7
	 */
	function test_filter_handler() {
		$test_width = 1;

		add_filter( 'woocommerce_product_width', function( $width ) {
			return -1 * $width;
		} );

		$new_width = apply_filters( 'woocommerce_product_get_width', $test_width );
		$this->assertEquals( -1, $new_width );
	}

	/**
	 * Test a complete deprecated action mapping
	 *
	 * @since 2.7
	 */
	function test_action_handler() {
		$test_product = WC_Helper_Product::create_simple_product();
		$test_order = WC_Helper_Order::create_order( 1, $test_product );
		$test_order_id = $test_order->get_id();
		$test_items = $test_order->get_items();
		$test_item = reset( $test_items );
		$test_item_id = $test_item->get_id();

		add_action( 'woocommerce_order_edit_product', function( $order_id, $item_id, $item ) {
			$this->assertInstanceOf( 'WC_Order_Item_Product', $item );
			update_post_meta( $order_id, 'wc_action_test_order_meta', true );
		}, 10, 3 );

		do_action( 'woocommerce_update_order_item', $test_item_id, $test_item, $test_order_id );

		$order_update_worked = (bool) get_post_meta( $test_order_id, 'wc_action_test_order_meta', true );
		$this->assertTrue( $order_update_worked );
	}
}
