<?php
/**
 * Ability field functions.
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityFields;

/**
 * Register a field that the abilities of an object type return under `extensions`.
 *
 * Several extensions can add fields to the same object type, so prefix the
 * attribute with the extension's name, such as `subscriptions_trial_length`.
 *
 * @since 11.3.0
 *
 * @param string $object_type Object type, such as `product` or `order`.
 * @param string $attribute   Attribute under `extensions`.
 * @param array  $args        {
 *     Field arguments.
 *
 *     @type array    $schema       JSON schema of the value. Its `title` is the label clients show.
 *     @type callable $get_callback Receives the object and returns the value, or null to leave the attribute out.
 * }
 */
function wc_register_ability_field( string $object_type, string $attribute, array $args ): void {
	if ( ! is_array( $args['schema'] ?? null ) ) {
		wc_doing_it_wrong( __FUNCTION__, 'The "schema" argument must be an array.', '11.3.0' );
		return;
	}

	AbilityFields::register( $object_type, $attribute, $args );
}
