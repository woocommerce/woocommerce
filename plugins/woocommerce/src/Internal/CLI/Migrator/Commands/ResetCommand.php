<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Commands;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
use WP_CLI;

/**
 * The command for resetting platform credentials.
 */
class ResetCommand {

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
		// Resolve and validate the platform.
		$platform = $this->platform_registry->resolve_platform( $assoc_args );

		if ( ! $this->credential_manager->has_credentials( $platform ) ) {
			WP_CLI::warning( "No credentials found for '{$platform}' to reset." );
			return;
		}

		$this->credential_manager->delete_credentials( $platform );

		WP_CLI::success( "Credentials for the '{$platform}' platform have been cleared." );
	}
}
