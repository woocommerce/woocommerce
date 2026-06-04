<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests;

use Automattic\WooCommerce\Autoloader;

/**
 * Tests for the WooCommerce-scoped Composer PSR-4 fallback autoloader.
 *
 * @package Automattic\WooCommerce\Tests
 */
class AutoloaderTest extends \WP_UnitTestCase {

	/**
	 * The builder returns a ClassLoader scoped to WooCommerce namespaces only:
	 * it resolves a real WooCommerce src class, refuses non-WooCommerce vendor
	 * namespaces, and refuses non-existent WooCommerce classes.
	 *
	 * @testdox build_woocommerce_psr4_fallback() resolves WooCommerce classes only
	 */
	public function test_build_woocommerce_psr4_fallback_scopes_to_woocommerce(): void {
		$loader = Autoloader::build_woocommerce_psr4_fallback();

		$this->assertInstanceOf(
			\Composer\Autoload\ClassLoader::class,
			$loader,
			'Builder must return a ClassLoader when the Composer files are present (they ship in the build).'
		);

		// Positive: resolves a real WooCommerce src class from disk via PSR-4.
		$this->assertNotFalse(
			$loader->findFile( 'Automattic\\WooCommerce\\Enums\\DefaultCustomerAddress' ),
			'Fallback must resolve a WooCommerce src class.'
		);

		// Scoping: must NOT resolve a non-WooCommerce vendor namespace that exists in the full map.
		$this->assertFalse(
			$loader->findFile( 'Opis\\JsonSchema\\Validator' ),
			'Fallback must be scoped to WooCommerce namespaces and refuse non-WooCommerce ones.'
		);

		// Bogus: must not invent files for non-existent WooCommerce classes.
		$this->assertFalse(
			$loader->findFile( 'Automattic\\WooCommerce\\Nope\\Does_Not_Exist_XYZ' ),
			'Fallback must not resolve non-existent classes.'
		);
	}

	/**
	 * The plugin bootstrap must register a WooCommerce-scoped Composer PSR-4
	 * fallback. We identify it among the live SPL autoloaders by its signature:
	 * a Composer ClassLoader that resolves a WooCommerce class but refuses a
	 * non-WooCommerce vendor namespace. (An ambient full-map Composer loader,
	 * e.g. from wp-cli, would resolve the non-WooCommerce namespace and so does
	 * not match — this avoids a false pass.)
	 *
	 * @testdox woocommerce.php registers a WooCommerce-scoped PSR-4 fallback
	 */
	public function test_bootstrap_registers_scoped_psr4_fallback(): void {
		$found = false;

		foreach ( spl_autoload_functions() as $callback ) {
			if ( ! is_array( $callback ) || ! isset( $callback[0] ) ) {
				continue;
			}
			if ( ! ( $callback[0] instanceof \Composer\Autoload\ClassLoader ) ) {
				continue;
			}

			$resolves_wc  = false !== $callback[0]->findFile( 'Automattic\\WooCommerce\\Enums\\DefaultCustomerAddress' );
			$refuses_opis = false === $callback[0]->findFile( 'Opis\\JsonSchema\\Validator' );

			if ( $resolves_wc && $refuses_opis ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue(
			$found,
			'woocommerce.php must register a WooCommerce-scoped Composer PSR-4 fallback loader.'
		);
	}
}
