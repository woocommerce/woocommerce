<?php
/**
 * Personal data class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether Core's abilities return personal data, also known as PII
 * (personally identifiable information), such as customer emails and the
 * extension fields flagged as personal data. The fields are flagged where they
 * are registered, and AbilityResources withholds them from every formatted
 * object, so the abilities themselves do not check it.
 *
 * @since 11.4.0
 */
class PersonalData {

	/**
	 * Filter that switches personal data off for AI agents and other ability clients.
	 */
	public const FILTER = 'woocommerce_abilities_share_personal_data';

	/**
	 * Whether the abilities return the personal data of a resource. Any filter
	 * value other than true withholds it, so a broken callback fails closed.
	 *
	 * @param string $resource_name Resource being formatted, such as `order`.
	 * @return bool
	 */
	public static function is_shared( string $resource_name ): bool {
		/**
		 * Filters whether WooCommerce abilities return personal data.
		 *
		 * Return false to leave out customer details, such as billing emails, and
		 * the extension fields registered with `personal_data`.
		 *
		 * @since 11.4.0
		 *
		 * @param bool   $shared        Whether personal data is returned. Default true.
		 * @param string $resource_name Resource being formatted, such as `order`.
		 */
		return true === apply_filters( self::FILTER, true, $resource_name );
	}
}
