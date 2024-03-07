<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

use Automattic\WooCommerce\Internal\Admin\BlockTemplates\AbstractBlock;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockContainerTrait;

/**
 * Custom block class for testing.
 */
class CustomBlock extends AbstractBlock implements CustomBlockInterface {
	use BlockContainerTrait;

	/**
	 * Custom method.
	 *
	 * @param string $title The title.
	 */
	public function add_custom_inner_block( string $title ): BlockInterface {
		$block = new Block(
			[
				'blockName'  => 'custom-inner-block',
				'attributes' => [
					'title' => $title,
				],
			],
			$this->get_root_template(),
			$this
		);

		$this->add_inner_block( $block );

		return $block;
	}
}

