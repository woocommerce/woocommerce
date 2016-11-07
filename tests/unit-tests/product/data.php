<?php
/**
 * Data Functions.
 * @package WooCommerce\Tests\Product
 * @since 2.7.0
 */
class WC_Tests_Product_Data extends WC_Unit_Test_Case {

	/**
	 * Test product setters and getters
	 * @todo needs tests for attributes
	 * @since 2.7.0
	 */
	public function test_product_getters_and_setters() {
		$getters_and_setters = array(
			'name'               => 'Test',
			'slug'               => 'test',
			'status'             => 'publish',
			'catalog_visibility' => 'search',
			'featured'           => false,
			'description'        => 'Hello world',
			'short_description'  => 'hello',
			'sku'                => 'TEST SKU',
			'regular_price'      => 15.00,
			'sale_price'         => 10.00,
			'date_on_sale_from'  => '1475798400',
			'date_on_sale_to'    => '1477267200',
			'total_sales'        => 20,
			'tax_status'         => 'none',
			'tax_class'          => '',
			'manage_stock'       => true,
			'stock_quantity'     => 10,
			'stock_status'       => 'instock',
			'backorders'         => 'notify',
			'sold_individually'  => false,
			'weight'             => 100,
			'length'             => 10,
			'width'              => 10,
			'height'             => 10,
			'upsell_ids'         => array( 2, 3 ),
			'cross_sell_ids'     => array( 4, 5 ),
			'parent_id'          => 0,
			'reviews_allowed'    => true,
			'default_attributes' => array(),
			'purchase_note'      => 'A note',
			'menu_order'         => 2,
			'gallery_image_ids'  => array(),
			'download_type'      => 'standard',
			'download_expiry'    => -1,
			'download_limit'     => 5,
			'image_id'           => 2,
		 );
		$product = new WC_Product;
		foreach ( $getters_and_setters as $function => $value ) {
			$product->{"set_{$function}"}( $value );
		}
		$product->create();
		$product = new WC_Product_Simple( $product->get_id() );
		foreach ( $getters_and_setters as $function => $value ) {
			$this->assertEquals( $value, $product->{"get_{$function}"}(), $function );
		}
	 }

	/**
	 * Test product term setters and getters
	 * @since 2.7.0
	 */
	public function test_product_term_getters_and_setters() {
		$test_cat_1 = wp_insert_term( 'Testing 1', 'product_cat' );
		$test_cat_2 = wp_insert_term( 'Testing 2', 'product_cat' );

		$test_tag_1 = wp_insert_term( 'Tag 1', 'product_tag' );
		$test_tag_2 = wp_insert_term( 'Tag 2', 'product_tag' );

		$getters_and_setters = array(
			'tag_ids'      => array( 'Tag 1', 'Tag 2' ),
			'category_ids' => array( $test_cat_1['term_id'], $test_cat_2['term_id'] ),
		);
		$product = new WC_Product;
		foreach ( $getters_and_setters as $function => $value ) {
			$product->{"set_{$function}"}( $value );
		}
		$product->create();
		$product = new WC_Product_Simple( $product->get_id() );

		$this->assertEquals( array( $test_cat_1['term_id'], $test_cat_2['term_id'] ), $product->get_category_ids() );
		$this->assertEquals( array( $test_tag_1['term_id'], $test_tag_2['term_id'] ), $product->get_tag_ids() );
	}

	/**
	 * Test grouped product setters and getters
	 *
	 * @since 2.7.0
	 */
	 public function test_grouped_product_getters_and_setters() {
		$getters_and_setters = array(
			'children' => array( 1, 2, 3 ),
		);
		$product = new WC_Product_Grouped;
		foreach ( $getters_and_setters as $function => $value ) {
			$product->{"set_{$function}"}( $value );
			$this->assertEquals( $value, $product->{"get_{$function}"}(), $function );
		}
	 }

	/**
	 * Test external product setters and getters
	 *
	 * @since 2.7.0
	 */
	 public function test_external_product_getters_and_setters() {
		 $time = time();
		 $getters_and_setters = array(
			 'button_text' => 'Test Button Text',
			 'product_url' => 'http://wordpress.org',
		 );
		 $product = new WC_Product_External;
		  foreach ( $getters_and_setters as $function => $value ) {
			 $product->{"set_{$function}"}( $value );
			 $this->assertEquals( $value, $product->{"get_{$function}"}(), $function );
		 }
	 }

	 /**
	 * Test reading a variable product.
	 *
	 * @since 2.7.0
	 */
	public function test_variable_read() {
		$product = WC_Helper_Product::create_variation_product();
		$children = $product->get_children();

		// Test sale prices too
		update_post_meta( $children[0], '_price', '8' );
		update_post_meta( $children[0], '_sale_price', '8' );
		delete_transient( 'wc_var_prices_' . $product->get_id() );

		$product = new WC_Product_Variable( $product->get_id() );

		$this->assertEquals( 2, count( $product->get_children() ) );

		$expected_prices['price'][ $children[0] ] = 8.00;
		$expected_prices['price'][ $children[1] ] = 15.00;
		$expected_prices['regular_price'][ $children[0] ] = 10.00;
		$expected_prices['regular_price'][ $children[1] ] = 15.00;
		$expected_prices['sale_price'][ $children[0] ] = 8.00;
		$expected_prices['sale_price'][ $children[1] ] = 15.00;

		$this->assertEquals( $expected_prices, $product->get_variation_prices() );

		$expected_attributes = array( 'pa_size' => array( 'small', 'large' ) );
		$this->assertEquals( $expected_attributes, $product->get_variation_attributes() );
	}

	/**
	 * Test creating a new variable product.
	 *
	 * @todo - this test can be improved. Once WC_Product_Variation is updated
	 * with CRUD as well, this test should add a variation to a variable product
	 * that way, and test things like get_children and attributes.
	 *
	 * @since 2.7.0
	 */
	function test_variable_create_and_update() {
		$product = new WC_Product_Variable;
		$product->set_name( 'Variable Product' );
		$product->set_attributes( array( array(
			'name'         => 'pa_size',
			'value'        => 'small | large',
			'position'     => '1',
			'is_visible'   => 0,
			'is_variation' => 1,
			'is_taxonomy'  => 0,
		) ) );
		$product->create();

		$this->assertEquals( 'Variable Product', $product->get_name() );

		// Create a variation
		$variation_id = wp_insert_post( array(
			'post_title'  => 'Variation #1 of Dummy Variable CRUD Product',
			'post_type'   => 'product_variation',
			'post_parent' => $product->get_id(),
			'post_status' => 'publish',
		) );

		update_post_meta( $variation_id, '_price', '10' );
		update_post_meta( $variation_id, '_regular_price', '10' );
		update_post_meta( $variation_id, '_sku', 'CRUD DUMMY SKU VARIABLE SMALL' );
		update_post_meta( $variation_id, '_manage_stock', 'no' );
		update_post_meta( $variation_id, '_downloadable', 'no' );
		update_post_meta( $variation_id, '_virtual', 'no' );
		update_post_meta( $variation_id, '_stock_status', 'instock' );
		update_post_meta( $variation_id, 'attribute_pa_size', 'small' );

		delete_transient( 'wc_product_children_' . $product->get_id() );
		delete_transient( 'wc_var_prices_' . $product->get_id() );

		$product = new WC_Product_Variable( $product->get_id() );
		$children = $product->get_children();
		$this->assertEquals( $variation_id, $children[0] );

		$expected_attributes = array( 'pa_size' => array( 'small' ) );
		$this->assertEquals( $expected_attributes, $product->get_variation_attributes() );

		$product->set_name( 'Renamed Variable Product' );
		$product->update();

		$this->assertEquals( 'Renamed Variable Product', $product->get_name() );
	 }

}
