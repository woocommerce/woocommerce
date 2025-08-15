<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationSelectorAttributeOptions;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * AddToCartWithOptionsVariationSelectorAttributeOptionsMock used to test VariationSelectorAttributeOptions block functions.
 */
class AddToCartWithOptionsVariationSelectorAttributeOptionsMock extends VariationSelectorAttributeOptions {
	/**
	 * Initialize our mock class.
	 */
	public function __construct() {
		parent::__construct(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry(),
		);
	}
}
