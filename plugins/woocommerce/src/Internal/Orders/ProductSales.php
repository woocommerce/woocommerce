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
	 * Persisted item values before saving, keyed by PHP object ID.
	 *
	 * @var array<int, array{product_id: int, quantity: int, order_id: int}>
	 */
	private array $item_snapshots_before_save = array();

	/**
	 * Recorded item sales captured before deletion, keyed by item ID.
	 *
	 * @var array<int, array{product_id: int, quantity: int}>
	 */
	private array $item_sales_before_delete = array();

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

		$object_id = spl_object_id( $item );
		unset( $this->item_snapshots_before_save[ $object_id ] );
		if ( $item->get_id() && ! array_intersect( array( 'product_id', 'quantity', 'order_id' ), array_keys( $item->get_changes() ) ) ) {
			return;
		}

		$persisted_item                                 = $item->get_id() ? WC_Order_Factory::get_order_item( $item->get_id() ) : false;
		$this->item_snapshots_before_save[ $object_id ] = array(
			'product_id' => $persisted_item instanceof WC_Order_Item_Product ? $persisted_item->get_product_id() : 0,
			'quantity'   => $persisted_item instanceof WC_Order_Item_Product ? absint( $persisted_item->get_quantity() ) : 0,
			'order_id'   => $persisted_item ? $persisted_item->get_order_id() : $item->get_order_id(),
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

		$object_id        = spl_object_id( $item );
		$item_before_save = $this->item_snapshots_before_save[ $object_id ] ?? null;
		unset( $this->item_snapshots_before_save[ $object_id ] );
		if ( null === $item_before_save ) {
			return;
		}

		$previous_order_has_recorded_sales = $this->has_recorded_sales( $item_before_save['order_id'] );
		$current_order_has_recorded_sales  = $item_before_save['order_id'] === $item->get_order_id() ? $previous_order_has_recorded_sales : $this->has_recorded_sales( $item->get_order_id() );
		if ( ! $previous_order_has_recorded_sales && ! $current_order_has_recorded_sales ) {
			return;
		}

		$current_product_id      = $item->get_product_id();
		$current_sales_quantity  = $current_order_has_recorded_sales ? absint( $item->get_quantity() ) : 0;
		$previous_sales_quantity = $previous_order_has_recorded_sales ? $item_before_save['quantity'] : 0;
		if ( $item_before_save['product_id'] === $current_product_id ) {
			$this->update_sales( $current_product_id, $current_sales_quantity - $previous_sales_quantity );
		} else {
			$this->update_sales( $item_before_save['product_id'], -$previous_sales_quantity );
			$this->update_sales( $current_product_id, $current_sales_quantity );
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
		unset( $this->item_sales_before_delete[ $item_id ] );
		$item = WC_Order_Factory::get_order_item( $item_id );
		if ( ! $item instanceof WC_Order_Item_Product || ! $this->has_recorded_sales( $item->get_order_id() ) ) {
			return;
		}

		$this->item_sales_before_delete[ $item_id ] = array(
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

		$item_id             = absint( $item_id );
		$sales_before_delete = $this->item_sales_before_delete[ $item_id ] ?? null;
		unset( $this->item_sales_before_delete[ $item_id ] );
		if ( null !== $sales_before_delete ) {
			$this->update_sales( $sales_before_delete['product_id'], -$sales_before_delete['quantity'] );
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
