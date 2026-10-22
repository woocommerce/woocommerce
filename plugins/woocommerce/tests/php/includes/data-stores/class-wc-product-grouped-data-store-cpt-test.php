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

	/**
	 * @testdox An older grouped sale write cannot undo a completed child, membership, or product-type change.
	 * @testWith ["child"]
	 *           ["membership"]
	 *           ["type"]
	 * @param string $change Mutation completed while the earlier lookup write is pending.
	 */
	public function test_overlapping_sale_changes_converge( string $change ): void {
		global $wpdb;

		$first = new WC_Product_Simple();
		$first->set_regular_price( '37' );
		$first->set_sale_price( '19' );
		$first->save();
		$second = new WC_Product_Simple();
		$second->set_regular_price( '53' );
		$second->set_sale_price( '11' );
		$second->save();
		$group = new WC_Product_Grouped();
		$group->set_children( array( $first->get_id(), $second->get_id() ) );
		$group->save();

		$interleave = null;
		$interleave = static function ( $query ) use ( &$interleave, $wpdb, $group, $second, $change ) {
			if ( 0 !== strpos( $query, "UPDATE `{$wpdb->wc_product_meta_lookup}` SET `onsale` = 1" ) || false === strpos( $query, '`product_id` = ' . $group->get_id() ) ) {
				return $query;
			}
			remove_filter( 'query', $interleave );
			if ( 'child' === $change ) {
				$second->set_sale_price( '' );
				$second->save();
			} elseif ( 'membership' === $change ) {
				$group->set_children( array() );
				$group->save();
			} else {
				$replacement = new WC_Product_Simple( $group->get_id() );
				$replacement->set_regular_price( '71' );
				$replacement->save();
			}
			return $query;
		};
		add_filter( 'query', $interleave );
		try {
			$first->set_sale_price( '' );
			$first->save();
		} finally {
			remove_filter( 'query', $interleave );
		}
		if ( 'type' === $change ) {
			$repairs = as_get_scheduled_actions(
				array(
					'hook'   => 'woocommerce_refresh_grouped_product_sale',
					'args'   => array( 'product_id' => $group->get_id() ),
					'status' => ActionScheduler_Store::STATUS_PENDING,
				),
				'ids'
			);
			$this->assertNotEmpty( $repairs, 'A conversion repair must be delegated to the new product type owner.' );
			$this->assertContains( $group->get_id(), wc_get_product_ids_on_sale(), 'Warm the stale result before the queued repair runs.' );
			ActionScheduler::store()->fetch_action( $repairs[0] )->execute();
		}
		$this->assertNotContains( $group->get_id(), wc_get_product_ids_on_sale(), 'The late stale write must not restore a group after its last eligible child, membership, or grouped type disappears.' );
		$this->assertSame( '0', $wpdb->get_var( $wpdb->prepare( "SELECT onsale FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $group->get_id() ) ) );
	}

	/**
	 * @testdox Deleting more variations does not repeatedly reconcile their remaining siblings' groups.
	 */
	public function test_variation_deletion_lookup_writes_are_linear(): void {
		global $wpdb;

		$delete_writes = function ( int $count ) use ( $wpdb ): int {
			$parent = new WC_Product_Variable();
			$parent->save();
			$groups = array();
			for ( $index = 0; $index < $count; ++$index ) {
				$child = new WC_Product_Variation();
				$child->set_parent_id( $parent->get_id() );
				$child->set_regular_price( '37' );
				$child->set_sale_price( '19' );
				$child->save();
				$group = new WC_Product_Grouped();
				$group->set_children( array( $child->get_id() ) );
				$group->save();
				$groups[] = $group->get_id();
			}
			$writes      = 0;
			$count_write = static function ( $query ) use ( &$writes, $wpdb ) {
				if ( false !== strpos( $query, $wpdb->wc_product_meta_lookup ) && preg_match( '/^\s*(UPDATE|REPLACE|INSERT)\b/i', $query ) ) {
					++$writes;
				}
				return $query;
			};
			add_filter( 'query', $count_write );
			try {
				$parent->delete( true );
			} finally {
				remove_filter( 'query', $count_write );
			}
			$this->assertSame( array(), array_intersect( $groups, wc_get_product_ids_on_sale() ), 'All groups must lose eligibility after their children are deleted.' );
			return $writes;
		};
		$small         = $delete_writes( 4 );
		$large         = $delete_writes( 12 );
		$this->assertLessThanOrEqual( max( 1, 4 * $small ), $large, 'Tripling variations must not produce quadratic lookup write growth.' );
	}

	/**
	 * @testdox A generic lookup refresh that started before conversion cannot erase a newly grouped sale.
	 */
	public function test_generic_refresh_overlapping_grouped_conversion(): void {
		global $wpdb;

		$child = new WC_Product_Simple();
		$child->set_regular_price( '37' );
		$child->set_sale_price( '19' );
		$child->save();
		$product = new WC_Product_Simple();
		$product->set_regular_price( '71' );
		$product->save();
		$interleave = null;
		$interleave = static function ( $query ) use ( &$interleave, $wpdb, $product, $child ) {
			if ( 0 === strpos( $query, "REPLACE INTO `{$wpdb->wc_product_meta_lookup}`" ) ) {
				remove_filter( 'query', $interleave );
				$group = new WC_Product_Grouped( $product->get_id() );
				$group->set_children( array( $child->get_id() ) );
				$group->save();
			}
			return $query;
		};
		wp_cache_delete( 'lookup_table', 'object_' . $product->get_id() );
		add_filter( 'query', $interleave );
		try {
			WC_Data_Store::load( 'product' )->refresh_product_lookup_table( $product->get_id() );
		} finally {
			remove_filter( 'query', $interleave );
		}
		$this->assertContains( $product->get_id(), wc_get_product_ids_on_sale() );
		$this->assertSame( '1', $wpdb->get_var( $wpdb->prepare( "SELECT onsale FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $product->get_id() ) ) );
	}

	/**
	 * @testdox A replacement simple-product store retains its own sale projection during save, invalidation, and repair.
	 */
	public function test_type_specific_store_retains_sale_ownership(): void {
		$custom_store = new class() extends WC_Product_Data_Store_CPT {
			/**
			 * Supply an extension-owned sale flag.
			 *
			 * @param int    $id Product ID.
			 * @param string $table Lookup table.
			 * @return array
			 */
			protected function get_data_for_lookup_table( $id, $table ) {
				$data           = parent::get_data_for_lookup_table( $id, $table );
				$data['onsale'] = 1;
				return $data;
			}
		};
		add_filter(
			'woocommerce_data_stores',
			static function ( $stores ) use ( $custom_store ) {
				$stores['product-simple'] = $custom_store;
				return $stores;
			}
		);
		$product = new WC_Product_Simple();
		$product->set_regular_price( '71' );
		$product->save();
		$this->assertContains( $product->get_id(), wc_get_product_ids_on_sale() );
		wc_delete_product_transients( $product->get_id() );
		$this->assertContains( $product->get_id(), wc_get_product_ids_on_sale() );
		do_action( 'woocommerce_refresh_grouped_product_sale', $product->get_id() );
		$this->assertContains( $product->get_id(), wc_get_product_ids_on_sale(), 'Repair must use the product type store, not the generic CPT projection.' );
	}
}
