<?php declare( strict_types = 1 );

/**
 * Class WC_Product_Grouped_Data_Store_CPT_Test
 */
final class WC_Product_Grouped_Data_Store_CPT_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox sync_price updates parent price meta from children and re-populates it when absent.
	 */
	public function test_sync_price_updates_and_repopulates_price_meta(): void {
		$child1 = new WC_Product_Simple();
		$child1->set_regular_price( '3.00' );
		$child1->set_price( '3.00' );
		$child1->save();

		$child2 = new WC_Product_Simple();
		$child2->set_regular_price( '7.00' );
		$child2->set_price( '7.00' );
		$child2->save();

		$grouped = new WC_Product_Grouped();
		$grouped->set_children( array( $child1->get_id(), $child2->get_id() ) );
		$grouped->save();
		$grouped_id = $grouped->get_id();

		$this->assertSame( array( '3.00', '7.00' ), get_post_meta( $grouped_id, '_price', false ) );

		$child2->set_price( '9.00' );
		$child2->set_regular_price( '9.00' );
		$child2->save();

		( new WC_Product_Grouped_Data_Store_CPT() )->sync_price( $grouped );

		$this->assertSame( array( '3.00', '9.00' ), get_post_meta( $grouped_id, '_price', false ) );

		delete_post_meta( $grouped_id, '_price' );
		$this->assertFalse( metadata_exists( 'post', $grouped_id, '_price' ) );

		( new WC_Product_Grouped_Data_Store_CPT() )->sync_price( $grouped );

		$this->assertSame( array( '3.00', '9.00' ), get_post_meta( $grouped_id, '_price', false ) );

		$child1->delete();
		$child2->delete();
		$grouped->delete();
	}
}
