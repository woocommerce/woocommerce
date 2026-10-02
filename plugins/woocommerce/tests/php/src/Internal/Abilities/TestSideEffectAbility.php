<?php
/**
 * Test side effect ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\AbilitiesApi\ObjectChangeAbility;

/**
 * Renames a product, then causes the side effect the input names.
 */
class TestSideEffectAbility extends ObjectChangeAbility {

	/**
	 * Object type.
	 */
	public static function object_type(): string {
		return 'product';
	}

	/**
	 * Load the product.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Product|null
	 */
	public function load( array $input ) {
		$product = wc_get_product( $input['id'] );
		return $product ? $product : null;
	}

	/**
	 * Rename in memory, then cause the side effect the input names.
	 *
	 * @param \WC_Product $subject Product.
	 * @param array       $input   Ability input.
	 */
	public function change( $subject, array $input ) {
		$subject->set_name( $input['name'] );

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
			case 'transient':
				set_transient( 'test_side_effect_transient', 'during' );
				break;
		}
		return null;
	}

	/**
	 * The renamed product.
	 *
	 * @param \WC_Product $subject Saved product.
	 * @return array
	 */
	public function prepare_response( $subject ) {
		return array(
			'id'   => $subject->get_id(),
			'name' => $subject->get_name(),
		);
	}
}
