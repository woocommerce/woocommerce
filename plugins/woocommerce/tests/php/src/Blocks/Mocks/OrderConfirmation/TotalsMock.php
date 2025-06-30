<?php
declare( strict_types = 1 );

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

	/**
	 * For now don't need to initialize anything in tests so let's
	 * just override the default behaviour.
	 */
	protected function initialize() {
	}

	/**
	 * This renders the content of the block within the wrapper.
	 *
	 * @param \WC_Order    $order      Order object.
	 * @param string|false $permission If the current user can view the order details or not.
	 * @param array        $attributes Block attributes.
	 * @param string       $content    Original block content.
	 *
	 * @return string
	 */
	public function render_content( $order, $permission = false, $attributes = [], $content = '' ) {
		return parent::render_content( $order, $permission, $attributes, $content );
	}
}
