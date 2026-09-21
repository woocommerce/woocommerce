<?php
/**
 * WC_Address class.
 *
 * @package WooCommerce\Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Address class.
 */
class WC_Address {

	/**
	 * @var string
	 */
	protected $state;

	/**
	 * @param string $state
	 */
	public function set_state( $state ) {
		$this->state = $state;
	}

	/**
	 * @return string
	 */
	public function get_state() {
		return $this->state;
	}

	/**
	 * @param string $state
	 */
	public function set_state_french( $state ) {
		// Replace French accent mark (^) with a regular accent mark (é)
		$this->state = str_replace( '^', 'é', $state );
	}

	/**
	 * @return string
	 */
	public function get_state_french() {
		return $this->state;
	}

}