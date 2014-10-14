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

	/** @public string Order type */
	public $order_type = 'refund';

	/**
	 * Init/load the order object. Called from the contructor.
	 *
	 * @param  string|int|WP_POST|WC_Order $order Order to init
	 */
	protected function init( $order ) {
		if ( is_numeric( $order ) ) {
			$this->id   = absint( $order );
			$this->post = get_post( $order );
			$this->get_order( $this->id );
		} elseif ( $order instanceof WC_Order ) {
			$this->id   = absint( $order->id );
			$this->post = $order->post;
			$this->get_order( $this->id );
		} elseif ( $order instanceof WP_Post || isset( $order->ID ) ) {
			$this->id   = absint( $order->ID );
			$this->post = $order;
			$this->get_order( $this->id );
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
