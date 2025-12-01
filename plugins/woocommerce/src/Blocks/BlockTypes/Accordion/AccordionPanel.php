<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\Accordion;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
/**
 * AccordionPanel class.
 */
class AccordionPanel extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'accordion-panel';
}
