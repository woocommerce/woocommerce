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
	 * ## EXAMPLES
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

		// The logic will be handled by the Products_Controller.
		// For now, we just show a success message if credentials exist.
		WP_CLI::success( 'Credentials found. Proceeding with migration...' );
	}
}
