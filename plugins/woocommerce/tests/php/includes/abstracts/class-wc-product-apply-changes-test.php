<?php
declare( strict_types = 1 );

/**
 * Tests for applying product property changes.
 */
class WC_Product_Apply_Changes_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Shortened and cleared linked-product lists should remain exact after the first and second saves.
	 */
	public function test_linked_product_lists_are_replaced_across_repeated_saves(): void {
		$product = new WC_Product_Simple();
		$product->set_name( 'Linked product change test' );
		$product->set_upsell_ids( array( 101, 202 ) );
		$product->set_cross_sell_ids( array( 303 ) );
		$product->save();

		$product = new WC_Product_Simple( $product->get_id() );
		$product->set_upsell_ids( array( 101 ) );
		$product->set_cross_sell_ids( array() );
		$product->save();

		$after_first_save = array(
			'upsell_ids'     => $product->get_upsell_ids(),
			'cross_sell_ids' => $product->get_cross_sell_ids(),
		);

		$product->save();
		$fresh_product     = new WC_Product_Simple( $product->get_id() );
		$after_second_save = array(
			'upsell_ids'     => $fresh_product->get_upsell_ids(),
			'cross_sell_ids' => $fresh_product->get_cross_sell_ids(),
		);

		$this->assertSame(
			array(
				'after_first_save'  => array(
					'upsell_ids'     => array( 101 ),
					'cross_sell_ids' => array(),
				),
				'after_second_save' => array(
					'upsell_ids'     => array( 101 ),
					'cross_sell_ids' => array(),
				),
			),
			array(
				'after_first_save'  => $after_first_save,
				'after_second_save' => $after_second_save,
			),
			'Linked-product lists should not retain or restore removed IDs.'
		);
	}

	/**
	 * @testdox A complete default-attributes change should remove omitted keys.
	 */
	public function test_default_attributes_are_replaced_as_a_complete_map(): void {
		$product = new WC_Product_Simple();
		$product->set_default_attributes(
			array(
				'color' => 'blue',
				'size'  => 'large',
			)
		);
		$product->apply_changes();

		$product->set_default_attributes( array( 'color' => 'red' ) );
		$product->apply_changes();

		$this->assertSame(
			array( 'color' => 'red' ),
			$product->get_default_attributes( 'edit' ),
			'Default attributes omitted from a complete replacement should be removed.'
		);
	}

	/**
	 * @testdox Shortening a grouped product's children should remove omitted IDs.
	 */
	public function test_grouped_product_children_are_replaced_as_a_complete_list(): void {
		$product = new WC_Product_Grouped();
		$product->set_children( array( 101, 202 ) );
		$product->apply_changes();

		$product->set_children( array( 101 ) );
		$product->apply_changes();

		$this->assertSame(
			array( 101 ),
			$product->get_children( 'edit' ),
			'Grouped product children omitted from a complete replacement should be removed.'
		);
	}

	/**
	 * @testdox An external product subclass should retain untouched entries from a partial nested custom-map change.
	 */
	public function test_external_product_subclass_preserves_partial_nested_map_entries(): void {
		$product = new class() extends WC_Product {
			/**
			 * External product properties.
			 *
			 * @var array
			 */
			protected $extra_data = array(
				'custom_map' => array(
					'changed'   => 'before',
					'preserved' => 'keep',
				),
			);

			/**
			 * Set one nested custom-map entry as a partial change.
			 *
			 * @param string $key   Entry key.
			 * @param string $value Entry value.
			 */
			public function set_custom_map_entry( string $key, string $value ): void {
				$this->changes['custom_map'][ $key ] = $value;
			}

			/**
			 * Get the custom map.
			 *
			 * @return array
			 */
			public function get_custom_map(): array {
				return $this->get_prop( 'custom_map', 'edit' );
			}
		};

		$product->set_custom_map_entry( 'changed', 'after' );
		$product->apply_changes();

		$this->assertSame(
			array(
				'changed'   => 'after',
				'preserved' => 'keep',
			),
			$product->get_custom_map(),
			'Unknown nested product properties should retain recursive change application.'
		);
	}
}
