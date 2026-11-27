<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\BlockTypes\MiniCart;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * MiniCartMock used to test MiniCart block functions.
 */
class MiniCartMock extends MiniCart {
	/**
	 * Initialize our mock class.
	 *
	 * @param AssetDataRegistry|null $asset_data_registry Registry to use instead of the shared one.
	 */
	public function __construct( ?AssetDataRegistry $asset_data_registry = null ) {
		parent::__construct(
			Package::container()->get( Api::class ),
			$asset_data_registry ?? Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry()
		);
	}

	/**
	 * Public wrapper for the process_template_contents method.
	 *
	 * @param string $template_contents The template contents to process.
	 * @return string The processed template contents.
	 */
	public function call_process_template_contents( $template_contents ) {
		return $this->process_template_contents( $template_contents );
	}
}
