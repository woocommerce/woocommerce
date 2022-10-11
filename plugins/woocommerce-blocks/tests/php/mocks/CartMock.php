<?php
namespace Automattic\WooCommerce\Blocks\Tests\Mocks;

use Automattic\WooCommerce\Blocks\BlockTypes\Cart;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * CartMock used to test cart block functions.
 */
class CartMock extends Cart {

	/**
	 * We initaite our mock class.
	 */
	public function __construct() {
		parent::__construct(
			Package::container()->get( API::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry(),
		);
	}

	/**
	 * For now don't need to initialize anything in tests so let's
	 * just override the default behaviour.
	 */
	protected function initialize() {
	}

	/**
	 * Protected test wrapper for deep_sort_with_accents.
	 *
	 * @param array $array The array we want to sort.
	 */
	public function deep_sort_test( $array ) {
		return $this->deep_sort_with_accents( $array );
	}
}
