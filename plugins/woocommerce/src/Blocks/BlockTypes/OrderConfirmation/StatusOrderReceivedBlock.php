<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractInnerBlock;

/**
 * Order received confirmation status block.
 */
class StatusOrderReceivedBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-status-order-received';
}
