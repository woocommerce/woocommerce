<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tag Cloud Widget.
 *
 * @author   WooThemes
 * @category Widgets
 * @package  WooCommerce/Widgets
 * @version  2.3.0
 * @extends  WC_Widget
 */
class WC_Widget_Product_Tag_Cloud extends WC_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_product_tag_cloud';
		$this->widget_description = __( 'Your most used product tags in cloud format.', 'woocommerce' );
		$this->widget_id          = 'woocommerce_product_tag_cloud';
		$this->widget_name        = __( 'WooCommerce Product Tags', 'woocommerce' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Product Tags', 'woocommerce' ),
				'label' => __( 'Title', 'woocommerce' )
			)
		);

		parent::__construct();
	}

	/**
	 * Output widget.
	 *
	 * @see WP_Widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$current_taxonomy = $this->_get_current_taxonomy( $instance );

		if ( empty( $instance['title'] ) ) {
			$taxonomy = get_taxonomy( $current_taxonomy );
			$instance['title'] = $taxonomy->labels->name;
		}

		$this->widget_start( $args, $instance );

		echo '<div class="tagcloud">';

		wp_tag_cloud( apply_filters( 'woocommerce_product_tag_cloud_widget_args', array( 'taxonomy' => $current_taxonomy ) ) );

		echo '</div>';

		$this->widget_end( $args );
	}

	/**
	 * Return the taxonomy being displayed.
	 *
	 * @param  object $instance
	 * @return string
	 */
	public function _get_current_taxonomy( $instance ) {
		return 'product_tag';
	}
}
