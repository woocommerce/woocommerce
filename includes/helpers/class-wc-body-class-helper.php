<?php

class WC_Body_Class_Helper extends WC_Helper {
	private $_body_classes = array();
	
	/**
	 * Add a class to the webpage body.
	 *
	 * @access public
	 * @param string $class
	 * @return void
	 */
	public function add_body_class( $class ) {
		$this->_body_classes[] = sanitize_html_class( strtolower($class) );
	}

	/**
	 * Output classes on the body tag.
	 *
	 * @access public
	 * @param mixed $classes
	 * @return array
	 */
	public function output_body_class( $classes ) {
		if ( sizeof( $this->_body_classes ) > 0 ) $classes = array_merge( $classes, $this->_body_classes );

		if ( is_singular('product') ) {
			$key = array_search( 'singular', $classes );
			if ( $key !== false ) unset( $classes[$key] );
		}

		return $classes;
	}
}

return new WC_Body_Class_Helper();