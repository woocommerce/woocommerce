<?php
/**
 * Dummy widget class extending WC_Widget.
 */
class Dummy_Widget extends WC_Widget {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'widget_dummy';
		$this->widget_description = __( 'A dummy widget', 'woocommerce' );
		$this->widget_id          = 'wc_dummy_widget';
		$this->widget_name        = __( 'Dummy Widget', 'woocommerce' );
		parent::__construct();
	}

	/**
	 * Output widget.
	 *
	 * @param mixed     $args Arguments.
	 * @param WP_Widget $instance Instance of WP_Widget.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		ob_start();
		echo 'Dummy';
		echo $this->cache_widget( $args, ob_get_clean() );
	}
}
