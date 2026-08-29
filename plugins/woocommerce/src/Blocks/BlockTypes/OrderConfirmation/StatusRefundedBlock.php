<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractInnerBlock;

/**
 * Refunded order confirmation status block.
 */
class StatusRefundedBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-status-refunded';
}
