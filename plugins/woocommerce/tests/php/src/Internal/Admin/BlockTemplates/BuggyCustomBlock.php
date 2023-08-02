<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockTemplate;

/**
 * Custom block class for testing.
 */
class BuggyCustomBlock extends Block implements CustomBlockInterface {
	/**
	 * Block constructor.
	 *
	 * @param array                        $config The block configuration.
	 * @param BlockTemplateInterface       $root_template The block template that this block belongs to.
	 * @param BlockContainerInterface|null $parent The parent block container.
	 *
	 * @throws \ValueError If the block configuration is invalid.
	 * @throws \ValueError If the parent block container does not belong to the same template as the block.
	 */
	public function __construct( array $config, BlockTemplateInterface $root_template, BlockContainerInterface $parent = null ) {
		// Note: we failed to pass in the parent, so it won't get properly set.
		parent::__construct( $config, $root_template );
	}

	/**
	 * Custom method.
	 */
	public function custom_method(): string {
		return 'custom';
	}
}
