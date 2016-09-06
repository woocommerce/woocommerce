<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart Fees API.
 *
 * Fees are additional costs added to orders. Implemtns Iterator for backwards compatibility.
 *
 * @class 		WC_Cart_Fees
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart_Fees implements Iterator {

	/**
	 * An array of fee objects.
	 * @var object[]
	 */
	private $fees = array();

	/**
	 * Iterator Position.
	 * @var integer
	 */
	private $position = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->position = 0;
	}

	/**
	 * Rewind Iterator.
	 */
	public function rewind() {
		$this->position = 0;
	}

	/**
	 * Current Iterator.
	 */
	public function current() {
		return $this->fees[ $this->position ];
	}

	/**
	 * Key Iterator.
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * Next Iterator.
	 */
	public function next() {
		++$this->position;
	}

	/**
	 * Valid Iterator.
	 */
	public function valid() {
		return isset( $this->fees[ $this->position ] );
	}

	/**
	 * Generate a unique ID for the fee being added.
	 *
	 * @param string $fee Fee name.
	 * @return string fee key.
	 */
	public function generate_key( $fee ) {
		return sanitize_title( $fee );
	}

	/**
	 * Get fees.
	 * @return array
	 */
	public function get_fees() {
		return $this->fees;
	}

	/**
	 * Set fees.
	 * @param object[] $value Array of fees.
	 */
	public function set_fees( $value ) {
		$this->fees = array_filter( (array) $value );
	}
}
