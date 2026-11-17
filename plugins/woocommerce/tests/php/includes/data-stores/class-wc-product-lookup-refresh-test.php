<?php
/**
 * Product-type data store coverage for direct lookup refreshes.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Tests for lookup refreshes after sales, stock and scheduled price updates.
 */
class WC_Product_Lookup_Refresh_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Should preserve a product data store's custom lookup prices after sales, stock and scheduled sale updates.
	 * @testWith ["sales", 10]
	 *           ["stock", 10]
	 *           ["sale-start", 5]
	 *           ["sale-end", 10]
	 *           ["direct-sales", 10]
	 *           ["direct-stock", 10]
	 *           ["direct-sales", 10, "fqcn"]
	 *           ["direct-sales", 10, "alias"]
	 * @param string $operation Update to perform.
	 * @param int    $min_price Expected minimum price.
	 * @param string $registration Data-store registration shape.
	 */
	public function test_refresh_uses_product_data_store( string $operation, int $min_price, string $registration = 'object' ): void {
		$store = new class() extends WC_Product_Data_Store_CPT {
			/**
			 * Supply the configuration's highest price, as extension data stores do.
			 *
			 * @param int    $id Product ID.
			 * @param string $table Lookup table name.
			 * @return array
			 */
			protected function get_data_for_lookup_table( $id, $table ) {
				$data = parent::get_data_for_lookup_table( $id, $table );
				if ( 'wc_product_meta_lookup' === $table ) {
					$data['max_price'] = get_post_meta( $id, '_test_max_price', true );
				}
				return $data;
			}
		};
		if ( 'fqcn' === $registration ) {
			$store = '\\' . get_class( $store );
		} elseif ( 'alias' === $registration ) {
			if ( ! class_exists( 'WC_Test_Lookup_Store_Alias', false ) ) {
				class_alias( get_class( $store ), 'WC_Test_Lookup_Store_Alias' );
			}
			$store = 'WC_Test_Lookup_Store_Alias';
		}
		add_filter(
			'woocommerce_data_stores',
			function ( $stores ) use ( $store ) {
				$stores['product-simple'] = $store;
				return $stores;
			}
		);
		$product = new WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->set_sale_price( '5' );
		$product->set_date_on_sale_from( time() + DAY_IN_SECONDS );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->update_meta_data( '_test_max_price', '30' );
		$product->save();
		$product->get_data_store()->refresh_product_lookup_table( $product->get_id() );

		global $wpdb;
		$lookup = static function () use ( $wpdb, $product ) {
			return $wpdb->get_row(
				$wpdb->prepare( "SELECT min_price, max_price, total_sales, stock_quantity FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $product->get_id() ),
				ARRAY_A
			);
		};
		$this->assertSame( 30.0, (float) $lookup()['max_price'], 'The fixture must start with the custom lookup range.' );

		switch ( $operation ) {
			case 'direct-sales':
				WC_Data_Store::load( 'product' )->update_product_sales( $product->get_id(), 2, 'increase' );
				$this->assertSame( 2, (int) $lookup()['total_sales'] );
				break;
			case 'direct-stock':
				WC_Data_Store::load( 'product' )->update_product_stock( $product->get_id(), 4 );
				$this->assertSame( 4, (int) $lookup()['stock_quantity'] );
				break;
			case 'sales':
				$order = wc_create_order();
				$order->add_product( $product, 2 );
				$order->save();
				add_filter( 'woocommerce_payment_complete_reduce_order_stock', '__return_false' );
				$order->set_status( OrderStatus::PROCESSING );
				$order->save();
				$this->assertSame( 2, (int) $lookup()['total_sales'] );
				break;
			case 'stock':
				$this->assertSame( 4, wc_update_product_stock( $product, 4, 'set', true ) );
				$this->assertSame( 4, (int) $lookup()['stock_quantity'] );
				break;
			case 'sale-start':
				wc_apply_sale_state_for_product( $product, 'start' );
				break;
			case 'sale-end':
				$product->set_price( '5' );
				$product->save();
				wc_apply_sale_state_for_product( $product, 'end' );
				break;
		}

		$row = $lookup();
		$this->assertSame( (float) $min_price, (float) $row['min_price'] );
		$this->assertSame( 30.0, (float) $row['max_price'], 'The type-specific maximum price must survive the refresh.' );
	}

	/**
	 * @testdox Should keep a customized generic product store's lookup data for types with a Core store.
	 */
	public function test_custom_generic_store_keeps_lookup_data(): void {
		$store = new class() extends WC_Product_Data_Store_CPT {
			/**
			 * Supply a custom highest price, as an extension replacing the generic store might.
			 *
			 * @param int    $id Product ID.
			 * @param string $table Lookup table name.
			 * @return array
			 */
			protected function get_data_for_lookup_table( $id, $table ) {
				$data = parent::get_data_for_lookup_table( $id, $table );
				if ( 'wc_product_meta_lookup' === $table ) {
					$data['max_price'] = get_post_meta( $id, '_test_max_price', true );
				}
				return $data;
			}
		};
		add_filter(
			'woocommerce_data_stores',
			function ( $stores ) use ( $store ) {
				$stores['product'] = $store;
				return $stores;
			}
		);
		$variation_id = WC_Helper_Product::create_variation_product()->get_children()[0];
		update_post_meta( $variation_id, '_test_max_price', '30' );

		WC_Data_Store::load( 'product' )->update_product_stock( $variation_id, 4 );

		global $wpdb;
		$this->assertSame( 30.0, (float) $wpdb->get_var( $wpdb->prepare( "SELECT max_price FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $variation_id ) ) );
	}

	/**
	 * @testdox Should not recurse when a product-type store hands its refresh back to the generic store.
	 */
	public function test_type_store_calling_generic_refresh(): void {
		$store = new class() extends WC_Product_Data_Store_CPT {
			/**
			 * Number of refreshes received.
			 *
			 * @var int
			 */
			public static $calls = 0;

			/**
			 * Hand the refresh back to the generic product store.
			 *
			 * @param int $product_id Product ID.
			 */
			public function refresh_product_lookup_table( int $product_id ): void {
				++self::$calls;
				WC_Data_Store::load( 'product' )->refresh_product_lookup_table( $product_id );
			}
		};
		add_filter(
			'woocommerce_data_stores',
			function ( $stores ) use ( $store ) {
				$stores['product-simple'] = $store;
				return $stores;
			}
		);
		$product = WC_Helper_Product::create_simple_product();

		WC_Data_Store::load( 'product' )->update_product_sales( $product->get_id(), 2, 'increase' );

		global $wpdb;
		$this->assertSame( 1, $store::$calls );
		$this->assertSame( 2, (int) $wpdb->get_var( $wpdb->prepare( "SELECT total_sales FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $product->get_id() ) ) );
	}

	/**
	 * @testdox Should still refresh the lookup table when a product store wraps the generic CPT store.
	 */
	public function test_delegating_store_refresh(): void {
		$product = WC_Helper_Product::create_simple_product();
		$store   = new class( 'product' ) extends WC_Data_Store implements WC_Object_Data_Store_Interface {
			/**
			 * Read metadata through the wrapped store.
			 *
			 * @param WC_Data $data Data object.
			 * @return array
			 */
			public function read_meta( &$data ) {
				return $this->__call( 'read_meta', array( &$data ) );
			}

			/**
			 * Delete metadata through the wrapped store.
			 *
			 * @param WC_Data $data Data object.
			 * @param object  $meta Metadata.
			 * @return mixed
			 */
			public function delete_meta( &$data, $meta ) {
				return $this->__call( 'delete_meta', array( &$data, $meta ) );
			}

			/**
			 * Add metadata through the wrapped store.
			 *
			 * @param WC_Data $data Data object.
			 * @param object  $meta Metadata.
			 * @return mixed
			 */
			public function add_meta( &$data, $meta ) {
				return $this->__call( 'add_meta', array( &$data, $meta ) );
			}

			/**
			 * Update metadata through the wrapped store.
			 *
			 * @param WC_Data $data Data object.
			 * @param object  $meta Metadata.
			 * @return mixed
			 */
			public function update_meta( &$data, $meta ) {
				return $this->__call( 'update_meta', array( &$data, $meta ) );
			}
		};
		add_filter(
			'woocommerce_data_stores',
			function ( $stores ) use ( $store ) {
				$stores['product-simple'] = $store;
				return $stores;
			}
		);

		WC_Data_Store::load( 'product' )->update_product_sales( $product->get_id(), 2, 'increase' );
		$this->assertSame( '2', get_post_meta( $product->get_id(), 'total_sales', true ) );
		global $wpdb;
		$this->assertSame( 2, (int) $wpdb->get_var( $wpdb->prepare( "SELECT total_sales FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $product->get_id() ) ) );
	}

	/**
	 * @testdox Should retain harmless handling of empty IDs in direct stock and sales updates.
	 * @testWith [null]
	 *           [""]
	 * @param mixed $product_id Empty legacy ID.
	 */
	public function test_empty_product_id( $product_id ): void {
		$this->setExpectedIncorrectUsage( 'WP_Object_Cache::delete' );
		$store = WC_Data_Store::load( 'product' );
		$this->assertNull( $store->update_product_sales( $product_id, 1, 'increase' ) );
		$this->assertSame( 4, $store->update_product_stock( $product_id, 4 ) );
	}
}
