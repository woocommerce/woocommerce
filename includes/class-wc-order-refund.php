<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order refund. Refunds are based on orders (essentially negative orders) and
 * contain much of the same data.
 *
 * @version  3.0.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Refund extends WC_Abstract_Order {

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
	protected $data_store_name = 'order-refund';

	/**
	 * This is the name of this object type.
	 * @var string
	 */
	protected $object_type = 'order_refund';

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'amount'      => '',
		'reason'      => '',
		'refunded_by' => 0,
	);

	/**
	 * Get internal type (post type.)
	 * @return string
	 */
	public function get_type() {
		return 'shop_order_refund';
	}

	/**
	 * Get status - always completed for refunds.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return 'completed';
	}

	/**
	 * Get a title for the new post type.
	 */
	public function get_post_title() {
		// @codingStandardsIgnoreStart
		return sprintf( __( 'Refund &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) );
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Get refunded amount.
	 *
	 * @param  string $context
	 * @return int|float
	 */
	public function get_amount( $context = 'view' ) {
		return $this->get_prop( 'amount', $context );
	}

	/**
	 * Get refund reason.
	 *
	 * @since 2.2
	 * @param  string $context
	 * @return int|float
	 */
	public function get_reason( $context = 'view' ) {
		return $this->get_prop( 'reason', $context );
	}

	/**
	 * Get ID of user who did the refund.
	 *
	 * @since 3.0
	 * @param  string $context
	 * @return int
	 */
	public function get_refunded_by( $context = 'view' ) {
		return $this->get_prop( 'refunded_by', $context );

	}

	/**
	 * Get formatted refunded amount.
	 *
	 * @since 2.4
	 * @return string
	 */
	public function get_formatted_refund_amount() {
		return apply_filters( 'woocommerce_formatted_refund_amount', wc_price( $this->get_amount(), array( 'currency' => $this->get_currency() ) ), $this );
	}

	/**
	 * Set refunded amount.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_amount( $value ) {
		$this->set_prop( 'amount', wc_format_decimal( $value ) );
	}

	/**
	 * Set refund reason.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_reason( $value ) {
		$this->set_prop( 'reason', $value );
	}

	/**
	 * Set refunded by.
	 *
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_refunded_by( $value ) {
		$this->set_prop( 'refunded_by', absint( $value ) );
	}

	/**
	 * Magic __get method for backwards compatibility.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		wc_doing_it_wrong( $key, 'Refund properties should not be accessed directly.', '3.0' );
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
	 * @deprecated 3.0
	 * @param int $id (default: 0).
	 * @return bool
	 */
	public function get_refund( $id = 0 ) {
		wc_deprecated_function( 'get_refund', '3.0', 'read' );
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
	 * @deprecated 3.0
	 * @return int|float
	 */
	public function get_refund_amount() {
		wc_deprecated_function( 'get_refund_amount', '3.0', 'get_amount' );
		return $this->get_amount();
	}

	/**
	 * Get refund reason.
	 * @deprecated 3.0
	 * @return int|float
	 */
	public function get_refund_reason() {
		wc_deprecated_function( 'get_refund_reason', '3.0', 'get_reason' );
		return $this->get_reason();
	}
}
