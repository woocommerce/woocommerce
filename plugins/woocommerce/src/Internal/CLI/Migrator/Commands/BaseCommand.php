<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Commands;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use WP_CLI;

/**
 * Abstract base class for migrator commands.
 */
abstract class BaseCommand {

	/**
	 * The credential manager.
	 *
	 * @var CredentialManager
	 */
	protected CredentialManager $credential_manager;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @param CredentialManager $credential_manager The credential manager.
	 *
	 * @internal
	 */
	final public function init( CredentialManager $credential_manager ): void {
		$this->credential_manager = $credential_manager;
	}

	/**
	 * Determines the platform to use, defaulting to 'shopify'.
	 *
	 * @param array $assoc_args Associative arguments from the command.
	 *
	 * @return string The platform slug.
	 */
	protected function get_platform( array $assoc_args ): string {
		$platform = $assoc_args['platform'] ?? null;
		if ( is_null( $platform ) ) {
			$platform = 'shopify';
			WP_CLI::log( "Platform not specified, using default: '{$platform}'." );
		}

		return $platform;
	}

	/**
	 * Handles the interactive credential setup process.
	 *
	 * @param string $platform The platform slug.
	 *
	 * @return void
	 */
	protected function handle_credential_setup( string $platform ): void {
		// For now, we only support Shopify.
		if ( 'shopify' !== $platform ) {
			WP_CLI::error( "The specified platform '{$platform}' is not supported for setup." );
		}

		WP_CLI::log( 'Configuring credentials for ' . ucfirst( $platform ) . '...' );

		$required_fields = array(
			'api_key'  => 'Enter your Shopify API Access Token:',
			'shop_url' => 'Enter your Shopify store URL (e.g., my-store.myshopify.com):',
		);

		$credentials = $this->credential_manager->prompt_for_credentials( $required_fields );
		$this->credential_manager->save_credentials( $platform, $credentials );
	}
}
