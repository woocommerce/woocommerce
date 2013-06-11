<?php

return new WC_Inline_Javascript_Helper();

class WC_Inline_Javascript_Helper extends WC_Helper {
	protected $_inline_js;
	
	/**
	 * Add some JavaScript inline to be output in the footer.
	 *
	 * @access public
	 * @param string $code
	 * @return void
	 */
	public function add_inline_js( $code ) {
		$this->_inline_js .= "\n" . $code . "\n";
	}

	/**
	 * Output any queued inline JS.
	 *
	 * @access public
	 * @return void
	 */
	public function output_inline_js() {
		if ( $this->_inline_js ) {

			echo "<!-- WooCommerce JavaScript-->\n<script type=\"text/javascript\">\njQuery(document).ready(function($) {";

			// Sanitize
			$this->_inline_js = wp_check_invalid_utf8( $this->_inline_js );
			$this->_inline_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $this->_inline_js );
			$this->_inline_js = str_replace( "\r", '', $this->_inline_js );

			// Output
			echo $this->_inline_js;

			echo "});\n</script>\n";

			$this->_inline_js = '';
		}
	}
}