<?php

/**
 * Order Item Meta Tests.
 * @package WooCommerce\Tests\Order_Items
 * @since 3.0.8
 */
class WC_Tests_Order_Item_Meta extends WC_Unit_Test_Case {

	/**
	 * Suppress deprecation notice from WC_Order_Item_Meta constructor.
	 */
	public function setUp() {
		parent::setUp();

		wp_insert_term( 'Testing Categories', 'category', array( 'slug' => 'testing' ) );
		$this->setExpectedDeprecated( 'WC_Order_Item_Meta::__construct' );
	}

	/**
	 * Test retrieving meta values where some have the same key.
	 */
	public function test_multiple_meta_values() {
		$item = new WC_Order_Item_Fee();
		$item->add_meta_data( 'testkey', '1' );
		$item->add_meta_data( 'testkey', '2' );
		$item->add_meta_data( 'otherkey', 'val' );
		$item->save();

		$meta = new WC_Order_Item_Meta( $item );

		$expected = array();
		foreach ( $item->get_meta_data() as $metadata ) {
			$expected[ $metadata->id ] = array(
				'key'   => $metadata->key,
				'label' => wc_attribute_label( $metadata->key, null ),
				'value' => $metadata->value,
			);
		}

		$result = $meta->get_formatted();

		$this->assertEquals( 3, count( $result ) );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test the get_formatted method of WC_Order_Item_Meta.
	 *
	 * @since 3.2.0
	 */
	public function test_get_formatted() {
		$item = new WC_Order_Item_Fee();
		$item->add_meta_data( 'regularkey', '1' );
		$item->add_meta_data( 'category', 'testing' );
		$item->add_meta_data( '_hiddenkey', '3' );
		$item->save();

		$meta = new WC_Order_Item_Meta( $item );

		$expected = array(
			'regularkey' => '1',
			'category'   => 'Testing Categories',
		);
		$actual   = wp_list_pluck( $meta->get_formatted(), 'value', 'key' );
		$this->assertEquals( $expected, $actual );
	}


	/**
	 * Test the display method of WC_Order_Item_Meta.
	 *
	 * @since 3.2.0
	 */
	public function test_display() {
		$item = new WC_Order_Item_Fee();
		$item->add_meta_data( 'regularkey', '1' );
		$item->add_meta_data( 'category', 'testing' );
		$item->add_meta_data( '_hiddenkey', '3' );
		$item->save();

		$meta = new WC_Order_Item_Meta( $item );

		$expected = "regularkey: 1, \ncategory: Testing Categories";
		$flat     = $meta->display( true, true );
		$this->assertEquals( $expected, $flat );

		$not_flat = $meta->display( false, true );
		$this->assertContains( 'class="variation-regularkey">regularkey:', $not_flat );
		$this->assertContains( 'class="variation-regularkey"><p>1</p>', $not_flat );
		$this->assertContains( 'class="variation-category">category:', $not_flat );
		$this->assertContains( 'class="variation-category"><p>Testing Categories</p>', $not_flat );
	}
}
