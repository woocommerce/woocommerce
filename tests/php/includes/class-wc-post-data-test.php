<?php
/**
 * Post data tests
 *
 * @package WooCommerce\Tests\Post_Data.
 */

/**
 * Class WC_Post_Data_Test
 */
class WC_Post_Data_Test extends \WC_Unit_Test_Case {

	/**
	 * @testdox coupon code should be always sanitized.
	 */
	public function test_coupon_code_sanitization() {
		$this->login_as_role( 'shop_manager' );
		$coupon    = WC_Helper_Coupon::create_coupon( 'a&a' );
		$post_data = get_post( $coupon->get_id() );
		$this->assertEquals( 'a&amp;a', $post_data->post_title );
		$coupon->delete( true );

		$this->login_as_administrator();
		$coupon    = WC_Helper_Coupon::create_coupon( 'b&b' );
		$post_data = get_post( $coupon->get_id() );
		$this->assertEquals( 'b&amp;b', $post_data->post_title );
		$coupon->delete( true );

		wp_set_current_user( 0 );
		$coupon    = WC_Helper_Coupon::create_coupon( 'c&c' );
		$post_data = get_post( $coupon->get_id() );
		$this->assertEquals( 'c&amp;c', $post_data->post_title );
		$coupon->delete( true );
	}
}
