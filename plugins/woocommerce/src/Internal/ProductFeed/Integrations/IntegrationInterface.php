<?php
/**
 * Interface that should be implemented by all provider integrations.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Integrations;

use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedValidatorInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductMapperInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IntegrationInterface
 *
 * @since 10.5.0
 */
interface IntegrationInterface {
	/**
	 * Get the ID of the provider.
	 *
	 * @return string The ID of the provider.
	 */
	public function get_id(): string;

	/**
	 * Register hooks for the integration.
	 *
	 * @return void
	 */
	public function register_hooks(): void;

	/**
	 * Activate the integration.
	 *
	 * This method is called when the plugin is activated.
	 * If there is ever a setting that controls active integrations,
	 * this method might also be called when the integration is activated.
	 *
	 * @return void
	 */
	public function activate(): void;

	/**
	 * Deactivate the integration.
	 *
	 * This method is called when the plugin is deactivated.
	 * If there is ever a setting that controls active integrations,
	 * this method might also be called when the integration is deactivated.
	 *
	 * @return void
	 */
	public function deactivate(): void;

	/**
	 * Get the query arguments for the product feed.
	 *
	 * @see wc_get_products()
	 * @return array The query arguments.
	 */
	public function get_product_feed_query_args(): array;

	/**
	 * Create a feed that is to be populated.
	 *
	 * @return FeedInterface The feed.
	 */
	public function create_feed(): FeedInterface;

	/**
	 * Get the product mapper for the provider.
	 *
	 * @return ProductMapperInterface The product mapper.
	 */
	public function get_product_mapper(): ProductMapperInterface;

	/**
	 * Get the feed validator for the provider.
	 *
	 * @return FeedValidatorInterface The feed validator.
	 */
	public function get_feed_validator(): FeedValidatorInterface;
}
