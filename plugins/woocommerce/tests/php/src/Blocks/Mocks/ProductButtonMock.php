<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductButton;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * ProductButtonMock used to test ProductButton block functions.
 */
class ProductButtonMock extends ProductButton {

	/**
	 * Initialize the mock class using the Blocks package container.
	 */
	public function __construct() {
		parent::__construct(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry()
		);
	}

	/**
	 * Get the quantity of the product's first canonical cart line, in cart order.
	 *
	 * @param int $product_id The product ID.
	 * @return int|float The quantity of the product's first canonical cart line, or 0.
	 */
	public function call_get_cart_item_quantity_by_product_id( int $product_id ) {
		$reflection = new \ReflectionClass( ProductButton::class );
		$method     = $reflection->getMethod( 'get_cart_item_quantity_by_product_id' );
		$method->setAccessible( true );

		return $method->invoke( $this, $product_id );
	}
}
