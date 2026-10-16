<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductResultsCount class.
 */
class ProductResultsCount extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-results-count';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content Block content.
	 * @param \WP_Block $block Block instance.
	 *
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		// Buffer the result count and use it as the block's frontend content.
		ob_start();
		woocommerce_result_count();
		$product_results_count = (string) ob_get_clean();

		$processor = new \WP_HTML_Tag_Processor( $product_results_count );
		if ( $processor->next_tag( array( 'class_name' => 'woocommerce-result-count' ) ) ) {
			// The block speaks the count after client-side navigation; a second live region would compete with it.
			$processor->remove_attribute( 'role' );
			$processor->remove_attribute( 'aria-relevant' );
		}
		$product_results_count = $processor->get_updated_html();

		$classes = 'woocommerce wc-block-product-results-count';
		if ( ! empty( $attributes['fontSize'] ) ) {
			// Preserve the legacy class for backward compatibility.
			$classes .= ' has-font-size';
		}

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'                 => $classes,
				'data-wp-interactive'   => $this->get_full_block_name(),
				'data-wp-router-region' => 'wc-product-results-count-' . ( $block->context['queryId'] ?? 0 ),
				'data-wp-context'       => (string) wp_json_encode( array( 'announcement' => $this->get_announcement_text( $product_results_count ) ) ),
				'data-wp-watch'         => 'callbacks.announceResultsCount',
			)
		);

		return sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $product_results_count );
	}

	/**
	 * Get the result count as plain text, with each text fragment (such as the hidden sort label) separated by a space.
	 *
	 * @param string $markup Result count markup.
	 *
	 * @return string Text for screen readers.
	 */
	private function get_announcement_text( string $markup ): string {
		$parts     = array();
		$processor = new \WP_HTML_Tag_Processor( $markup );

		while ( $processor->next_token() ) {
			if ( '#text' !== $processor->get_token_name() ) {
				continue;
			}

			$part = trim( $processor->get_modifiable_text() );
			if ( '' !== $part ) {
				$parts[] = $part;
			}
		}

		return implode( ' ', $parts );
	}
}
