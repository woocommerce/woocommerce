<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Helpers;

/**
 * Registers block types for a test and puts the block type registry back as the test found it.
 *
 * Constructing a block class registers its block type, and WP_Block_Type_Registry outlives a
 * test, so a type registered without cleanup changes what every later test sees. Call
 * restore_block_types() from tearDown().
 */
trait BlockTypeRegistryTrait {

	/**
	 * What each replaced name had registered before the test, or null when it had nothing.
	 *
	 * @var array<string, \WP_Block_Type|null>
	 */
	private $block_types_before_test = array();

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

		if ( ! array_key_exists( $block_name, $this->block_types_before_test ) ) {
			$this->block_types_before_test[ $block_name ] = $registry->get_registered( $block_name );
		}

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
			$this->replace_block_type(
				$block_name,
				static function () use ( $block_class ) {
					return new $block_class();
				}
			);
		}
	}

	/**
	 * Put back the block types replaced during the test, and remove the ones that were new.
	 */
	protected function restore_block_types(): void {
		$registry = \WP_Block_Type_Registry::get_instance();

		foreach ( $this->block_types_before_test as $block_name => $block_type ) {
			if ( $registry->is_registered( $block_name ) ) {
				$registry->unregister( $block_name );
			}
			if ( $block_type ) {
				$registry->register( $block_type );
			}
		}

		$this->block_types_before_test = array();
	}
}
