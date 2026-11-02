<?php
/**
 * Class WC_Order_Item_Product_Data_Store file.
 *
 * @package WooCommerce\DataStores
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Product Data Store
 *
 * @version  3.0.0
 */
class WC_Order_Item_Product_Data_Store extends Abstract_WC_Order_Item_Type_Data_Store implements WC_Object_Data_Store_Interface, WC_Order_Item_Product_Data_Store_Interface, WC_Order_Item_Type_Data_Store_Interface {

	/**
	 * Data stored in meta keys.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array( '_product_id', '_variation_id', '_qty', '_tax_class', '_line_subtotal', '_line_subtotal_tax', '_line_total', '_line_tax', '_line_tax_data' );

	/**
	 * Persisted product and quantity before each item save.
	 *
	 * @var array<int, array{product_id: int, quantity: int, order_id: int}>
	 */
	private static array $previous_items = array();

	/**
	 * Sales to reverse after item deletion, keyed by item ID.
	 *
	 * @var array<int, array{product_id: int, quantity: int}>
	 */
	private static array $deleted_items = array();

	/**
	 * Capture the persisted values before an item is saved.
	 *
	 * @since 11.4.0
	 * @internal
	 * @param mixed $item Order item.
	 */
	public static function handle_woocommerce_before_order_item_object_save( $item ): void {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			return;
		}

		$key = spl_object_id( $item );
		unset( self::$previous_items[ $key ] );
		if ( $item->get_id() && ! array_intersect( array( 'product_id', 'quantity', 'order_id' ), array_keys( $item->get_changes() ) ) ) {
			return;
		}

		$previous                     = $item->get_id() ? WC_Order_Factory::get_order_item( $item->get_id() ) : false;
		self::$previous_items[ $key ] = array(
			'product_id' => $previous instanceof WC_Order_Item_Product ? $previous->get_product_id() : 0,
			'quantity'   => $previous instanceof WC_Order_Item_Product ? absint( $previous->get_quantity() ) : 0,
			'order_id'   => $previous ? $previous->get_order_id() : $item->get_order_id(),
		);
	}

	/**
	 * Apply only the sales difference after an item is saved.
	 *
	 * @since 11.4.0
	 * @internal
	 * @param mixed $item Order item.
	 */
	public static function handle_woocommerce_after_order_item_object_save( $item ): void {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			return;
		}

		$key      = spl_object_id( $item );
		$previous = self::$previous_items[ $key ] ?? null;
		unset( self::$previous_items[ $key ] );
		if ( null === $previous ) {
			return;
		}

		$previous_recorded = self::has_recorded_sales( $previous['order_id'] );
		$current_recorded  = $previous['order_id'] === $item->get_order_id() ? $previous_recorded : self::has_recorded_sales( $item->get_order_id() );
		if ( ! $previous_recorded && ! $current_recorded ) {
			return;
		}

		$product_id           = $item->get_product_id();
		$quantity             = $current_recorded ? absint( $item->get_quantity() ) : 0;
		$previous['quantity'] = $previous_recorded ? $previous['quantity'] : 0;
		if ( $previous['product_id'] === $product_id ) {
			self::update_sales( $product_id, $quantity - $previous['quantity'] );
		} else {
			self::update_sales( $previous['product_id'], -$previous['quantity'] );
			self::update_sales( $product_id, $quantity );
		}
	}

	/**
	 * Capture recorded sales before the item data is deleted.
	 *
	 * @since 11.4.0
	 * @internal
	 * @param mixed $item_id Order item ID.
	 */
	public static function handle_woocommerce_before_delete_order_item( $item_id ): void {
		if ( ! is_numeric( $item_id ) ) {
			return;
		}

		$item_id = absint( $item_id );
		unset( self::$deleted_items[ $item_id ] );
		$item = WC_Order_Factory::get_order_item( $item_id );
		if ( ! $item instanceof WC_Order_Item_Product || ! self::has_recorded_sales( $item->get_order_id() ) ) {
			return;
		}

		self::$deleted_items[ $item_id ] = array(
			'product_id' => $item->get_product_id(),
			'quantity'   => absint( $item->get_quantity() ),
		);
	}

	/**
	 * Reverse the removed item's sales.
	 *
	 * @since 11.4.0
	 * @internal
	 * @param mixed $item_id Order item ID.
	 */
	public static function handle_woocommerce_delete_order_item( $item_id ): void {
		if ( ! is_numeric( $item_id ) ) {
			return;
		}

		$item_id  = absint( $item_id );
		$previous = self::$deleted_items[ $item_id ] ?? null;
		unset( self::$deleted_items[ $item_id ] );
		if ( null !== $previous ) {
			self::update_sales( $previous['product_id'], -$previous['quantity'] );
		}
	}

	/**
	 * Check the persisted order, rather than an item's potentially stale order instance.
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	private static function has_recorded_sales( int $order_id ): bool {
		$order = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		/**
		 * Order data store.
		 *
		 * @var WC_Order_Data_Store_Interface $data_store
		 */
		$data_store = $order->get_data_store();
		return $data_store->get_recorded_sales( $order );
	}

	/**
	 * Update sales through the product data store, including its lookup table and caches.
	 *
	 * @param int $product_id Product ID.
	 * @param int $difference Quantity difference.
	 */
	private static function update_sales( int $product_id, int $difference ): void {
		if ( $product_id && $difference ) {
			/**
			 * Product data store.
			 *
			 * @var WC_Product_Data_Store_Interface $data_store
			 */
			$data_store = WC_Data_Store::load( 'product' );
			$data_store->update_product_sales( $product_id, abs( $difference ), $difference > 0 ? 'increase' : 'decrease' );
		}
	}

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @since 3.0.0
	 * @param WC_Order_Item_Product $item Product order item object.
	 */
	public function read( &$item ) {
		parent::read( $item );
		$id = $item->get_id();
		$item->set_props(
			array(
				'product_id'   => get_metadata( 'order_item', $id, '_product_id', true ),
				'variation_id' => get_metadata( 'order_item', $id, '_variation_id', true ),
				'quantity'     => get_metadata( 'order_item', $id, '_qty', true ),
				'tax_class'    => get_metadata( 'order_item', $id, '_tax_class', true ),
				'subtotal'     => get_metadata( 'order_item', $id, '_line_subtotal', true ),
				'total'        => get_metadata( 'order_item', $id, '_line_total', true ),
				'taxes'        => get_metadata( 'order_item', $id, '_line_tax_data', true ),
			)
		);
		$item->set_object_read( true );
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $id will be set.
	 *
	 * @since 3.0.0
	 * @param WC_Order_Item_Product $item Product order item object.
	 */
	public function save_item_data( &$item ) {
		$id                = $item->get_id();
		$changes           = $item->get_changes();
		$meta_key_to_props = array(
			'_product_id'        => 'product_id',
			'_variation_id'      => 'variation_id',
			'_qty'               => 'quantity',
			'_tax_class'         => 'tax_class',
			'_line_subtotal'     => 'subtotal',
			'_line_subtotal_tax' => 'subtotal_tax',
			'_line_total'        => 'total',
			'_line_tax'          => 'total_tax',
			'_line_tax_data'     => 'taxes',
		);
		$props_to_update   = $this->get_props_to_update( $item, $meta_key_to_props, 'order_item' );

		foreach ( $props_to_update as $meta_key => $prop ) {
			update_metadata( 'order_item', $id, $meta_key, $item->{"get_$prop"}( 'edit' ) );
		}
	}

	/**
	 * Get a list of download IDs for a specific item from an order.
	 *
	 * @since 3.0.0
	 * @param WC_Order_Item_Product $item Product order item object.
	 * @param WC_Order              $order Order object.
	 * @return array
	 */
	public function get_download_ids( $item, $order ) {
		global $wpdb;
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT download_id FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE user_email = %s AND order_key = %s AND product_id = %d ORDER BY permission_id",
				$order->get_billing_email(),
				$order->get_order_key(),
				$item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id()
			)
		);
	}
}
