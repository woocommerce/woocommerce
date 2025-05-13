<?php declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;

/**
 * ProductReviewsPagination class.
 */
class ProductReviewsPagination extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-reviews-pagination';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( empty( trim( $content ) ) ) {
			return '';
		}

		if ( post_password_required() ) {
			return;
		}

		$classes            = ( isset( $attributes['style']['elements']['link']['color']['text'] ) ) ? 'has-link-color' : '';
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'               => $classes,
				'data-wp-interactive' => 'woocommerce/blockified-product-reviews',
			)
		);

		$p = new \WP_HTML_Tag_Processor( $content );
		while ( $p->next_tag( 'a' ) ) {
			$p->set_attribute( 'data-wp-on--click', 'actions.navigate' );
		}

		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			$p->get_updated_html()
		);
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
}
