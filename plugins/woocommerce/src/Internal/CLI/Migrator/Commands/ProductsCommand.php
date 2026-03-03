<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Commands;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\ProductsController;
use WP_CLI;

/**
 * The products command.
 */
final class ProductsCommand {

	/**
	 * The credential manager.
	 *
	 * @var CredentialManager
	 */
	private CredentialManager $credential_manager;

	/**
	 * The platform registry.
	 *
	 * @var PlatformRegistry
	 */
	private PlatformRegistry $platform_registry;

	/**
	 * The products controller.
	 *
	 * @var ProductsController
	 */
	private ProductsController $products_controller;

	/**
	 * Initialize the command with its dependencies.
	 *
	 * @param CredentialManager  $credential_manager The credential manager.
	 * @param PlatformRegistry   $platform_registry  The platform registry.
	 * @param ProductsController $products_controller The products controller.
	 *
	 * @internal
	 */
	final public function init( CredentialManager $credential_manager, PlatformRegistry $platform_registry, ProductsController $products_controller ): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found -- Required by WooCommerce injection method rules
		$this->credential_manager  = $credential_manager;
		$this->platform_registry   = $platform_registry;
		$this->products_controller = $products_controller;
	}
	/**
	 * The main execution logic for the command.
	 *
	 * [--platform=<platform>]
	 * : The platform to migrate products from.
	 * ---
	 * default: shopify
	 * ---
	 *
	 * [--count]
	 * : Only fetch and display the total product count.
	 *
	 * [--limit=<limit>]
	 * : Maximum number of products to migrate.
	 *
	 * [--status=<status>]
	 * : Filter products by status (active, archived, draft).
	 *
	 * [--product-type=<product-type>]
	 * : Filter products by type (for Shopify: any product type name, or 'single'/'variable' for WooCommerce equivalents).
	 *
	 * [--vendor=<vendor>]
	 * : Filter products by vendor name.
	 *
	 * [--ids=<ids>]
	 * : Comma-separated list of product IDs to migrate.
	 *
	 * [--batch-size=<size>]
	 * : Number of products to process per batch (default: 20, max: 250).
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to migrate.
	 *
	 * [--exclude-fields=<fields>]
	 * : Comma-separated list of fields to exclude from migration.
	 *
	 * [--resume]
	 * : Resume from previous migration session without prompting.
	 *
	 * [--skip-existing]
	 * : Skip products that already exist in WooCommerce.
	 *
	 * [--dry-run]
	 * : Perform a dry run without creating products.
	 *
	 * [--verbose]
	 * : Show detailed progress information including warnings and errors.
	 *
	 * [--assign-default-category]
	 * : Assign WooCommerce default category to products that have no categories.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc migrate products --count
	 *     wp wc migrate products --count --status=active
	 *     wp wc migrate products --count --product-type="T-Shirt"
	 *     wp wc migrate products --count --vendor="My Brand"
	 *     wp wc migrate products --limit=100 --batch-size=25
	 *     wp wc migrate products --product-type="single" --status=active --limit=50
	 *     wp wc migrate products --ids="123,456,789"
	 *     wp wc migrate products --fields=name,price,sku --resume
	 *     wp wc migrate products --verbose --limit=50
	 *     wp wc migrate products --assign-default-category --limit=100
	 *
	 * @param array $args       The positional arguments.
	 * @param array $assoc_args The associative arguments.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		// Resolve and validate the platform.
		$platform              = $this->platform_registry->resolve_platform( $assoc_args );
		$platform_display_name = $this->platform_registry->get_platform_display_name( $platform );

		if ( ! $this->credential_manager->has_credentials( $platform ) ) {
			WP_CLI::log( "Credentials for '{$platform_display_name}' not found. Let's set them up." );

			// Get platform-specific credential fields and set them up.
			$required_fields = $this->platform_registry->get_platform_credential_fields( $platform );
			if ( empty( $required_fields ) ) {
				WP_CLI::error( "The platform '{$platform_display_name}' does not have configured credential fields." );
				return;
			}

			$this->credential_manager->setup_credentials( $platform, $required_fields );
			WP_CLI::success( 'Credentials saved successfully. Please run the command again to begin the migration.' );
			return;
		}

		// Handle count request if specified.
		if ( isset( $assoc_args['count'] ) ) {
			$this->handle_count_request( $platform, $platform_display_name, $assoc_args );
			return;
		}

		// Delegate actual migration logic to ProductsController with resolved platform.
		$this->products_controller->migrate_products( $assoc_args, $platform );
	}

	/**
	 * Handle the count request.
	 *
	 * @param string $platform             The platform name.
	 * @param string $platform_display_name The platform display name.
	 * @param array  $assoc_args           The associative arguments.
	 */
	private function handle_count_request( string $platform, string $platform_display_name, array $assoc_args ): void {
		WP_CLI::log( "Fetching product count from {$platform_display_name}..." );

		$fetcher = $this->platform_registry->get_fetcher( $platform );
		if ( ! $fetcher ) {
			WP_CLI::error( "Could not get fetcher for platform '{$platform_display_name}'" );
			return;
		}

		// Build filter arguments.
		$filter_args = array();
		if ( isset( $assoc_args['status'] ) ) {
			$filter_args['status'] = $assoc_args['status'];
		}
		if ( isset( $assoc_args['product-type'] ) ) {
			$filter_args['product_type'] = $assoc_args['product-type'];
		}
		if ( isset( $assoc_args['vendor'] ) ) {
			$filter_args['vendor'] = $assoc_args['vendor'];
		}
		if ( isset( $assoc_args['ids'] ) ) {
			$filter_args['ids'] = $assoc_args['ids'];
		}

		$count = $fetcher->fetch_total_count( $filter_args );

		if ( 0 === $count ) {
			WP_CLI::log( 'No products found or unable to fetch count.' );
		} else {
			$filters = array();
			if ( isset( $assoc_args['status'] ) ) {
				$filters[] = "status '{$assoc_args['status']}'";
			}
			if ( isset( $assoc_args['product-type'] ) ) {
				$filters[] = "type '{$assoc_args['product-type']}'";
			}
			if ( isset( $assoc_args['vendor'] ) ) {
				$filters[] = "vendor '{$assoc_args['vendor']}'";
			}
			if ( isset( $assoc_args['ids'] ) ) {
				$filters[] = "IDs '{$assoc_args['ids']}'";
			}

			$filter_description = empty( $filters ) ? '' : ' with ' . implode( ', ', $filters );
			WP_CLI::success( "Found {$count} products{$filter_description} on {$platform_display_name}." );
		}
	}
}
