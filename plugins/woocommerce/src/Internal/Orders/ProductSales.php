<?php
/**
 * ProductSales class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Data_Store;
use WC_Order;
use WC_Order_Data_Store_Interface;
use WC_Order_Factory;
use WC_Order_Item_Product;
use WC_Product_Data_Store_Interface;

/**
 * Keeps product sales counts in sync when recorded order items change.
 *
 * @internal
 * @since 11.4.0
 */
class ProductSales implements RegisterHooksInterface {

	/**
	 * Persisted product and quantity before each item save.
	 *
	 * @var array<int, array{product_id: int, quantity: int, order_id: int}>
	 */
	private array $previous_items = array();

	/**
	 * Sales to reverse after item deletion, keyed by item ID.
	 *
	 * @var array<int, array{product_id: int, quantity: int}>
	 */
	private array $deleted_items = array();

	/**
	 * Register hooks.
	 *
	 * @since 11.4.0
	 */
	public function register(): void {
		add_action( 'woocommerce_before_order_item_object_save', array( $this, 'handle_woocommerce_before_order_item_object_save' ) );
		add_action( 'woocommerce_after_order_item_object_save', array( $this, 'handle_woocommerce_after_order_item_object_save' ) );
		add_action( 'woocommerce_before_delete_order_item', array( $this, 'handle_woocommerce_before_delete_order_item' ) );
		add_action( 'woocommerce_delete_order_item', array( $this, 'handle_woocommerce_delete_order_item' ) );
	}

	/**
	 * Capture the persisted values before an item is saved.
	 *
	 * @since 11.4.0
	 * @internal
	 * @param mixed $item Order item.
	 */
	public function handle_woocommerce_before_order_item_object_save( $item ): void {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			return;
		}

		$key = spl_object_id( $item );
		unset( $this->previous_items[ $key ] );
		if ( $item->get_id() && ! array_intersect( array( 'product_id', 'quantity', 'order_id' ), array_keys( $item->get_changes() ) ) ) {
			return;
		}

		$previous                     = $item->get_id() ? WC_Order_Factory::get_order_item( $item->get_id() ) : false;
		$this->previous_items[ $key ] = array(
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
	public function handle_woocommerce_after_order_item_object_save( $item ): void {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			return;
		}

		$key      = spl_object_id( $item );
		$previous = $this->previous_items[ $key ] ?? null;
		unset( $this->previous_items[ $key ] );
		if ( null === $previous ) {
			return;
		}

		$previous_recorded = $this->has_recorded_sales( $previous['order_id'] );
		$current_recorded  = $previous['order_id'] === $item->get_order_id() ? $previous_recorded : $this->has_recorded_sales( $item->get_order_id() );
		if ( ! $previous_recorded && ! $current_recorded ) {
			return;
		}

		$product_id           = $item->get_product_id();
		$quantity             = $current_recorded ? absint( $item->get_quantity() ) : 0;
		$previous['quantity'] = $previous_recorded ? $previous['quantity'] : 0;
		if ( $previous['product_id'] === $product_id ) {
			$this->update_sales( $product_id, $quantity - $previous['quantity'] );
		} else {
			$this->update_sales( $previous['product_id'], -$previous['quantity'] );
			$this->update_sales( $product_id, $quantity );
		}
	}

	/**
	 * Capture recorded sales before the item data is deleted.
	 *
	 * @since 11.4.0
	 * @internal
	 * @param mixed $item_id Order item ID.
	 */
	public function handle_woocommerce_before_delete_order_item( $item_id ): void {
		if ( ! is_numeric( $item_id ) ) {
			return;
		}

		$item_id = absint( $item_id );
		unset( $this->deleted_items[ $item_id ] );
		$item = WC_Order_Factory::get_order_item( $item_id );
		if ( ! $item instanceof WC_Order_Item_Product || ! $this->has_recorded_sales( $item->get_order_id() ) ) {
			return;
		}

		$this->deleted_items[ $item_id ] = array(
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
	public function handle_woocommerce_delete_order_item( $item_id ): void {
		if ( ! is_numeric( $item_id ) ) {
			return;
		}

		$item_id  = absint( $item_id );
		$previous = $this->deleted_items[ $item_id ] ?? null;
		unset( $this->deleted_items[ $item_id ] );
		if ( null !== $previous ) {
			$this->update_sales( $previous['product_id'], -$previous['quantity'] );
		}
	}

	/**
	 * Check the persisted order, rather than an item's potentially stale order instance.
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	private function has_recorded_sales( int $order_id ): bool {
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
	private function update_sales( int $product_id, int $difference ): void {
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
}
