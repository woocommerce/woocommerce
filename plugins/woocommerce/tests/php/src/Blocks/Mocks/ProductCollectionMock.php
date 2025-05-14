<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Controller;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\QueryBuilder;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\HandlerRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found

/**
 * ProductCollectionMock used to test Product Query block functions.
 */
class ProductCollectionMock extends Controller {

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
	 * Override the normal initialization behavior to prevent registering the block with WordPress filters.
	 */
	protected function initialize() {
		$this->query_builder               = new QueryBuilder();
		$this->collection_handler_registry = new HandlerRegistry();
		$this->register_core_collections_and_set_handler_store();
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
		$this->query_builder->set_attributes_filter_query_args( $data );
	}

	/**
	 * Makes a protected method public so that it can be used in tests.
	 *
	 * @param string        $collection_name The name of the custom collection.
	 * @param callable      $build_query     A hook returning any custom query arguments to merge with the collection's query.
	 * @param callable|null $frontend_args   An optional hook that returns any frontend collection arguments to pass to the query builder.
	 * @param callable|null $editor_args     An optional hook that returns any REST collection arguments to pass to the query builder.
	 * @param callable|null $preview_query   An optional hook that returns a query to use in preview mode.
	 */
	public function register_collection_handlers( $collection_name, $build_query, $frontend_args = null, $editor_args = null, $preview_query = null ) {
		$handlers = $this->collection_handler_registry->register_collection_handlers( $collection_name, $build_query, $frontend_args, $editor_args, $preview_query );
		$this->query_builder->set_collection_handler( $collection_name, $handlers );
	}

	/**
	 * Removes any custom collection handlers for the given collection.
	 *
	 * @param string $collection_name The name of the collection to unregister.
	 */
	public function unregister_collection_handlers( $collection_name ) {
		$this->collection_handler_registry->unregister_collection_handlers( $collection_name );
	}
}
