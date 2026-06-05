<?php
/**
 * Includes the composer Autoloader used for packages and classes in the src/ directory.
 */

namespace Automattic\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Autoloader class.
 *
 * @since 3.7.0
 */
class Autoloader {

	/**
	 * Static-only class.
	 */
	private function __construct() {}

	/**
	 * Require the autoloader and return the result.
	 *
	 * If the autoloader is not present, let's log the failure and display a nice admin notice.
	 *
	 * @return boolean
	 */
	public static function init() {
		$autoloader = dirname( __DIR__ ) . '/vendor/autoload_packages.php';

		if ( ! is_readable( $autoloader ) ) {
			self::missing_autoloader();
			return false;
		}

		$autoloader_result = require $autoloader;
		if ( ! $autoloader_result ) {
			return false;
		}

		return $autoloader_result;
	}

	/**
	 * Build a WooCommerce-scoped Composer PSR-4 ClassLoader to use as a fallback
	 * to the Jetpack autoloader.
	 *
	 * The Jetpack autoloader reads its classmap into an in-memory snapshot once
	 * per request and never refreshes it. During a WordPress in-place upgrade the
	 * plugin files are swapped mid-request, so a class that is new in the upgraded
	 * version cannot be found in the snapshot and the request fatals. This loader,
	 * registered as an appended (lowest-priority) fallback, resolves such classes
	 * from disk via PSR-4.
	 *
	 * Scoped to `Automattic\WooCommerce*` namespaces only: the fatal is always a
	 * WooCommerce class, and narrowing the map keeps the fallback from ever
	 * resolving an unrelated vendor package from WooCommerce's bundled copy.
	 *
	 * Returns the configured (but NOT registered) loader so the caller controls
	 * registration and tests can exercise it without touching the global SPL stack.
	 *
	 * @since 11.0.0
	 *
	 * @return \Composer\Autoload\ClassLoader|null The loader, or null if the
	 *                                             Composer files are unavailable or
	 *                                             a foreign ClassLoader shape is present.
	 */
	public static function build_woocommerce_psr4_fallback(): ?\Composer\Autoload\ClassLoader {
		$base     = dirname( __DIR__ );
		$psr4_map = $base . '/vendor/composer/autoload_psr4.php';

		if ( ! is_readable( $psr4_map ) ) {
			return null;
		}

		// Reuse an already-loaded ClassLoader (another plugin or wp-cli may have
		// loaded it from a different path); requiring our copy then would fatal
		// with "Cannot declare class ... already in use".
		if ( ! class_exists( \Composer\Autoload\ClassLoader::class, false ) ) {
			$classloader_file = $base . '/vendor/composer/ClassLoader.php';
			if ( ! is_readable( $classloader_file ) ) {
				return null;
			}
			require_once $classloader_file;
		}

		try {
			$psr4_entries = require $psr4_map;
			if ( ! is_array( $psr4_entries ) ) {
				return null;
			}

			$loader = new \Composer\Autoload\ClassLoader();
			foreach ( $psr4_entries as $namespace => $paths ) {
				if ( 0 === strpos( $namespace, 'Automattic\\WooCommerce\\' ) ) {
					$loader->setPsr4( $namespace, $paths );
				}
			}
			return $loader;
		} catch ( \Throwable $e ) {
			// Foreign/ancient ClassLoader shape — skip the fallback rather than fatal.
			return null;
		}
	}

	/**
	 * Register the WooCommerce-scoped PSR-4 fallback as an appended (lowest-priority)
	 * SPL autoloader, so it is consulted only after every other autoloader — including
	 * the primary Jetpack autoloader — has missed.
	 *
	 * Rather than registering a single long-lived `ClassLoader` instance, this builds a
	 * fresh loader on each miss. Composer's `ClassLoader` records a per-instance negative
	 * cache (`missingClasses`) on a PSR-4 miss and short-circuits subsequent lookups for
	 * that class. A shared instance would therefore cache a miss for a class probed
	 * *before* an in-place upgrade swaps the files, then keep refusing that same class
	 * *after* the new file is on disk — for the remainder of the request. A new loader per
	 * miss keeps every resolution honest while still reusing Composer's PSR-4 resolution.
	 *
	 * The handler is scoped to `Automattic\WooCommerce*` classes and degrades gracefully:
	 * a foreign/malformed `ClassLoader` yields a miss rather than fataling the autoload path.
	 *
	 * @since 11.0.0
	 *
	 * @return \Closure|null The registered autoloader, or null if the Composer files are
	 *                       unavailable (nothing was registered).
	 */
	public static function register_woocommerce_psr4_fallback(): ?\Closure {
		// Build once to validate availability and capture the WooCommerce-scoped PSR-4 map;
		// the handler rebuilds a fresh loader per miss from these captured entries.
		$prototype = self::build_woocommerce_psr4_fallback();
		if ( null === $prototype ) {
			return null;
		}
		$psr4_entries = $prototype->getPrefixesPsr4();

		$handler = static function ( string $class_name ) use ( $psr4_entries ) {
			if ( 0 !== strpos( $class_name, 'Automattic\\WooCommerce\\' ) ) {
				return;
			}

			$file = false;
			try {
				// Fresh loader per miss — see the method docblock for why a shared
				// instance would poison its own negative cache across the file swap.
				$loader = new \Composer\Autoload\ClassLoader();
				foreach ( $psr4_entries as $namespace => $paths ) {
					$loader->setPsr4( $namespace, $paths );
				}
				$file = $loader->findFile( $class_name );
			} catch ( \Throwable $e ) {
				// Foreign/malformed ClassLoader — miss rather than fatal the autoload path.
				return;
			}

			if ( false !== $file ) {
				require $file;
			}
		};

		spl_autoload_register( $handler, true, false );

		return $handler;
	}

	/**
	 * If the autoloader is missing, add an admin notice.
	 */
	protected static function missing_autoloader() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// This message is not translated as at this point it's too early to load translations.
			error_log(  // phpcs:ignore
				esc_html( 'Your installation of WooCommerce is incomplete. If you installed WooCommerce from GitHub, please refer to this document to set up your development environment: https://developer.woocommerce.com/docs/contribution/contributing/#setting-up-your-development-environment' )
			);
		}
		add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p>
						<?php
						printf(
							/* translators: 1: is a link to a support document. 2: closing link */
							esc_html__( 'Your installation of WooCommerce is incomplete. If you installed WooCommerce from GitHub, %1$splease refer to this document%2$s to set up your development environment.', 'woocommerce' ),
							'<a href="' . esc_url( 'https://developer.woocommerce.com/docs/contribution/contributing/#setting-up-your-development-environment' ) . '" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}
		);
	}
}
