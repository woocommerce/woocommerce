<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Core;

use InvalidArgumentException;
use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformFetcherInterface;
use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformMapperInterface;
use WP_CLI;

/**
 * PlatformRegistry class.
 *
 * This class is responsible for loading and providing access to registered migration platforms.
 */
class PlatformRegistry {

	/**
	 * An array to hold the configuration for all registered platforms.
	 *
	 * @var array
	 */
	private array $platforms = array();

	/**
	 * The credential manager instance.
	 *
	 * @var CredentialManager
	 */
	private CredentialManager $credential_manager;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_platforms();
	}

	/**
	 * Initialize the registry with dependencies.
	 *
	 * @internal
	 * @param CredentialManager $credential_manager The credential manager.
	 */
	final public function init( CredentialManager $credential_manager ): void {
		$this->credential_manager = $credential_manager;
	}

	/**
	 * Loads platforms discovered via a filter.
	 *
	 * It also validates that each registered platform provides both a fetcher and a mapper class.
	 */
	private function load_platforms(): void {
		/**
		 * Filters the list of registered migration platforms.
		 *
		 * External platform plugins should hook into this filter to register themselves.
		 * Each platform plugin is responsible for its own autoloading and initialization.
		 *
		 * @param array $platforms An associative array of platform configurations.
		 *                         Each key is a unique platform ID (e.g., 'shopify'), and the value
		 *                         is another array containing 'name', 'fetcher', and 'mapper' class names.
		 * @since 1.0.0
		 */
		$platforms = apply_filters( 'woocommerce_migrator_platforms', array() );

		if ( ! is_array( $platforms ) ) {
			return;
		}

		foreach ( $platforms as $platform_id => $config ) {
			// Validate that required keys exist and have valid values.
			if ( isset( $config['fetcher'], $config['mapper'] ) &&
				is_string( $config['fetcher'] ) && ! empty( $config['fetcher'] ) &&
				is_string( $config['mapper'] ) && ! empty( $config['mapper'] ) ) {
				$this->platforms[ $platform_id ] = $config;
			}
		}
	}

	/**
	 * Returns the entire array of registered platform configurations.
	 *
	 * @return array
	 */
	public function get_platforms(): array {
		return $this->platforms;
	}

	/**
	 * Returns the configuration array for a single, specified platform ID.
	 *
	 * @param string $platform_id The ID of the platform (e.g., 'shopify').
	 *
	 * @return array|null The platform configuration or null if not found.
	 */
	public function get_platform( string $platform_id ): ?array {
		return $this->platforms[ $platform_id ] ?? null;
	}

	/**
	 * Retrieves and instantiates the fetcher class for a given platform.
	 *
	 * @param string $platform_id The ID of the platform.
	 *
	 * @return PlatformFetcherInterface An instance of the platform's fetcher class.
	 *
	 * @throws InvalidArgumentException If the platform is not found, fetcher class is invalid, or credentials are not configured.
	 */
	public function get_fetcher( string $platform_id ): PlatformFetcherInterface {
		$platform = $this->get_platform( $platform_id );

		if ( ! $platform ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %s: Platform ID */
					esc_html__( 'Platform %s not found.', 'woocommerce' ),
					esc_html( $platform_id )
				)
			);
		}

		$fetcher_class = $platform['fetcher'];

		// Validate that fetcher class is a non-empty string.
		if ( ! is_string( $fetcher_class ) || empty( $fetcher_class ) ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %s: Platform ID */
					esc_html__( 'Invalid fetcher class for platform %s. Fetcher must be a non-empty string.', 'woocommerce' ),
					esc_html( $platform_id )
				)
			);
		}

		if ( ! class_exists( $fetcher_class ) ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %1$s: Platform ID, %2$s: Class name */
					esc_html__( 'Invalid fetcher class for platform %1$s. Class %2$s does not exist.', 'woocommerce' ),
					esc_html( $platform_id ),
					esc_html( $fetcher_class )
				)
			);
		}

		if ( ! in_array( PlatformFetcherInterface::class, class_implements( $fetcher_class ), true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %1$s: Platform ID, %2$s: Class name, %3$s: Interface name */
					esc_html__( 'Invalid fetcher class for platform %1$s. Class %2$s does not implement %3$s.', 'woocommerce' ),
					esc_html( $platform_id ),
					esc_html( $fetcher_class ),
					esc_html( PlatformFetcherInterface::class )
				)
			);
		}

		// Get credentials from credential manager and pass to fetcher constructor.
		$credentials = $this->credential_manager->get_credentials( $platform_id );
		if ( ! is_array( $credentials ) ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %s: platform ID */
					'No credentials found for platform "%s". Please configure credentials using: wp wc migrate setup',
					esc_html( $platform_id )
				)
			);
		}
		return new $fetcher_class( $credentials );
	}

	/**
	 * Retrieves and instantiates the mapper class for a given platform.
	 *
	 * @param string $platform_id The ID of the platform.
	 * @param array  $args Optional arguments to pass to the mapper constructor.
	 *
	 * @return PlatformMapperInterface An instance of the platform's mapper class.
	 *
	 * @throws InvalidArgumentException If the platform is not found or the mapper class is invalid.
	 */
	public function get_mapper( string $platform_id, array $args = array() ): PlatformMapperInterface {
		$platform = $this->get_platform( $platform_id );

		if ( ! $platform ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %s: Platform ID */
					esc_html__( 'Platform %s not found.', 'woocommerce' ),
					esc_html( $platform_id )
				)
			);
		}

		$mapper_class = $platform['mapper'];

		// Validate that mapper class is a non-empty string.
		if ( ! is_string( $mapper_class ) || empty( $mapper_class ) ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %s: Platform ID */
					esc_html__( 'Invalid mapper class for platform %s. Mapper must be a non-empty string.', 'woocommerce' ),
					esc_html( $platform_id )
				)
			);
		}

		if ( ! class_exists( $mapper_class ) ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %1$s: Platform ID, %2$s: Class name */
					esc_html__( 'Invalid mapper class for platform %1$s. Class %2$s does not exist.', 'woocommerce' ),
					esc_html( $platform_id ),
					esc_html( $mapper_class )
				)
			);
		}

		if ( ! in_array( PlatformMapperInterface::class, class_implements( $mapper_class ), true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %1$s: Platform ID, %2$s: Class name, %3$s: Interface name */
					esc_html__( 'Invalid mapper class for platform %1$s. Class %2$s does not implement %3$s.', 'woocommerce' ),
					esc_html( $platform_id ),
					esc_html( $mapper_class ),
					esc_html( PlatformMapperInterface::class )
				)
			);
		}

		// If arguments are provided, instantiate manually to pass constructor args.
		// Otherwise, use the WooCommerce DI container for dependency injection.
		if ( ! empty( $args ) ) {
			return new $mapper_class( $args );
		} else {
			$container = wc_get_container();
			return $container->get( $mapper_class );
		}
	}

	/**
	 * Determines the platform to use from command arguments, with validation and fallback.
	 *
	 * @param array  $assoc_args     Associative arguments from the command.
	 * @param string $default_platform The default platform to use if none specified.
	 *
	 * @return string The validated platform slug.
	 */
	public function resolve_platform( array $assoc_args, string $default_platform = 'shopify' ): string {
		$platform = $assoc_args['platform'] ?? null;

		if ( empty( $platform ) ) {
			$platform              = $default_platform;
			$platform_display_name = $this->get_platform_display_name( $platform );
			WP_CLI::log( "Platform not specified, using default: '{$platform_display_name}'." );
		}

		// Validate the platform exists.
		if ( ! $this->get_platform( $platform ) ) {
			$available_platforms = array_keys( $this->get_platforms() );
			if ( empty( $available_platforms ) ) {
				WP_CLI::error( 'No platforms are currently registered. Please ensure platform plugins are installed and activated.' );
			} else {
				WP_CLI::error(
					sprintf(
						"Platform '%s' is not registered. Available platforms: %s",
						$platform,
						implode( ', ', $available_platforms )
					)
				);
			}
		}

		return $platform;
	}

	/**
	 * Get platform-specific credential fields for setup prompts.
	 *
	 * @param string $platform_slug The platform identifier.
	 *
	 * @return array Array of field_name => prompt_text pairs.
	 */
	public function get_platform_credential_fields( string $platform_slug ): array {
		$platform = $this->get_platform( $platform_slug );
		if ( ! is_array( $platform ) ) {
			return array();
		}
		$credentials = $platform['credentials'] ?? array();
		return is_array( $credentials ) ? $credentials : array();
	}

	/**
	 * Gets the display name for a platform.
	 *
	 * @param string $platform_slug The platform identifier (e.g., 'shopify').
	 *
	 * @return string The proper display name (e.g., 'Shopify').
	 */
	public function get_platform_display_name( string $platform_slug ): string {
		$platform = $this->get_platform( $platform_slug );

		if ( is_array( $platform ) && isset( $platform['name'] ) ) {
			return $platform['name'];
		}

		// Fallback to ucfirst if platform not found or no name configured.
		return ucfirst( $platform_slug );
	}
}
