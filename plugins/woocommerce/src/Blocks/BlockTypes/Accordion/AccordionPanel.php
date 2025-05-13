<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\Accordion;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\Utils\BlockUtils;
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

	public static function create_block( $attrs, $inner_blocks = array() ) {
		$block_type_name = 'woocommerce/accordion-panel';

		$attrs = BlockUtils::filter_block_attributes( $block_type_name, $attrs );

		$block = array(
			'blockName'   => $block_type_name,
			'attrs'       => $attrs,
			'innerBlocks' => $inner_blocks,
		);

		$block_wrapper_attributes = BlockUtils::get_block_wrapper_attributes( $block );

		$block['innerContent'] = array_merge(
			array(
				"<div $block_wrapper_attributes>",
				'<div class="accordion-content__wrapper">',
			),
			array_fill( 0, count( $inner_blocks ), null ),
			array( '</div>', '</div>' )
		);
		return $block;
	}
}
