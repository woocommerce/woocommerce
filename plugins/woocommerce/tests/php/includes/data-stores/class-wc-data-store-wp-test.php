<?php declare( strict_types = 1 );

/**
 * Class WC_Data_Store_WP_Test
 */
final class WC_Data_Store_WP_Test extends WC_Unit_Test_Case {

	/**
	 * @return array
	 */
	public function provider_update_or_delete_post_meta(): array {
		return array(
			'empty string — key absent'  => array( static fn() => null, '', false, '' ),
			'empty string — key present' => array( static fn( $id ) => add_post_meta( $id, '_sku', 'old' ), '', true, '' ),
			'empty array — key absent'   => array( static fn() => null, array(), false, '' ),
			'empty array — key present'  => array( static fn( $id ) => add_post_meta( $id, '_sku', 'old' ), array(), true, '' ),
			'non-empty — key absent'     => array( static fn() => null, 'new-sku', true, 'new-sku' ),
			'non-empty — key present'    => array( static fn( $id ) => add_post_meta( $id, '_sku', 'old' ), 'new-sku', true, 'new-sku' ),
		);
	}

	/**
	 * @dataProvider provider_update_or_delete_post_meta
	 * @testdox update_or_delete_post_meta writes, deletes, or skips based on value and key presence.
	 *
	 * @param Closure $setup           Receives the product ID; sets up pre-existing meta if needed.
	 * @param mixed   $meta_value      Value passed to update_or_delete_post_meta.
	 * @param bool    $expected_result Expected boolean return value.
	 * @param mixed   $expected_stored Expected get_post_meta result after the call.
	 */
	public function test_update_or_delete_post_meta( Closure $setup, $meta_value, bool $expected_result, $expected_stored ): void {
		$store = new class() extends WC_Product_Data_Store_CPT {
			public function update_or_delete_post_meta( $product, $meta_key, $meta_value ): bool { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found, Squiz.Commenting.FunctionComment.Missing
				return parent::update_or_delete_post_meta( $product, $meta_key, $meta_value );
			}
		};

		$product = new WC_Product();
		$product->save();
		$product_id = $product->get_id();
		$setup( $product_id );

		$result = $store->update_or_delete_post_meta( $product, '_sku', $meta_value );

		$this->assertSame( $expected_result, $result );
		$this->assertSame( $expected_stored, get_post_meta( $product_id, '_sku', true ) );

		$product->delete();
	}
}
