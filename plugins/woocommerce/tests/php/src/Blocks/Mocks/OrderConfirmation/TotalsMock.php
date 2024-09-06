<?php
namespace Automattic\WooCommerce\Tests\Blocks\Mocks\OrderConfirmation;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\Totals;

/**
 * ProductCollectionMock used to test Product Query block functions.
 */
class TotalsMock extends Totals {

	/**
	 * Initialize our mock class.
	 */
	public function __construct() {
		parent::__construct(
			Package::container()->get( API::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry(),
		);
	}

	public function render_content( $order, $permission = false, $attributes = [], $content = '' ) {
		return parent::render_content( $order, $permission, $attributes, $content );
	}

	/**
	 * For now don't need to initialize anything in tests so let's
	 * just override the default behaviour.
	 */
	protected function initialize() {
	}
}

