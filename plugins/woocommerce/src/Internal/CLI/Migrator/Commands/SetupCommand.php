<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Commands;

use WP_CLI;

/**
 * The command for interactively setting up platform credentials.
 */
class SetupCommand extends BaseCommand {

	/**
	 * Sets up the credentials for a given platform.
	 *
	 * ## OPTIONS
	 *
	 * [--platform=<platform>]
	 * : The platform to set up credentials for. Defaults to 'shopify'.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc migrate setup
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ) {
		$platform = $this->get_platform( $assoc_args );
		$this->handle_credential_setup( $platform );
		WP_CLI::success( 'Credentials saved successfully.' );
	}
}
