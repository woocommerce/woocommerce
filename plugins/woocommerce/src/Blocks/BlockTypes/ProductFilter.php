<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\QueryFilters;
use Automattic\WooCommerce\Blocks\Package;
use WP_HTML_Tag_Processor;

/**
 * Product Filter Block.
 */
final class ProductFilter extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		global $pagenow;
		parent::enqueue_data( $attributes );

		$this->asset_data_registry->add( 'isBlockTheme', wc_current_theme_is_fse_theme() );
		$this->asset_data_registry->add( 'isProductArchive', is_shop() || is_product_taxonomy() );
		$this->asset_data_registry->add( 'isSiteEditor', 'site-editor.php' === $pagenow );
		$this->asset_data_registry->add( 'isWidgetEditor', 'widgets.php' === $pagenow || 'customize.php' === $pagenow );
	}

	/**
	 * Check array for checked item.
	 *
	 * @param array $items Items to check.
	 */
	private function hasSelectedFilter( $items ) {
		foreach ( $items as $key => $value ) {
			if ( 'checked' === $key && true === $value ) {
				return true;
			}

			if ( is_array( $value ) && $this->hasSelectedFilter( $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( is_admin() ) {
			return $content;
		}

		$tags                = new WP_HTML_Tag_Processor( $content );
		$has_selected_filter = false;

		while ( $tags->next_tag( 'div' ) ) {
			$items = $tags->get_attribute( 'data-wc-context' ) ? json_decode( $tags->get_attribute( 'data-wc-context' ), true ) : null;

			// For checked box filters.
			if ( $items && array_key_exists( 'items', $items ) ) {
				$has_selected_filter = $this->hasSelectedFilter( $items['items'] );
				break;
			}

			// For price range filter.
			if ( $items && array_key_exists( 'minPrice', $items ) ) {
				if ( $items['minPrice'] > $items['minRange'] || $items['maxPrice'] < $items['maxRange'] ) {
					$has_selected_filter = true;
					break;
				}
			}

			// For dropdown filters.
			if ( $items && array_key_exists( 'selectedItems', $items ) ) {
				if ( count( $items['selectedItems'] ) > 0 ) {
					$has_selected_filter = true;
					break;
				}
			}
		}

		$attributes_data = array(
			'data-wc-interactive' => wp_json_encode( array( 'namespace' => $this->get_full_block_name() ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
			'data-wc-context'     => wp_json_encode( array( 'hasSelectedFilter' => $has_selected_filter ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
			'class'               => 'wc-block-product-filters',
		);

		if ( ! isset( $block->context['queryId'] ) ) {
			$attributes_data['data-wc-navigation-id'] = $this->generate_navigation_id( $block );
		}

		$tags = new WP_HTML_Tag_Processor( $content );

		while ( $tags->next_tag( 'div' ) ) {
			if ( 'yes' === $tags->get_attribute( 'data-has-filter' ) ) {
				return sprintf(
					'<nav %1$s>%2$s</nav>',
					get_block_wrapper_attributes( $attributes_data ),
					$content
				);
			}
		}

		return sprintf(
			'<nav %1$s></nav>',
			get_block_wrapper_attributes( $attributes_data ),
		);
	}

	/**
	 * Generate a unique navigation ID for the block.
	 *
	 * @param mixed $block - Block instance.
	 * @return string - Unique navigation ID.
	 */
	private function generate_navigation_id( $block ) {
		return sprintf(
			'wc-product-filter-%s',
			md5( wp_json_encode( $block->parsed_block['innerBlocks'] ) )
		);
	}
}
