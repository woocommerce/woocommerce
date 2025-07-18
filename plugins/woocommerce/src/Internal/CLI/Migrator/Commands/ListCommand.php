<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Commands;

use WP_CLI;

/**
 * Lists all registered migration platforms.
 */
class ListCommand extends BaseCommand {

	/**
	 * Lists all registered migration platforms.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp wc migrator list
	 *
	 * @param array $args       The positional arguments.
	 * @param array $assoc_args The associative arguments.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		// This will be implemented once we migrate the PlatformRegistry.
		// For now, we show the currently supported platforms.

		$platforms = array(
			array(
				'id'      => 'shopify',
				'name'    => 'Shopify',
				'fetcher' => 'ShopifyFetcher',
				'mapper'  => 'ShopifyMapper',
			),
		);

		if ( empty( $platforms ) ) {
			WP_CLI::line( 'No migration platforms are registered.' );
			return;
		}

		WP_CLI\Utils\format_items(
			'table',
			$platforms,
			array( 'id', 'name', 'fetcher', 'mapper' )
		);
	}
}
