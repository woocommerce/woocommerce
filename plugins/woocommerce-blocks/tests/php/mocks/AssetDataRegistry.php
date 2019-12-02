<?php

namespace Automattic\WooCommerce\Blocks\Tests\Mocks;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;

class AssetDataRegistryMock extends AssetDataRegistry {

	private $debug = true;

	public function execute_lazy_data() {
		parent::execute_lazy_data();
	}

	public function get() {
		return parent::get();
	}

	public function set_debug( $debug ) {
		$this->debug = $debug;
	}

	public function initialize_core_data() {
		return parent::initialize_core_data();
	}

	protected function debug() {
		return $this->debug;
	}
}
