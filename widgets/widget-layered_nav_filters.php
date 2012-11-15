<?php
/**
 * Layered Navigation Fitlers Widget
 *
 * @author 		WooThemes
 * @category 	Widgets
 * @package 	WooCommerce/Widgets
 * @version 	1.7.0
 * @extends 	WP_Widget
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WooCommerce_Widget_Layered_Nav_Filters extends WP_Widget {

	var $woo_widget_cssclass;
	var $woo_widget_description;
	var $woo_widget_idbase;
	var $woo_widget_name;

	/**
	 * constructor
	 *
	 * @access public
	 * @return void
	 */
	function WooCommerce_Widget_Layered_Nav_Filters() {

		/* Widget variable settings. */
		$this->woo_widget_cssclass 		= 'woocommerce widget_layered_nav_filters';
		$this->woo_widget_description	= __( 'Shows active layered nav filters so users can see and deactivate them.', 'woocommerce' );
		$this->woo_widget_idbase 		= 'woocommerce_layered_nav_filters';
		$this->woo_widget_name 			= __( 'WooCommerce Layered Nav Filters', 'woocommerce' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Create the widget. */
		$this->WP_Widget( 'woocommerce_layered_nav_filters', $this->woo_widget_name, $widget_ops );
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	function widget( $args, $instance ) {
		global $_chosen_attributes, $woocommerce, $_attributes_array;
		
		extract( $args );

		if ( ! is_post_type_archive( 'product' ) && ! is_tax( array_merge( $_attributes_array, array( 'product_cat', 'product_tag' ) ) ) ) 
			return;
			
		$current_term 	= $_attributes_array && is_tax( $_attributes_array ) ? get_queried_object()->term_id : '';
		$current_tax 	= $_attributes_array && is_tax( $_attributes_array ) ? get_queried_object()->taxonomy : '';
		
		$title = __( 'Active filters', 'woocommerce' );
		//$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base );
		
		// Price
		$post_min = isset( $woocommerce->session->min_price ) ? $woocommerce->session->min_price : 0;
		$post_max = isset( $woocommerce->session->max_price ) ? $woocommerce->session->max_price : 0;
			
		if ( count( $_chosen_attributes ) > 0 || $post_min > 0 || $post_max > 0 ) {
			
			echo $before_widget . $before_title . $title . $after_title;

			echo "<ul>";
			
			// Attributes
			foreach ( $_chosen_attributes as $taxonomy => $data ) {
					
				foreach ( $data['terms'] as $term_id ) {
					$term 				= get_term( $term_id, $taxonomy );
					$taxonomy_filter 	= str_replace( 'pa_', '', $taxonomy );
					$current_filter 	= ! empty( $_GET[ 'filter_' . $taxonomy_filter ] ) ? $_GET[ 'filter_' . $taxonomy_filter ] : '';
					$new_filter			= array_map( 'absint', explode( ',', $current_filter ) );
					$new_filter			= array_diff( $new_filter, array( $term_id ) );
					
					$link = remove_query_arg( 'filter_' . $taxonomy_filter );
					
					if ( sizeof( $new_filter ) > 0 )
						$link = add_query_arg( 'filter_' . $taxonomy_filter, implode( ',', $new_filter ), $link );
					
					echo '<li><a title="' . __( 'Remove filter', 'woocommerce' ) . '" href="' . $link . '">' . $term->name . '</a></li>';
				}
			}
			
			if ( $post_min ) {
				$link = remove_query_arg( 'min_price' );
				echo '<li><a title="' . __( 'Remove filter', 'woocommerce' ) . '" href="' . $link . '">' . __( 'Min', 'woocommerce' ) . ' ' . woocommerce_price( $post_min ) . '</a></li>';
			}
				
			if ( $post_max ) {	
				$link = remove_query_arg( 'max_price' );
				echo '<li><a title="' . __( 'Remove filter', 'woocommerce' ) . '" href="' . $link . '">' . __( 'Max', 'woocommerce' ) . ' ' . woocommerce_price( $post_max ) . '</a></li>';
			}
			
			echo "</ul>";

			echo $after_widget;
		}
	}
}