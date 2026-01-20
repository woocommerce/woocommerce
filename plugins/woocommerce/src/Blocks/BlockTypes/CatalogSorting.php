<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * CatalogSorting class.
 */
class CatalogSorting extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'catalog-sorting';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string | void Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		// Check if we should display the sorting block.
		if ( ! wc_get_loop_prop( 'is_paginated' ) || ! woocommerce_products_will_display() ) {
			return;
		}

		// Get sorting options and current orderby value.
		$catalog_orderby_options = $this->get_catalog_orderby_options( $attributes );
		$orderby                 = $this->get_current_orderby();

		if ( empty( $catalog_orderby_options ) ) {
			return;
		}

		$use_label = $attributes['useLabel'] ?? false;
		$id_suffix = wp_unique_id( 'orderby_' );

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'               => implode(
					' ',
					array_filter(
						[
							'woocommerce wc-block-catalog-sorting',
							esc_attr( $classes_and_styles['classes'] ),
						]
					)
				),
				'style'               => esc_attr( $classes_and_styles['styles'] ?? '' ),
				'data-wp-interactive' => 'woocommerce/catalog-sorting',
				'data-wp-context'     => wp_json_encode(
					array(
						'currentOrderBy' => $orderby,
						'options'        => $catalog_orderby_options,
					)
				),
			)
		);

		// Build select element with Interactivity API directives.
		$select_html  = '';
		$select_html .= $use_label ? sprintf(
			'<label for="%s">%s</label>',
			esc_attr( $id_suffix ),
			esc_html__( 'Sort by', 'woocommerce' )
		) : '';

		$select_html .= sprintf(
			'<select name="orderby" class="orderby" id="%s" data-wp-on--change="actions.handleSortChange"%s>',
			esc_attr( $id_suffix ),
			! $use_label ? ' aria-label="' . esc_attr__( 'Shop order', 'woocommerce' ) . '"' : ''
		);

		foreach ( $catalog_orderby_options as $id => $name ) {
			$select_html .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $id ),
				selected( $orderby, $id, false ),
				esc_html( $name )
			);
		}

		$select_html .= '</select>';

		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			$select_html
		);
	}

	/**
	 * Get catalog orderby options.
	 *
	 * @param array $attributes Block attributes.
	 * @return array Orderby options.
	 */
	private function get_catalog_orderby_options( $attributes ) {
		$use_label = $attributes['useLabel'] ?? false;

		if ( $use_label ) {
			$catalog_orderby_options = array(
				'menu_order' => __( 'Default', 'woocommerce' ),
				'popularity' => __( 'Popularity', 'woocommerce' ),
				'rating'     => __( 'Average rating', 'woocommerce' ),
				'date'       => __( 'Latest', 'woocommerce' ),
				'price'      => __( 'Price: low to high', 'woocommerce' ),
				'price-desc' => __( 'Price: high to low', 'woocommerce' ),
			);
		} else {
			$catalog_orderby_options = array(
				'menu_order' => __( 'Default sorting', 'woocommerce' ),
				'popularity' => __( 'Sort by popularity', 'woocommerce' ),
				'rating'     => __( 'Sort by average rating', 'woocommerce' ),
				'date'       => __( 'Sort by latest', 'woocommerce' ),
				'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
				'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
			);
		}

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$catalog_orderby_options = apply_filters( 'woocommerce_catalog_orderby', $catalog_orderby_options );

		// Remove 'menu_order' if not set as default in settings.
		$default_orderby = wc_get_loop_prop( 'is_search' ) ? 'relevance' : get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
		if ( 'menu_order' !== $default_orderby ) {
			unset( $catalog_orderby_options['menu_order'] );
		}

		// Add 'relevance' option for search results.
		if ( wc_get_loop_prop( 'is_search' ) ) {
			$catalog_orderby_options = array_merge(
				array( 'relevance' => __( 'Relevance', 'woocommerce' ) ),
				$catalog_orderby_options
			);
			unset( $catalog_orderby_options['menu_order'] );
		}

		// Remove 'rating' option if reviews are disabled.
		if ( ! wc_reviews_enabled() ) {
			unset( $catalog_orderby_options['rating'] );
		}

		return $catalog_orderby_options;
	}

	/**
	 * Get current orderby value.
	 *
	 * @return string Current orderby value.
	 */
	private function get_current_orderby() {
		$default_orderby = wc_get_loop_prop( 'is_search' ) ? 'relevance' : get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['orderby'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return wc_clean( wp_unslash( $_GET['orderby'] ) );
		}

		return $default_orderby;
	}
}
