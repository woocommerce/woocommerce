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
 * This Abilities-only disclosure API is experimental. Classification describes the field; this
 * policy does not control erasure, REST access, inputs or searches. Existing permissions still apply.
 *
 * @since 11.4.0
 */
class PersonalData {

	/**
	 * Whether each resource shares its personal data. Decided once per site and user in a request, so schema and output agree.
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
		$key = get_current_blog_id() . ':' . get_current_user_id() . ':' . $resource_name;
		if ( ! isset( self::$shared[ $key ] ) ) {
			/**
			 * Filters whether WooCommerce abilities return personal data.
			 *
			 * Return false to leave out fields flagged as personal data, such as billing emails, from
			 * the output and the output schema. It runs on first schema or output access for each resource, site and user in a request.
			 * Switching users or sites selects that context's decision and Core output schema. Other policy
			 * inputs must stay fixed for that context within the request. Clients must refresh cached schemas when their disclosure context changes;
			 * a cached schema never overrides the server's current decision.
			 *
			 * @since 11.4.0
			 *
			 * @param bool   $shared        Whether personal data is returned. Default true.
			 * @param string $resource_name Resource being formatted, such as `order`.
			 */
			self::$shared[ $key ] = true === apply_filters( 'woocommerce_abilities_share_personal_data', true, $resource_name );
		}
		return self::$shared[ $key ];
	}
}
