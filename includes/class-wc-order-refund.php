<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order refund. Refunds are based on orders (essentially negative orders) and
 * contain much of the same data.
 *
 * @version  2.7.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Refund extends WC_Abstract_Order {

	/**
	 * Extend the abstract _data properties and then read the order object.
	 *
	 * @param  int|object|WC_Order $order Order to init.
	 */
	public function __construct( $order = 0 ) {
		$this->_data = array_merge( $this->_data, array(
			'refund_amount' => '',
			'refund_reason' => '',
			'refunded_by'   => 0,
		) );
		parent::__construct( $order );
	}

	/**
	 * Insert data into the database.
	 * @since 2.7.0
	 */
	public function create() {
		parent::create();

		// Store additonal order data
		if ( $this->get_id() ) {
			$this->update_post_meta( '_refund_amount', $this->get_refund_amount() );
			$this->update_post_meta( '_refunded_by', $this->get_refunded_by() );
			$this->update_post_meta( '_refund_reason', $this->get_refund_reason() );
		}
	}

	/**
     * Read from the database.
     * @since 2.7.0
     * @param int $id ID of object to read.
     */
    public function read( $id ) {
		parent::read( $id );

		// Read additonal order data
		if ( $this->get_id() ) {
			$post_object = get_post( $id );
			$this->set_refund_amount( get_post_meta( $this->get_id(), '_refund_amount', true ) );

			// post_author was used before refunded_by meta.
			$this->set_refunded_by( metadata_exists( 'post', $this->get_id(), '_refunded_by' ) ? get_post_meta( $this->get_id(), '_refunded_by', true ) : absint( $post_object->post_author ) );

			// post_excerpt was used before refund_reason meta.
			$this->set_refund_reason( metadata_exists( 'post', $this->get_id(), '_refund_reason' ) ? get_post_meta( $this->get_id(), '_refund_reason', true ) : absint( $post_object->post_excerpt ) );
		}
	}

	/**
	 * Update data in the database.
	 * @since 2.7.0
	 */
	public function update() {
		parent::update();

		// Store additonal order data
		$this->update_post_meta( '_refund_amount', $this->get_refund_amount() );
		$this->update_post_meta( '_refunded_by', $this->get_refunded_by() );
		$this->update_post_meta( '_refund_reason', $this->get_refund_reason() );
	}

	/**
	 * Get internal type (post type.)
	 * @return string
	 */
	public function get_type() {
		return 'shop_order_refund';
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
		$this->_data['refund_amount'] = wc_format_decimal( $value );
	}

	/**
	 * Get refunded amount.
	 * @since 2.2
	 * @return int|float
	 */
	public function get_refund_amount() {
		return apply_filters( 'woocommerce_refund_amount', (double) $this->_data['refund_amount'], $this );
	}

	/**
	 * Get formatted refunded amount.
	 * @since 2.4
	 * @return string
	 */
	public function get_formatted_refund_amount() {
		return apply_filters( 'woocommerce_formatted_refund_amount', wc_price( $this->get_refund_amount(), array( 'currency' => $this->get_currency() ) ), $this );
	}

	/**
	 * Set refund reason.
	 * @param string $value
	 */
	public function set_refund_reason( $value ) {
		$this->_data['refund_reason'] = $value;
	}

	/**
	 * Get refund reason.
	 * @since 2.2
	 * @return int|float
	 */
	public function get_refund_reason() {
		return apply_filters( 'woocommerce_refund_reason', $this->_data['refund_reason'], $this );
	}

	/**
	 * Set refunded by.
	 * @param int $value
	 */
	public function set_refunded_by( $value ) {
		$this->_data['refunded_by'] = absint( $value );
	}

	/**
	 * Get ID of user who did the refund.
	 * @since 2.7
	 * @return int
	 */
	public function get_refunded_by() {
		return absint( $this->_data['refunded_by'] );
	}

	/**
     * Magic __get method for backwards compatibility.
     * @param string $key
     * @return mixed
     */
    public function __get( $key ) {
		_doing_it_wrong( $key, 'Refund properties should not be accessed directly.', '2.7' );

        /**
         * Maps legacy vars to new getters.
         */
        if ( 'reason' === $key ) {
            return $this->get_refund_reason();
		} elseif ( 'refund_amount' === $key ) {
            return $this->get_refund_amount();
		}
		return parent::__get( $key );
	}

	/**
     * Gets an refund from the database.
     * @deprecated 2.7
     * @param int $id (default: 0).
     * @return bool
     */
    public function get_refund( $id = 0 ) {
        _deprecated_function( 'get_refund', '2.7', 'read' );
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
