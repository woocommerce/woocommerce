<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Refund Data Store: Stored in CPT.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Refund_Data_Store_CPT extends Abstract_WC_Order_Data_Store_CPT implements WC_Object_Data_Store_Interface, WC_Order_Refund_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_order_currency',
		'_cart_discount',
		'_refund_amount',
		'_refunded_by',
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
	 * Delete a refund - no trash is supported.
	 * @param WC_Order $order
	 * @param array $args Array of args to pass to the delete method.
	 */
	public function delete( &$order, $args = array() ) {
		$id = $order->get_id();

		wp_delete_post( $id );
		$order->set_id( 0 );
		do_action( 'woocommerce_delete_order_refund', $id );
	}

	/**
	 * Read refund data. Can be overridden by child classes to load other props.
	 *
	 * @param WC_Order $refund
	 * @param object $post_object
	 * @since 3.0.0
	 */
	protected function read_order_data( &$refund, $post_object ) {
		parent::read_order_data( $refund, $post_object );
		$id = $refund->get_id();
		$refund->set_props( array(
			'amount'      => get_post_meta( $id, '_refund_amount', true ),
			'refunded_by' => metadata_exists( 'post', $id, '_refunded_by' ) ? get_post_meta( $id, '_refunded_by', true ) : absint( $post_object->post_author ),
			'reason'      => metadata_exists( 'post', $id, '_refund_reason' ) ? get_post_meta( $id, '_refund_reason', true ) : $post_object->post_excerpt,
		) );
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the WC_Order class.
	 *
	 * @param WC_Order
	 * @param WC_Order $refund
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$refund ) {
		parent::update_post_meta( $refund );

		$updated_props     = array();
		$meta_key_to_props = array(
			'_refund_amount' => 'amount',
			'_refunded_by'   => 'refunded_by',
			'_refund_reason' => 'reason',
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
		// @codingStandardsIgnoreStart
		/* translators: %s: Order date */
		return sprintf( __( 'Refund &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) );
		// @codingStandardsIgnoreEnd
	}
}
