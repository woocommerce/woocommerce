<?php

class WC_Meta_Data {
	public $id;
	public $key;
	public $value;

	protected $previous_value = array();

	protected $properties = array( 'id', 'key', 'value' );

	public function __construct( Array $meta ) {
		foreach ( $meta as $key => $value ) {
			if ( in_array( $key, $this->properties ) ) {
				$this->$key = $value;
			}
		}
		$this->apply_changes();
	}

	/**
	 * Merge changes with data and clear.
	 *
	 */
	public function apply_changes() {
		foreach ( $this->properties as $property ) {
			$this->previous_value[ $property ] = $this->$property;
		}
	}

	/**
	 * Return data changes only.
	 *
	 * @return array
	 */
	public function get_changes() {
		$changes = array();
		foreach ( array( 'id', 'key', 'value' ) as $property ) {
			if ( array_key_exists( $property, $this->previous_value ) && 
					$this->previous_value[ $property ] !== $this->$property ) {
				$changes[ $property ] = $this->$property;
			}
		}

		return $changes;
	}

}
