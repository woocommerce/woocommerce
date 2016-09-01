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
	 * @param int|object|WC_Order $read Order to init.
	 */
	 public function __construct( $read = 0 ) {
		// Extend order data
		$this->_data = array_merge( $this->_data, array(
			'amount'      => '',
			'reason'      => '',
			'refunded_by' => 0,
		) );
		parent::__construct( $read );
	}

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_internal_meta_keys = array(
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
	 * Insert data into the database.
	 * @since 2.7.0
	 */
	public function create() {
		parent::create();

		// Store additonal order data
		if ( $this->get_id() ) {
			$this->update_post_meta( '_refund_amount', $this->get_amount() );
			$this->update_post_meta( '_refunded_by', $this->get_refunded_by() );
			$this->update_post_meta( '_refund_reason', $this->get_reason() );
		}
	}

	/**
	 * Read from the database.
	 * @since 2.7.0
	 * @param int $id ID of object to read.
	 */
	public function read( $id ) {
		parent::read( $id );

		if ( ! $this->get_id() ) {
			return;
		}

		$post_object = get_post( $id );

		$this->set_props( array(
			'amount'      => get_post_meta( $this->get_id(), '_refund_amount', true ),
			'refunded_by' => metadata_exists( 'post', $this->get_id(), '_refunded_by' ) ? get_post_meta( $this->get_id(), '_refunded_by', true ) : absint( $post_object->post_author ),
			'reason'      => metadata_exists( 'post', $this->get_id(), '_refund_reason' ) ? get_post_meta( $this->get_id(), '_refund_reason', true ) : $post_object->post_excerpt,
		) );
	}

	/**
	 * Update data in the database.
	 * @since 2.7.0
	 */
	public function update() {
		parent::update();

		// Store additonal order data
		$this->update_post_meta( '_refund_amount', $this->get_amount() );
		$this->update_post_meta( '_refunded_by', $this->get_refunded_by() );
		$this->update_post_meta( '_refund_reason', $this->get_reason() );
	}

	/**
	 * Get internal type (post type.)
	 * @return string
	 */
	public function get_type() {
		return 'shop_order_refund';
	}

	/**
	 * Get status - always completed for refunds.
	 * @return string
	 */
	public function get_status() {
		return 'completed';
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
	 * @throws WC_Data_Exception
	 */
	public function set_amount( $value ) {
		$this->_data['amount'] = wc_format_decimal( $value );
	}

	/**
	 * Get refunded amount.
	 * @return int|float
	 */
	public function get_amount() {
		return apply_filters( 'woocommerce_refund_amount', (double) $this->_data['amount'], $this );
	}

	/**
	 * Get formatted refunded amount.
	 * @since 2.4
	 * @return string
	 */
	public function get_formatted_refund_amount() {
		return apply_filters( 'woocommerce_formatted_refund_amount', wc_price( $this->get_amount(), array( 'currency' => $this->get_currency() ) ), $this );
	}

	/**
	 * Set refund reason.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_reason( $value ) {
		$this->_data['reason'] = $value;
	}

	/**
	 * Get refund reason.
	 * @since 2.2
	 * @return int|float
	 */
	public function get_reason() {
		return apply_filters( 'woocommerce_refund_reason', $this->_data['reason'], $this );
	}

	/**
	 * Set refunded by.
	 * @param int $value
	 * @throws WC_Data_Exception
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
			return $this->get_reason();
		} elseif ( 'refund_amount' === $key ) {
			return $this->get_amount();
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

	/**
	 * Get refund amount.
	 * @deprecated 2.7
	 * @return int|float
	 */
	public function get_refund_amount() {
		_deprecated_function( 'get_refund_amount', '2.7', 'get_amount' );
		return $this->get_amount();
	}

	/**
	 * Get refund reason.
	 * @deprecated 2.7
	 * @return int|float
	 */
	public function get_refund_reason() {
		_deprecated_function( 'get_refund_reason', '2.7', 'get_reason' );
		return $this->get_reason();
	}
}
