<?php

/**
 * Class Functions.
 * @package WooCommerce\Tests\Attributes
 * @since 3.2.0
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
}
