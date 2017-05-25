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
		add_filter( 'deprecated_function_trigger_error', '__return_false' );
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
		foreach( $item->get_meta_data() as $metadata ) {
			$expected[ $metadata->id ] = array(
				'key' => $metadata->key,
				'label' => wc_attribute_label( $metadata->key, null ),
				'value' => $metadata->value );
		}

		$result = $meta->get_formatted();

		$this->assertEquals( 3, count( $result ) );
		$this->assertEquals( $expected, $result );

		// Clean up.
		$item->delete( true );
	}
}
