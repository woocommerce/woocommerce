<?php
/**
 * Test extension root product definition class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

/**
 * An extension's read ability whose whole output is one product.
 */
class TestExtensionRootProductDefinition extends TestExtensionProductsDefinition {

	/**
	 * Ability name.
	 */
	public static function get_name(): string {
		return 'test-extension/product';
	}

	/**
	 * The whole output is the product.
	 */
	protected static function output_key(): string {
		return '';
	}
}
