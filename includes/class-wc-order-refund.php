<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order refund. Refunds are based on orders (essentially negative orders) and
 * contain much of the same data.
 *
 * @class    WC_Order_Refund
 * @version  2.6.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Refund extends WC_Abstract_Order {

	/**
     * Stores meta data.
     * @var array
     */
    protected $_meta = array(
		'prices_include_tax' => false,
		'refund_amount'      => '',
		'refund_reason'      => '',
		'refunded_by'        => 0,
    );

    /**
     * Get the refund if ID is passed, otherwise the refund is new and empty.
     * @param  int|object|WC_Order_Refund $refund Refund to init.
     */
    public function __construct( $refund = 0 ) {
		if ( is_numeric( $refund ) ) {
            $this->read( $refund );
        } elseif ( $order instanceof WC_Order_Refund ) {
            $this->read( $refund->get_id() );
        } elseif ( ! empty( $refund->ID ) ) {
            $this->read( absint( $refund->ID ) );
        }
		$this->set_order_type( 'refund' );
    }

	/**
     * Read from the database.
     * @since 2.6.0
     * @param int $id ID of object to read.
     */
    public function read( $id ) {
        $post_object = get_post( $id );
		$this->_meta['refunded_by'] = absint( $post_object->post_author ); // post_author was used before refunded_by meta.
		parent::read( $post_object );
	}

	/**
	 * Get refunded amount.
	 * @since 2.2
	 * @return int|float
	 */
	public function get_refund_amount() {
		return apply_filters( 'woocommerce_refund_amount', (double) $this->get_meta( 'refund_amount' ), $this );
	}

	/**
	 * Get formatted refunded amount.
	 * @since 2.4
	 * @return string
	 */
	public function get_formatted_refund_amount() {
		return apply_filters( 'woocommerce_formatted_refund_amount', wc_price( $this->get_refund_amount(), array( 'currency' => $this->get_order_currency() ) ), $this );
	}

	/**
	 * Get refunded amount.
	 * @since 2.2
	 * @return int|float
	 */
	public function get_refund_reason() {
		return apply_filters( 'woocommerce_refund_reason', $this->get_meta( 'refund_reason' ), $this );
	}

	/**
	 * Get ID of user who did the refund.
	 * @since 2.6
	 * @return int
	 */
	public function get_refunded_by() {
		return absint( $this->get_meta( 'refunded_by' ) );
	}

	/**
     * Magic __get method for backwards compatibility.
     * @param string $key
     * @return mixed
     */
    public function __get( $key ) {
        /**
         * Maps legacy vars to new getters.
         */
        if ( 'reason' === $key ) {
            _doing_it_wrong( $key, 'Order properties should not be accessed directly.', '2.6' );
            return $this->get_date_completed();
		} elseif ( 'refund_amount' === $key ) {
			_doing_it_wrong( $key, 'Order properties should not be accessed directly.', '2.6' );
            return $this->get_date_completed();
		}

		return parent::__get( $key );
	}

	/**
     * Gets an refund from the database.
     * @deprecated 2.6
     * @param int $id (default: 0).
     * @return bool
     */
    public function get_refund( $id = 0 ) {
        _deprecated_function( 'get_refund', '2.6', 'read' );
        if ( ! $id ) {
            return false;
        }
        if ( $result = get_post( $id ) ) {
            $this->populate( $result );
            return true;
        }
        return false;
    }
}
