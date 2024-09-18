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
		$template_part = BlockTemplateUtils::get_template_part( 'product-filters-overlay' );

		$html = $this->render_template_part( $template_part );

		$html = strtr(
			'<dialog hidden role="dialog" aria-modal="true">
				{{html}}
			</dialog>',
			array(
				'{{html}}' => $html,
			)
		);

		$p = new \WP_HTML_Tag_Processor( $html );
		if ( $p->next_tag() ) {
			$p->set_attribute( 'data-wc-interactive', wp_json_encode( array( 'namespace' => 'woocommerce/product-filters' ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) );
			$p->set_attribute( 'data-wc-bind--hidden', '!state.isDialogOpen' );
			$p->set_attribute( 'data-wc-class--wc-block-product-filters--dialog-open', 'state.isDialogOpen' );
			$p->set_attribute( 'data-wc-class--wc-block-product-filters--with-admin-bar', 'context.hasPageWithWordPressAdminBar' );
			$html = $p->get_updated_html();
		}

		return $html;
	}

	/**
	 * This method is used to render the template part. For each template part, we parse the blocks and render them.
	 *
	 * @param string $template_part The template part to render.
	 * @return string The rendered template part.
	 */
	protected function render_template_part( $template_part ) {
		$parsed_blocks               = parse_blocks( $template_part );
		$wrapper_template_part_block = $parsed_blocks[0];
		$html                        = $wrapper_template_part_block['innerHTML'];
		$target_div                  = '</div>';

		$template_part_content_html = array_reduce(
			$wrapper_template_part_block['innerBlocks'],
			function ( $carry, $item ) {
				if ( 'core/template-part' === $item['blockName'] ) {
					$inner_template_part              = BlockTemplateUtils::get_template_part( $item['attrs']['slug'] );
					$inner_template_part_content_html = $this->render_template_part( $inner_template_part );

					return $carry . $inner_template_part_content_html;
				}
				return $carry . render_block( $item );
			},
			''
		);

		$html = str_replace( $target_div, $template_part_content_html . $target_div, $html );

		return $html;
	}

	/**
	 * Inject dialog into the product filters HTML.
	 *
	 * @param string $product_filters_html The Product Filters HTML.
	 * @param string $dialog_html The dialog HTML.
	 *
	 * @return string
	 */
	protected function inject_dialog( $product_filters_html, $dialog_html ) {
		// Find the position of the last </div>.
		$pos = strrpos( $product_filters_html, '</div>' );

		if ( $pos ) {
			// Inject the dialog_html at the correct position.
			$html = substr_replace( $product_filters_html, $dialog_html, $pos, 0 );

			return $html;
		}

		return $product_filters_html;
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
		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag() ) {
			$tags->set_attribute( 'data-wc-interactive', wp_json_encode( array( 'namespace' => 'woocommerce/' . $this->block_name ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) );
			$tags->set_attribute(
				'data-wc-context',
				wp_json_encode(
					array(
						'isDialogOpen'                 => false,
						'hasPageWithWordPressAdminBar' => false,
						'params'                       => $this->get_filter_query_params( 0 ),
						'originalParams'               => $this->get_filter_query_params( 0 ),
					),
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
				)
			);
			$tags->set_attribute( 'data-wc-navigation-id', $this->generate_navigation_id( $block ) );
			$tags->set_attribute( 'data-wc-watch', 'callbacks.maybeNavigate' );

			if (
				'always' === $attributes['overlay'] ||
				( 'mobile' === $attributes['overlay'] && wp_is_mobile() )
			) {
				return $this->inject_dialog( $tags->get_updated_html(), $this->render_dialog() );
			}

			return $tags->get_updated_html();
		}
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
	 * Parse the filter parameters from the URL.
	 * For now we only get the global query params from the URL. In the future,
	 * we should get the query params based on $query_id.
	 *
	 * @param int $query_id Query ID.
	 * @return array Parsed filter params.
	 */
	private function get_filter_query_params( $query_id ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		$parsed_url = wp_parse_url( esc_url_raw( $request_uri ) );

		if ( empty( $parsed_url['query'] ) ) {
			return array();
		}

		parse_str( $parsed_url['query'], $url_query_params );

		/**
		 * Filters the active filter data provided by filter blocks.
		 *
		 * @since 11.7.0
		 *
		 * @param array $filter_param_keys The active filters data
		 * @param array $url_param_keys    The query param parsed from the URL.
		 *
		 * @return array Active filters params.
		 */
		$filter_param_keys = array_unique( apply_filters( 'collection_filter_query_param_keys', array(), array_keys( $url_query_params ) ) );

		return array_filter(
			$url_query_params,
			function ( $key ) use ( $filter_param_keys ) {
				return in_array( $key, $filter_param_keys, true );
			},
			ARRAY_FILTER_USE_KEY
		);
	}
}
