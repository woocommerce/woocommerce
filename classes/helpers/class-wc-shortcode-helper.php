<?php

return new WC_Shortcode_Helper();

class WC_Shortcode_Helper extends WC_Helper {
	/**
	 * Shortcode Wrapper
	 *
	 * @access public
	 * @param mixed $function
	 * @param array $atts (default: array())
	 * @return string
	 */
	public function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class' => 'woocommerce',
			'before' => null,
			'after' => null
		)
	){
		ob_start();

		$before 	= empty( $wrapper['before'] ) ? '<div class="' . $wrapper['class'] . '">' : $wrapper['before'];
		$after 		= empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];

		echo $before;
		call_user_func( $function, $atts );
		echo $after;

		return ob_get_clean();
	}
}