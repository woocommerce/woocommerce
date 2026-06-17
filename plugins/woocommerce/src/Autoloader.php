<?php
/**
 * Includes the composer Autoloader used for packages and classes in the src/ directory.
 */

namespace Automattic\WooCommerce;

use Composer\Autoload\ClassLoader;

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
	 * Scoped to the first-party `Automattic\WooCommerce\` (src/) namespace only —
	 * the family that actually fatals during an in-place upgrade (e.g.
	 * `Enums\DefaultCustomerAddress`). Every other prefix in the Composer map is
	 * deliberately excluded: bundled third-party packages
	 * (`Automattic\WooCommerce\Vendor\` → lib/packages) so the fallback can never
	 * load WooCommerce's bundled copy over the version the Jetpack autoloader
	 * coordinates across plugins, and the non-runtime prefixes (Blueprint, tests,
	 * build tooling) which never fatal during a front-end upgrade request.
	 *
	 * Returns the configured (but NOT registered) loader so the caller controls
	 * registration and tests can exercise it without touching the global SPL stack.
	 *
	 * @internal Public only so {@see self::register_woocommerce_psr4_fallback()} and
	 *           the unit tests can build the loader in isolation.
	 *
	 * @since 11.0.0
	 *
	 * @return ClassLoader|null The loader, or null if the Composer files are
	 *                          unavailable or a foreign ClassLoader shape is present.
	 */
	public static function build_woocommerce_psr4_fallback(): ?ClassLoader {
		$base     = dirname( __DIR__ );
		$psr4_map = $base . '/vendor/composer/autoload_psr4.php';

		if ( ! is_readable( $psr4_map ) ) {
			return null;
		}

		try {
			// Reuse an already-loaded ClassLoader (another plugin or wp-cli may have
			// loaded it from a different path); requiring our copy then would fatal
			// with "Cannot declare class ... already in use". Kept inside the try so a
			// torn/partially-written ClassLoader.php during a vendor-bundle upgrade
			// degrades to a null fallback instead of fataling the bootstrap.
			if ( ! class_exists( ClassLoader::class, false ) ) {
				$classloader_file = $base . '/vendor/composer/ClassLoader.php';
				if ( ! is_readable( $classloader_file ) ) {
					return null;
				}
				require_once $classloader_file;
			}

			$psr4_entries = require $psr4_map;
			if ( ! is_array( $psr4_entries ) ) {
				return null;
			}

			$loader = new ClassLoader();
			foreach ( $psr4_entries as $namespace => $paths ) {
				// First-party src/ only — exclude bundled Vendor\ and non-runtime prefixes.
				if ( 'Automattic\\WooCommerce\\' === $namespace ) {
					$loader->setPsr4( $namespace, $paths );
				}
			}
			return $loader;
		} catch ( \Throwable $e ) {
			// Foreign/ancient ClassLoader shape, or a torn Composer file — skip the
			// fallback rather than fatal the bootstrap.
			return null;
		}
	}

	/**
	 * Register the WooCommerce-scoped PSR-4 fallback as an appended (lowest-priority)
	 * SPL autoloader, so it is consulted only after every other autoloader — including
	 * the primary Jetpack autoloader — has missed.
	 *
	 * The handler resolves each miss with a throwaway loader (see {@see self::find_scoped_file()})
	 * rather than a single long-lived `ClassLoader`. Composer's `ClassLoader` records a
	 * per-instance negative cache (`missingClasses`) on a PSR-4 miss and short-circuits
	 * subsequent lookups for that class; a shared instance would therefore cache a miss for a
	 * class probed *before* an in-place upgrade swaps the files, then keep refusing that same
	 * class *after* the new file is on disk — for the remainder of the request. A fresh loader
	 * per miss keeps every resolution honest while still reusing Composer's PSR-4 resolution.
	 *
	 * Registration is idempotent: at most one handler is ever added per request.
	 *
	 * Degrades to null (nothing registered) if the Composer files are unavailable or a
	 * foreign/malformed `ClassLoader` shape is present. The handler likewise leaves a class
	 * unresolved — rather than fataling — if a resolved file is torn/unparseable mid-upgrade,
	 * so a defensive `class_exists()` probe during an upgrade gets `false` instead of an error.
	 * The failed attempt stays retryable: the handler records only the files it has executed
	 * cleanly, so once the upgrade finishes writing a file that previously failed to parse,
	 * link, or run, a later probe in the same request re-attempts and loads it. It never
	 * re-executes a path it already loaded (an uncatchable "Cannot redeclare class" fatal);
	 * it cannot, however, guard the first execution of a file that declares a class already
	 * loaded elsewhere under a non-matching PSR-4 path.
	 *
	 * @since 11.0.0
	 *
	 * @return \Closure|null The registered autoloader, or null if no fallback was registered.
	 */
	public static function register_woocommerce_psr4_fallback(): ?\Closure {
		static $registered_handler = null;

		// Idempotent: a re-entrant bootstrap, WP-CLI, or a test without teardown must not
		// stack duplicate handlers (each one re-builds a loader + stats the FS on every miss).
		if ( null !== $registered_handler ) {
			return $registered_handler;
		}

		// Build once ONLY to validate availability and snapshot the scoped PSR-4 map. The handler
		// rebuilds a throwaway loader per miss from this captured map (for performance — the map
		// is read once, not on every miss). Do NOT collapse this into a shared loader or a per-miss
		// build() call: either reintroduces the negative-cache bug the fresh-per-miss design avoids.
		//
		// A foreign/malformed ClassLoader shape must degrade to "no fallback" rather than fatal the
		// bootstrap — matching build()'s own contract. The guard is twofold, because the map reaches
		// find_scoped_file()'s `array $psr4_entries` parameter on every miss, outside the handler's
		// own try/catch: the try/catch here handles a getPrefixesPsr4() that THROWS, and
		// read_scoped_psr4_map() handles one that RETURNS a non-array (the method carries no
		// return-type declaration, so an older/foreign loader can) — which would otherwise raise an
		// uncatchable TypeError on the first autoload miss.
		try {
			$availability_probe = self::build_woocommerce_psr4_fallback();
			if ( null === $availability_probe ) {
				self::log_fallback_declined( 'the Composer files are unavailable or a foreign ClassLoader shape was rejected by build()' );
				return null;
			}
			$psr4_entries = self::read_scoped_psr4_map( $availability_probe );
			if ( null === $psr4_entries ) {
				self::log_fallback_declined( 'getPrefixesPsr4() returned a non-array shape' );
				return null;
			}
		} catch ( \Throwable $e ) {
			self::log_fallback_declined( 'building the availability probe threw: ' . $e->getMessage() );
			return null;
		}

		$handler = static function ( string $class_name ) use ( $psr4_entries ) {
			/*
			 * Paths this handler has executed, so a repeated probe never re-runs a file:
			 * - $loaded: includes that returned cleanly. Re-including one would redeclare its
			 *   class — an UNCATCHABLE "Cannot redeclare class" fatal (e.g. a probe whose PSR-4
			 *   file declares a different class name, then a second probe of the same path).
			 * - $attempted: every path we have tried, success or failure. Used only to tell our
			 *   own failed (and therefore retryable) attempt apart from a file some other loader
			 *   already executed — see the get_included_files() check below.
			 */
			static $loaded    = array();
			static $attempted = array();

			$file = self::find_scoped_file( $class_name, $psr4_entries );
			if ( null === $file ) {
				return;
			}

			$canonical = realpath( $file );
			if ( false !== $canonical ) {
				// Already executed cleanly by this handler: re-including would redeclare.
				if ( isset( $loaded[ $canonical ] ) ) {
					return;
				}

				/*
				 * Executed by another mechanism (the primary autoloader, a manual require) but
				 * never attempted by us: re-including risks the same redeclare fatal, so skip.
				 * A path WE attempted and that threw is deliberately excluded from this check so
				 * it stays retryable once the upgrade finishes writing it.
				 */
				if ( ! isset( $attempted[ $canonical ] ) && in_array( $canonical, get_included_files(), true ) ) {
					return;
				}
				$attempted[ $canonical ] = true;
			}

			try {
				/*
				 * Deliberately a plain `include`, NOT `require_once`: the *_once variants record
				 * a path in the engine's included-files table BEFORE compiling it, so a torn
				 * file's caught error would mark the path included and every later attempt would
				 * no-op — the completed file could never load for the rest of the request. A
				 * plain include lets us record success ourselves (in $loaded, below) only after
				 * it returns, so a file that fails to parse, link (e.g. a parent not yet written
				 * mid-upgrade), or run stays retryable. A file that vanishes between findFile()
				 * and here degrades to a warning plus a FALSE return, where require would fatal —
				 * no Throwable reaches the catch below, so the return value is the only signal
				 * that nothing was compiled or executed.
				 */
				$included = include $file;

				// A false return means the include never OPENED the file (deleted/unreadable
				// mid-upgrade): nothing ran, so re-including is safe and the path must stay
				// retryable — recording it as loaded would skip the restored file for the
				// rest of the request. A successful include of a src/ file never yields false
				// (class files return 1; the odd config file returns an array), so false here
				// always means the open failed.
				if ( false !== $included && false !== $canonical ) {
					$loaded[ $canonical ] = true;
				}
			} catch ( \Throwable $e ) {
				/*
				 * A torn/partially-written file mid-upgrade must not turn a class probe into a
				 * fatal: leave the class unresolved so e.g. class_exists() returns false and the
				 * request continues, instead of an uncatchable error escaping the autoload handler.
				 * Surface it under WP_DEBUG so a genuine (non-upgrade) parse/link error in a
				 * shipped src/ file is not an invisible miss.
				 */
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						sprintf(
							'WooCommerce PSR-4 fallback could not load %1$s for %2$s: %3$s',
							$file,
							$class_name,
							$e->getMessage()
						)
					);
				}
				return;
			}
		};

		spl_autoload_register( $handler, true, false );
		$registered_handler = $handler;

		return $handler;
	}

	/**
	 * Log, under WP_DEBUG only, why the PSR-4 fallback declined to register.
	 *
	 * When the fallback bails to "no fallback", the downstream "class not found" fatal an operator
	 * eventually sees during an in-place upgrade carries no breadcrumb back to this decision — yet
	 * that breadcrumb is the most useful signal in the system, since the fallback exists precisely
	 * to prevent that fatal. Mirrors the WP_DEBUG error_log the registered handler already emits for
	 * a caught autoload error.
	 *
	 * @since 11.0.0
	 *
	 * @param string $reason Human-readable reason the fallback was not registered.
	 */
	private static function log_fallback_declined( string $reason ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				'WooCommerce PSR-4 fallback not registered: ' . $reason
			);
		}
	}

	/**
	 * Read the scoped PSR-4 prefix map out of a built fallback loader, degrading to null on any
	 * non-array shape.
	 *
	 * {@see ClassLoader::getPrefixesPsr4()} carries no return-type declaration, so an older or
	 * foreign `Composer\Autoload\ClassLoader` — one another plugin or wp-cli loaded from a
	 * different path, then reused by {@see self::build_woocommerce_psr4_fallback()} — may return a
	 * non-array. The registered handler passes this map straight into {@see self::find_scoped_file()},
	 * whose `array $psr4_entries` parameter would raise an uncatchable TypeError on the first
	 * autoload miss, outside the handler's own try/catch. Validating here keeps the fallback's
	 * degrade-don't-fatal contract whole, mirroring the is_array() guard build() already applies to
	 * the file-sourced map.
	 *
	 * @internal Public only so the unit tests can reach it; its sole production caller is
	 *           {@see self::register_woocommerce_psr4_fallback()}.
	 *
	 * @since 11.0.0
	 *
	 * @param ClassLoader $loader A loader returned by build_woocommerce_psr4_fallback().
	 *
	 * @return array<string, list<string>>|null The scoped PSR-4 map, or null on a non-array shape.
	 */
	public static function read_scoped_psr4_map( ClassLoader $loader ): ?array {
		$psr4_entries = $loader->getPrefixesPsr4();

		return is_array( $psr4_entries ) ? $psr4_entries : null;
	}

	/**
	 * Read a resolved file path out of a fallback loader's findFile(), degrading to null on any
	 * non-string shape.
	 *
	 * The sibling of {@see self::read_scoped_psr4_map()}: {@see ClassLoader::findFile()} carries no
	 * return-type declaration either, so the same older or foreign `Composer\Autoload\ClassLoader`
	 * reused by {@see self::build_woocommerce_psr4_fallback()} may return a non-string. The caller,
	 * {@see self::find_scoped_file()}, declares a `: ?string` return, so a non-string result would
	 * raise an uncatchable TypeError at that return statement — which, on an autoload miss, runs
	 * outside the registered handler's own try/catch, exactly the shape this fallback guards against
	 * for getPrefixesPsr4(). Composer's own miss sentinel is `false`, which is not a string and so
	 * degrades to null here, unchanged. Validating keeps the degrade-don't-fatal contract whole.
	 *
	 * @internal Public only so the unit tests can reach it; its sole production caller is
	 *           {@see self::find_scoped_file()}.
	 *
	 * @since 11.0.0
	 *
	 * @param ClassLoader $loader     A loader built from the scoped PSR-4 map.
	 * @param string      $class_name Fully-qualified class name to resolve.
	 *
	 * @return string|null The resolved absolute file path, or null on a miss or non-string shape.
	 */
	public static function read_scoped_file_path( ClassLoader $loader, string $class_name ): ?string {
		$file = $loader->findFile( $class_name );

		return is_string( $file ) ? $file : null;
	}

	/**
	 * Resolve a WooCommerce `src/` class to a file via a throwaway PSR-4 `ClassLoader`.
	 *
	 * A new loader per call is deliberate (and is the property the fallback exists for): Composer's
	 * `ClassLoader` keeps a per-instance negative cache, so a single shared instance that missed a
	 * class *before* an in-place upgrade swapped the files would keep refusing it *after* the new
	 * file is on disk. Building fresh here guarantees a class missed pre-swap resolves post-swap,
	 * within the same request.
	 *
	 * @internal Public only so the unit tests can reach it; in production only the registered
	 *           autoload handler calls it.
	 *
	 * @param string                      $class_name   Fully-qualified class name.
	 * @param array<string, list<string>> $psr4_entries Pre-scoped PSR-4 prefix => dirs map.
	 *
	 * @return string|null Absolute file path to require, or null on a miss or a
	 *                     non-`Automattic\WooCommerce\` class.
	 */
	public static function find_scoped_file( string $class_name, array $psr4_entries ): ?string {
		if ( 0 !== strpos( $class_name, 'Automattic\\WooCommerce\\' ) ) {
			return null;
		}

		try {
			$loader = new ClassLoader();
			foreach ( $psr4_entries as $namespace => $paths ) {
				$loader->setPsr4( $namespace, $paths );
			}

			// read_scoped_file_path() guards findFile()'s untyped return: a non-string would
			// otherwise TypeError against this method's `: ?string`, outside the handler's try/catch.
			return self::read_scoped_file_path( $loader, $class_name );
		} catch ( \Throwable $e ) {
			// Foreign/malformed ClassLoader — miss rather than fatal the autoload path.
			return null;
		}
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
