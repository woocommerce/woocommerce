<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;


/**
 * ProductFiltersOverlay class.
 */
class ProductFiltersOverlay extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filters-overlay';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
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
		return $content;
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );

		$template_part_edit_uri = '';

		if (
			current_user_can( 'edit_theme_options' ) &&
			( wc_current_theme_is_fse_theme() || current_theme_supports( 'block-template-parts' ) )
		) {
			$theme_slug = BlockTemplateUtils::theme_has_template_part( 'product-filters-overlay' ) ? wp_get_theme()->get_stylesheet() : BlockTemplateUtils::PLUGIN_SLUG;

			$site_editor_uri = add_query_arg(
				array(
					'canvas' => 'edit',
					'path'   => '/template-parts/single',
				),
				admin_url( 'site-editor.php' )
			);

			$template_part_edit_uri = esc_url_raw(
				add_query_arg(
					array(
						'postId'   => sprintf( '%s//%s', $theme_slug, 'product-filters-overlay' ),
						'postType' => 'wp_template_part',
					),
					$site_editor_uri
				)
			);
		}

		$this->asset_data_registry->add(
			'templatePartProductFiltersOverlayEditUri',
			$template_part_edit_uri
		);
	}
}
