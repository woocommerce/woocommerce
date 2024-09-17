<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * ProductFilters class.
 */
class ProductFilters extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filters';

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		add_filter( 'block_type_metadata_settings', array( $this, 'add_block_type_metadata_settings' ), 10, 2 );
		parent::initialize();
	}

	/**
	 * Register the context.
	 *
	 * @return string[]
	 */
	protected function get_block_type_uses_context() {
		return array( 'postId' );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = array() ) {
		global $pagenow;
		parent::enqueue_data( $attributes );

		$this->asset_data_registry->add( 'isBlockTheme', wc_current_theme_is_fse_theme() );
		$this->asset_data_registry->add( 'isProductArchive', is_shop() || is_product_taxonomy() );
		$this->asset_data_registry->add( 'isSiteEditor', 'site-editor.php' === $pagenow );
		$this->asset_data_registry->add( 'isWidgetEditor', 'widgets.php' === $pagenow || 'customize.php' === $pagenow );
	}

	/**
	 * Return the dialog content.
	 *
	 * @return string
	 */
	protected function render_dialog() {
		$html = strtr(
			'<dialog
				hidden
				role="dialog"
				aria-modal="true"
				data-wc-bind--hidden="!state.isDialogOpen"
				data-wc-class--wc-block-product-filters--dialog-open="state.isDialogOpen"
				data-wc-class--wc-block-product-filters--with-admin-bar="context.hasPageWithWordPressAdminBar"
				data-wc-interactive="{{namespace}}"
			>
				{{html}}
			</dialog>',
			array(
				'{{html}}'      => do_blocks( BlockTemplateUtils::get_template_part( 'product-filters-overlay' ) ),
				'{{namespace}}' => wp_json_encode( array( 'namespace' => 'woocommerce/product-filters' ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
			)
		);

		return $html;
	}

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		foreach ( $block->parsed_block['innerBlocks'] as $inner_block ) {
			if ( 'mobile' === $attributes['overlay'] && wp_is_mobile() && 'core/template-part' === $inner_block['blockName'] && 'product-filter-blocks' === $inner_block['attrs']['slug'] ) {
				continue;
			}

			if ( 'mobile' === $attributes['overlay'] && ! wp_is_mobile() && 'woocommerce/product-filters-overlay-navigation' === $inner_block['blockName'] ) {
				continue;
			}

			$content .= render_block( $inner_block );// ( new \WP_Block( $inner_block, array() ) )->render();
		}

		if ( 'always' === $attributes['overlay'] || ( 'mobile' === $attributes['overlay'] && wp_is_mobile() ) ) {
			$content .= $this->render_dialog();
		}

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes(
				array(
					'data-wc-context'       => wp_json_encode(
						array(
							'isDialogOpen'                 => false,
							'hasPageWithWordPressAdminBar' => false,
						),
						JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
					),
					'data-wc-interactive'   => wp_json_encode( array( 'namespace' => 'woocommerce/' . $this->block_name ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
					'data-wc-navigation-id' => $this->generate_navigation_id( $block ),
				),
			),
			$content
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
			'wc-product-filters-%s',
			md5( wp_json_encode( $block->parsed_block['innerBlocks'] ) )
		);
	}

	/**
	 * Skip default rendering routine for inner blocks.
	 *
	 * @param array $settings Array of determined settings for registering a block type.
	 * @param array $metadata Metadata provided for registering a block type.
	 * @return array
	 */
	public function add_block_type_metadata_settings( $settings, $metadata ) {
		if ( ! empty( $metadata['name'] ) && "woocommerce/{$this->block_name}" === $metadata['name'] ) {
			$settings['skip_inner_blocks'] = true;
		}
		return $settings;
	}
}
