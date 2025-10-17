<?php
/**
 * Class Abstract_WC_Order_Item_Type_Data_Store file.
 *
 * @package WooCommerce\DataStores
 */

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareTrait;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Data Store
 *
 * @version  3.0.0
 */
abstract class Abstract_WC_Order_Item_Type_Data_Store extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	use CogsAwareTrait;

	/**
	 * Meta type. This should match up with
	 * the types available at https://developer.wordpress.org/reference/functions/add_metadata/.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 *
	 * @var string
	 */
	protected $meta_type = 'order_item';

	/**
	 * This only needs set if you are using a custom metadata type (for example payment tokens.
	 * This should be the name of the field your table uses for associating meta with objects.
	 * For example, in payment_tokenmeta, this would be payment_token_id.
	 *
	 * @var string
	 */
	protected $object_id_field_for_meta = 'order_item_id';

	/**
	 * Indicates if the Cost of Goods Sold feature is enabled.
	 *
	 * @var bool
	 */
	private bool $cogs_is_enabled;

	/**
	 * The instance of WC_Order_Item_Data_Store to use for COGS related operations.
	 *
	 * @var WC_Order_Item_Data_Store
	 */
	private WC_Data_Store $order_item_data_store;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->cogs_is_enabled = $this->cogs_is_enabled();
		if ( $this->cogs_is_enabled ) {
			$this->order_item_data_store = WC_Data_Store::load( 'order-item' );
		}
	}

	/**
	 * Create a new order item in the database.
	 *
	 * @param WC_Order_Item $item Order item object.
	 * @since 3.0.0
	 */
	public function create( &$item ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_order_items',
			array(
				'order_item_name' => $item->get_name(),
				'order_item_type' => $item->get_type(),
				'order_id'        => $item->get_order_id(),
			)
		);
		$item->set_id( $wpdb->insert_id );
		$this->save_item_data( $item );
		$item->save_meta_data();

		if ( $this->cogs_is_enabled && $item->has_cogs() ) {
			$this->save_cogs_data( $item );
		}

		$item->apply_changes();
		$this->clear_cache( $item );

		do_action( 'woocommerce_new_order_item', $item->get_id(), $item, $item->get_order_id() );
	}

	/**
	 * Update a order item in the database.
	 *
	 * @param WC_Order_Item $item Order item object.
	 * @since 3.0.0
	 */
	public function update( &$item ) {
		global $wpdb;

		$changes = $item->get_changes();

		if ( array_intersect( array( 'name', 'order_id' ), array_keys( $changes ) ) ) {
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_order_items',
				array(
					'order_item_name' => $item->get_name(),
					'order_item_type' => $item->get_type(),
					'order_id'        => $item->get_order_id(),
				),
				array( 'order_item_id' => $item->get_id() )
			);
		}

		$this->save_item_data( $item );
		$item->save_meta_data();
		if ( $this->cogs_is_enabled && $item->has_cogs() ) {
			$this->save_cogs_data( $item );
		}
		$item->apply_changes();
		$this->clear_cache( $item );

		do_action( 'woocommerce_update_order_item', $item->get_id(), $item, $item->get_order_id() );
	}

	/**
	 * Remove an order item from the database.
	 *
	 * @param WC_Order_Item $item Order item object.
	 * @param array         $args Array of args to pass to the delete method.
	 * @since 3.0.0
	 */
	public function delete( &$item, $args = array() ) {
		if ( $item->get_id() ) {
			global $wpdb;
			do_action( 'woocommerce_before_delete_order_item', $item->get_id() );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_order_items', array( 'order_item_id' => $item->get_id() ) );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_order_itemmeta', array( 'order_item_id' => $item->get_id() ) );
			do_action( 'woocommerce_delete_order_item', $item->get_id() );
			$this->clear_cache( $item );
		}
	}

	/**
	 * Read a order item from the database.
	 *
	 * @param WC_Order_Item $item Order item object.
	 *
	 * @throws Exception If invalid order item.
	 * @since 3.0.0
	 */
	public function read( &$item ) {
		global $wpdb;

		$item->set_defaults();

		// Get from cache if available.
		$data = wp_cache_get( 'item-' . $item->get_id(), 'order-items' );

		if ( false === $data ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT order_id, order_item_name FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d LIMIT 1;", $item->get_id() ) );
			wp_cache_set( 'item-' . $item->get_id(), $data, 'order-items' );
		}

		if ( ! $data ) {
			throw new Exception( __( 'Invalid order item.', 'woocommerce' ) );
		}

		$item->set_props(
			array(
				'order_id' => $data->order_id,
				'name'     => $data->order_item_name,
			)
		);
		$item->read_meta_data();

		if ( $this->cogs_is_enabled && $item->has_cogs() ) {
			$cogs_metadata = $this->order_item_data_store->get_metadata( $item->get_id(), '_cogs_value', true );

			// Only set COGS value if the metadata actually exists.
			// If it doesn't exist, leave it as null so it can be calculated later.
			if ( '' !== $cogs_metadata && false !== $cogs_metadata ) {
				$cogs_value = (float) $cogs_metadata;

				/**
				 * Filter to customize the Cost of Goods Sold value that gets loaded for a given order item.
				 *
				 * @since 9.5.0
				 *
				 * @param float $cogs_value The value as read from the database.
				 * @param WC_Order_Item $product The order item for which the value is being loaded.
				 */
				$cogs_value = apply_filters( 'woocommerce_load_order_item_cogs_value', $cogs_value, $item );

				$item->set_cogs_value( (float) $cogs_value );
			}
		}
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $item->get_id() will be set.
	 *
	 * @param WC_Order_Item $item Order item object.
	 * @since 3.0.0
	 */
	public function save_item_data( &$item ) {
	}

	/**
	 * Clear meta cache.
	 *
	 * @param WC_Order_Item $item Order item object.
	 */
	public function clear_cache( &$item ) {
		wp_cache_delete( 'item-' . $item->get_id(), 'order-items' );
		wp_cache_delete( 'order-items-' . $item->get_order_id(), 'orders' );
		wp_cache_delete( $item->get_id(), $this->meta_type . '_meta' );
	}

	/**
	 * Persist the Cost of Goods Sold related data to the database.
	 *
	 * @param WC_Order_Item $item The order item for which the data will be persisted.
	 */
	private function save_cogs_data( WC_Order_Item $item ) {
		$cogs_value = $item->get_cogs_value();

		/**
		 * Filter to customize the Cost of Goods Sold value that gets saved for a given order item,
		 * or to suppress the saving of the value (so that custom storage can be used).
		 *
		 * @since 9.5.0
		 *
		 * @param float|null $cogs_value The value to be written to the database. If returned as null, nothing will be written.
		 * @param WC_Order_Item $item The order item for which the value is being saved.
		 */
		$cogs_value = apply_filters( 'woocommerce_save_order_item_cogs_value', $cogs_value, $item );
		if ( is_null( $cogs_value ) ) {
			return;
		}

		$cogs_value = (float) $cogs_value;
		if ( 0.0 === $cogs_value ) {
			$this->order_item_data_store->delete_metadata( $item->get_id(), '_cogs_value', '' );
		} else {
			$this->order_item_data_store->update_metadata( $item->get_id(), '_cogs_value', $cogs_value );
		}
	}
}
