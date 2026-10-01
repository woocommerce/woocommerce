<?php
/**
 * Test side-effect write definition class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

/**
 * Renames a product, then saves, sends email, calls out over HTTP or logs from
 * apply() when the input asks for it.
 */
class TestSideEffectWriteDefinition extends TestInMemoryWriteDefinition {

	public const ABILITY_ID = 'test-extension/rename-product-side-effect';

	/**
	 * Get the ability name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return self::ABILITY_ID;
	}

	/**
	 * Registration arguments whose own execute callback saves directly.
	 *
	 * @return array
	 */
	public static function get_registration_args(): array {
		$args                     = parent::get_registration_args();
		$args['execute_callback'] = static function ( $input ): array {
			$product = wc_get_product( $input['id'] );
			$product->set_name( $input['name'] );
			$product->save();
			return array( 'own_execute' => true );
		};
		return $args;
	}

	/**
	 * Rename in memory, then cause the side effect the input names.
	 *
	 * @param \WC_Data $subject Product.
	 * @param array    $input   Ability input.
	 */
	public static function apply( $subject, array $input ): void {
		parent::apply( $subject, $input );

		switch ( $input['side_effect'] ?? '' ) {
			case 'save':
				$subject->save();
				break;
			case 'mail':
				wp_mail( 'merchant@example.com', 'Renamed', 'The product was renamed.' );
				break;
			case 'http':
				wp_remote_get( 'http://127.0.0.1:9/' );
				break;
			case 'log':
				( new \WC_Log_Handler_DB() )->handle( time(), 'info', 'Renaming.', array( 'source' => 'test' ) );
				break;
		}
	}
}
