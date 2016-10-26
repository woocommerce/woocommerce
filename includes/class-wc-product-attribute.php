<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents a product attribute.
 *
 * Attributes can be global (taxonomy based) or local to the product itself.
 * Uses ArrayAccess to be BW compatible with previous ways of reading attributes.
 *
 * @version     2.7.0
 * @since       2.7.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Product_Attribute implements ArrayAccess {

	/**
	 * Data array.
	 * @since 2.7.0
	 * @var array
	 */
	protected $data = array(
		'id'        => 0,
		'name'      => '',
		'options'   => '',
		'position'  => 0,
		'visible'   => false,
		'variation' => false,
	);

	/**
	 * offsetGet for ArrayAccess/Backwards compatibility.
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		switch ( $offset ) {
			case 'is_variation' :

				break;
			case 'is_visible' :

				break;
			case 'is_taxonomy' :

				break;
			case 'value' :

				break;
			default :
				if ( is_callable( array( $this, "get_$offset" ) ) ) {
					return $this->{"get_$offset"}();
				}
				break;
		}
		return '';
	}

	/**
	 * offsetSet for ArrayAccess/Backwards compatibility.
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		switch ( $offset ) {
			case 'is_variation' :

				break;
			case 'is_visible' :

				break;
			case 'is_taxonomy' :

				break;
			case 'value' :

				break;
			default :
				if ( is_callable( array( $this, "set_$offset" ) ) ) {
					return $this->{"set_$offset"}( $value );
				}
				break;
		}
	}

	public function offsetUnset( $offset ) {

	}

	/**
	 * offsetExists for ArrayAccess
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		return in_array( $offset, array_merge( array( 'is_variation', 'is_visible', 'is_taxonomy', 'value' ), array_keys( $this->data ) ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set ID (this is the attribute ID).
	 * @param int $value
	 */
	public function set_id( $value ) {
		$this->data['id'] = absint( $value );
	}

	/**
	 * Set name (this is the attribute name or taxonomy).
	 * @param int $value
	 */
	public function set_name( $value ) {
		$this->data['name'] = $value;
	}

	/**
	 * Set ID (this is the attribute ID).
	 * @param string|array $value
	 */
	public function set_options( $value ) {
		$this->data['options'] = $value;
	}

	/**
	 * Set position.
	 * @param int $value
	 */
	public function set_position( $value ) {
		$this->data['position'] = absint( $value );
	}

	/**
	 * Set if visible.
	 * @param bool $value
	 */
	public function set_visible( $value ) {
		$this->data['visible'] = wc_string_to_bool( $value );
	}

	/**
	 * Set if variation.
	 * @param bool $value
	 */
	public function set_variation( $value ) {
		$this->data['variation'] = wc_string_to_bool( $value );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the ID.
	 * @return int
	 */
	public function get_id() {
		return $this->data['id'];
	}

	/**
	 * Get name.
	 * @return int
	 */
	public function get_name() {
		return $this->data['name'];
	}

	/**
	 * Get options.
	 * @return string|array
	 */
	public function get_options() {
		return $this->data['options'];
	}

	/**
	 * Get position.
	 * @return int
	 */
	public function get_position() {
		return $this->data['position'];
	}

	/**
	 * Get if visible.
	 * @return bool
	 */
	public function get_visible() {
		return $this->data['visible'];
	}

	/**
	 * Get if variation.
	 * @return bool
	 */
	public function get_variation() {
		return $this->data['variation'];
	}
}
