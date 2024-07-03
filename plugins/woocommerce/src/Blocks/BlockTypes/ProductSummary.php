<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductSummary class.
 */
class ProductSummary extends AbstractBlock
{

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-summary';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '2';


	/**
	 * Get block supports. Shared with the frontend.
	 * IMPORTANT: If you change anything here, make sure to update the JS file too.
	 *
	 * @return array
	 */
	protected function get_block_type_supports()
	{
		return array(
			'color'                  =>
			array(
				'link'       => true,
				'background' => false,
				'text'       => true,
			),
			'typography'             =>
			array(
				'fontSize' => true,
			),
			'__experimentalSelector' => '.wc-block-components-product-summary',
		);
	}

	/**
	 * Overwrite parent method to prevent script registration.
	 *
	 * It is necessary to register and enqueues assets during the render
	 * phase because we want to load assets only if the block has the content.
	 */
	protected function register_block_type_assets()
	{
		return null;
	}

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context()
	{
		return ['query', 'queryId', 'postId'];
	}

	private function get_source($product, $show_description_if_empty)
	{
		$short_description = $product->get_short_description();

		if ($short_description) {
			return $short_description;
		}

		$description = $product->get_description();

		if ($show_description_if_empty && $description) {
			return $description;
		}

		return '';
	}

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render($attributes, $content, $block)
	{
		if (!empty($content)) {
			parent::register_block_type_assets();
			$this->register_chunk_translations([$this->block_name]);
			return $content;
		}

		$post_id = isset($block->context['postId']) ? $block->context['postId'] : '';
		$product = wc_get_product($post_id);

		if (!$product) {
			return '';
		}

		$show_description_if_empty = isset($attributes['showDescriptionIfEmpty']) && $attributes['showDescriptionIfEmpty'];
		$source                    = $this->get_source($product, $show_description_if_empty);

		if (!$source) {
			return '';
		}

		$summary_length = isset($attributes['summaryLength']) ? $attributes['summaryLength'] : false;
		$summary        = $summary_length ? wp_trim_words($source, $summary_length) : $source;

		$styles_and_classes = StyleAttributesUtils::get_classes_and_styles_by_attributes($attributes);

		return sprintf(
			'<p class="wc-block-components-product-sku wc-block-grid__product-sku wp-block-woocommerce-product-sku product_meta %1$s" style="%2$s">
				%3$s
			</p>',
			esc_attr($styles_and_classes['classes']),
			esc_attr($styles_and_classes['styles'] ?? ''),
			$summary
		);
	}
}
