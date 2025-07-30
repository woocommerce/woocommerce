<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator;

use Automattic\WooCommerce\Internal\CLI\Migrator\Commands\ProductsCommand;
use Automattic\WooCommerce\Internal\CLI\Migrator\Commands\ResetCommand;
use Automattic\WooCommerce\Internal\CLI\Migrator\Commands\SetupCommand;
use Automattic\WooCommerce\Internal\CLI\Migrator\Commands\ListCommand;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify\ShopifyPlatform;
use WP_CLI;
use WC_Product_Factory;

/**
 * The main runner for the migrator.
 */
final class Runner {

	/**
	 * Register the commands for the migrator.
	 *
	 * @return void
	 */
	public static function register_commands(): void {
		// Initialize built-in platforms.
		self::init_platforms();

		$container = wc_get_container();

		WP_CLI::add_command(
			'wc migrate products',
			$container->get( ProductsCommand::class ),
			array(
				'shortdesc' => 'Migrate products from a source platform to WooCommerce.',
				'longdesc'  => 'Migrate products from a source platform to WooCommerce. The migrator will fetch products from the source platform, map them to the WooCommerce product schema, and then import them into WooCommerce.',
			)
		);

		WP_CLI::add_command(
			'wc migrate reset',
			$container->get( ResetCommand::class ),
			array(
				'shortdesc' => 'Resets (deletes) the credentials for a given platform.',
			)
		);

		WP_CLI::add_command(
			'wc migrate setup',
			$container->get( SetupCommand::class ),
			array(
				'shortdesc' => 'Interactively sets up the credentials for a given platform.',
			)
		);

		WP_CLI::add_command(
			'wc migrate list',
			$container->get( ListCommand::class ),
			array(
				'shortdesc' => 'Lists all registered migration platforms.',
			)
		);
	}

	/**
	 * Initialize built-in migration platforms.
	 *
	 * @return void
	 */
	private static function init_platforms(): void {
		ShopifyPlatform::init();
	}
}
