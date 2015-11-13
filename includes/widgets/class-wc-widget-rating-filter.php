<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rating Filter Widget and related functions.
 *
 *
 * @author   WooThemes
 * @category Widgets
 * @package  WooCommerce/Widgets
 * @version  2.3.0
 * @extends  WC_Widget
 */
class WC_Widget_Rating_Filter extends WC_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_rating_filter';
		$this->widget_description = __( 'Shows a rating filter in a widget which lets you narrow down the list of shown products when viewing product categories.', 'woocommerce' );
		$this->widget_id          = 'woocommerce_rating_filter';
		$this->widget_name        = __( 'WooCommerce Rating Filter', 'woocommerce' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Filter by rating', 'woocommerce' ),
				'label' => __( 'Title', 'woocommerce' )
			)
		);

		parent::__construct();
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		global $_chosen_attributes, $wpdb, $wp;

		if ( ! is_post_type_archive( 'product' ) && ! is_tax( get_object_taxonomies( 'product' ) ) ) {
			return;
		}

		if ( sizeof( WC()->query->unfiltered_product_ids ) == 0 ) {
			return; // None shown - return
		}

		$min_rating = isset( $_GET['min_rating'] ) ? esc_attr( $_GET['min_rating'] ) : '';

		$this->widget_start( $args, $instance );

		echo '<ul>';

		for ( $rating = 4; $rating >= 1; $rating-- ) {
			
			// Base Link decided by current page
			if ( defined( 'SHOP_IS_ON_FRONT' ) ) {
				$link = home_url();
			} elseif ( is_post_type_archive( 'product' ) || is_page( wc_get_page_id('shop') ) ) {
				$link = get_post_type_archive_link( 'product' );
			} else {
				$link = get_term_link( get_query_var('term'), get_query_var('taxonomy') );
			}

			// All current filters
			if ( $_chosen_attributes ) {
				foreach ( $_chosen_attributes as $attribute => $data ) {
					$taxonomy_filter = 'filter_' . str_replace( 'pa_', '', $attribute );

					$link = add_query_arg( $taxonomy_filter, implode( ',', $data['terms'] ), $link );

					if ( 'or' == $data['query_type'] ) {
						$link = add_query_arg( str_replace( 'pa_', 'query_type_', $attribute ), 'or', $link );
					}
				}
			}

			// Min/Max
			if ( isset( $_GET['min_price'] ) ) {
				$link = add_query_arg( 'min_price', $_GET['min_price'], $link );
			}

			if ( isset( $_GET['max_price'] ) ) {
				$link = add_query_arg( 'max_price', $_GET['max_price'], $link );
			}

			// Orderby
			if ( isset( $_GET['orderby'] ) ) {
				$link = add_query_arg( 'orderby', $_GET['orderby'], $link );
			}

			// Current Filter - this widget
			if( isset( $_GET['min_rating'] ) && absint( $_GET['min_rating'] ) === $rating) {
				$class = 'class="chosen"';
			} else {
				$class 	= '';
				$link = add_query_arg( 'min_rating', $rating, $link );
			}

			// Search Arg
			if ( get_search_query() ) {
				$link = add_query_arg( 's', get_search_query(), $link );
			}

			// Post Type Arg
			if ( isset( $_GET['post_type'] ) ) {
				$link = add_query_arg( 'post_type', $_GET['post_type'], $link );
			}

			echo '<li ' . $class . '>';

			echo '<a href="' . esc_url( apply_filters( 'woocommerce_rating_filter_link', $link ) ) . '">';

			echo '<div class="star-rating" title="' . esc_attr( sprintf( __( 'Rated %s and above', 'woocommerce' ), $rating ) ). '">
					<span style="width:' . esc_attr ( ( $rating / 5 ) * 100 ) . '%">' . sprintf( __( 'Rated %s and above', 'woocommerce'), $rating ) . '</span>
				</div>';

			echo __( 'and above', 'woocommerce' );

			echo '</a>';

			echo '</li>';
		}

		echo '</ul>';

		$this->widget_end( $args );
	}
}