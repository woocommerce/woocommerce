<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use WP_HTML_Tag_Processor;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductDetails class.
 */
class ProductDetails extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-details';

	/**
	 * It isn't necessary register block assets because it is a server side block.
	 */
	protected function register_block_type_assets() {

		// Register block styles.
		register_block_style(
			'woocommerce/product-details',
			array(
				'name'       => 'classic',
				'label'      => __( 'Classic', 'woocommerce' ),
				'is_default' => true,
			)
		);

		register_block_style(
			'woocommerce/product-details',
			array(
				'name'  => 'minimal',
				'label' => __( 'Minimal', 'woocommerce' ),
			)
		);

		return null;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$hide_tab_title = isset( $attributes['hideTabTitle'] ) ? $attributes['hideTabTitle'] : false;

		if ( $hide_tab_title ) {
			add_filter( 'woocommerce_product_description_heading', '__return_empty_string' );
			add_filter( 'woocommerce_product_additional_information_heading', '__return_empty_string' );
			add_filter( 'woocommerce_reviews_title', '__return_empty_string' );
		}

		$tabs = $this->render_tabs();

		if ( $hide_tab_title ) {
			remove_filter( 'woocommerce_product_description_heading', '__return_empty_string' );
			remove_filter( 'woocommerce_product_additional_information_heading', '__return_empty_string' );
			remove_filter( 'woocommerce_reviews_title', '__return_empty_string' );

			// Remove the first `h2` of every `.wc-tab`. This is required for the Reviews tabs when there are no reviews and for plugin tabs.
			$tabs_html = new WP_HTML_Tag_Processor( $tabs );
			while ( $tabs_html->next_tag( array( 'class_name' => 'wc-tab' ) ) ) {
				if ( $tabs_html->next_tag( 'h2' ) ) {
					$tabs_html->set_attribute( 'hidden', 'true' );
				}
			}
			$tabs = $tabs_html->get_updated_html();
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );

		return sprintf(
			'<div class="wp-block-woocommerce-product-details %1$s">
				<div style="%2$s">
					%3$s
				</div>
			</div>',
			esc_attr( $classes_and_styles['classes'] ),
			esc_attr( $classes_and_styles['styles'] ),
			$tabs
		);
	}

	/**
	 * Gets the tabs with their content to be rendered by the block.
	 *
	 * @return string The tabs html to be rendered by the block
	 */
	protected function render_tabs() {
		ob_start();
		rewind_posts();
		while ( have_posts() ) {
			the_post();
			woocommerce_output_product_data_tabs();
		}

		$tabs = ob_get_clean();

		return $tabs;
	}
}
