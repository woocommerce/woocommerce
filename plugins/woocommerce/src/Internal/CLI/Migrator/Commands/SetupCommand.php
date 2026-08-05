<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Commands;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
use WP_CLI;

/**
 * The command for interactively setting up platform credentials.
 */
class SetupCommand {

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
	final public function init( CredentialManager $credential_manager, PlatformRegistry $platform_registry ): void {
		$this->credential_manager = $credential_manager;
		$this->platform_registry  = $platform_registry;
	}

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
		// Resolve and validate the platform.
		$platform              = $this->platform_registry->resolve_platform( $assoc_args );
		$platform_display_name = $this->platform_registry->get_platform_display_name( $platform );

		// Get platform-specific credential fields and set them up.
		$required_fields = $this->platform_registry->get_platform_credential_fields( $platform );
		if ( empty( $required_fields ) ) {
			WP_CLI::error( "The platform '{$platform_display_name}' does not have configured credential fields." );
		}

		$this->credential_manager->setup_credentials( $platform, $required_fields );
		WP_CLI::success( 'Credentials saved successfully.' );
	}
}
