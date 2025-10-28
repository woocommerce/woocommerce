<?php
/**
 * POS Catalog Integration class.
 *
 * This class is adapted from the OpenAI-Product-Feed plugin for compatibility.
 * In WooCommerce core, AsyncGenerator hooks are registered directly in class-woocommerce.php.
 *
 * @package WooCommerce\Internal\ProductCatalog
 * @since   10.4.0
 * @internal
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductCatalog;

use Automattic\WooCommerce\ProductCatalog\ProductMapper;
use Automattic\WooCommerce\ProductCatalog\Interfaces\ProductMapperInterface;
use Automattic\WooCommerce\ProductCatalog\Interfaces\FeedInterface;
use Automattic\WooCommerce\ProductCatalog\Interfaces\FeedValidatorInterface;

defined( 'ABSPATH' ) || exit;

/**
 * POS Catalog Integration
 *
 * @internal This class is intended for internal use only and should not be used by extensions.
 * @package  WooCommerce\Internal\ProductCatalog
 */
class POSIntegration {
	/**
	 * Product mapper instance.
	 *
	 * @var ProductMapper
	 */
	private ProductMapper $product_mapper;

	/**
	 * Initialize the integration with dependencies.
	 *
	 * @internal
	 * @param ProductMapper $product_mapper Product mapper instance.
	 */
	final public function init( ProductMapper $product_mapper ): void {
		$this->product_mapper = $product_mapper;
	}

	/**
	 * Get the ID of the provider.
	 *
	 * @return string The ID of the provider.
	 */
	public function get_id(): string {
		return 'pos';
	}

	/**
	 * Register hooks for the integration.
	 *
	 * Note: In WooCommerce core, AsyncGenerator hooks are registered
	 * directly in class-woocommerce.php init_hooks() method.
	 */
	public function register_hooks(): void {
		// Hooks are registered in class-woocommerce.php
		// This method is kept for compatibility with the plugin interface.
	}

	/**
	 * Activate the integration.
	 */
	public function activate(): void {
		// No activation steps needed for core integration.
	}

	/**
	 * Deactivate the integration.
	 */
	public function deactivate(): void {
		// No deactivation steps needed for core integration.
	}

	/**
	 * Create a feed that is to be populated.
	 *
	 * @return FeedInterface The feed.
	 */
	public function create_feed(): FeedInterface {
		return new JsonFileFeed( 'pos-catalog-feed' );
	}

	/**
	 * Get the product mapper for the provider.
	 *
	 * @return ProductMapperInterface The product mapper.
	 */
	public function get_product_mapper(): ProductMapperInterface {
		return $this->product_mapper;
	}

	/**
	 * Get the feed validator for the provider.
	 *
	 * @return FeedValidatorInterface The feed validator.
	 */
	public function get_feed_validator(): FeedValidatorInterface {
		return new FeedValidator();
	}
}
