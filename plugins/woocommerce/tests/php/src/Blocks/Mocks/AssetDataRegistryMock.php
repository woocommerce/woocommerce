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

	/**
	 * Exposes private registered data to child classes.
	 *
	 * @return array  The registered data on the private data property
	 */
	public function get() {
		return parent::get();
	}

	/**
	 * Used for on demand initialization of asset data and registering it with
	 * the internal data registry.
	 *
	 * Note: core data will overwrite any externally registered data via the api.
	 */
	public function initialize_core_data() {
		parent::initialize_core_data();
	}

	/**
	 * Loops through each registered lazy data callback and adds the returned
	 * value to the data array.
	 *
	 * This method is executed right before preparing the data for printing to
	 * the rendered screen.
	 *
	 * @return void
	 */
	public function execute_lazy_data() {
		parent::execute_lazy_data();
	}
}
