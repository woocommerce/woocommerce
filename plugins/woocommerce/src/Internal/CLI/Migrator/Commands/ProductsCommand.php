<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Commands;

use WP_CLI;

/**
 * The products command.
 */
final class ProductsCommand extends BaseCommand {
	/**
	 * The main execution logic for the command.
	 *
	 * [--platform=<platform>]
	 * : The platform to migrate products from.
	 * ---
	 * default: shopify
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * @param array $args       The positional arguments.
	 * @param array $assoc_args The associative arguments.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$platform = $this->get_platform( $assoc_args );

		if ( ! $this->credential_manager->has_credentials( $platform ) ) {
			WP_CLI::log( "Credentials for '{$platform}' not found. Let's set them up." );
			$this->handle_credential_setup( $platform );
			WP_CLI::success( 'Credentials saved successfully. Please run the command again to begin the migration.' );
			return;
		}

		// The logic will be handled by the Products_Controller.
		// For now, we just show a success message if credentials exist.
		WP_CLI::success( 'Credentials found. Proceeding with migration...' );
	}
}
