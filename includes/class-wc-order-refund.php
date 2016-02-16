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

	/** @public string Order type */
	public $order_type = 'refund';

	/** @var string Date */
	public $date;

	/** @var string Refund reason */
	public $reason;

	/**
     * Data array, with defaults.
     *
     * @todo when migrating to custom tables, these will be columns
     * @since 2.6.0
     * @var array
     */
    protected $_refund_data = array(
		'refund_amount' => '',
		'refund_reason' => '',
	);

    /**
     * Get the refund if ID is passed, otherwise the refund is new and empty.
     * @param  int|object|WC_Order_Refund $refund Refund to init.
     */
    public function __construct( $refund = 0 ) {
		if ( is_numeric( $refund ) ) {
            $this->read( $refund );
        } elseif ( $order instanceof WC_Order_Refund ) {
            $this->read( absint( $refund->get_id() ) );
        } elseif ( ! empty( $refund->ID ) ) {
            $this->read( absint( $refund->ID ) );
        }
    }

	/**
	 * Get refunded amount.
	 *
	 * @since 2.2
	 * @return int|float
	 */
	public function get_refund_amount() {
		return apply_filters( 'woocommerce_refund_amount', (double) $this->refund_amount, $this );
	}

	/**
	 * Get formatted refunded amount.
	 *
	 * @since 2.4
	 * @return string
	 */
	public function get_formatted_refund_amount() {
		return apply_filters( 'woocommerce_formatted_refund_amount', wc_price( $this->refund_amount, array('currency' => $this->get_order_currency()) ), $this );
	}

	/**
	 * Get refunded amount.
	 *
	 * @since 2.2
	 * @return int|float
	 */
	public function get_refund_reason() {
		return apply_filters( 'woocommerce_refund_reason', $this->reason, $this );
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
