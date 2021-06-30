<?php
/**
 * Classes WC_Deprecated_Filter_Hooks & WC_Deprecated_Action_Hooks.
 * @package WooCommerce\Tests\Util
 * @since 3.0
 */

/**
 * Class WC_Tests_Deprecated_Hooks.
 */
class WC_Tests_Deprecated_Hooks extends WC_Unit_Test_Case {

	/**
	 * @var array Deprecated hook handlers.
	 */
	protected $handlers = array();

	/**
	 * Generic toggle value function that can be hooked to a filter for testing.
	 *
	 * @param bool/int $value Value to change.
	 *
	 * @return bool/int Changed value.
	 */
	public function toggle_value( $value ) {
		return ! $value;
	}

	/**
	 * Generic toggle value function that can be hooked to an action for testing.
	 *
	 * @param bool/int $value Value to change.
	 */
	public function toggle_value_by_ref( &$value ) {
		$value = ! $value;
	}

	/**
	 * Generic meta setting function that can be hooked to an action for testing.
	 *
	 * @param int $item1_id Post ID.
	 * @param int $item2_id Post ID.
	 */
	public function set_meta( $item1_id, $item2_id = false ) {
		update_post_meta( $item1_id, 'wc_deprecated_hook_test_item1_meta', 1 );
		if ( $item2_id ) {
			update_post_meta( $item2_id, 'wc_deprecated_hook_test_item2_meta', 2 );
		}
	}

	/**
	 * Disable deprecation error trigger for this class.
	 */
	public function setUp() {
		parent::setUp();
		add_filter( 'deprecated_function_trigger_error', '__return_false' );
		add_filter( 'deprecated_hook_trigger_error', '__return_false' );
		$this->handlers = WC()->deprecated_hook_handlers;
	}

	/**
	 * Test the deprecated hook handlers are initialized.
	 *
	 * @since 3.0
	 */
	public function test_deprecated_hook_handlers_exist() {
		$this->assertArrayHasKey( 'filters', $this->handlers );
		$this->assertInstanceOf( 'WC_Deprecated_Filter_Hooks', $this->handlers['filters'] );

		$this->assertArrayHasKey( 'actions', $this->handlers );
		$this->assertInstanceOf( 'WC_Deprecated_Action_Hooks', $this->handlers['actions'] );
	}

	/**
	 * Test the get_old_hooks method.
	 *
	 * @since 3.0
	 */
	public function test_get_old_hooks() {
		$old_filters = $this->handlers['filters']->get_old_hooks( 'woocommerce_structured_data_order' );
		$old_actions = $this->handlers['actions']->get_old_hooks( 'woocommerce_new_order_item' );

		$this->assertContains( 'woocommerce_email_order_schema_markup', $old_filters );
		$this->assertContains( 'woocommerce_order_add_shipping', $old_actions );
	}

	/**
	 * Test the hook_in method.
	 *
	 * @since 3.0
	 */
	public function test_hook_in() {
		$this->assertTrue( (bool) has_filter( 'woocommerce_structured_data_order', array( $this->handlers['filters'], 'maybe_handle_deprecated_hook' ) ) );
		$this->assertTrue( (bool) has_action( 'woocommerce_new_order_item', array( $this->handlers['actions'], 'maybe_handle_deprecated_hook' ) ) );
	}

	/**
	 * Test the handle_deprecated_hook method in the filters handler.
	 *
	 * @since 3.0
	 */
	public function test_handle_deprecated_hook_filter() {
		$new_hook = 'wc_new_hook';
		$old_hook = 'wc_old_hook';
		$args     = array( false );
		$return   = -1;

		$this->setExpectedDeprecated( 'wc_old_hook' );
		add_filter( $old_hook, array( $this, 'toggle_value' ) );

		$result = $this->handlers['filters']->handle_deprecated_hook( $new_hook, $old_hook, $args, $return );
		$this->assertTrue( $result );
	}

	/**
	 * Test the handle_deprecated_hook method in the actions handler.
	 *
	 * @since 3.0
	 */
	public function test_handle_deprecated_hook_action() {
		$new_hook   = 'wc_new_hook';
		$old_hook   = 'wc_old_hook';
		$test_value = false;
		$args       = array( &$test_value );
		$return     = -1;

		$this->setExpectedDeprecated( 'wc_old_hook' );
		add_filter( $old_hook, array( $this, 'toggle_value_by_ref' ) );

		$this->handlers['actions']->handle_deprecated_hook( $new_hook, $old_hook, $args, $return );
		$this->assertTrue( $test_value );
	}

	/**
	 * Test a complete deprecated filter mapping.
	 *
	 * @since 3.0
	 */
	public function test_filter_handler() {
		$test_width = 1;

		$this->setExpectedDeprecated( 'woocommerce_product_width' );
		add_filter( 'woocommerce_product_width', array( $this, 'toggle_value' ) );

		$new_width = apply_filters( 'woocommerce_product_get_width', $test_width );
		$this->assertEquals( 0, $new_width );
	}

	/**
	 * Test a complete deprecated action mapping.
	 *
	 * @since 3.0
	 */
	public function test_action_handler() {
		$test_product  = WC_Helper_Product::create_simple_product();
		$test_order    = WC_Helper_Order::create_order( 1, $test_product );
		$test_order_id = $test_order->get_id();
		$test_items    = $test_order->get_items();
		$test_item     = reset( $test_items );
		$test_item_id  = $test_item->get_id();

		$this->setExpectedDeprecated( 'woocommerce_order_edit_product' );
		add_action( 'woocommerce_order_edit_product', array( $this, 'set_meta' ), 10, 2 );
		do_action( 'woocommerce_update_order_item', $test_item_id, $test_item, $test_order_id );

		$order_update_worked = (bool) get_post_meta( $test_order_id, 'wc_deprecated_hook_test_item1_meta', 1 );
		$item_update_worked  = (bool) get_post_meta( $test_item_id, 'wc_deprecated_hook_test_item2_meta', 2 );

		$this->assertTrue( $order_update_worked );
		$this->assertTrue( $item_update_worked );
	}

	/**
	 * Test the mapping of deprecated created_* hooks to new_* hooks.
	 *
	 * @since 3.0
	 */
	public function test_created_actions_deprecation() {
		add_filter( 'woocommerce_payment_token_created', '__return_true' );
		add_filter( 'woocommerce_create_product_variation', '__return_true' );

		$this->setExpectedDeprecated( 'woocommerce_payment_token_created' );
		$this->setExpectedDeprecated( 'woocommerce_create_product_variation' );

		$token = WC_Helper_Payment_Token::create_stub_token( __FUNCTION__ );
		$token->save();

		$product = new WC_Product_Variation();
		$product->save();

		$this->assertEquals( 1, did_action( 'woocommerce_payment_token_created' ) );
		$this->assertEquals( 1, did_action( 'woocommerce_new_payment_token' ) );
		$this->assertEquals( 1, did_action( 'woocommerce_create_product_variation' ) );
		$this->assertEquals( 1, did_action( 'woocommerce_new_product_variation' ) );
	}
}
