<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;

/**
 * A mock class.
 */
class AssetDataRegistryMock extends AssetDataRegistry {
	/**
	 * Debug flag.
	 *
	 * @var bool
	 */
	private $debug = true;

	/**
	 * Sets the debug flag value.
	 *
	 * @param bool $debug The debug flag value to set.
	 *
	 * @return void
	 */
	public function set_debug( $debug ) {
		$this->debug = $debug;
	}

	/**
	 * Returns the debug flag value.
	 *
	 * @return bool
	 */
	protected function debug() {
		return $this->debug;
	}
}
