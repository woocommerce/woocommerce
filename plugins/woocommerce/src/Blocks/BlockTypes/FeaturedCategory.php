<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * FeaturedCategory class.
 */
class FeaturedCategory extends FeaturedItem {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'featured-category';

	/**
	 * Get block attributes.
	 *
	 * @return array
	 */
	protected function get_block_type_attributes() {
		return array_merge(
			parent::get_block_type_attributes(),
			array(
				'textColor'  => $this->get_schema_string(),
				'fontSize'   => $this->get_schema_string(),
				'lineHeight' => $this->get_schema_string(),
				'style'      => array( 'type' => 'object' ),
			)
		);
	}

	/**
	 * Register the term context used by this block.
	 *
	 * @since 11.2.0
	 *
	 * @return array
	 */
	protected function get_block_type_uses_context() {
		return [ 'termId', 'termTaxonomy', 'taxonomy' ];
	}

	/**
	 * Render the selected category or the product category inherited from context.
	 *
	 * @since 11.2.0
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	protected function render( $attributes, $content, $block ) {
		$attributes['categoryId'] = self::resolve_category_id( $attributes, $block->context );

		return parent::render( $attributes, $content, $block );
	}

	/**
	 * Pass the resolved product category to inner blocks.
	 *
	 * @since 11.2.0
	 *
	 * @param array          $context      Block context.
	 * @param array          $parsed_block Block attributes.
	 * @param \WP_Block|null $parent_block Parent block instance.
	 * @return array Updated block context.
	 */
	public function update_context( $context, $parsed_block, $parent_block ) {
		$context = parent::update_context( $context, $parsed_block, $parent_block );

		if ( is_array( $context ) && $parent_block instanceof \WP_Block && 'woocommerce/featured-category' === $parent_block->name ) {
			$category_id = self::resolve_category_id( $parent_block->attributes, $parent_block->context );
			if ( $category_id ) {
				$context['termId']       = $category_id;
				$context['termTaxonomy'] = 'product_cat';
				$context['taxonomy']     = 'product_cat';
			}
		}

		return $context;
	}

	/**
	 * Resolve the selected or inherited product category ID.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $context Block context.
	 * @return int
	 */
	private static function resolve_category_id( array $attributes, array $context ): int {
		if ( ! empty( $attributes['categoryId'] ) ) {
			return absint( $attributes['categoryId'] );
		}

		$taxonomy = $context['termTaxonomy'] ?? $context['taxonomy'] ?? '';
		return 'product_cat' === $taxonomy ? absint( $context['termId'] ?? 0 ) : 0;
	}

	/**
	 * Returns the featured category.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return \WP_Term|null
	 */
	protected function get_item( $attributes ) {
		$id = absint( $attributes['categoryId'] ?? 0 );

		$category = get_term( $id, 'product_cat' );
		if ( ! $category || is_wp_error( $category ) ) {
			return null;
		}

		return $category;
	}

	/**
	 * Returns the name of the featured category.
	 *
	 * @param \WP_Term $category Featured category.
	 * @return string
	 */
	protected function get_item_title( $category ) {
		return $category->name;
	}

	/**
	 * Returns the featured category image attachment ID.
	 *
	 * @param \WP_Term $category Term object.
	 * @return int
	 */
	protected function get_item_image_id( $category ) {
		return (int) get_term_meta( $category->term_id, 'thumbnail_id', true );
	}

	/**
	 * Returns the featured category image URL.
	 *
	 * @param \WP_Term $category Term object.
	 * @param string   $size Image size, defaults to 'full'.
	 * @return string
	 */
	protected function get_item_image( $category, $size = 'full' ) {
		$image_id = $this->get_item_image_id( $category );

		if ( $image_id ) {
			return wp_get_attachment_image_url( $image_id, $size );
		}

		return '';
	}

	/**
	 * Renders the featured category attributes.
	 *
	 * @param \WP_Term $category Term object.
	 * @param array    $attributes Block attributes. Default empty array.
	 * @return string
	 */
	protected function render_attributes( $category, $attributes ) {
		$output = '';

		// Backwards compatibility: Only render legacy attributes if `editMode` exists as boolean value
		// This allows us to distinguish between old and new version of the block (which accept inner blocks).
		if ( array_key_exists( 'editMode', $attributes ) && is_bool( $attributes['editMode'] ) ) {
			$legacy_title = sprintf(
				'<h2 class="wc-block-featured-category__title">%s</h2>',
				wp_kses_post( $category->name )
			);

			$output .= $legacy_title;

			if (
				! isset( $attributes['showDesc'] ) ||
				( isset( $attributes['showDesc'] ) && false !== $attributes['showDesc'] )
			) {
				$desc_str = sprintf(
					'<div class="wc-block-featured-category__description">%s</div>',
					wc_format_content( wp_kses_post( $category->description ) )
				);
				$output  .= $desc_str;
			}
		}

		return $output;
	}
}
