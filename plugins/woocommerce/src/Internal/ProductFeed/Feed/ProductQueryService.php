<?php
/**
 * Product Query Service class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Feed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service for querying mapped product data without file delivery concerns.
 *
 * Composes the same product loading, mapping, and validation pipeline used by
 * ProductWalker, but returns data directly instead of pushing to a feed file.
 * This enables pull/query consumers (e.g., UCP, live APIs) to reuse the
 * integration's product shaping logic without CSV or file dependencies.
 *
 * @since 10.10.0
 */
class ProductQueryService implements ProductQueryInterface {

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
	 * The feed validator.
	 *
	 * @var FeedValidatorInterface
	 */
	private FeedValidatorInterface $validator;

	/**
	 * The base query arguments from the integration.
	 *
	 * @var array
	 */
	private array $query_args;

	/**
	 * Constructor.
	 *
	 * @since 10.10.0
	 *
	 * @param ProductMapperInterface $mapper         The product mapper.
	 * @param FeedValidatorInterface $validator       The feed validator.
	 * @param ProductLoader          $product_loader  The product loader.
	 * @param array                  $query_args      Base query arguments from the integration.
	 */
	public function __construct(
		ProductMapperInterface $mapper,
		FeedValidatorInterface $validator,
		ProductLoader $product_loader,
		array $query_args
	) {
		$this->mapper         = $mapper;
		$this->validator      = $validator;
		$this->product_loader = $product_loader;
		$this->query_args     = $query_args;
	}

	/**
	 * Query mapped products with pagination.
	 *
	 * @since 10.10.0
	 *
	 * @param array $args  Additional query arguments to merge with integration defaults.
	 * @param int   $page  Page number (1-based).
	 * @param int   $limit Products per page.
	 * @return array {
	 *     @type array $products        Array of mapped product data arrays.
	 *     @type int   $total           Total matching products (before pagination).
	 *     @type int   $max_num_pages   Total pages available.
	 * }
	 */
	public function query_products( array $args, int $page, int $limit ): array {
		// Coerce pagination inputs to valid values so the loader never receives page<1 or limit<1.
		$page  = max( 1, $page );
		$limit = max( 1, $limit );

		/**
		 * Result is always stdClass when paginate=true.
		 *
		 * @var \stdClass $result
		 */
		$result = $this->product_loader->get_products(
			array_merge(
				$this->query_args,
				$args,
				array(
					'page'     => $page,
					'limit'    => $limit,
					'paginate' => true,
				)
			)
		);

		$entries = array();
		foreach ( $result->products as $product ) {
			$mapped_data = $this->mapper->map_product( $product );

			if ( ! empty( $this->validator->validate_entry( $mapped_data, $product ) ) ) {
				continue;
			}

			$entries[] = $mapped_data;
		}

		return array(
			'products'      => $entries,
			'total'         => $result->total ?? 0,
			'max_num_pages' => $result->max_num_pages ?? 0,
		);
	}

	/**
	 * Get a single mapped product by ID.
	 *
	 * Loads the product through the ProductLoader with the integration's query
	 * args, so status, type, and tax_query filters are applied. This prevents
	 * returning draft, disallowed-type, or pos-hidden products that the
	 * integration excludes.
	 *
	 * @since 10.10.0
	 *
	 * @param int $product_id Product ID.
	 * @return array|null Mapped product data, or null if not found or excluded.
	 */
	public function get_product( int $product_id ): ?array {
		/**
		 * Result is always stdClass when paginate=true.
		 *
		 * @var \stdClass $result
		 */
		$result = $this->product_loader->get_products(
			array_merge(
				$this->query_args,
				array(
					'include'  => array( $product_id ),
					'limit'    => 1,
					'page'     => 1,
					'paginate' => true,
					'return'   => 'objects',
				)
			)
		);

		if ( empty( $result->products ) ) {
			return null;
		}

		$product     = $result->products[0];
		$mapped_data = $this->mapper->map_product( $product );

		if ( ! empty( $this->validator->validate_entry( $mapped_data, $product ) ) ) {
			return null;
		}

		return $mapped_data;
	}

	/**
	 * Get the total count of products matching the current query configuration.
	 *
	 * @since 10.10.0
	 *
	 * @param array $args Additional query arguments to merge with integration defaults.
	 * @return int Total matching product count.
	 */
	public function get_total_count( array $args = array() ): int {
		/**
		 * Result is always stdClass when paginate=true.
		 *
		 * @var \stdClass $result
		 */
		$result = $this->product_loader->get_products(
			array_merge(
				$this->query_args,
				$args,
				array(
					'limit'    => 1,
					'page'     => 1,
					'paginate' => true,
				)
			)
		);

		return (int) ( $result->total ?? 0 );
	}
}
