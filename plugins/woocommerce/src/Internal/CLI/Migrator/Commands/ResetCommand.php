<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Commands;

use WP_CLI;

/**
 * The command for resetting platform credentials.
 */
class ResetCommand extends BaseCommand {

	/**
	 * Resets (deletes) the credentials for a given platform.
	 *
	 * ## OPTIONS
	 *
	 * [--platform=<platform>]
	 * : The platform to reset credentials for. Defaults to 'shopify'.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc migrate reset
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ) {
		$platform = $this->get_platform( $assoc_args );

		if ( ! $this->credential_manager->has_credentials( $platform ) ) {
			WP_CLI::warning( "No credentials found for '{$platform}' to reset." );
			return;
		}

		$this->credential_manager->delete_credentials( $platform );

		WP_CLI::success( "Credentials for the '{$platform}' platform have been cleared." );
	}
}
