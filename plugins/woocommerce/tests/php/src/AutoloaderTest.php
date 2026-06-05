<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests;

use Automattic\WooCommerce\Autoloader;

/**
 * Tests for the WooCommerce-scoped Composer PSR-4 fallback autoloader.
 *
 * @package Automattic\WooCommerce\Tests
 */
class AutoloaderTest extends \WC_Unit_Test_Case {

	/**
	 * The builder returns a ClassLoader scoped to WooCommerce namespaces only:
	 * it resolves a real WooCommerce src class, refuses non-WooCommerce vendor
	 * namespaces, and refuses non-existent WooCommerce classes.
	 *
	 * @testdox build_woocommerce_psr4_fallback() resolves WooCommerce classes only.
	 */
	public function test_build_woocommerce_psr4_fallback_scopes_to_woocommerce(): void {
		$sut = Autoloader::build_woocommerce_psr4_fallback();

		$this->assertInstanceOf(
			\Composer\Autoload\ClassLoader::class,
			$sut,
			'Builder must return a ClassLoader when the Composer files are present (they ship in the build).'
		);

		// Positive: resolves a real WooCommerce src class from disk via PSR-4.
		$this->assertNotFalse(
			$sut->findFile( 'Automattic\\WooCommerce\\Enums\\DefaultCustomerAddress' ),
			'Fallback must resolve a WooCommerce src class.'
		);

		// Scoping: must NOT resolve a non-WooCommerce vendor namespace that exists in the full map.
		$this->assertFalse(
			$sut->findFile( 'Opis\\JsonSchema\\Validator' ),
			'Fallback must be scoped to WooCommerce namespaces and refuse non-WooCommerce ones.'
		);

		// Bogus: must not invent files for non-existent WooCommerce classes.
		$this->assertFalse(
			$sut->findFile( 'Automattic\\WooCommerce\\Nope\\Does_Not_Exist_XYZ' ),
			'Fallback must not resolve non-existent classes.'
		);
	}

	/**
	 * Each builder call returns a distinct ClassLoader, so Composer's per-instance
	 * negative cache (missingClasses) is never shared across resolutions. This is
	 * what lets a class missed before an in-place upgrade still resolve once the new
	 * file is on disk later in the same request.
	 *
	 * @testdox build_woocommerce_psr4_fallback() returns a fresh loader each call.
	 */
	public function test_build_woocommerce_psr4_fallback_is_not_shared(): void {
		$first  = Autoloader::build_woocommerce_psr4_fallback();
		$second = Autoloader::build_woocommerce_psr4_fallback();

		$this->assertInstanceOf( \Composer\Autoload\ClassLoader::class, $first );
		$this->assertInstanceOf( \Composer\Autoload\ClassLoader::class, $second );
		$this->assertNotSame(
			$first,
			$second,
			'Each call must return a distinct loader so the negative cache is never shared across resolutions.'
		);
	}

	/**
	 * The registrar appends a WooCommerce-scoped autoloader to the SPL stack — this
	 * is the method `woocommerce.php` calls at bootstrap. The handler ignores
	 * non-WooCommerce classes and never fatals on a miss.
	 *
	 * @testdox register_woocommerce_psr4_fallback() appends a WooCommerce-scoped handler.
	 */
	public function test_register_woocommerce_psr4_fallback_appends_scoped_handler(): void {
		$before  = spl_autoload_functions();
		$handler = Autoloader::register_woocommerce_psr4_fallback();

		if ( ! is_callable( $handler ) ) {
			$this->fail( 'Registrar must return the registered autoloader when the Composer files are present.' );
		}

		try {
			$after = spl_autoload_functions();
			$this->assertCount(
				count( $before ) + 1,
				$after,
				'Registrar must append exactly one autoloader to the SPL stack.'
			);
			$this->assertTrue(
				in_array( $handler, $after, true ),
				'The registered handler must be present on the SPL stack.'
			);

			// Non-WooCommerce class: the handler must no-op without resolving or fataling.
			$this->assertNull(
				$handler( 'Opis\\JsonSchema\\Validator' ),
				'Handler must ignore non-WooCommerce classes.'
			);

			// WooCommerce but non-existent: the handler must miss without fataling (no require).
			$this->assertNull(
				$handler( 'Automattic\\WooCommerce\\Nope\\Does_Not_Exist_XYZ' ),
				'Handler must miss gracefully on a non-existent WooCommerce class.'
			);
		} finally {
			spl_autoload_unregister( $handler );
		}
	}
}
