<?php
/**
 * Unit tests for the product data methods.
 *
 * @package WooCommerce\Tests\Product
 */

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * Data Functions.
 *
 * @package WooCommerce\Tests\Product
 * @since 3.0.0
 */
class WC_Tests_Product_Data extends WC_Unit_Test_Case {

	use ArraySubsetAsserts;

	/**
	 * Test product setters and getters
	 *
	 * @since 3.0.0
	 */
	public function test_product_getters_and_setters() {
		global $wpdb;

		$attributes = array();
		$attribute  = new WC_Product_Attribute();
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
		);

		$product = new WC_Product();
		foreach ( $getters_and_setters as $function => $value ) {
			$product->{"set_{$function}"}( $value );
		}
		$product->set_attributes( $attributes );
		$product->set_date_on_sale_from( '1475798400' );
		$product->set_date_on_sale_to( '1477267200' );
		$product->save();
		$product = new WC_Product_Simple( $product->get_id() );
		foreach ( $getters_and_setters as $function => $value ) {
			$this->assertEquals( $value, $product->{"get_{$function}"}(), $function );
		}
		$this->assertCount( 1, $product->get_attributes() );
		$this->assertArraySubset(
			array(
				'name'      => 'Test Attribute',
				'options'   => array( 'Fish', 'Fingers' ),
				'position'  => 0,
				'visible'   => true,
				'variation' => false,
			),
			current( $product->get_attributes() )->get_data()
		);
		$this->assertEquals( $product->get_date_on_sale_from()->getTimestamp(), 1475798400 );
		$this->assertEquals( $product->get_date_on_sale_to()->getTimestamp(), 1477267200 );

		$image_url = media_sideload_image( 'http://cldup.com/Dr1Bczxq4q.png', $product->get_id(), '', 'src' );

		$this->assertNotWPError( $image_url );

		$image_id = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid = %s", $image_url ) );
		$product->set_image_id( $image_id[0] );
		$product->save();
		$this->assertEquals( $image_id[0], $product->get_image_id() );
		wp_delete_attachment( $image_id[0], true ); // Remove attachment.
	}

	/**
	 * Test the onbackorder stock status.
	 *
	 * @since 3.3.0
	 */
	public function test_product_backorder_stock_status() {
		$product = new WC_Product();
		$product->set_stock_status( 'onbackorder' );
		$this->assertEquals( 'onbackorder', $product->get_stock_status() );

		$product->save();
		$product = new WC_Product( $product->get_id() );
		$this->assertEquals( 'onbackorder', $product->get_stock_status() );
	}

	/**
	 * Test the automatic stock status transitions done on product save.
	 *
	 * @since 3.3.0
	 */
	public function test_product_auto_stock_status() {
		$product = new WC_Product();

		// Product should not have quantity and stock status should not be updated automatically if not managing stock.
		$product->set_manage_stock( false );
		$product->set_stock_quantity( 5 );
		$product->set_stock_status( 'instock' );
		$product->save();
		$this->assertEquals( '', $product->get_stock_quantity() );
		$this->assertEquals( 'instock', $product->get_stock_status() );
		$product->set_stock_status( 'outofstock' );
		$product->save();
		$this->assertEquals( 'outofstock', $product->get_stock_status() );

		$product->set_manage_stock( true );

		// Product should be out of stock if managing orders, no backorders allowed, and quantity too low.
		$product->set_stock_quantity( 0 );
		$product->set_stock_status( 'instock' );
		$product->set_backorders( 'no' );
		$product->save();
		$this->assertEquals( 0, $product->get_stock_quantity() );
		$this->assertEquals( 'outofstock', $product->get_stock_status() );

		// Product should be on backorder if managing orders, backorders allowed, and quantity too low.
		$product->set_stock_quantity( 0 );
		$product->set_stock_status( 'instock' );
		$product->set_backorders( 'yes' );
		$product->save();
		$this->assertEquals( 0, $product->get_stock_quantity() );
		$this->assertEquals( 'onbackorder', $product->get_stock_status() );

		// Product should go to in stock if backordered and inventory increases.
		$product->set_stock_quantity( 5 );
		$product->set_stock_status( 'onbackorder' );
		$product->set_backorders( 'notify' );
		$product->save();
		$this->assertEquals( 5, $product->get_stock_quantity() );
		$this->assertEquals( 'instock', $product->get_stock_status() );

		// Product should go to in stock if out of stock and inventory increases.
		$product->set_stock_quantity( 3 );
		$product->set_stock_status( 'outofstock' );
		$product->set_backorders( 'no' );
		$product->save();
		$this->assertEquals( 3, $product->get_stock_quantity() );
		$this->assertEquals( 'instock', $product->get_stock_status() );
	}

	/**
	 * Test product term setters and getters
	 *
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

		$product = new WC_Product_Simple();

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

		$product = new WC_Product_Grouped();

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
		$getters_and_setters = array(
			'button_text' => 'Test Button Text',
			'product_url' => 'https://wordpress.org',
		);

		$product = new WC_Product_External();

		foreach ( $getters_and_setters as $function => $value ) {
			$product->{"set_{$function}"}( $value );
			$this->assertEquals( $value, $product->{"get_{$function}"}(), $function );
		}
	}

	/**
	 * A test ensuring get_price_html works independently for issue #18037.
	 */
	public function test_multiple_get_price_html_calls() {
		// Set up 3 products with differing prices.
		$product1 = new WC_Product_Simple();
		$product2 = new WC_Product_Simple();
		$product3 = new WC_Product_Simple();

		$product1->set_regular_price( '10.00' );
		$product1->set_sale_price( '7.00' );
		$product2->set_regular_price( '20.00' );
		$product2->set_sale_price( '16.00' );
		$product3->set_regular_price( '50.00' );

		$product1_id = $product1->save();
		$product2_id = $product2->save();
		$product3_id = $product3->save();

		$product = wc_get_product( $product1_id );
		$this->assertEquals( $product1_id, $product->get_id() );
		$this->assertEquals( '<del aria-hidden="true"><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&#36;</span>10.00</bdi></span></del> <ins><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&#36;</span>7.00</bdi></span></ins>', $product->get_price_html() );

		$product = wc_get_product( $product2_id );
		$this->assertEquals( $product2_id, $product->get_id() );
		$this->assertEquals( '<del aria-hidden="true"><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&#36;</span>20.00</bdi></span></del> <ins><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&#36;</span>16.00</bdi></span></ins>', $product->get_price_html() );

		$product = wc_get_product( $product3_id );
		$this->assertEquals( $product3_id, $product->get_id() );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&#36;</span>50.00</bdi></span>', $product->get_price_html() );
	}

	/**
	 * Test: test_get_image_should_return_product_image.
	 */
	public function test_get_image_should_return_product_image() {
		$product = new WC_Product();
		$image   = $this->set_product_image( $product );
		$needle  = 'width="186" height="144" src="' . $image['url'] . '" class="%s"';

		$this->assertStringContainsString(
			sprintf( $needle, 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail' ),
			$product->get_image()
		);

		$this->assertStringContainsString(
			sprintf( $needle, 'attachment-single size-single' ),
			$product->get_image( 'single' )
		);

		$this->assertStringContainsString(
			sprintf( $needle, 'custom-class' ),
			$product->get_image( 'single', array( 'class' => 'custom-class' ) )
		);

		wp_delete_attachment( $image['id'], true ); // Remove attachment.
	}

	/**
	 * Test: test_get_image_should_return_parent_product_image.
	 */
	public function test_get_image_should_return_parent_product_image() {
		$variable_product = WC_Helper_Product::create_variation_product();
		$variations       = $variable_product->get_children();
		$variation_1      = wc_get_product( $variations[0] );
		$image            = $this->set_product_image( $variable_product );
		$needle           = 'width="186" height="144" src="' . $image['url'] . '" class="%s"';

		$this->assertStringContainsString(
			sprintf( $needle, 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail' ),
			$variation_1->get_image()
		);

		$this->assertStringContainsString(
			sprintf( $needle, 'attachment-single size-single' ),
			$variation_1->get_image( 'single' )
		);

		$this->assertStringContainsString(
			sprintf( $needle, 'custom-class' ),
			$variation_1->get_image( 'single', array( 'class' => 'custom-class' ) )
		);

		wp_delete_attachment( $image['id'], true ); // Remove attachment.
	}

	/**
	 * Test: test_get_image_should_return_place_holder_image.
	 */
	public function test_get_image_should_return_place_holder_image() {
		$product = new WC_Product();

		$this->assertStringContainsString( wc_placeholder_img_src(), $product->get_image() );

		// Test custom class attribute is honoured.
		$image = $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'custom-class' ) );
		$this->assertStringContainsString( 'class="custom-class"', $image );
	}

	/**
	 * Test: test_get_image_should_return_empty_string.
	 */
	public function test_get_image_should_return_empty_string() {
		$product = new WC_Product();
		$this->assertEquals( '', $product->get_image( 'woocommerce_thumbnail', array(), false ) );
	}

	/**
	 * Helper method to define a image for a product and return its URL.
	 *
	 * @param WC_Product $product Product object.
	 * @return array Image ID and URL.
	 */
	protected function set_product_image( $product ) {
		global $wpdb;

		// TODO: find a way to set the product image without performing a HTTP request to make the tests faster.
		$image_url = media_sideload_image( 'http://cldup.com/Dr1Bczxq4q.png', $product->get_id(), '', 'src' );

		$this->assertNotWPError( $image_url );

		$image_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid = %s", $image_url ) );
		$product->set_image_id( $image_id );
		$product->save();

		return array(
			'id'  => $image_id,
			'url' => $image_url,
		);
	}
}
