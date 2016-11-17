<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Refund Data Store: Stored in CPT.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Refund_Data_Store_CPT extends Abstract_WC_Order_Data_Store_CPT implements WC_Object_Data_Store, WC_Order_Refund_Data_Store_Interface {

	/**
	 * Read refund data. Can be overridden by child classes to load other props.
	 *
	 * @param WC_Order
	 * @param object $post_object
	 * @since 2.7.0
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
	 * @since 2.7.0
	 */
	protected function update_post_meta( &$refund ) {
		$updated_props     = array();
		$changed_props     = $refund->get_changes();
		$meta_key_to_props = array(
			'_refund_amount' => 'amount',
			'_refunded_by'   => 'refunded_by',
			'_refund_reason' => 'reason',
		);

		foreach ( $meta_key_to_props as $meta_key => $prop ) {
			if ( ! array_key_exists( $prop, $changed_props ) ) {
				continue;
			}
			$value = $refund->{"get_$prop"}( 'edit' );

			if ( '' !== $value ? update_post_meta( $refund->get_id(), $meta_key, $value ) : delete_post_meta( $refund->get_id(), $meta_key ) ) {
				$updated_props[] = $prop;
			}
		}
	}
}
