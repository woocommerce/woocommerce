<?php
/**
 * POS Catalog Integration class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog;

use Automattic\WooCommerce\Container;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedValidatorInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductLoader;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductQueryInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductQueryService;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Storage\JsonFileFeed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * POS Catalog Integration
 *
 * @since 10.5.0
 */
class POSIntegration implements IntegrationInterface {
	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Dependency injector.
	 *
	 * @param Container $container Dependency container.
	 * @internal
	 */
	final public function init( Container $container ): void {
		$this->container = $container;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_id(): string {
		return 'pos';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_product_feed_query_args(): array {
		return array(
			'type'      => array( 'simple', 'variable', 'variation' ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query' => array(
				array(
					'taxonomy' => 'pos_product_visibility',
					'field'    => 'slug',
					'terms'    => 'pos-hidden',
					'operator' => 'NOT IN',
				),
			),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		$this->container->get( AsyncGenerator::class )->register_hooks();
		$this->container->get( POSProductVisibilitySync::class )->register_hooks();
	}

	/**
	 * Initialize the REST API.
	 *
	 * @return void
	 */
	public function rest_api_init(): void {
		// Only load the controller when necessary.
		$this->container->get( ApiController::class )->register_routes();
	}

	/**
	 * {@inheritdoc}
	 */
	public function activate(): void {
		// At the moment, there are no activation steps for the POS catalog.
	}

	/**
	 * {@inheritdoc}
	 */
	public function deactivate(): void {
		// At the moment, there are no deactivation steps for the POS catalog.
	}

	/**
	 * {@inheritdoc}
	 */
	public function create_feed(): FeedInterface {
		return new JsonFileFeed( 'pos-catalog-feed' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_product_mapper(): ProductMapper {
		return $this->container->get( ProductMapper::class );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_feed_validator(): FeedValidatorInterface {
		return $this->container->get( FeedValidator::class );
	}

	/**
	 * {@inheritdoc}
	 */
	public function create_query_provider(): ProductQueryInterface {
		$query_args = array_merge(
			array(
				'status' => array( 'publish' ),
				'return' => 'objects',
			),
			$this->get_product_feed_query_args()
		);

		/**
		 * Filters the product query arguments.
		 *
		 * @since 10.10.0
		 *
		 * @param array                $query_args  The arguments to pass to wc_get_products().
		 * @param IntegrationInterface $integration The integration that the query belongs to.
		 * @return array
		 */
		$query_args = apply_filters(
			'woocommerce_product_feed_args',
			$query_args,
			$this
		);

		return new ProductQueryService(
			$this->get_product_mapper(),
			$this->get_feed_validator(),
			$this->container->get( ProductLoader::class ),
			$query_args
		);
	}
}
