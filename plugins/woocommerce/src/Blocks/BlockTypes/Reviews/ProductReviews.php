<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductReviews class.
 */
class ProductReviews extends AbstractBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-reviews';

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
		if ( empty( $block->parsed_block['innerBlocks'] ) ) {
			return $this->render_legacy_block( $attributes, $content, $block );
		}

		if ( ! comments_open() ) {
			return '';
		}

		$p = new \WP_HTML_Tag_Processor( $content );
		$p->next_tag();
		$p->set_attribute( 'data-wp-interactive', $this->get_full_block_name() );
		$p->set_attribute( 'data-wp-router-region', $this->get_full_block_name() );

		return $p->get_updated_html();
	}

	/**
	 * Previously, the Product Reviews block was a standalone block. It doesn't
	 * have any inner blocks and it rendered the tabs directly like the classic
	 * template. When upgrading, we want the existing stores using the block to
	 * continue working as before, so we moved the logic the legacy render
	 * method here.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string Rendered block output.
	 */
	protected function render_legacy_block( $attributes, $content, $block ) {
		if ( ! is_singular( 'product' ) ) {
			return $content;
		}

		ob_start();

		rewind_posts();
		while ( have_posts() ) {
			the_post();
			comments_template();
		}

		$reviews = ob_get_clean();

		return sprintf(
			'<div class="wp-block-woocommerce-product-reviews %1$s">
				%2$s
			</div>',
			StyleAttributesUtils::get_classes_by_attributes( $attributes, array( 'extra_classes' ) ),
			$reviews
		);
	}
}
