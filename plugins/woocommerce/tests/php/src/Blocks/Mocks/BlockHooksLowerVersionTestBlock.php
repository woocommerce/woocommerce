<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\Utils\BlockHooksTrait;

/**
 * Mock block with an independent version cache for the lower-version test.
 */
class BlockHooksLowerVersionTestBlock extends BlockHooksTestBlock {
	use BlockHooksTrait;
}
