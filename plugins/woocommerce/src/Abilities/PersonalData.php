<?php
/**
 * Personal data class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps fields flagged as personal data out of ability outputs and output schemas while the store
 * withholds them through the `woocommerce_abilities_share_personal_data` filter. It applies to the fields
 * of every resource, also while the experimental `extensible_abilities` feature is off.
 *
 * @since 11.4.0
 */
class PersonalData {

	/**
	 * Whether each resource shares its personal data. Decided once per request, like the output schemas, so schema and output agree.
	 *
	 * @var array<string, bool>
	 */
	private static array $shared = array();

	/**
	 * Whether abilities return the fields of a resource flagged as personal data. Any filter value other
	 * than true withholds them, so a broken callback fails closed.
	 *
	 * @since 11.4.0
	 *
	 * @param string $resource_name Resource being formatted, such as `order`.
	 * @return bool
	 */
	public static function is_shared( string $resource_name ): bool {
		if ( ! isset( self::$shared[ $resource_name ] ) ) {
			/**
			 * Filters whether WooCommerce abilities return personal data.
			 *
			 * Return false to leave out fields flagged as personal data, such as billing emails, from
			 * the output and the output schema. It runs once per resource and request, and the result
			 * applies to both.
			 *
			 * @since 11.4.0
			 *
			 * @param bool   $shared        Whether personal data is returned. Default true.
			 * @param string $resource_name Resource being formatted, such as `order`.
			 */
			self::$shared[ $resource_name ] = true === apply_filters( 'woocommerce_abilities_share_personal_data', true, $resource_name );
		}
		return self::$shared[ $resource_name ];
	}
}
