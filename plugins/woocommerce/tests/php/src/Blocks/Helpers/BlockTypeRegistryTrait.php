<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Helpers;

/**
 * Registers block types for a test in place of whatever is registered under their names.
 *
 * It does not clean up: WC_Unit_Test_Case puts the block type registry back after each test,
 * so use this only in classes that extend it.
 */
trait BlockTypeRegistryTrait {

	/**
	 * Register a block type for this test in place of whatever is registered under its name.
	 *
	 * @param string   $block_name Full block name, such as 'woocommerce/mini-cart'.
	 * @param callable $register   Registers the block type, usually by constructing a block class.
	 * @return mixed What $register returned.
	 * @throws \LogicException When $register does not register $block_name.
	 */
	protected function replace_block_type( string $block_name, callable $register ) {
		$registry = \WP_Block_Type_Registry::get_instance();

		// Constructing a block class registers it, and the registry rejects a name that is taken.
		if ( $registry->is_registered( $block_name ) ) {
			$registry->unregister( $block_name );
		}

		$result = $register();

		if ( ! $registry->is_registered( $block_name ) ) {
			throw new \LogicException( esc_html( "The callback did not register {$block_name}." ) );
		}

		return $result;
	}

	/**
	 * Register block classes for this test, each constructed with no arguments.
	 *
	 * @param array<string, string> $block_classes Block class names keyed by full block name.
	 */
	protected function replace_block_types( array $block_classes ): void {
		foreach ( $block_classes as $block_name => $block_class ) {
			$this->replace_block_type( $block_name, static fn () => new $block_class() );
		}
	}
}
