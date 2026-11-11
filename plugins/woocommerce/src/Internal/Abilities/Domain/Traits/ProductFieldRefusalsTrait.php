<?php
/**
 * Product field refusals trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;

defined( 'ABSPATH' ) || exit;

/**
 * Extension field refusals for the product abilities that write a product and
 * its `variations` items. The using class extends `ActionableAbility` and uses
 * `ProductAbilityTrait`.
 */
trait ProductFieldRefusalsTrait {

	/**
	 * Say what is wrong with each bad extension field of the input and of its
	 * `variations` items.
	 *
	 * @param array $input Ability input that the schema refused.
	 * @return string[]
	 */
	protected function get_field_refusals( array $input ): array {
		return array_merge( parent::get_field_refusals( $input ), self::get_variation_field_refusals( $input ) );
	}

	/**
	 * Say what is wrong with each bad extension field of the `variations` items.
	 * The message starts with the item, as `variations[0]: `.
	 *
	 * @param array $input Ability input that the schema refused.
	 * @return string[]
	 */
	private static function get_variation_field_refusals( array $input ): array {
		if ( ! ExtensibleAbilities::is_enabled() || ! is_array( $input['variations'] ?? null ) ) {
			return array();
		}
		$refusals = array();
		foreach ( $input['variations'] as $index => $item ) {
			foreach ( self::describe_bad_fields( $item['extensions'] ?? null, 'product', array( self::class, 'field_applies_to_variations' ) ) as $message ) {
				$refusals[] = 'variations[' . $index . ']: ' . $message;
			}
		}
		return $refusals;
	}
}
