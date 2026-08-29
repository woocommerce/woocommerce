<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractInnerBlock;

/**
 * Completed order confirmation status block.
 */
class StatusCompletedBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-status-completed';
}
