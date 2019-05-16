<?php
/**
 * Attribute function tests.
 *
 * @package WooCommerce\Tests\Attributes
 * @since 3.2.0
 */

/**
 * WC_Tests_Attributes_Functions class.
 */
class WC_Tests_Attributes_Functions extends WC_Unit_Test_Case {

	/**
	 * Tests wc_get_attribute().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_attribute() {
		$args = array(
			'name'         => 'Brand',
			'type'         => 'select',
			'order_by'     => 'name',
			'has_archives' => true,
		);

		$id = wc_create_attribute( $args );

		$attribute = (array) wc_get_attribute( $id );
		$expected  = array(
			'id'           => $id,
			'name'         => 'Brand',
			'slug'         => 'pa_brand',
			'type'         => 'select',
			'order_by'     => 'name',
			'has_archives' => true,
		);

		wc_delete_attribute( $id );

		$this->assertEquals( $expected, $attribute );
	}

	/**
	 * Tests wc_create_attribute().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_create_attribute() {
		// Test success.
		$id = wc_create_attribute( array( 'name' => 'Brand' ) );
		$this->assertInternalType( 'int', $id );

		// Test failures.
		$err = wc_create_attribute( array() );
		$this->assertEquals( 'missing_attribute_name', $err->get_error_code() );

		$err = wc_create_attribute( array( 'name' => 'This is a big name for a product attribute!' ) );
		$this->assertEquals( 'invalid_product_attribute_slug_too_long', $err->get_error_code() );

		$err = wc_create_attribute( array( 'name' => 'Cat' ) );
		$this->assertEquals( 'invalid_product_attribute_slug_reserved_name', $err->get_error_code() );

		register_taxonomy( 'pa_brand', array( 'product' ), array( 'labels' => array( 'name' => 'Brand' ) ) );
		$err = wc_create_attribute( array( 'name' => 'Brand' ) );
		$this->assertEquals( 'invalid_product_attribute_slug_already_exists', $err->get_error_code() );
		unregister_taxonomy( 'pa_brand' );

		wc_delete_attribute( $id );
	}

	/**
	 * Test that updating a global attribute will not modify local attribute data.
	 *
	 * @since 3.4.6
	 */
	public function test_wc_create_attribute_serialized_data() {
		global $wpdb;

		$global_attribute_data = WC_Helper_Product::create_attribute( 'test', array( 'Chicken', 'Nuggets' ) );

		$local_attribute = new WC_Product_Attribute();
		$local_attribute->set_id( 0 );
		$local_attribute->set_name( 'Test Local Attribute' );
		$local_attribute->set_options( array( 'Fish', 'Fingers', 's:7:"pa_test' ) );
		$local_attribute->set_position( 0 );
		$local_attribute->set_visible( true );
		$local_attribute->set_variation( false );

		$global_attribute = new WC_Product_Attribute();
		$global_attribute->set_id( $global_attribute_data['attribute_id'] );
		$global_attribute->set_name( $global_attribute_data['attribute_taxonomy'] );
		$global_attribute->set_options( $global_attribute_data['term_ids'] );
		$global_attribute->set_position( 1 );
		$global_attribute->set_visible( true );
		$global_attribute->set_variation( false );

		$product = new WC_Product_Simple();
		$product->set_attributes(
			array(
				'test-local'  => $local_attribute,
				'test-global' => $global_attribute,
			)
		);
		$product->save();

		// Check everything looks good before updating the attribute.
		$meta_before_update         = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_product_attributes' AND post_id = %d", $product->get_id() ), ARRAY_A );
		$product_meta_before_update = @unserialize( $meta_before_update[0]['meta_value'] ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		$this->assertNotFalse( $product_meta_before_update, 'Meta should be an unserializable string' );

		$expected_local_attribute_data  = array(
			'name'         => 'Test Local Attribute',
			'value'        => 'Fish | Fingers | s:7:"pa_test',
			'position'     => 0,
			'is_visible'   => 1,
			'is_variation' => 0,
			'is_taxonomy'  => 0,
		);
		$expected_global_attribute_data = array(
			'name'         => 'pa_test',
			'value'        => '',
			'position'     => 1,
			'is_visible'   => 1,
			'is_variation' => 0,
			'is_taxonomy'  => 1,
		);
		$local_before                   = isset( $product_meta_before_update['Test Local Attribute'] ) ? $product_meta_before_update['Test Local Attribute'] : $product_meta_before_update['test-local-attribute'];
		$this->assertEquals( $expected_local_attribute_data, $local_before );
		$this->assertEquals( $expected_global_attribute_data, $product_meta_before_update['pa_test'] );

		// Update the global attribute.
		$updated_global_attribute_id = wc_create_attribute(
			array(
				'id'       => $global_attribute_data['attribute_id'],
				'name'     => 'Test Update',
				'old_slug' => 'test',
				'slug'     => 'testupdate',
			)
		);
		$this->assertEquals( $updated_global_attribute_id, $global_attribute_data['attribute_id'] );

		// Changes to the global attribute should update in the product without causing side-effects.
		$meta_after_update         = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_product_attributes' AND post_id = %d", $product->get_id() ), ARRAY_A );
		$product_meta_after_update = @unserialize( $meta_after_update[0]['meta_value'] ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		$this->assertNotFalse( $product_meta_after_update, 'Meta should be an unserializable string' );

		$expected_global_attribute_data = array(
			'name'         => 'pa_testupdate',
			'value'        => '',
			'position'     => 1,
			'is_visible'   => 1,
			'is_variation' => 0,
			'is_taxonomy'  => 1,
		);
		$this->assertEquals( $local_before, isset( $product_meta_after_update['Test Local Attribute'] ) ? $product_meta_after_update['Test Local Attribute'] : $product_meta_after_update['test-local-attribute'] );
		$this->assertEquals( $expected_global_attribute_data, $product_meta_after_update['pa_testupdate'] );
		$this->assertArrayNotHasKey( 'pa_test', $product_meta_after_update );
	}

	/**
	 * Tests wc_update_attribute().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_update_attribute() {
		$args = array(
			'name'         => 'Brand',
			'type'         => 'select',
			'order_by'     => 'name',
			'has_archives' => true,
		);

		$id = wc_create_attribute( $args );

		$updated = array(
			'id'           => $id,
			'name'         => 'Brand',
			'slug'         => 'pa_brand',
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => true,
		);

		wc_update_attribute( $id, $updated );

		$attribute = (array) wc_get_attribute( $id );

		wc_delete_attribute( $id );

		$this->assertEquals( $updated, $attribute );
	}

	/**
	 * Tests wc_delete_attribute().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_delete_attribute() {
		// Success.
		$id     = wc_create_attribute( array( 'name' => 'Brand' ) );
		$result = wc_delete_attribute( $id );
		$this->assertTrue( $result );

		// Failure.
		$result = wc_delete_attribute( 9999999 );
		$this->assertFalse( $result );
	}

	/**
	 * Test counts of attributes.
	 */
	public function test_count_attribute_terms() {
		$global_attribute_data = WC_Helper_Product::create_attribute( 'test', array( 'Chicken', 'Nuggets' ) );
		$count                 = wp_count_terms(
			$global_attribute_data['attribute_taxonomy'],
			array(
				'hide_empty' => false,
			)
		);

		$this->assertEquals( 2, $count );
	}
}
