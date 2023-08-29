<?php
/**
 * Class WC_Order_Refund_Data_Store_CPT file.
 *
 * @package WooCommerce\DataStores
 */

use Automattic\WooCommerce\Caches\OrderDataCache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Refund Data Store: Stored in CPT.
 *
 * @version  3.0.0
 */
class WC_Order_Refund_Data_Store_CPT extends Abstract_WC_Order_Data_Store_CPT implements WC_Object_Data_Store_Interface, WC_Order_Refund_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_order_currency',
		'_cart_discount',
		'_refund_amount',
		'_refunded_by',
		'_refunded_payment',
		'_refund_reason',
		'_cart_discount_tax',
		'_order_shipping',
		'_order_shipping_tax',
		'_order_tax',
		'_order_total',
		'_order_version',
		'_prices_include_tax',
		'_payment_tokens',
	);

	/**
	 * Method to create a refund in the database.
	 *
	 * @param \WC_Abstract_Order $refund Refund object.
	 */
	public function create( &$refund ) {
		$this->remove_from_order_data_cache( $refund );
		parent::create( $refund );
	}

	/**
	 * Update refund in database.
	 *
	 * @param \WC_Order $refund Refund object.
	 */
	public function update( &$refund ) {
		$this->remove_from_order_data_cache( $refund );
		parent::update( $refund );
	}

	/**
	 * Delete a refund - no trash is supported.
	 *
	 * @param WC_Order $refund Order object.
	 * @param array    $args Array of args to pass to the delete method.
	 */
	public function delete( &$refund, $args = array() ) {
		$id               = $refund->get_id();
		$parent_order_id  = $refund->get_parent_id();
		$refund_cache_key = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'refunds' . $parent_order_id;

		if ( ! $id ) {
			return;
		}

		$this->remove_from_order_data_cache( $refund );

		wp_delete_post( $id );
		wp_cache_delete( $refund_cache_key, 'orders' );
		$refund->set_id( 0 );
		do_action( 'woocommerce_delete_order_refund', $id );
	}

	/**
	 * Read refund data. Can be overridden by child classes to load other props.
	 *
	 * @param WC_Order_Refund $refund Refund object.
	 * @param object          $post_object Post object.
	 * @since 3.0.0
	 */
	protected function read_order_data( &$refund, $post_object ) {
		parent::read_order_data( $refund, $post_object );
		$id = $refund->get_id();
		$refund->set_props(
			array(
				'amount'           => get_post_meta( $id, '_refund_amount', true ),
				'refunded_by'      => metadata_exists( 'post', $id, '_refunded_by' ) ? get_post_meta( $id, '_refunded_by', true ) : absint( $post_object->post_author ),
				'refunded_payment' => wc_string_to_bool( get_post_meta( $id, '_refunded_payment', true ) ),
				'reason'           => metadata_exists( 'post', $id, '_refund_reason' ) ? get_post_meta( $id, '_refund_reason', true ) : $post_object->post_excerpt,
			)
		);
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the WC_Order class.
	 *
	 * @param WC_Order_Refund $refund Refund object.
	 *
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$refund ) {
		parent::update_post_meta( $refund );

		$updated_props     = array();
		$meta_key_to_props = array(
			'_refund_amount'    => 'amount',
			'_refunded_by'      => 'refunded_by',
			'_refunded_payment' => 'refunded_payment',
			'_refund_reason'    => 'reason',
		);

		$props_to_update = $this->get_props_to_update( $refund, $meta_key_to_props );
		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $refund->{"get_$prop"}( 'edit' );
			update_post_meta( $refund->get_id(), $meta_key, $value );
			$updated_props[] = $prop;
		}

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
			( new DateTime( 'now' ) )->format( _x( 'M d, Y @ h:i A', 'Order date parsed by DateTime::format', 'woocommerce' ) ) // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment, WordPress.WP.I18n.UnorderedPlaceholdersText
		);
	}

	/**
	 * Deletes the order data cached for the order corresponding to a given refund.
	 *
	 * @param \WC_Order $refund Refund object whose order will get its associated data deleted.
	 */
	private function remove_from_order_data_cache( $refund ) {
		wc_get_container()->get( OrderDataCache::class )->remove( $refund->get_parent_id() );
	}
}
