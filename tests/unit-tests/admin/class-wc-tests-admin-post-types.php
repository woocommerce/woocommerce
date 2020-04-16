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
	 * Get a new SUT (System Under Test) instance and configure the specified fake request data.
	 *
	 * @param array $request_data Fake request data to configure.
	 * @return WC_Admin_Post_Types
	 */
	private function get_sut_with_request_data( $request_data ) {
		$sut = $this
			->getMockBuilder( WC_Admin_Post_Types::class )
			->setMethods( array( 'request_data' ) )
			->getMock();

		$sut->method( 'request_data' )->willReturn( $request_data );

		return $sut;
	}

	/**
	 * Data for bulk_and_quick_edit_stock_status_for_variable_product test.
	 *
	 * @return array
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
			'_stock_status'                => $change_stock_request_value,
			'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
		);

		$sut = $this->get_sut_with_request_data( $request_data );

		$sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			$this->assertEquals( $expected_new_stock_status, $child->get_stock_status() );
		}
	}

	/**
	 * Data for bulk_change_price test.
	 *
	 * @return array
	 */
	public function data_provider_bulk_change_price() {
		$dataset = array();

		// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		// $type_of_price, $initial_price, $type_of_change, $change_amount, $expected_new_price
		foreach ( array( 'regular', 'sale' ) as $type ) {
			array_push( $dataset, array( $type, '10.33', '1', '5.339', 5.34 ) );
			array_push( $dataset, array( $type, '10.33', '2', '5.3333', 15.66 ) );
			array_push( $dataset, array( $type, '10.33', '2', '15.555%', 11.94 ) );
			array_push( $dataset, array( $type, '10.33', '3', '5.339', 4.99 ) );
			array_push( $dataset, array( $type, '10.33', '3', '15.555%', 8.72 ) );
		}

		array_push( $dataset, array( 'regular', '10.33', '4', '5.339', 10.33 ) );
		array_push( $dataset, array( 'regular', '10.33', '4', '15.555%', 10.33 ) );

		array_push( $dataset, array( 'sale', '10.33', '4', '5.339', 97.96 ) );
		array_push( $dataset, array( 'sale', '10.33', '4', '15.555%', 87.23 ) );

		return $dataset;
	}

	/**
	 * @test
	 * @testdox Prices should change appropriately when a price change is requested via bulk edit.
	 * @dataProvider data_provider_bulk_change_price
	 *
	 * @param string $type_of_price 'regular' or 'sale'.
	 * @param string $initial_price Initial value for the price.
	 * @param string $type_of_change 1=absolute, 2=increase, 3=decrease, 4=regular minus (for sale price only).
	 * @param string $change_amount The amount to change, ending with '%' if it's a percent.
	 * @param string $expected_new_price Expected value of the product price after the save operation.
	 */
	public function bulk_change_price( $type_of_price, $initial_price, $type_of_change, $change_amount, $expected_new_price ) {
		if ( 'regular' === $type_of_price ) {
			$props = array( 'regular_price' => $initial_price );
		} else {
			$props = array(
				'regular_price' => $initial_price * 10,
				'sale_price'    => $initial_price,
			);
		}

		$product = WC_Helper_Product::create_simple_product( true, $props );

		$this->login_as_administrator();

		$request_data = array(
			'woocommerce_bulk_edit'         => '1',
			'woocommerce_quick_edit_nonce'  => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
			"change_{$type_of_price}_price" => $type_of_change,
			"_{$type_of_price}_price"       => $change_amount,
		);

		$sut = $this->get_sut_with_request_data( $request_data );

		$sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		$product = wc_get_product( $product->get_id() );
		$actual  = $product->{"get_{$type_of_price}_price"}();
		$this->assertEquals( $expected_new_price, $actual );
	}
}
