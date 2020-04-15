<?php
/**
 * Unit tests for the post types admin class.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * WC_Admin_Post_Types tests.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Tests_Admin_Post_Types extends WC_Unit_Test_Case {

	/**
	 * Data for bulk_and_quick_edit_stock_status_for_variable_product test.
	 *
	 * @return string[][]
	 */
	public function data_provider_bulk_and_quick_edit_stock_status_for_variable_product() {
		return array(
			// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			// $edit_type, $change_stock_request_value, $expected_new_stock_status
			array( 'quick_edit', '', 'outofstock' ),
			array( 'quick_edit', 'instock', 'instock' ),
			array( 'bulk_edit', '', 'outofstock' ),
			array( 'bulk_edit', 'instock', 'instock' ),
		);
	}

	/**
	 * @test
	 * @testdox When quick or bulk editing a variable product, stock status for the variations should change only if a new stock status is supplied.
	 * @dataProvider data_provider_bulk_and_quick_edit_stock_status_for_variable_product
	 *
	 * @param string $edit_type 'quick_edit' or 'bulk_edit'.
	 * @param string $change_stock_request_value The value of '_stock_status' from the request.
	 * @param string $expected_new_stock_status Expected value of the stock status for the variations after the save operation.
	 */
	public function bulk_and_quick_edit_stock_status_for_variable_product( $edit_type, $change_stock_request_value, $expected_new_stock_status ) {
		$product = WC_Helper_Product::create_variation_product();

		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			$product->set_manage_stock( false );
			$child->set_stock_status( 'outofstock' );
			$child->save();
		}

		$this->login_as_administrator();

		$request_data = array(
			"woocommerce_{$edit_type}"     => '1',
			'change_stock'                 => '',
			'_stock_status'                => $change_stock_request_value,
			'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
		);

		$sut = $this
			->getMockBuilder( WC_Admin_Post_Types::class )
			->setMethods( array( 'request_data' ) )
			->getMock();

		$sut->method( 'request_data' )->willReturn( $request_data );

		$sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			$this->assertEquals( $expected_new_stock_status, $child->get_stock_status() );
		}
	}
}
