<?php
/**
 * Layered Navigation Filters Widget.
 *
 * @package WooCommerce\Widgets
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Widget layered nav filters.
 */
class WC_Widget_Layered_Nav_Filters extends WC_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_layered_nav_filters';
		$this->widget_description = __( 'Display a list of active product filters.', 'woocommerce' );
		$this->widget_id          = 'woocommerce_layered_nav_filters';
		$this->widget_name        = __( 'Active Product Filters', 'woocommerce' );
		$this->settings           = array(
			'title' => array(
				'type'  => 'text',
				'std'   => __( 'Active filters', 'woocommerce' ),
				'label' => __( 'Title', 'woocommerce' ),
			),
		);

		parent::__construct();
	}

	/**
	 * Output widget.
	 *
	 * @see WP_Widget
	 * @param array $args     Arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		if ( ! is_shop() && ! is_product_taxonomy() ) {
			return;
		}

		$_chosen_attributes = WC_Query::get_layered_nav_chosen_attributes();
		$min_price          = isset( $_GET['min_price'] ) ? wc_clean( wp_unslash( $_GET['min_price'] ) ) : 0; // phpcs:ignore Standard.Category.SniffName.ErrorCode
		$max_price          = isset( $_GET['max_price'] ) ? wc_clean( wp_unslash( $_GET['max_price'] ) ) : 0; // phpcs:ignore Standard.Category.SniffName.ErrorCode
		$rating_filter      = isset( $_GET['rating_filter'] ) ? array_filter( array_map( 'absint', explode( ',', wp_unslash( $_GET['rating_filter'] ) ) ) ) : array(); // phpcs:ignore Standard.Category.SniffName.ErrorCode
		$base_link          = $this->get_current_page_url();
		$filters            = array();

		// Attributes.
		if ( ! empty( $_chosen_attributes ) ) {
			foreach ( $_chosen_attributes as $taxonomy => $data ) {
				foreach ( $data['terms'] as $term_slug ) {
					$term = get_term_by( 'slug', $term_slug, $taxonomy );
					if ( ! $term ) {
						continue;
					}

					$filter_name    = 'filter_' . wc_attribute_taxonomy_slug( $taxonomy );
					$current_filter = isset( $_GET[ $filter_name ] ) ? explode( ',', wc_clean( wp_unslash( $_GET[ $filter_name ] ) ) ) : array(); // phpcs:ignore Standard.Category.SniffName.ErrorCode
					$current_filter = array_map( 'sanitize_title', $current_filter );
					$new_filter     = array_diff( $current_filter, array( $term_slug ) );

					$link = remove_query_arg( array( 'add-to-cart', $filter_name ), $base_link );

					if ( count( $new_filter ) > 0 ) {
						$link = add_query_arg( $filter_name, implode( ',', $new_filter ), $link );
					}

					$filter_classes = array( 'chosen', 'chosen-' . sanitize_html_class( str_replace( 'pa_', '', $taxonomy ) ), 'chosen-' . sanitize_html_class( str_replace( 'pa_', '', $taxonomy ) . '-' . $term_slug ) );

					$filters[] = array(
						'label' => $term->name,
						'link'  => $link,
						'class' => implode( ' ', $filter_classes ),
					);
				}
			}
		}

		if ( $min_price ) {
			$filters[] = array(
				/* translators: %s: minimum price */
				'label' => sprintf( __( 'Min %s', 'woocommerce' ), wc_price( $min_price ) ),
				'link'  => remove_query_arg( 'min_price', $base_link ),
				'class' => 'chosen',
			);
		}

		if ( $max_price ) {
			$filters[] = array(
				/* translators: %s: maximum price */
				'label' => sprintf( __( 'Max %s', 'woocommerce' ), wc_price( $max_price ) ),
				'link'  => remove_query_arg( 'max_price', $base_link ),
				'class' => 'chosen',
			);
		}

		if ( ! empty( $rating_filter ) ) {
			foreach ( $rating_filter as $rating ) {
				$link_ratings = implode( ',', array_diff( $rating_filter, array( $rating ) ) );
				$link         = $link_ratings ? add_query_arg( 'rating_filter', $link_ratings ) : remove_query_arg( 'rating_filter', $base_link );

				$filters[] = array(
					/* translators: %s: rating */
					'label' => sprintf( esc_html__( 'Rated %s out of 5', 'woocommerce' ), esc_html( $rating ) ),
					'link'  => $link,
					'class' => 'chosen',
				);
			}
		}

		$filters = apply_filters( 'woocommerce_layered_nav_filters', $filters );

		if ( 0 === count( $filters ) ) {
			return;
		}

		$this->widget_start( $args, $instance );

		wc_get_template(
			'content-widget-layered-nav-filters.php',
			array(
				'filters' => $filters,
			)
		);

		$this->widget_end( $args );
	}
}
