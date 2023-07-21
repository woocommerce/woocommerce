<?php
namespace Automattic\WooCommerce\Blocks\Tests\Mocks;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * ProductCollectionMock used to test Product Query block functions.
 */
class ProductCollectionMock extends ProductCollection {

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
	 * Allow test to set the parsed block data.
	 *
	 * @param array $parsed_block The block data.
	 */
	public function set_parsed_block( $parsed_block ) {
		$this->parsed_block = $parsed_block;
	}

	/**
	 * Allow test to set the $attributes_filter_query_args.
	 *
	 * @param array $data The attribute data.
	 */
	public function set_attributes_filter_query_args( $data ) {
		$this->attributes_filter_query_args = $data;
	}
}
