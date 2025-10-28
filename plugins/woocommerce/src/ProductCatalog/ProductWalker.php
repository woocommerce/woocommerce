<?php
/**
 * Product walker for catalog generation.
 *
 * @package WooCommerce\ProductCatalog
 * @since   10.4.0
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\ProductCatalog;

use Automattic\WooCommerce\ProductCatalog\Interfaces\ProductMapperInterface;
use Automattic\WooCommerce\ProductCatalog\Interfaces\FeedValidatorInterface;
use Automattic\WooCommerce\ProductCatalog\Interfaces\FeedInterface;
use Automattic\WooCommerce\ProductCatalog\MemoryManager;

defined( 'ABSPATH' ) || exit;

/**
 * Walks through products and generates catalog entries.
 *
 * @package WooCommerce\ProductCatalog
 */
class ProductWalker {
	/**
	 * The product mapper.
	 *
	 * @var ProductMapperInterface
	 */
	private $mapper;

	/**
	 * The feed.
	 *
	 * @var FeedInterface
	 */
	private $feed;

	/**
	 * The feed validator.
	 *
	 * @var FeedValidatorInterface
	 */
	private $validator;

	/**
	 * The number of products to iterate through per batch.
	 *
	 * @var int
	 */
	private int $per_page = 100;

	/**
	 * The time limit to extend the execution time limit per batch.
	 *
	 * @var int
	 */
	private int $time_limit = 0;

	/**
	 * Class constructor.
	 *
	 * This class will not be available through DI. Instead, it needs to be instantiated directly.
	 *
	 * @param ProductMapperInterface $mapper The product mapper.
	 * @param FeedValidatorInterface $validator The feed validator.
	 * @param FeedInterface          $feed The feed.
	 */
	public function __construct(
		ProductMapperInterface $mapper,
		FeedValidatorInterface $validator,
		FeedInterface $feed
	) {
		$this->mapper    = $mapper;
		$this->validator = $validator;
		$this->feed      = $feed;
	}

	/**
	 * Set the number of products to iterate through per batch.
	 *
	 * @param int $batch_size The number of products to iterate through per batch.
	 * @return self
	 */
	public function set_batch_size( int $batch_size ): self {
		$this->per_page = $batch_size;
		return $this;
	}

	/**
	 * Set the time limit to extend the execution time limit per batch.
	 *
	 * @param int $time_limit Time limit in seconds.
	 * @return self
	 */
	public function add_time_limit( int $time_limit ): self {
		$this->time_limit = $time_limit;
		return $this;
	}

	/**
	 * Walks through all products.
	 *
	 * @param callable $callback The callback to call after each batch of products is processed.
	 * @param array    $additional_args Optional. Additional arguments to merge into the base query args.
	 * @param array    $product_types Optional. Product types to include. If provided, overrides the instance property.
	 * @return int The total number of products processed.
	 */
	public function walk( ?callable $callback = null, array $additional_args = array(), array $product_types = array() ): int {
		$progress = null;

		// Use provided product types or fall back to default.
		$types = ! empty( $product_types ) ? $product_types : array( 'simple', 'variation' );

		/**
		 * Allows the base arguments for querying products for product feeds to be changed.
		 *
		 * Variable products are not included by default, as their variations will be included.
		 *
		 * @since 0.1.0
		 *
		 * @param array $args The arguments to pass to wc_get_products().
		 * @return array
		 */
		$args = apply_filters(
			'wpfoai_product_feed_args',
			array_merge(
				array(
					'status' => array( 'publish' ),
					'type'   => $types,
					'return' => 'objects',
				),
				$additional_args
			)
		);

		// Instruct the feed to start.
		$this->feed->start();

		// Check how much memory is available at first.
		$initial_available_memory = MemoryManager::get_available_memory();

		do {
			$result   = $this->iterate( $args, $progress ? $progress->processed_batches + 1 : 1, $this->per_page );
			$iterated = count( $result->products );

			// Only done when the progress is not set. Will be modified otherwise.
			if ( is_null( $progress ) ) {
				$progress = WalkerProgress::from_wc_get_products_result( $result );
			}
			$progress->processed_items += $iterated;
			++$progress->processed_batches;

			if ( is_callable( $callback ) && $iterated > 0 ) {
				$callback( $progress );
			}

			if ( $this->time_limit > 0 ) {
				set_time_limit( $this->time_limit );
			}

			// We don't want to use more than half of the available memory at the beginning of the script.
			if ( $initial_available_memory - MemoryManager::get_available_memory() >= $initial_available_memory / 2 ) {
				MemoryManager::flush_caches();
			}
		} while ( $iterated === $this->per_page );

		// Instruct the feed to end.
		$this->feed->end();

		return $progress->processed_items;
	}

	/**
	 * Iterates through a batch of products.
	 *
	 * @param array $args The arguments to pass to wc_get_products().
	 * @param int   $page The page number to iterate through.
	 * @param int   $limit The maximum number of products to iterate through.
	 * @return object The result of the query.
	 */
	private function iterate( array $args = array(), int $page = 1, int $limit = 100 ): object {
		$result = wc_get_products(
			array_merge(
				$args,
				array(
					'page'     => $page,
					'limit'    => $limit,
					'paginate' => true,
				)
			)
		);

		foreach ( $result->products as $product ) {
			$mapped_data = $this->mapper->map_product( $product );

			if ( ! empty( $this->validator->validate_entry( $mapped_data, $product ) ) ) {
				continue;
			}

			$this->feed->add_entry( $mapped_data );
		}

		return $result;
	}
}
