<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractInnerBlock;

/**
 * Default order confirmation status block.
 */
class StatusDefaultBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-status-default';
}
