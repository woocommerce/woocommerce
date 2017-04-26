<?php
/**
 * Data Functions.
 * @package WooCommerce\Tests\Product
 * @since 3.0.0
 */
class WC_Tests_Product_Data extends WC_Unit_Test_Case {

	/**
	 * Test product setters and getters
	 * @since 3.0.0
	 */
	public function test_product_getters_and_setters() {
		global $wpdb;

		$attributes = array();
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'Test Attribute' );
		$attribute->set_options( array( 'Fish', 'Fingers' ) );
		$attribute->set_position( 0 );
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$attributes['test-attribute'] = $attribute;

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
			'download_expiry'    => -1,
			'download_limit'     => 5,
			'attributes'         => $attributes,
		 );
		$product = new WC_Product();
		foreach ( $getters_and_setters as $function => $value ) {
			$product->{"set_{$function}"}( $value );
		}
		$product->set_date_on_sale_from( '1475798400' );
		$product->set_date_on_sale_to( '1477267200' );
		$product->save();
		$product = new WC_Product_Simple( $product->get_id() );
		foreach ( $getters_and_setters as $function => $value ) {
			$this->assertEquals( $value, $product->{"get_{$function}"}(), $function );
		}
		$this->assertEquals( $product->get_date_on_sale_from()->getTimestamp(), 1475798400 );
		$this->assertEquals( $product->get_date_on_sale_to()->getTimestamp(), 1477267200 );

		$image_url = media_sideload_image( "https://cldup.com/Dr1Bczxq4q.png", $product->get_id(), '', 'src' );
		$image_id  = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url ) );
		$product->set_image_id( $image_id[0] );
		$product->save();
		$this->assertEquals( $image_id[0], $product->get_image_id() );
	 }

	/**
	 * Test product term setters and getters
	 * @since 3.0.0
	 */
	public function test_product_term_getters_and_setters() {
		$test_cat_1 = wp_insert_term( 'Testing 1', 'product_cat' );
		$test_cat_2 = wp_insert_term( 'Testing 2', 'product_cat' );

		$test_tag_1 = wp_insert_term( 'Tag 1', 'product_tag' );
		$test_tag_2 = wp_insert_term( 'Tag 2', 'product_tag' );

		$getters_and_setters = array(
			'tag_ids'      => array( $test_tag_1['term_id'], $test_tag_2['term_id'] ),
			'category_ids' => array( $test_cat_1['term_id'], $test_cat_2['term_id'] ),
		);
		$product = new WC_Product_Simple;
		foreach ( $getters_and_setters as $function => $value ) {
			$product->{"set_{$function}"}( $value );
		}
		$product->save();

		$this->assertEquals( array( $test_cat_1['term_id'], $test_cat_2['term_id'] ), $product->get_category_ids() );
		$this->assertEquals( array( $test_tag_1['term_id'], $test_tag_2['term_id'] ), $product->get_tag_ids() );
	}

	/**
	 * Test grouped product setters and getters
	 *
	 * @since 3.0.0
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
	 * @since 3.0.0
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

}
