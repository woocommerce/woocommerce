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
}
