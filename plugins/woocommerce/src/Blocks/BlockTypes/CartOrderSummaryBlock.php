<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;


/**
 * CartOrderSummaryBlock class.
 */
class CartOrderSummaryBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'cart-order-summary-block';

	private function get_inner_block_content($block_name, $content) {
		if ( preg_match( $this->inner_block_regex( $block_name ), $content, $matches) ) {
			return $matches[0];
		}
		return false;
	}

	private function inner_block_regex($block_name) {
		return '/<div data-block-name="woocommerce\/cart-order-summary-' . $block_name . '-block"(.+?)>(.*?)<\/div>/si';
	}

	protected function render( $attributes, $content, $block ) {
		// The order-summary-totals block was introduced as a new parent block for the totals
		// (subtotal, discount, fees, shipping and taxes) blocks.
		$regex_for_cart_order_summary_totals = '/<div data-block-name="woocommerce\/cart-order-summary-totals-block"(.+?)>/';
		$order_summary_totals_content = '<div data-block-name="woocommerce/cart-order-summary-totals-block" class="wp-block-woocommerce-cart-order-summary-totals-block">';

		// We want to move these blocks inside a parent 'totals' block
		$totals_inner_blocks = array('subtotal', 'discount', 'fee', 'shipping', 'taxes');

		if ( ! preg_match( $regex_for_cart_order_summary_totals, $content ) ) {
			foreach ($totals_inner_blocks as $key => $block_name) {
				$inner_block_content = $this->get_inner_block_content( $block_name, $content );

				if ( $inner_block_content ) {
					$order_summary_totals_content .= "\n" . $inner_block_content;
					
					// The last block is replaced with the totals block.
					if( $key === count( $totals_inner_blocks ) - 1 ) {
						$order_summary_totals_content .= '</div>';
						$content = preg_replace( $this->inner_block_regex( $block_name ), $order_summary_totals_content, $content);
					}
					// Otherwise, remove the block
					else {
						$content = preg_replace( $this->inner_block_regex( $block_name ), '', $content );
					}
				}
			}
		}
		return $content;
	}
}