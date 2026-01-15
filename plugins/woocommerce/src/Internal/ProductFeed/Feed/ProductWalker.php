<?php
/**
 * Product Walker class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Feed;

use Automattic\WooCommerce\Internal\ProductFeed\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Utils\MemoryManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Walker for products.
 *
 * @since 10.5.0
 */
class ProductWalker {
	/**
	 * The product loader.
	 *
	 * @var ProductLoader
	 */
	private ProductLoader $product_loader;

	/**
	 * The product mapper.
	 *
	 * @var ProductMapperInterface
	 */
	private ProductMapperInterface $mapper;

	/**
	 * The feed.
	 *
	 * @var FeedInterface
	 */
	private FeedInterface $feed;

	/**
	 * The feed validator.
	 *
	 * @var FeedValidatorInterface
	 */
	private FeedValidatorInterface $validator;

	/**
	 * The memory manager.
	 *
	 * @var MemoryManager
	 */
	private MemoryManager $memory_manager;

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
	 * The query arguments to apply to the product query.
	 *
	 * @var array
	 */
	private array $query_args;

	/**
	 * Class constructor.
	 *
	 * This class will not be available through DI. Instead, it needs to be instantiated directly.
	 *
	 * @param ProductMapperInterface $mapper The product mapper.
	 * @param FeedValidatorInterface $validator The feed validator.
	 * @param FeedInterface          $feed The feed.
	 * @param ProductLoader          $product_loader The product loader.
	 * @param MemoryManager          $memory_manager The memory manager.
	 * @param array                  $query_args The query arguments.
	 */
	private function __construct(
		ProductMapperInterface $mapper,
		FeedValidatorInterface $validator,
		FeedInterface $feed,
		ProductLoader $product_loader,
		MemoryManager $memory_manager,
		array $query_args
	) {
		$this->mapper         = $mapper;
		$this->validator      = $validator;
		$this->feed           = $feed;
		$this->product_loader = $product_loader;
		$this->memory_manager = $memory_manager;
		$this->query_args     = $query_args;
	}

	/**
	 * Creates a new instance of the ProductWalker class based on an integration.
	 *
	 * The walker will mostly be set up based on the integration.
	 * The feed is provided externally, as it might be based on the context (CLI, REST, Action Scheduler, etc.).
	 *
	 * @since 10.5.0
	 *
	 * @param IntegrationInterface $integration The integration.
	 * @param FeedInterface        $feed        The feed.
	 * @return self The ProductWalker instance.
	 */
	public static function from_integration(
		IntegrationInterface $integration,
		FeedInterface $feed
	): self {
		$query_args = array_merge(
			array(
				'status' => array( 'publish' ),
				'return' => 'objects',
			),
			$integration->get_product_feed_query_args()
		);

		/**
		 * Allows the base arguments for querying products for product feeds to be changed.
		 *
		 * Variable products are not included by default, as their variations will be included.
		 *
		 * @since 10.5.0
		 *
		 * @param array                $query_args The arguments to pass to wc_get_products().
		 * @param IntegrationInterface $integration The integration that the query belongs to.
		 * @return array
		 */
		$query_args = apply_filters(
			'woocommerce_product_feed_args',
			$query_args,
			$integration
		);

		$instance = new self(
			$integration->get_product_mapper(),
			$integration->get_feed_validator(),
			$feed,
			wc_get_container()->get( ProductLoader::class ),
			wc_get_container()->get( MemoryManager::class ),
			$query_args
		);

		return $instance;
	}

	/**
	 * Set the number of products to iterate through per batch.
	 *
	 * @since 10.5.0
	 *
	 * @param int $batch_size The number of products to iterate through per batch.
	 * @return self
	 */
	public function set_batch_size( int $batch_size ): self {
		if ( $batch_size < 1 ) {
			$batch_size = 1;
		}

		$this->per_page = $batch_size;
		return $this;
	}

	/**
	 * Set the time limit to extend the execution time limit per batch.
	 *
	 * @since 10.5.0
	 *
	 * @param int $time_limit Time limit in seconds.
	 * @return self
	 */
	public function add_time_limit( int $time_limit ): self {
		if ( $time_limit < 0 ) {
			$time_limit = 0;
		}

		$this->time_limit = $time_limit;
		return $this;
	}

	/**
	 * Walks through all products.
	 *
	 * @since 10.5.0
	 *
	 * @param callable $callback The callback to call after each batch of products is processed.
	 * @return int The total number of products processed.
	 */
	public function walk( ?callable $callback = null ): int {
		$progress = null;

		// Instruct the feed to start.
		$this->feed->start();

		// Check how much memory is available at first.
		$initial_available_memory = $this->memory_manager->get_available_memory();

		do {
			$result   = $this->iterate( $this->query_args, $progress ? $progress->processed_batches + 1 : 1, $this->per_page );
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
			$current_memory = $this->memory_manager->get_available_memory();
			if ( $initial_available_memory - $current_memory >= $initial_available_memory / 2 ) {
				$this->memory_manager->flush_caches();
			}
		} while (
			// If `wc_get_products()` returns less than the batch size, it was the last page.
			$iterated === $this->per_page

			// For the cases where the above is true, make sure that we do not exceed the total number of pages.
			&& $progress->processed_batches < $progress->total_batch_count
		);

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
	 * @return \stdClass The result of the query with properties: products, total, max_num_pages.
	 */
	private function iterate( array $args = array(), int $page = 1, int $limit = 100 ): \stdClass {
		/**
		 * Result is always stdClass when paginate=true.
		 *
		 * @var \stdClass $result
		 */
		$result = $this->product_loader->get_products(
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
