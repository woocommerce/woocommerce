<?php
/**
 * Unit tests for the post types admin class.
 *
 * @package WooCommerce\Tests\Admin
 */

use Automattic\WooCommerce\Enums\ProductStockStatus;

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
			array( 'quick_edit', '', ProductStockStatus::OUT_OF_STOCK ),
			array( 'quick_edit', ProductStockStatus::IN_STOCK, ProductStockStatus::IN_STOCK ),
			array( 'bulk_edit', '', ProductStockStatus::OUT_OF_STOCK ),
			array( 'bulk_edit', ProductStockStatus::IN_STOCK, ProductStockStatus::IN_STOCK ),
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
			$child->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
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

	/**
	 * Data for shipping class quick/bulk edit with non-ASCII (Asian / Cyrillic) slugs test.
	 *
	 * Term slugs in WordPress are stored URL-encoded when they contain non-ASCII characters
	 * (e.g. `冷凍` is stored as `%e5%86%b7%e5%87%8d`). The quick/bulk edit form posts the slug
	 * back unchanged, so the save handler must look the term up by that percent-encoded slug.
	 *
	 * @return array
	 */
	public function data_provider_shipping_class_non_ascii_slugs() {
		return array(
			'quick_edit_chinese'  => array( 'quick_edit', '冷凍' ),
			'bulk_edit_chinese'   => array( 'bulk_edit', '常溫' ),
			'quick_edit_cyrillic' => array( 'quick_edit', 'охлаждённый' ),
		);
	}

	/**
	 * @test
	 * @testdox Quick/bulk edit should save the shipping class even when its slug contains non-ASCII (Asian) characters.
	 * @dataProvider data_provider_shipping_class_non_ascii_slugs
	 *
	 * @param string $edit_type 'quick_edit' or 'bulk_edit'.
	 * @param string $term_name Name of the shipping class term (used to derive the non-ASCII slug).
	 */
	public function shipping_class_non_ascii_slug_is_saved( $edit_type, $term_name ) {
		// Create a shipping class with a non-ASCII name. WordPress will URL-encode the slug.
		$term = wp_insert_term( $term_name, 'product_shipping_class' );
		$this->assertIsArray( $term, 'Failed to create shipping class term.' );

		$term_data = get_term( $term['term_id'], 'product_shipping_class' );
		$slug      = $term_data->slug;

		// Sanity check: the slug should contain a percent-encoded octet for non-ASCII characters.
		$this->assertMatchesRegularExpression( '/%[a-f0-9]{2}/i', $slug, 'Expected slug to be percent-encoded.' );

		$product = WC_Helper_Product::create_simple_product( true );

		$this->login_as_administrator();

		$request_data = array(
			"woocommerce_{$edit_type}"     => '1',
			'_shipping_class'              => $slug,
			'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
		);

		$sut = $this->get_sut_with_request_data( $request_data );

		$sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		$updated = wc_get_product( $product->get_id() );
		$this->assertEquals(
			$term['term_id'],
			$updated->get_shipping_class_id(),
			'Shipping class with non-ASCII slug should be saved on the product.'
		);

		// Cleanup.
		wp_delete_term( $term['term_id'], 'product_shipping_class' );
	}

	/**
	 * @test
	 * @testdox Quick edit should clear the shipping class when '_no_shipping_class' is selected.
	 */
	public function quick_edit_clears_shipping_class_when_no_shipping_class_selected() {
		$term = wp_insert_term( 'Standard', 'product_shipping_class' );
		$this->assertIsArray( $term );

		$product = WC_Helper_Product::create_simple_product( true );
		$product->set_shipping_class_id( $term['term_id'] );
		$product->save();

		$this->login_as_administrator();

		$request_data = array(
			'woocommerce_quick_edit'       => '1',
			'_shipping_class'              => '_no_shipping_class',
			'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
		);

		$sut = $this->get_sut_with_request_data( $request_data );

		$sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		$updated = wc_get_product( $product->get_id() );
		$this->assertEquals( 0, $updated->get_shipping_class_id() );

		wp_delete_term( $term['term_id'], 'product_shipping_class' );
	}
}
