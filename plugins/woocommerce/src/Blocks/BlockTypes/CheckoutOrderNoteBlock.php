<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutOrderNoteBlock class.
 */
class CheckoutOrderNoteBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-order-note-block';
}
