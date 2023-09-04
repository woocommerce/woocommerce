<?php
/**
 * Order refund data store. Refunds are based on orders (essentially negative orders) but there is slight difference in how we save them.
 * For example, order save hooks etc can't be fired when saving refund, so we need to do it a separate datastore.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use WC_Meta_Data;

/**
 * Class OrdersTableRefundDataStore.
 */
class OrdersTableRefundDataStore extends OrdersTableDataStore {

	/**
	 * Data stored in meta keys, but not considered "meta" for refund.
	 *
	 * @var string[]
	 */
	protected $internal_meta_keys = array(
		'_refund_amount',
		'_refund_reason',
		'_refunded_by',
		'_refunded_payment',
	);

	/**
	 * We do not have and use all the getters and setters from OrderTableDataStore, so we only select the props we actually need.
	 *
	 * @var \string[][]
	 */
	protected $operational_data_column_mapping = array(
		'id'                        => array( 'type' => 'int' ),
		'order_id'                  => array( 'type' => 'int' ),
		'woocommerce_version'       => array(
			'type' => 'string',
			'name' => 'version',
		),
		'prices_include_tax'        => array(
			'type' => 'bool',
			'name' => 'prices_include_tax',
		),
		'coupon_usages_are_counted' => array(
			'type' => 'bool',
			'name' => 'recorded_coupon_usage_counts',
		),
		'shipping_tax_amount'       => array(
			'type' => 'decimal',
			'name' => 'shipping_tax',
		),
		'shipping_total_amount'     => array(
			'type' => 'decimal',
			'name' => 'shipping_total',
		),
		'discount_tax_amount'       => array(
			'type' => 'decimal',
			'name' => 'discount_tax',
		),
		'discount_total_amount'     => array(
			'type' => 'decimal',
			'name' => 'discount_total',
		),
	);

	/**
	 * Delete a refund order from database.
	 *
	 * @param \WC_Order $refund Refund object to delete.
	 * @param array     $args Array of args to pass to the delete method.
	 *
	 * @return void
	 */
	public function delete( &$refund, $args = array() ) {
		$refund_id = $refund->get_id();
		if ( ! $refund_id ) {
			return;
		}

		$this->delete_order_data_from_custom_order_tables( $refund_id );
		$refund->set_id( 0 );

		$orders_table_is_authoritative = $refund->get_data_store()->get_current_class_name() === self::class;

		if ( $orders_table_is_authoritative ) {
			$data_synchronizer = wc_get_container()->get( DataSynchronizer::class );
			if ( $data_synchronizer->data_sync_is_enabled() ) {
				// Delete the associated post, which in turn deletes order items, etc. through {@see WC_Post_Data}.
				// Once we stop creating posts for orders, we should do the cleanup here instead.
				wp_delete_post( $refund_id );
			} else {
				$this->handle_order_deletion_with_sync_disabled( $refund_id );
			}
		}
	}

	/**
	 * Helper method to set refund props.
	 *
	 * @param \WC_Order_Refund $refund Refund object.
	 * @param object           $data   DB data object.
	 *
	 * @since 8.0.0
	 */
	protected function set_order_props_from_data( &$refund, $data ) {
		parent::set_order_props_from_data( $refund, $data );
		foreach ( $data->meta_data as $meta ) {
			switch ( $meta->meta_key ) {
				case '_refund_amount':
					$refund->set_amount( $meta->meta_value );
					break;
				case '_refunded_by':
					$refund->set_refunded_by( $meta->meta_value );
					break;
				case '_refunded_payment':
					$refund->set_refunded_payment( wc_string_to_bool( $meta->meta_value ) );
					break;
				case '_refund_reason':
					$refund->set_reason( $meta->meta_value );
					break;
			}
		}
	}

	/**
	 * Method to create a refund in the database.
	 *
	 * @param \WC_Abstract_Order $refund Refund object.
	 */
	public function create( &$refund ) {
		$refund->set_status( 'completed' ); // Refund are always marked completed.
		$this->persist_save( $refund );
	}

	/**
	 * Update refund in database.
	 *
	 * @param \WC_Order $refund Refund object.
	 */
	public function update( &$refund ) {
		$this->persist_updates( $refund );
	}

	/**
	 * Helper method that updates post meta based on an refund object.
	 * Mostly used for backwards compatibility purposes in this datastore.
	 *
	 * @param \WC_Order $refund Refund object.
	 */
	public function update_order_meta( &$refund ) {
		parent::update_order_meta( $refund );

		// Update additional props.
		$updated_props     = array();
		$meta_key_to_props = array(
			'_refund_amount'    => 'amount',
			'_refunded_by'      => 'refunded_by',
			'_refunded_payment' => 'refunded_payment',
			'_refund_reason'    => 'reason',
		);

		$props_to_update = $this->get_props_to_update( $refund, $meta_key_to_props );
		foreach ( $props_to_update as $meta_key => $prop ) {
			$meta_object        = new WC_Meta_Data();
			$meta_object->key   = $meta_key;
			$meta_object->value = $refund->{"get_$prop"}( 'edit' );
			$existing_meta      = $this->data_store_meta->get_metadata_by_key( $refund, $meta_key );
			if ( $existing_meta ) {
				$existing_meta   = $existing_meta[0];
				$meta_object->id = $existing_meta->id;
				$this->update_meta( $refund, $meta_object );
			} else {
				$this->add_meta( $refund, $meta_object );
			}
			$updated_props[] = $prop;
		}

		/**
		 * Fires after updating meta for a order refund.
		 *
		 * @since 2.7.0
		 */
		do_action( 'woocommerce_order_refund_object_updated_props', $refund, $updated_props );
	}

	/**
	 * Get a title for the new post type.
	 *
	 * @return string
	 */
	protected function get_post_title() {
		return sprintf(
		/* translators: %s: Order date */
			__( 'Refund &ndash; %s', 'woocommerce' ),
			( new \DateTime( 'now' ) )->format( _x( 'M d, Y @ h:i A', 'Order date parsed by DateTime::format', 'woocommerce' ) ) // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment, WordPress.WP.I18n.UnorderedPlaceholdersText
		);
	}


	/**
	 * Returns data store object to use backfilling.
	 *
	 * @return \WC_Order_Refund_Data_Store_CPT
	 */
	protected function get_post_data_store_for_backfill() {
		return new \WC_Order_Refund_Data_Store_CPT();
	}

}
