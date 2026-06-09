<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests;

use Automattic\WooCommerce\Autoloader;
use Composer\Autoload\ClassLoader;

/**
 * Tests for the WooCommerce-scoped Composer PSR-4 fallback autoloader.
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
}
