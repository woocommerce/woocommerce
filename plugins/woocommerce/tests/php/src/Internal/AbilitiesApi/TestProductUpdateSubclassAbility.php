<?php
/**
 * TestProductUpdateSubclassAbility class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Internal\Abilities\Domain\ProductUpdate;

/**
 * An extension's write ability that extends Core's product update ability.
 */
class TestProductUpdateSubclassAbility extends ProductUpdate {

	/**
	 * Run Core's change, then add a suffix to the name.
	 *
	 * @param \WC_Product $subject Product.
	 * @param array       $input   Ability input.
	 * @return null|\WP_Error
	 */
	protected function change( $subject, array $input ) {
		$changed = parent::change( $subject, $input );
		if ( null === $changed ) {
			$subject->set_name( $subject->get_name() . ' (extended)' );
		}
		return $changed;
	}
}
