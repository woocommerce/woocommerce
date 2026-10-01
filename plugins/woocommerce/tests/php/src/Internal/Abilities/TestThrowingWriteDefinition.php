<?php
/**
 * Test throwing write definition class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

/**
 * Renames a product, throwing from validate() or apply() when the input asks for it.
 */
class TestThrowingWriteDefinition extends TestInMemoryWriteDefinition {

	public const ABILITY_ID = 'test-extension/rename-product-throwing';

	/**
	 * Get the ability name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return self::ABILITY_ID;
	}

	/**
	 * Throw when the input asks for it.
	 *
	 * @param \WC_Data $subject Product.
	 * @param array    $input   Ability input.
	 * @return true|\WP_Error
	 */
	public static function validate( $subject, array $input ) {
		self::maybe_throw( 'validate', $input );
		return parent::validate( $subject, $input );
	}

	/**
	 * Rename in memory, then throw when the input asks for it.
	 *
	 * @param \WC_Data $subject Product.
	 * @param array    $input   Ability input.
	 */
	public static function apply( $subject, array $input ): void {
		parent::apply( $subject, $input );
		self::maybe_throw( 'apply', $input );
	}

	/**
	 * Throw a data exception or a runtime exception from the named step.
	 *
	 * @param string $step  Step.
	 * @param array  $input Ability input.
	 * @throws \WC_Data_Exception When the input names the step with a data exception.
	 * @throws \RuntimeException When the input names the step with any other exception.
	 */
	private static function maybe_throw( string $step, array $input ): void {
		if ( ( $input['throw_in'] ?? '' ) !== $step ) {
			return;
		}
		if ( 'data' === ( $input['throw'] ?? '' ) ) {
			throw new \WC_Data_Exception( 'test_invalid', 'Invalid data.' );
		}
		throw new \RuntimeException( 'Something broke.' );
	}
}
