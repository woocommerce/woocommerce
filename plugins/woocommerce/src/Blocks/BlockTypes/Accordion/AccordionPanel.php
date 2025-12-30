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
	use AccordionVersionControlTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'accordion-panel';

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 *
	 * @since 10.4.3
	 */
	protected function initialize() {
		$this->initialize_accordion_version_control();
		parent::initialize();
	}
}
