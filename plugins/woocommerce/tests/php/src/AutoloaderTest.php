<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests;

use Automattic\WooCommerce\Autoloader;
use Composer\Autoload\ClassLoader;

/**
 * Tests for the WooCommerce-scoped Composer PSR-4 fallback autoloader.
 *
 * Coverage boundary: register_woocommerce_psr4_fallback()'s decline-to-null path (a foreign-shaped
 * probe loader) is pinned only at the helper level — see read_scoped_psr4_map() and
 * read_scoped_file_path() below. It is not driven end-to-end through register_() because that would
 * need two production testing seams on a bootstrap surface: register_() binds
 * build_woocommerce_psr4_fallback() with early static binding (self::, so a subclass override won't
 * dispatch) and memoizes its handler in a function-static that the woocommerce.php bootstrap already
 * populates before any test runs (and PHP cannot reset a function-static). The helper tests guard
 * the same null-returning logic; if the null-check wiring in register_() is ever refactored, add
 * those seams to cover the contract end-to-end.
 *
 * @package Automattic\WooCommerce\Tests
 */
class AutoloaderTest extends \WC_Unit_Test_Case {

	/**
	 * The builder returns a ClassLoader scoped to the first-party `src/` namespace
	 * only: it resolves a real src class, and refuses the bundled `Vendor\` packages,
	 * non-WooCommerce vendor namespaces, and non-existent classes.
	 *
	 * @testdox build_woocommerce_psr4_fallback() resolves src classes only.
	 */
	public function test_build_woocommerce_psr4_fallback_scopes_to_src(): void {
		$sut = Autoloader::build_woocommerce_psr4_fallback();

		$this->assertInstanceOf(
			ClassLoader::class,
			$sut,
			'Builder must return a ClassLoader when the Composer files are present (they ship in the build).'
		);

		// Positive: resolves a real WooCommerce src class from disk via PSR-4.
		$this->assertNotFalse(
			$sut->findFile( 'Automattic\\WooCommerce\\Enums\\DefaultCustomerAddress' ),
			'Fallback must resolve a WooCommerce src class.'
		);

		// Excluded: bundled third-party under Vendor\ (lib/packages) must NOT resolve, so the
		// fallback can never load WooCommerce's bundled copy over the Jetpack-coordinated version.
		$this->assertFalse(
			$sut->findFile( 'Automattic\\WooCommerce\\Vendor\\Psr\\Container\\ContainerInterface' ),
			'Fallback must exclude bundled Vendor\\ packages.'
		);

		// Excluded: a non-WooCommerce vendor namespace that exists in the full map.
		$this->assertFalse(
			$sut->findFile( 'Opis\\JsonSchema\\Validator' ),
			'Fallback must be scoped to WooCommerce src and refuse non-WooCommerce namespaces.'
		);

		// Bogus: must not invent files for non-existent classes.
		$this->assertFalse(
			$sut->findFile( 'Automattic\\WooCommerce\\Nope\\Does_Not_Exist_XYZ' ),
			'Fallback must not resolve non-existent classes.'
		);
	}

	/**
	 * Each builder call returns a distinct ClassLoader, so Composer's per-instance
	 * negative cache (missingClasses) is never shared across resolutions.
	 *
	 * @testdox build_woocommerce_psr4_fallback() returns a fresh loader each call.
	 */
	public function test_build_woocommerce_psr4_fallback_is_not_shared(): void {
		$first  = Autoloader::build_woocommerce_psr4_fallback();
		$second = Autoloader::build_woocommerce_psr4_fallback();

		$this->assertInstanceOf( ClassLoader::class, $first );
		$this->assertInstanceOf( ClassLoader::class, $second );
		$this->assertNotSame(
			$first,
			$second,
			'Each call must return a distinct loader so the negative cache is never shared across resolutions.'
		);
	}

	/**
	 * The core guarantee: a class missed *before* its file lands on disk resolves
	 * *after*, within the same request — because each call resolves with a throwaway
	 * loader that carries no negative cache from the earlier miss.
	 *
	 * @testdox find_scoped_file() resolves a class once its file appears mid-request.
	 */
	public function test_find_scoped_file_resolves_after_the_file_appears(): void {
		$base  = sys_get_temp_dir() . '/wc_autoloader_' . str_replace( '.', '', uniqid( '', true ) );
		$file  = $base . '/Widget.php';
		$class = 'Automattic\\WooCommerce\\ReproNs\\Widget';
		$map   = array( 'Automattic\\WooCommerce\\ReproNs\\' => array( $base ) );

		try {
			wp_mkdir_p( $base );

			// Miss: the class file does not exist yet.
			$this->assertNull(
				Autoloader::find_scoped_file( $class, $map ),
				'Must miss while the class file is absent.'
			);

			// The file appears mid-request (as a WordPress in-place upgrade would swap it in).
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture; WP_Filesystem adds no value here.
			file_put_contents( $file, "<?php\nnamespace Automattic\\WooCommerce\\ReproNs;\nclass Widget {}\n" );
			clearstatcache( true, $file );

			// Resolve: a fresh loader (no carried-over negative cache) finds the new file.
			$resolved = Autoloader::find_scoped_file( $class, $map );
			$this->assertNotNull( $resolved, 'Must resolve once the file is on disk.' );
			$this->assertSame(
				realpath( $file ),
				realpath( (string) $resolved ),
				'Must resolve to the file that appeared on disk.'
			);
		} finally {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
			if ( is_dir( $base ) ) {
				rmdir( $base ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture cleanup.
			}
		}
	}

	/**
	 * The resolver ignores classes outside the `Automattic\WooCommerce\` namespace.
	 *
	 * @testdox find_scoped_file() ignores non-WooCommerce classes.
	 */
	public function test_find_scoped_file_ignores_non_woocommerce_classes(): void {
		$map = array( 'Automattic\\WooCommerce\\' => array( dirname( WC_PLUGIN_FILE ) . '/src' ) );

		$this->assertNull(
			Autoloader::find_scoped_file( 'Opis\\JsonSchema\\Validator', $map ),
			'Must ignore classes outside the Automattic\\WooCommerce\\ namespace.'
		);
	}

	/**
	 * End-to-end: the autoloader registered by the bootstrap actually `require`s a real
	 * src class that appears on disk after an earlier miss, in the same request.
	 *
	 * @testdox the registered handler requires a src class that appears after a miss.
	 */
	public function test_registered_handler_requires_an_appearing_src_class(): void {
		$handler = Autoloader::register_woocommerce_psr4_fallback();
		$this->assertInstanceOf( \Closure::class, $handler, 'Bootstrap must register a handler.' );

		$suffix = 'ReproFixture' . str_replace( '.', '', uniqid( '', true ) );
		$dir    = dirname( WC_PLUGIN_FILE ) . '/src/' . $suffix;
		$file   = $dir . '/Widget.php';
		$class  = 'Automattic\\WooCommerce\\' . $suffix . '\\Widget';

		$this->assertFalse( class_exists( $class, false ), 'Precondition: fixture class must not be loaded.' );

		try {
			// File absent: the handler is a no-op (miss), never a fatal.
			$handler( $class );
			$this->assertFalse(
				class_exists( $class, false ),
				'Handler must not load a class whose file is absent.'
			);

			// File appears mid-request: the handler resolves it from disk and requires it.
			wp_mkdir_p( $dir );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture; WP_Filesystem adds no value here.
			file_put_contents( $file, "<?php\nnamespace Automattic\\WooCommerce\\{$suffix};\nclass Widget {}\n" );
			clearstatcache( true, $file );

			$handler( $class );
			$this->assertTrue(
				class_exists( $class, false ),
				'Handler must require a src class that appeared on disk after an earlier miss.'
			);
		} finally {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
			if ( is_dir( $dir ) ) {
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture cleanup.
			}
		}
	}

	/**
	 * A torn/partially-written file resolved mid-upgrade must not fatal: the handler
	 * leaves the class unresolved (so e.g. class_exists() returns false) rather than
	 * letting the include's ParseError escape and kill the request.
	 *
	 * @testdox the registered handler degrades (does not fatal) on a torn class file.
	 */
	public function test_registered_handler_degrades_on_a_torn_class_file(): void {
		$handler = Autoloader::register_woocommerce_psr4_fallback();
		$this->assertInstanceOf( \Closure::class, $handler, 'Bootstrap must register a handler.' );

		$suffix = 'ReproTorn' . str_replace( '.', '', uniqid( '', true ) );
		$dir    = dirname( WC_PLUGIN_FILE ) . '/src/' . $suffix;
		$file   = $dir . '/Widget.php';
		$class  = 'Automattic\\WooCommerce\\' . $suffix . '\\Widget';

		try {
			wp_mkdir_p( $dir );
			// A torn / partially-written file mid-upgrade: a syntax error (unclosed class body).
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture; WP_Filesystem adds no value here.
			file_put_contents( $file, "<?php\nnamespace Automattic\\WooCommerce\\{$suffix};\nclass Widget {\n" );
			clearstatcache( true, $file );

			// The handler must not let the include's ParseError escape.
			$threw = false;
			try {
				$handler( $class );
			} catch ( \Throwable $e ) {
				$threw = true;
			}

			$this->assertFalse(
				$threw,
				'Handler must not let a torn-file ParseError escape — it must degrade to a miss.'
			);
			$this->assertFalse(
				class_exists( $class, false ),
				'A torn class file must be left unresolved, not loaded or fataled.'
			);
		} finally {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
			if ( is_dir( $dir ) ) {
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture cleanup.
			}
		}
	}

	/**
	 * A torn file that degraded to a miss must stay retryable: once the upgrade finishes
	 * writing the file, a later probe in the same request loads the class. With a
	 * `require_once` in the handler this fails — PHP records the path as included BEFORE
	 * compiling it, so the caught ParseError would poison every later attempt and the
	 * completed file could never load for the rest of the request.
	 *
	 * @testdox the registered handler loads a torn class file once it is completed.
	 */
	public function test_registered_handler_recovers_after_a_torn_file_is_completed(): void {
		$handler = Autoloader::register_woocommerce_psr4_fallback();
		$this->assertInstanceOf( \Closure::class, $handler, 'Bootstrap must register a handler.' );

		$suffix = 'ReproRetry' . str_replace( '.', '', uniqid( '', true ) );
		$dir    = dirname( WC_PLUGIN_FILE ) . '/src/' . $suffix;
		$file   = $dir . '/Widget.php';
		$class  = 'Automattic\\WooCommerce\\' . $suffix . '\\Widget';

		try {
			wp_mkdir_p( $dir );
			// A torn / partially-written file mid-upgrade: a syntax error (unclosed class body).
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture; WP_Filesystem adds no value here.
			file_put_contents( $file, "<?php\nnamespace Automattic\\WooCommerce\\{$suffix};\nclass Widget {\n" );
			clearstatcache( true, $file );

			// First probe: degrades to a miss (covered by the torn-file test above).
			$handler( $class );
			$this->assertFalse( class_exists( $class, false ), 'Precondition: torn file must degrade to a miss.' );

			// The upgrade finishes writing the file mid-request.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture; WP_Filesystem adds no value here.
			file_put_contents( $file, "<?php\nnamespace Automattic\\WooCommerce\\{$suffix};\nclass Widget {}\n" );
			clearstatcache( true, $file );

			$handler( $class );
			$this->assertTrue(
				class_exists( $class, false ),
				'Handler must load the class once the torn file is completed — the failed attempt must not poison the retry.'
			);
		} finally {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
			if ( is_dir( $dir ) ) {
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture cleanup.
			}
		}
	}

	/**
	 * The handler must never re-execute a file that already loaded successfully: a second
	 * include of a file whose class is already declared is an UNCATCHABLE "Cannot redeclare
	 * class" fatal. Reproduced here with a file whose declared class does not match the
	 * probed name, so the first probe executes the file without resolving the class and a
	 * second probe resolves the same, already-executed file. If the guard regresses, this
	 * test fatals the PHPUnit process rather than failing an assertion.
	 *
	 * @testdox the registered handler never re-executes an already-included file.
	 */
	public function test_registered_handler_skips_an_already_included_file(): void {
		$handler = Autoloader::register_woocommerce_psr4_fallback();
		$this->assertInstanceOf( \Closure::class, $handler, 'Bootstrap must register a handler.' );

		$suffix = 'ReproRogue' . str_replace( '.', '', uniqid( '', true ) );
		$dir    = dirname( WC_PLUGIN_FILE ) . '/src/' . $suffix;
		$file   = $dir . '/Widget.php';
		$class  = 'Automattic\\WooCommerce\\' . $suffix . '\\Widget';

		try {
			wp_mkdir_p( $dir );
			// A rogue file: parses fine but declares a class that does not match its PSR-4 path.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture; WP_Filesystem adds no value here.
			file_put_contents( $file, "<?php\nnamespace Automattic\\WooCommerce\\{$suffix};\nclass Mismatch {}\n" );
			clearstatcache( true, $file );

			// First probe executes the file (declares Mismatch) without resolving Widget.
			$handler( $class );
			$this->assertFalse( class_exists( $class, false ), 'Precondition: the probed class must stay unresolved.' );
			$this->assertTrue(
				in_array( realpath( $file ), get_included_files(), true ),
				'Precondition: the first probe must have executed the rogue file.'
			);

			// Second probe resolves the same file again; the guard must skip it instead of
			// re-executing (which would be an uncatchable "Cannot redeclare class" fatal).
			$handler( $class );
			$this->assertFalse(
				class_exists( $class, false ),
				'A re-probe of an already-executed file must degrade to a miss.'
			);
		} finally {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
			if ( is_dir( $dir ) ) {
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture cleanup.
			}
		}
	}

	/**
	 * The redeclare guard must also skip a file the handler never touched but that some
	 * OTHER mechanism (the primary autoloader, a manual require) already executed. This is
	 * the get_included_files() branch of the guard: the handler's own $loaded/$attempted
	 * sets know nothing about the file, so only that check stands between a re-probe and
	 * an uncatchable "Cannot redeclare class" fatal. If the guard regresses, this test
	 * fatals the PHPUnit process rather than failing an assertion.
	 *
	 * @testdox the registered handler never re-executes a file another mechanism already loaded.
	 */
	public function test_registered_handler_skips_a_file_another_mechanism_loaded(): void {
		$handler = Autoloader::register_woocommerce_psr4_fallback();
		$this->assertInstanceOf( \Closure::class, $handler, 'Bootstrap must register a handler.' );

		$suffix = 'ReproForeign' . str_replace( '.', '', uniqid( '', true ) );
		$dir    = dirname( WC_PLUGIN_FILE ) . '/src/' . $suffix;
		$file   = $dir . '/Widget.php';
		$class  = 'Automattic\\WooCommerce\\' . $suffix . '\\Widget';

		try {
			wp_mkdir_p( $dir );
			// A rogue file: parses fine but declares a class that does not match its PSR-4 path,
			// so a probe for Widget keeps resolving to it without ever declaring Widget.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture; WP_Filesystem adds no value here.
			file_put_contents( $file, "<?php\nnamespace Automattic\\WooCommerce\\{$suffix};\nclass Mismatch {}\n" );
			clearstatcache( true, $file );

			// Another mechanism — not the handler — executes the file (declares Mismatch).
			require $file;
			$this->assertTrue(
				in_array( realpath( $file ), get_included_files(), true ),
				'Precondition: the file must be on record as executed outside the handler.'
			);

			// The probe resolves the same file; the guard must skip it instead of
			// re-executing (which would be an uncatchable "Cannot redeclare class" fatal).
			$handler( $class );
			$this->assertFalse(
				class_exists( $class, false ),
				'A probe of a file another mechanism executed must degrade to a miss.'
			);
		} finally {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
			if ( is_dir( $dir ) ) {
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture cleanup.
			}
		}
	}

	/**
	 * A torn file that COMPILES but fails while linking — e.g. a class whose parent is not
	 * written yet during a file-by-file upgrade — must also stay retryable. PHP records a
	 * plain include's path in get_included_files() after compilation but BEFORE the link
	 * error, so a guard that skips any already-included path would poison the completed file
	 * for the rest of the request. This test fails against such a guard and passes when the
	 * handler tracks only the files it has executed cleanly.
	 *
	 * @testdox the registered handler loads a link-failed class file once its dependency exists.
	 */
	public function test_registered_handler_recovers_after_a_link_failed_file_is_completed(): void {
		$handler = Autoloader::register_woocommerce_psr4_fallback();
		$this->assertInstanceOf( \Closure::class, $handler, 'Bootstrap must register a handler.' );

		$suffix = 'ReproLink' . str_replace( '.', '', uniqid( '', true ) );
		$dir    = dirname( WC_PLUGIN_FILE ) . '/src/' . $suffix;
		$file   = $dir . '/Widget.php';
		$class  = 'Automattic\\WooCommerce\\' . $suffix . '\\Widget';

		try {
			wp_mkdir_p( $dir );
			// Syntactically valid, but extends a sibling class whose file is not written yet:
			// the include compiles, then throws Error: Class "...Base" not found while linking.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture; WP_Filesystem adds no value here.
			file_put_contents( $file, "<?php\nnamespace Automattic\\WooCommerce\\{$suffix};\nclass Widget extends Base {}\n" );
			clearstatcache( true, $file );

			$handler( $class );
			$this->assertFalse( class_exists( $class, false ), 'Precondition: link-failed file must degrade to a miss.' );
			$this->assertTrue(
				in_array( realpath( $file ), get_included_files(), true ),
				'Precondition: a plain include records the link-failed path, which a get_included_files() guard would skip on retry.'
			);

			// The upgrade finishes: the dependency lands and the class file is now self-contained.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture; WP_Filesystem adds no value here.
			file_put_contents( $file, "<?php\nnamespace Automattic\\WooCommerce\\{$suffix};\nclass Widget {}\n" );
			clearstatcache( true, $file );

			$handler( $class );
			$this->assertTrue(
				class_exists( $class, false ),
				'Handler must load the class once the dependency exists — a link-failed attempt must not poison the retry.'
			);
		} finally {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
			if ( is_dir( $dir ) ) {
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture cleanup.
			}
		}
	}

	/**
	 * An include that fails to OPEN — the resolved file vanished or turned unreadable
	 * between findFile()/realpath() and the include, e.g. the upgrader replacing the old
	 * plugin directory — emits a warning and returns false instead of throwing. No
	 * Throwable reaches the handler's catch block in production, so only the include's
	 * return value distinguishes this failure from a clean load; recording the path as
	 * loaded would poison every retry after the upgrade restores the file. Reproduced
	 * deterministically with a DIRECTORY at the class-file path: file_exists() (Composer's
	 * findFile() predicate) and realpath() both accept it while the include's open fails.
	 * The temporary error handler keeps the warning a warning, as in production — this
	 * suite's convertWarningsToExceptions would otherwise turn it into a caught Throwable
	 * and mask the false-return path under test.
	 *
	 * @testdox the registered handler retries a class file whose include failed to open.
	 */
	public function test_registered_handler_recovers_after_a_failed_open(): void {
		$handler = Autoloader::register_woocommerce_psr4_fallback();
		$this->assertInstanceOf( \Closure::class, $handler, 'Bootstrap must register a handler.' );

		$suffix = 'ReproOpen' . str_replace( '.', '', uniqid( '', true ) );
		$dir    = dirname( WC_PLUGIN_FILE ) . '/src/' . $suffix;
		$file   = $dir . '/Widget.php';
		$class  = 'Automattic\\WooCommerce\\' . $suffix . '\\Widget';

		try {
			// A directory where the class file belongs: the failed-open shape, minus the race.
			wp_mkdir_p( $file );

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Keeps the include warning a warning, as in production; PHPUnit would convert it to an exception and mask the path under test.
			set_error_handler(
				static function () {
					return true;
				},
				E_WARNING
			);
			try {
				$handler( $class );
			} finally {
				restore_error_handler();
			}
			$this->assertFalse( class_exists( $class, false ), 'Precondition: a failed-open include must degrade to a miss.' );

			// The upgrade completes: the path is now a regular, self-contained class file.
			rmdir( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture; WP_Filesystem adds no value here.
			file_put_contents( $file, "<?php\nnamespace Automattic\\WooCommerce\\{$suffix};\nclass Widget {}\n" );
			clearstatcache( true, $file );

			$handler( $class );
			$this->assertTrue(
				class_exists( $class, false ),
				'Handler must load the class once the file is restored — a failed-open include must not be recorded as loaded.'
			);
		} finally {
			if ( is_dir( $file ) ) {
				rmdir( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture cleanup.
			}
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
			if ( is_dir( $dir ) ) {
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture cleanup.
			}
		}
	}

	/**
	 * Registration is idempotent: repeated calls return the same handler and never
	 * stack duplicate autoloaders on the SPL stack.
	 *
	 * @testdox register_woocommerce_psr4_fallback() is idempotent.
	 */
	public function test_register_woocommerce_psr4_fallback_is_idempotent(): void {
		$first       = Autoloader::register_woocommerce_psr4_fallback();
		$stack_after = spl_autoload_functions();
		$second      = Autoloader::register_woocommerce_psr4_fallback();

		$this->assertInstanceOf( \Closure::class, $first );
		$this->assertSame( $first, $second, 'Repeat registration must return the same handler.' );
		$this->assertSame(
			$stack_after,
			spl_autoload_functions(),
			'Repeat registration must not add a duplicate handler to the SPL stack.'
		);
		$this->assertTrue(
			in_array( $first, spl_autoload_functions(), true ),
			'The registered handler must be present on the SPL stack.'
		);
	}

	/**
	 * `ClassLoader::getPrefixesPsr4()` carries no return-type declaration, so an older or foreign
	 * `Composer\Autoload\ClassLoader` reused from another path may return a non-array. The reader
	 * must degrade that shape to null, because the map flows straight into find_scoped_file()'s
	 * `array $psr4_entries` parameter on every autoload miss — outside the handler's try/catch — so
	 * a non-array would raise an uncatchable TypeError and fatal the request.
	 *
	 * @testdox read_scoped_psr4_map() degrades to null on a foreign non-array shape.
	 */
	public function test_read_scoped_psr4_map_degrades_on_a_foreign_non_array_shape(): void {
		// A foreign loader whose getPrefixesPsr4() returns a non-array (legal: the parent declares
		// no return type). setPsr4() still works, so build() would treat it as a usable loader.
		$foreign = new class() extends ClassLoader {
			/**
			 * Model a foreign/ancient loader that returns a non-array prefix map.
			 *
			 * @return string A deliberately non-array value.
			 */
			public function getPrefixesPsr4() {
				return 'foreign-non-array-shape';
			}
		};

		$this->assertNull(
			Autoloader::read_scoped_psr4_map( $foreign ),
			'A non-array getPrefixesPsr4() return must degrade to null, not flow into find_scoped_file().'
		);
	}

	/**
	 * A genuine loader returns its scoped PSR-4 map unchanged, so the handler keeps resolving
	 * src classes normally.
	 *
	 * @testdox read_scoped_psr4_map() returns the array map from a genuine loader.
	 */
	public function test_read_scoped_psr4_map_returns_the_array_map_from_a_genuine_loader(): void {
		$loader = new ClassLoader();
		$loader->setPsr4( 'Automattic\\WooCommerce\\', array( dirname( WC_PLUGIN_FILE ) . '/src' ) );

		$map = Autoloader::read_scoped_psr4_map( $loader );

		$this->assertIsArray( $map, 'A genuine loader must yield an array map.' );
		$this->assertArrayHasKey(
			'Automattic\\WooCommerce\\',
			$map,
			'The scoped first-party prefix must be present in the returned map.'
		);
	}

	/**
	 * `ClassLoader::findFile()` also carries no return-type declaration, so the same older or foreign
	 * `Composer\Autoload\ClassLoader` reused from another path may return a non-string. The reader
	 * must degrade that shape to null, because find_scoped_file() declares a `: ?string` return and
	 * the value reaches it on every autoload miss — outside the handler's try/catch — so a non-string
	 * would raise an uncatchable TypeError and fatal the request.
	 *
	 * @testdox read_scoped_file_path() degrades to null on a foreign non-string shape.
	 */
	public function test_read_scoped_file_path_degrades_on_a_foreign_non_string_shape(): void {
		// A foreign loader whose findFile() returns a non-array, non-string value (legal: the parent
		// declares no return type).
		$foreign = new class() extends ClassLoader {
			/**
			 * Model a foreign/ancient loader that returns a non-string from findFile().
			 *
			 * @param string $class_name The probed class name (ignored).
			 *
			 * @return array<int, string> A deliberately non-string value.
			 */
			public function findFile( $class_name ) {
				// Avoid parameter not used PHPCS errors.
				unset( $class_name );
				return array( 'foreign-non-string-shape' );
			}
		};

		$this->assertNull(
			Autoloader::read_scoped_file_path( $foreign, 'Automattic\\WooCommerce\\Enums\\DefaultCustomerAddress' ),
			'A non-string findFile() return must degrade to null, not flow into find_scoped_file()\'s ?string return.'
		);
	}

	/**
	 * A genuine loader still resolves a real src class to its file path, so normal resolution is
	 * unaffected.
	 *
	 * @testdox read_scoped_file_path() returns the resolved path from a genuine loader.
	 */
	public function test_read_scoped_file_path_returns_the_path_from_a_genuine_loader(): void {
		$loader = new ClassLoader();
		$loader->setPsr4( 'Automattic\\WooCommerce\\', array( dirname( WC_PLUGIN_FILE ) . '/src' ) );

		$path = Autoloader::read_scoped_file_path( $loader, 'Automattic\\WooCommerce\\Enums\\DefaultCustomerAddress' );

		$this->assertIsString( $path, 'A genuine loader must resolve a real src class to a string path.' );
		$this->assertStringContainsString(
			'DefaultCustomerAddress',
			$path,
			'The resolved path must point at the probed class file.'
		);
	}

	/**
	 * Composer's findFile() returns the literal `false` on a miss — the most common non-string
	 * return, since every class miss flows through it. read_scoped_file_path() must promote that
	 * `false` to null (is_string() excludes it), because that promotion is exactly what stops a
	 * TypeError at find_scoped_file()'s `?string` return on the most-travelled path.
	 *
	 * @testdox read_scoped_file_path() promotes findFile()'s false miss to null.
	 */
	public function test_read_scoped_file_path_promotes_a_false_miss_to_null(): void {
		$loader = new ClassLoader();
		$loader->setPsr4( 'Automattic\\WooCommerce\\', array( dirname( WC_PLUGIN_FILE ) . '/src' ) );

		$this->assertNull(
			Autoloader::read_scoped_file_path( $loader, 'Automattic\\WooCommerce\\Nope\\DoesNotExistXYZ' ),
			'A findFile() miss (false) must promote to null, not surface as a non-string.'
		);
	}
}
