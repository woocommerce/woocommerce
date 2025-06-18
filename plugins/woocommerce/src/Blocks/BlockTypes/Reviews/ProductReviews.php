<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;

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
	protected $block_name = 'blockified-product-reviews';

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
		if ( ! comments_open() ) {
			return '';
		}

		$p = new \WP_HTML_Tag_Processor( $content );
		$p->next_tag();
		$p->set_attribute( 'data-wp-interactive', $this->get_full_block_name() );
		$p->set_attribute( 'data-wp-router-region', $this->get_full_block_name() );

		return $p->get_updated_html();
	}
}
