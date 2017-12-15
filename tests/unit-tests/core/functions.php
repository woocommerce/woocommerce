<?php

/**
 * Class Functions.
 *
 * @package WooCommerce\Tests\Core
 * @since 3.2.0
 */
class WC_Tests_WooCommerce_Functions extends WC_Unit_Test_Case {

	/**
	 * Tests wc_maybe_define_constant().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_maybe_define_constant() {
		$this->assertFalse( defined( 'WC_TESTING_DEFINE_FUNCTION' ) );

		// Check if defined.
		wc_maybe_define_constant( 'WC_TESTING_DEFINE_FUNCTION', true );
		$this->assertTrue( defined( 'WC_TESTING_DEFINE_FUNCTION' ) );

		// Check value.
		wc_maybe_define_constant( 'WC_TESTING_DEFINE_FUNCTION', false );
		$this->assertTrue( WC_TESTING_DEFINE_FUNCTION );
	}

	/**
	 * Tests wc_create_order() and wc_update_order() currency handling.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_create_update_order_currency() {
		$old_currency = get_woocommerce_currency();
		$new_currency = 'BGN';

		update_option( 'woocommerce_currency', $new_currency );

		// New order should be created using shop currency.
		$order = wc_create_order( array(
			'status'      => 'pending',
			'customer_id' => 1,
			'created_via'	=> 'unit tests',
			'cart_hash'		=> '',
		) );
		$this->assertEquals( $new_currency, $order->get_currency() );

		update_option( 'woocommerce_currency', $old_currency );

		// Currency should not change when order is updated.
		$order = wc_update_order( array(
			'customer_id' => 2,
			'order_id'    => $order->get_id(),
		) );
		$this->assertEquals( $new_currency, $order->get_currency() );

		$order = wc_update_order( array(
			'customer_id' => 2,
		) );
		$this->assertInstanceOf( 'WP_Error', $order );
	}

	/**
	 * Test the wc_is_active_theme function.
	 *
	 * @return void
	 */
	public function test_wc_is_active_theme() {
		$this->assertTrue( wc_is_active_theme( 'default' ) );
		$this->assertFalse( wc_is_active_theme( 'twentyfifteen' ) );
		$this->assertTrue( wc_is_active_theme( array( 'default', 'twentyseventeen' ) ) );
	}

	/**
	 * Test the wc_get_template function.
	 *
	 * @return void
	 */
	public function test_wc_get_template_part() {
		$this->assertEmpty( wc_get_template_part( 'nothinghere' ) );
	}
}
