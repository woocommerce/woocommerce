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
 *
 * Exposes the private `get_cart_item_quantities_by_product_id()` method as a
 * public `call_get_cart_item_quantities_by_product_id()` accessor for unit
 * testing without needing a full block rendering context.
 *
 * @since 11.0.0
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
	 * Public wrapper for the private get_cart_item_quantities_by_product_id method.
	 *
	 * @param int $product_id The parent product ID whose plain cart lines are summed.
	 * @return int The total quantity across plain (non-meta-differentiated) cart lines.
	 */
	public function call_get_cart_item_quantities_by_product_id( int $product_id ): int {
		$reflection = new \ReflectionClass( ProductButton::class );
		$method     = $reflection->getMethod( 'get_cart_item_quantities_by_product_id' );
		$method->setAccessible( true );

		return (int) $method->invoke( $this, $product_id );
	}
}
