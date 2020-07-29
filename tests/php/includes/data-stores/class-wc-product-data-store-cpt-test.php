<?php

/**
 * Class WC_Product_Data_Store_CPT_Test
 */
class WC_Product_Data_Store_CPT_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Variations should appear when searching for parent product's SKU.
	 */
	public function test_variation_searches_parent_sku() {
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Blue widget' );
		$parent->set_sku( 'blue-widget-1' );
		$parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_sku( '' );
		$variation->save();

		$data_store = WC_Data_Store::load( 'product' );

		// No variations should be found searching for just the parent.
		$results = $data_store->search_products( 'blue-widget-1', '', false, true );
		$this->assertContains( $parent->get_id(), $results );
		$this->assertNotContains( $variation->get_id(), $results );

		// Variation should be found when searching for it.
		$results = $data_store->search_products( 'blue-widget-1', '', true, true );
		$this->assertContains( $parent->get_id(), $results );
		$this->assertContains( $variation->get_id(), $results );

		$variation->set_sku( 'test-widget' );
		$variation->save();

		// Variations should be found when searching for their specific SKU.
		$results = $data_store->search_products( 'test-widget', '', true, true );
		$this->assertContains( $variation->get_id(), $results );
	}
}
