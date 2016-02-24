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
    protected $_meta_data = array(
		'refund_amount' => '',
		'refund_reason' => '',
		'refunded_by'   => 0,
    );

    /**
     * Get the refund if ID is passed, otherwise the refund is new and empty.
     * @param  int|object|WC_Order_Refund $refund Refund to init.
     */
    public function __construct( $refund = 0 ) {
		if ( is_numeric( $refund ) ) {
            $this->read( $refund );
        } elseif ( $refund instanceof WC_Order_Refund ) {
            $this->read( $refund->get_id() );
        } elseif ( ! empty( $refund->ID ) ) {
            $this->read( absint( $refund->ID ) );
        }
		$this->set_order_type( 'shop_order_refund' );
    }

	/**
     * Read from the database.
     * @since 2.6.0
     * @param int $id ID of object to read.
     */
    public function read( $id ) {
		if ( empty( $id ) ) {
			return;
		}
        $post_object                       = get_post( $id );
		$this->_meta_data['refunded_by']   = absint( $post_object->post_author ); // post_author was used before refunded_by meta.
		$this->_meta_data['refund_reason'] = absint( $post_object->post_excerpt ); // post_excerpt was used before refund_reason meta.
		parent::read( $post_object );
	}

	/**
	 * Get a title for the new post type.
	 */
	protected function get_post_title() {
		return sprintf( __( 'Refund &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) );
	}

	/**
	 * Set refunded amount.
	 * @param string $value
	 */
	public function set_refund_amount( $value ) {
		$this->_meta_data['refund_amount'] = wc_format_decimal( $value );
	}

	/**
	 * Get refunded amount.
	 * @since 2.2
	 * @return int|float
	 */
	public function get_refund_amount() {
		return apply_filters( 'woocommerce_refund_amount', (double) $this->_meta_data['refund_amount'], $this );
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
	 * Set refund reason.
	 * @param string $value
	 */
	public function set_refund_reason( $value ) {
		$this->_meta_data['refund_reason'] = $value;
	}

	/**
	 * Get refund reason.
	 * @since 2.2
	 * @return int|float
	 */
	public function get_refund_reason() {
		return apply_filters( 'woocommerce_refund_reason', $this->_meta_data['refund_reason'], $this );
	}

	/**
	 * Set refunded by.
	 * @param int $value
	 */
	public function set_refunded_by( $value ) {
		$this->_meta_data['refunded_by'] = absint( $value );
	}

	/**
	 * Get ID of user who did the refund.
	 * @since 2.6
	 * @return int
	 */
	public function get_refunded_by() {
		return absint( $this->_meta_data['refunded_by'] );
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
            return $this->get_refund_reason();
		} elseif ( 'refund_amount' === $key ) {
			_doing_it_wrong( $key, 'Order properties should not be accessed directly.', '2.6' );
            return $this->get_refund_amount();
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
