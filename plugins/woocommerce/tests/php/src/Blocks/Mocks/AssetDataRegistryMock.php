<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;

/**
 * A mock class.
 */
class AssetDataRegistryMock extends AssetDataRegistry {

	private $debug = true;

	public function set_debug( $debug ) {
		$this->debug = $debug;
	}

	protected function debug() {
		return $this->debug;
	}
}
