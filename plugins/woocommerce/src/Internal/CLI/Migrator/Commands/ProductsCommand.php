<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Commands;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
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
	 * Initialize the command with its dependencies.
	 *
	 * @param CredentialManager $credential_manager The credential manager.
	 * @param PlatformRegistry  $platform_registry  The platform registry.
	 *
	 * @internal
	 */
	final public function init( CredentialManager $credential_manager, PlatformRegistry $platform_registry ): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found -- Required by WooCommerce injection method rules
		$this->credential_manager = $credential_manager;
		$this->platform_registry  = $platform_registry;
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
	 * [--fetch]
	 * : Fetch and display product data from the platform.
	 *
	 * [--limit=<limit>]
	 * : Maximum number of products to fetch (default: 5).
	 *
	 * [--after=<cursor>]
	 * : Pagination cursor for fetching products after a specific point.
	 *
	 * [--status=<status>]
	 * : Filter products by status (active, archived, draft).
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc migrate products --count
	 *     wp wc migrate products --count --status=active
	 *     wp wc migrate products --fetch --limit=5
	 *     wp wc migrate products --fetch --limit=10 --after=cursor123
	 *
	 * @param array $args       The positional arguments.
	 * @param array $assoc_args The associative arguments.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		// Resolve and validate the platform.
		$platform = $this->platform_registry->resolve_platform( $assoc_args );

		if ( ! $this->credential_manager->has_credentials( $platform ) ) {
			WP_CLI::log( "Credentials for '{$platform}' not found. Let's set them up." );

			// Get platform-specific credential fields and set them up.
			$required_fields = $this->platform_registry->get_platform_credential_fields( $platform );
			if ( empty( $required_fields ) ) {
				WP_CLI::error( "The platform '{$platform}' does not have configured credential fields." );
				return;
			}

			$this->credential_manager->setup_credentials( $platform, $required_fields );
			WP_CLI::success( 'Credentials saved successfully. Please run the command again to begin the migration.' );
			return;
		}

		// Handle count request if specified.
		if ( isset( $assoc_args['count'] ) ) {
			$this->handle_count_request( $platform, $assoc_args );
			return;
		}

		// Handle fetch request if specified.
		if ( isset( $assoc_args['fetch'] ) ) {
			$this->handle_fetch_request( $platform, $assoc_args );
			return;
		}

		// The logic will be handled by the Products_Controller.
		// For now, we just show a success message if credentials exist.
		WP_CLI::success( 'Credentials found. Proceeding with migration...' );
	}

	/**
	 * Handle the count request.
	 *
	 * @param string $platform    The platform name.
	 * @param array  $assoc_args  The associative arguments.
	 */
	private function handle_count_request( string $platform, array $assoc_args ): void {
		WP_CLI::log( "Fetching product count from {$platform}..." );

		$fetcher = $this->platform_registry->get_fetcher( $platform );
		if ( ! $fetcher ) {
			WP_CLI::error( "Could not get fetcher for platform '{$platform}'" );
			return;
		}

		// Build filter arguments.
		$filter_args = array();
		if ( isset( $assoc_args['status'] ) ) {
			$filter_args['status'] = $assoc_args['status'];
		}

		$count = $fetcher->fetch_total_count( $filter_args );

		if ( 0 === $count ) {
			WP_CLI::log( 'No products found or unable to fetch count.' );
		} else {
			$status_filter = isset( $assoc_args['status'] ) ? " with status '{$assoc_args['status']}'" : '';
			WP_CLI::success( "Found {$count} products{$status_filter} on {$platform}." );
		}
	}

	/**
	 * Handle the fetch request.
	 *
	 * @param string $platform    The platform name.
	 * @param array  $assoc_args  The associative arguments.
	 */
	private function handle_fetch_request( string $platform, array $assoc_args ): void {
		$limit  = (int) ( $assoc_args['limit'] ?? 5 );
		$cursor = $assoc_args['after'] ?? null;

		WP_CLI::log( "Fetching {$limit} products from {$platform}..." );

		$fetcher = $this->platform_registry->get_fetcher( $platform );
		if ( ! $fetcher ) {
			WP_CLI::error( "Could not get fetcher for platform '{$platform}'" );
			return;
		}

		// Build fetch arguments.
		$fetch_args = array(
			'limit'        => $limit,
			'after_cursor' => $cursor,
		);

		$result = $fetcher->fetch_batch( $fetch_args );

		if ( empty( $result['items'] ) ) {
			WP_CLI::log( 'No products found or unable to fetch products.' );
			return;
		}

		WP_CLI::success( sprintf( 'Successfully fetched %d products.', count( $result['items'] ) ) );

		// Display basic product information.
		foreach ( $result['items'] as $item ) {
			$product = $item->node ?? null;
			if ( ! $product ) {
				continue;
			}

			$title          = $product->title ?? 'Unknown Title';
			$id             = $product->id ?? 'Unknown ID';
			$status         = $product->status ?? 'Unknown Status';
			$variants_count = isset( $product->variants->edges ) ? count( $product->variants->edges ) : 0;

			WP_CLI::log( "- {$title} (ID: {$id}, Status: {$status}, Variants: {$variants_count})" );
		}

		// Display pagination information.
		if ( $result['has_next_page'] && $result['cursor'] ) {
			WP_CLI::log( '' );
			WP_CLI::log( 'More products available. To fetch next batch, use:' );
			WP_CLI::log( "wp wc migrate products --fetch --limit={$limit} --after={$result['cursor']}" );
		} else {
			WP_CLI::log( '' );
			WP_CLI::log( 'No more products to fetch.' );
		}
	}
}
