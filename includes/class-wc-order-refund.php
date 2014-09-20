<?php
/**
 * Order refund
 *
 * @class    WC_Order_Refund
 * @version  2.2.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Refund extends WC_Abstract_Order {

	/**
	 * Initialize the order refund.
	 *
	 * @param int|WC_Order_Refund $refund
	 */
	public function __construct( $refund ) {
		$this->order_type = 'refund';

		if ( is_numeric( $refund ) ) {
			$this->id = absint( $refund );
			$this->post = get_post( $refund );
			$this->get_refund( $this->id );
		} elseif ( $refund instanceof WC_Order_Refund ) {
			$this->id = absint( $refund->id );
			$this->post = $refund->post;
			$this->get_refund( $this->id );
		} elseif ( $refund instanceof WC_Order_Refund || isset( $refund->ID ) ) {
			$this->id = absint( $refund->ID );
			$this->post = $refund;
			$this->get_refund( $this->id );
		}
	}

	/**
	 * Gets an refund from the database
	 *
	 * @since 2.2
	 * @param int $id
	 * @return bool
	 */
	public function get_refund( $id = 0 ) {
		if ( ! $id ) {
			return false;
		}

		if ( $result = get_post( $id ) ) {
			$this->populate( $result );

			return true;
		}

		return false;
	}

	/**
	 * Populates an refund from the loaded post data
	 *
	 * @since 2.2
	 * @param mixed $result
	 * @return void
	 */
	public function populate( $result ) {
		// Standard post data
		$this->id            = $result->ID;
		$this->date          = $result->post_date;
		$this->modified_date = $result->post_modified;
		$this->reason        = $result->post_excerpt;
	}

	/**
	 * Get refunded amount
	 *
	 * @since 2.2
	 * @return int|float
	 */
	public function get_refund_amount() {
		return apply_filters( 'woocommerce_refund_amount', (double) $this->refund_amount, $this );
	}

	/**
	 * Get refunded amount
	 *
	 * @since 2.2
	 * @return int|float
	 */
	public function get_refund_reason() {
		return apply_filters( 'woocommerce_refund_reason', $this->reason, $this );
	}
}
