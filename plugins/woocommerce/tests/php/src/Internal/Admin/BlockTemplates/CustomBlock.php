<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;

/**
 * Custom block class for testing.
 */
class CustomBlock extends Block implements CustomBlockInterface {
	/**
	 * Custom method.
	 */
	public function custom_method(): string {
		return 'custom';
	}
}

